<?php

namespace App\Services\Finance;

use App\Models\AcademicYear;
use App\Models\InsuranceConfig;
use App\Models\Payroll;
use App\Models\SalaryComponent;
use App\Models\TaxConfig;
use App\Models\Teacher;
use App\Models\TeacherPayrollInput;
use App\Models\TeacherSalaryConfig;
use App\Models\TeacherSalaryProfile;
use App\Models\TeachingExtraRecord;
use App\Models\User;
use App\Support\Decimal;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PayrollCalculationService
{
    public function __construct(
        private readonly TaxCalculationService $taxCalculator,
        private readonly InsuranceCalculationService $insuranceCalculator,
        private readonly FinanceAuditService $audit
    ) {}

    public function calculate(
        Teacher $teacher,
        int $year,
        int $month,
        ?AcademicYear $academicYear,
        User $actor
    ): Payroll {
        return DB::transaction(function () use ($teacher, $year, $month, $academicYear, $actor): Payroll {
            $periodDate = Carbon::create($year, $month, 1, 0, 0, 0, config('app.timezone'))->endOfMonth();
            $profiles = TeacherSalaryProfile::query()
                ->where('teacher_id', $teacher->id)
                ->where('status', 'active')
                ->whereDate('effective_from', '<=', $periodDate->toDateString())
                ->where(fn ($query) => $query->whereNull('effective_to')
                    ->orWhereDate('effective_to', '>=', $periodDate->toDateString()))
                ->with(['configs.component', 'configs.subject'])
                ->get();
            if ($profiles->count() !== 1) {
                throw ValidationException::withMessages([
                    'salary_profile' => $profiles->isEmpty()
                        ? 'Giáo viên chưa có cấu hình lương hiệu lực.'
                        : 'Có nhiều cấu hình lương cùng hiệu lực; không thể tự chọn cấu hình.',
                ]);
            }
            $profile = $profiles->first();

            $payroll = Payroll::query()->createOrFirst(
                ['teacher_id' => $teacher->id, 'payroll_year' => $year, 'payroll_month' => $month],
                [
                    'payroll_code' => 'PR-'.Str::upper((string) Str::ulid()),
                    'academic_year_id' => $academicYear?->id,
                    'teacher_salary_profile_id' => $profile->id,
                    'teacher_code_snapshot' => $teacher->teacher_code,
                    'teacher_name_snapshot' => $teacher->full_name,
                    'specialization_snapshot' => $teacher->specialization,
                    'academic_year_snapshot' => $academicYear?->name,
                    'salary_profile_code_snapshot' => $profile->profile_code,
                    'status' => 'draft',
                ]
            );
            $payroll = Payroll::query()->lockForUpdate()->findOrFail($payroll->id);
            if (! in_array($payroll->status, ['draft', 'calculated'], true)) {
                throw ValidationException::withMessages([
                    'payroll' => "Payroll trạng thái {$payroll->status} không thể tính lại.",
                ]);
            }

            $payroll->items()->delete();
            TeacherPayrollInput::where('included_payroll_id', $payroll->id)->update(['included_payroll_id' => null]);
            TeachingExtraRecord::where('included_payroll_id', $payroll->id)->update(['included_payroll_id' => null]);

            $configs = $profile->configs->where('is_active', true)->values();
            $items = collect();
            $deferredPercentageConfigs = collect();

            foreach ($configs as $config) {
                $component = $config->component;
                if (! $component || ! $component->is_active
                    || in_array($component->category, ['teaching_extra', 'insurance', 'tax'], true)) {
                    continue;
                }
                if ($component->calculation_type === 'percentage') {
                    $deferredPercentageConfigs->push($config);

                    continue;
                }
                $amount = $this->amountFromConfig($config, $config->default_quantity);
                if ($amount !== null) {
                    $items->push($this->componentLine($component, $amount, "config:{$config->id}", [
                        'quantity' => $config->default_quantity,
                        'rate' => $config->rate_per_unit,
                        'source_type' => TeacherSalaryConfig::class,
                        'source_id' => $config->id,
                    ]));
                }
            }

            $baseSalary = $this->sumCategory($items, 'base_salary', 'earning');
            foreach ($deferredPercentageConfigs as $config) {
                $component = $config->component;
                $base = match ($config->calculation_base) {
                    'gross' => $this->sumType($items, 'earning'),
                    default => $baseSalary,
                };
                $amount = Decimal::percentOf($base, $config->percentage_value ?? '0.0000');
                $items->push($this->componentLine($component, $amount, "config:{$config->id}", [
                    'percentage_value' => $config->percentage_value,
                    'calculation_base_amount' => $base,
                    'source_type' => TeacherSalaryConfig::class,
                    'source_id' => $config->id,
                ]));
            }

            $inputs = TeacherPayrollInput::query()
                ->with('component')
                ->where('teacher_id', $teacher->id)
                ->where('input_year', $year)
                ->where('input_month', $month)
                ->where('status', 'approved')
                ->whereNull('included_payroll_id')
                ->lockForUpdate()
                ->get();
            foreach ($inputs as $input) {
                $component = $input->component;
                $config = $configs->firstWhere('salary_component_id', $component->id);
                $amount = in_array($input->input_type, ['bonus', 'manual_adjustment', 'other_input'], true)
                    ? ($input->input_amount !== null ? Decimal::money($input->input_amount) : null)
                    : ($config ? $this->amountFromConfig($config, $input->quantity) : null);
                if ($amount === null) {
                    throw ValidationException::withMessages([
                        'payroll_input' => "Input {$input->input_code} thiếu cấu hình để server tính số tiền.",
                    ]);
                }
                $items->push($this->componentLine($component, $amount, "input:{$input->id}", [
                    'quantity' => $input->quantity,
                    'rate' => $config?->rate_per_unit,
                    'source_type' => TeacherPayrollInput::class,
                    'source_id' => $input->id,
                    'description' => $input->note,
                ]));
            }

            $teachingRecords = TeachingExtraRecord::query()
                ->where('teacher_id', $teacher->id)
                ->where('teaching_year', $year)
                ->where('teaching_month', $month)
                ->where('status', 'approved')
                ->whereNull('included_payroll_id')
                ->lockForUpdate()
                ->get();
            $teachingComponent = SalaryComponent::query()
                ->where('category', 'teaching_extra')->where('type', 'earning')->where('is_active', true)->first();
            if ($teachingRecords->isNotEmpty() && ! $teachingComponent) {
                throw ValidationException::withMessages(['salary_component' => 'Thiếu component dạy thêm đang hoạt động.']);
            }
            foreach ($teachingRecords as $record) {
                $items->push($this->componentLine(
                    $teachingComponent,
                    $record->total_amount,
                    "teaching:{$record->id}",
                    [
                        'quantity' => $record->number_of_periods,
                        'rate' => $record->rate_per_period,
                        'source_type' => TeachingExtraRecord::class,
                        'source_id' => $record->id,
                        'subject_code_snapshot' => $record->subject_code_snapshot,
                        'subject_name_snapshot' => $record->subject_name_snapshot,
                        'class_code_snapshot' => $record->class_code_snapshot,
                        'class_name_snapshot' => $record->class_name_snapshot,
                    ]
                ));
            }

            $gross = $this->sumType($items, 'earning');
            $insuranceTotal = '0.00';
            if ($profile->insurance_enabled) {
                $insuranceConfigs = InsuranceConfig::query()
                    ->where('is_active', true)
                    ->whereDate('effective_from', '<=', $periodDate->toDateString())
                    ->where(fn ($query) => $query->whereNull('effective_to')
                        ->orWhereDate('effective_to', '>=', $periodDate->toDateString()))
                    ->get();
                foreach ($insuranceConfigs as $insuranceConfig) {
                    $resolvedBase = match ($insuranceConfig->salary_base_type) {
                        'fixed' => $insuranceConfig->fixed_salary_base ?? '0.00',
                        'gross' => $gross,
                        'insurance_earnings' => Decimal::sum($items->where('type', 'earning')
                            ->where('is_insurance_base', true)->pluck('amount')),
                        default => $baseSalary,
                    };
                    $insurance = $this->insuranceCalculator->calculate($insuranceConfig, $resolvedBase);
                    $insuranceTotal = Decimal::add($insuranceTotal, $insurance['employee_amount']);
                    $items->push($this->syntheticDeductionLine(
                        "INSURANCE:{$insuranceConfig->code}",
                        $insuranceConfig->name,
                        'insurance',
                        $insurance['employee_amount'],
                        "insurance:{$insuranceConfig->id}",
                        $insuranceConfig->employee_rate_percentage,
                        $insurance['salary_base']
                    ));
                }
            }

            $taxTotal = '0.00';
            if ($profile->tax_enabled) {
                $taxConfigs = TaxConfig::query()
                    ->with('brackets')
                    ->where('tax_year', $year)
                    ->where('is_active', true)
                    ->whereDate('effective_from', '<=', $periodDate->toDateString())
                    ->where(fn ($query) => $query->whereNull('effective_to')
                        ->orWhereDate('effective_to', '>=', $periodDate->toDateString()))
                    ->get();
                if ($taxConfigs->count() !== 1) {
                    throw ValidationException::withMessages([
                        'tax_config' => 'Payroll yêu cầu đúng một cấu hình thuế hiệu lực cho kỳ tính.',
                    ]);
                }
                $taxConfig = $taxConfigs->first();
                $taxableEarnings = Decimal::sum(
                    $items->where('type', 'earning')->where('is_taxable', true)->pluck('amount')
                );
                $tax = $this->taxCalculator->calculate(
                    $taxConfig,
                    $taxableEarnings,
                    $insuranceTotal,
                    $profile->dependent_count
                );
                $taxTotal = $tax['tax_amount'];
                $items->push($this->syntheticDeductionLine(
                    "PIT:{$taxConfig->code}",
                    $taxConfig->name,
                    'tax',
                    $taxTotal,
                    "tax:{$taxConfig->id}",
                    null,
                    $tax['taxable_income']
                ));
            }

            $deductions = $this->sumType($items, 'deduction');
            if (Decimal::compare($deductions, $gross) > 0) {
                throw ValidationException::withMessages(['payroll' => 'Tổng khấu trừ vượt quá gross salary.']);
            }
            $net = Decimal::subtract($gross, $deductions);

            $payroll->update([
                'academic_year_id' => $academicYear?->id,
                'teacher_salary_profile_id' => $profile->id,
                'teacher_code_snapshot' => $teacher->teacher_code,
                'teacher_name_snapshot' => $teacher->full_name,
                'specialization_snapshot' => $teacher->specialization,
                'academic_year_snapshot' => $academicYear?->name,
                'salary_profile_code_snapshot' => $profile->profile_code,
                'base_salary' => $this->sumCategory($items, 'base_salary', 'earning'),
                'total_allowances' => $this->sumCategory($items, 'allowance', 'earning'),
                'total_teaching_extra' => $this->sumCategory($items, 'teaching_extra', 'earning'),
                'total_bonus' => $this->sumCategory($items, 'bonus', 'earning'),
                'gross_salary' => $gross,
                'total_insurance' => $insuranceTotal,
                'total_tax' => $taxTotal,
                'total_deductions' => $deductions,
                'net_salary' => $net,
                'paid_amount' => '0.00',
                'remaining_amount' => $net,
                'status' => 'calculated',
                'calculated_by' => $actor->id,
                'calculated_at' => now(),
            ]);
            $payroll->items()->createMany($items->values()->all());
            TeacherPayrollInput::whereIn('id', $inputs->pluck('id'))->update(['included_payroll_id' => $payroll->id]);
            TeachingExtraRecord::whereIn('id', $teachingRecords->pluck('id'))->update(['included_payroll_id' => $payroll->id]);

            $this->audit->record($payroll, 'payroll.calculated', $actor, newValues: [
                'gross_salary' => $gross, 'total_deductions' => $deductions,
                'net_salary' => $net, 'item_count' => $items->count(),
            ]);

            return $payroll->fresh(['items']);
        });
    }

    private function amountFromConfig(TeacherSalaryConfig $config, string|int|null $quantity): ?string
    {
        return match ($config->component?->calculation_type) {
            'fixed' => $config->fixed_amount !== null ? Decimal::money($config->fixed_amount) : null,
            'per_unit' => $config->rate_per_unit !== null && $quantity !== null
                ? Decimal::multiply($config->rate_per_unit, $quantity)
                : null,
            default => null,
        };
    }

    /** @param array<string, mixed> $extra @return array<string, mixed> */
    private function componentLine(SalaryComponent $component, string $amount, string $lineKey, array $extra = []): array
    {
        return array_merge([
            'salary_component_id' => $component->id,
            'line_key' => $lineKey,
            'component_code' => $component->code,
            'component_name' => $component->name,
            'type' => $component->type,
            'category' => $component->category,
            'calculation_type' => $component->calculation_type,
            'amount' => Decimal::money($amount),
            'is_taxable' => $component->is_taxable,
            'is_insurance_base' => $component->is_insurance_base,
        ], $extra);
    }

    /** @return array<string, mixed> */
    private function syntheticDeductionLine(
        string $code,
        string $name,
        string $category,
        string $amount,
        string $lineKey,
        ?string $percentage,
        string $base
    ): array {
        return [
            'salary_component_id' => null, 'line_key' => $lineKey,
            'component_code' => $code, 'component_name' => $name,
            'type' => 'deduction', 'category' => $category, 'calculation_type' => 'percentage',
            'percentage_value' => $percentage, 'calculation_base_amount' => $base,
            'amount' => Decimal::money($amount), 'is_taxable' => false,
            'is_insurance_base' => false,
        ];
    }

    private function sumType(Collection $items, string $type): string
    {
        return Decimal::sum($items->where('type', $type)->pluck('amount'));
    }

    private function sumCategory(Collection $items, string $category, string $type): string
    {
        return Decimal::sum($items->where('type', $type)->where('category', $category)->pluck('amount'));
    }
}

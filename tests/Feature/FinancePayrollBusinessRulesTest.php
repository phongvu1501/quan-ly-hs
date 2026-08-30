<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Grade;
use App\Models\InsuranceConfig;
use App\Models\Payroll;
use App\Models\SalaryComponent;
use App\Models\SchoolClass;
use App\Models\Semester;
use App\Models\Subject;
use App\Models\TaxConfig;
use App\Models\Teacher;
use App\Models\TeacherPayrollInput;
use App\Models\TeacherSalaryProfile;
use App\Models\TeachingAssignment;
use App\Models\TeachingExtraRecord;
use App\Models\User;
use App\Services\Finance\PayrollAdjustmentService;
use App\Services\Finance\PayrollCalculationService;
use App\Services\Finance\PayrollPaymentService;
use App\Services\Finance\PayrollService;
use App\Services\Finance\TeachingExtraService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class FinancePayrollBusinessRulesTest extends TestCase
{
    use RefreshDatabase;

    public function test_base_allowance_teaching_bonus_deduction_insurance_tax_gross_and_net_are_server_calculated(): void
    {
        $context = $this->payrollContext();
        $this->createApprovedTeachingExtra($context, '20', '2000000');
        $this->createInput($context, $context['bonus'], 'bonus', null, '500000');
        $this->createInput($context, $context['otherDeduction'], 'manual_adjustment', null, '1000000');
        InsuranceConfig::create([
            'code' => 'INS-TEST', 'name' => 'Bảo hiểm test',
            'employee_rate_percentage' => '10', 'employer_rate_percentage' => '0',
            'salary_base_type' => 'base_salary', 'effective_from' => '2026-01-01', 'is_active' => true,
        ]);
        $tax = TaxConfig::create([
            'code' => 'TAX-2026', 'name' => 'Thuế test', 'tax_year' => 2026,
            'personal_deduction' => '0', 'dependent_deduction' => '0',
            'effective_from' => '2026-01-01', 'is_active' => true,
        ]);
        $tax->brackets()->create([
            'sequence' => 1, 'lower_bound' => '0', 'upper_bound' => null,
            'rate_percentage' => '10', 'quick_deduction' => '0',
        ]);
        $context['profile']->update(['tax_enabled' => true, 'insurance_enabled' => true]);

        $payroll = app(PayrollCalculationService::class)->calculate(
            $context['teacher'], 2026, 8, $context['year'], $context['actor']
        );

        $this->assertSame('8000000.00', $payroll->base_salary);
        $this->assertSame('1000000.00', $payroll->total_allowances);
        $this->assertSame('2000000.00', $payroll->total_teaching_extra);
        $this->assertSame('500000.00', $payroll->total_bonus);
        $this->assertSame('11500000.00', $payroll->gross_salary);
        $this->assertSame('800000.00', $payroll->total_insurance);
        $this->assertSame('1070000.00', $payroll->total_tax);
        $this->assertSame('2870000.00', $payroll->total_deductions);
        $this->assertSame('8630000.00', $payroll->net_salary);
        $this->assertDatabaseHas('payroll_items', [
            'payroll_id' => $payroll->id, 'component_code' => 'EXTRA_TEACHING',
            'quantity' => '20.00', 'rate' => '100000.00', 'amount' => '2000000.00',
            'subject_name_snapshot' => 'Toán', 'class_name_snapshot' => '10A1',
        ]);
    }

    public function test_teaching_extra_uses_server_rate_and_calculates_forty_periods(): void
    {
        $context = $this->payrollContext();
        $record = app(TeachingExtraService::class)->store([
            'teacher_id' => $context['teacher']->id,
            'academic_year_id' => $context['year']->id,
            'semester_id' => $context['semester']->id,
            'subject_id' => $context['subject']->id,
            'class_id' => $context['class']->id,
            'teaching_year' => 2026,
            'teaching_month' => 8,
            'number_of_periods' => '40',
        ], $context['actor']);

        $this->assertSame('100000.00', $record->rate_per_period);
        $this->assertSame('4000000.00', $record->total_amount);
    }

    public function test_duplicate_payroll_is_prevented_and_historical_configuration_is_immutable(): void
    {
        $context = $this->payrollContext();
        $service = app(PayrollService::class);
        $first = $service->generate(2026, 8, $context['year'], [$context['teacher']->id], $context['actor']);
        $payroll = Payroll::firstOrFail();
        $baseItem = $payroll->items()->where('component_code', 'BASE_SALARY')->firstOrFail();

        $context['baseConfig']->update(['fixed_amount' => '9000000']);
        $payroll->refresh();

        $this->assertSame(1, $first['created']);
        $this->assertSame('8000000.00', $payroll->base_salary);
        $this->assertSame('8000000.00', $baseItem->fresh()->amount);
        $second = $service->generate(2026, 8, $context['year'], [$context['teacher']->id], $context['actor']);
        $this->assertSame(1, $second['skipped']);
        $this->assertDatabaseCount('payrolls', 1);
    }

    public function test_payroll_lock_immutability_and_payment_lifecycle(): void
    {
        $context = $this->payrollContext();
        $payroll = app(PayrollCalculationService::class)->calculate(
            $context['teacher'], 2026, 8, $context['year'], $context['actor']
        );
        $workflow = app(PayrollService::class);
        $payroll = $workflow->approve($payroll, $context['actor']);
        $payroll = $workflow->lock($payroll, $context['actor']);

        $this->assertSame('locked', $payroll->status);
        $this->assertNotNull($payroll->locked_at);
        try {
            app(PayrollCalculationService::class)->calculate(
                $context['teacher'], 2026, 8, $context['year'], $context['actor']
            );
            $this->fail('Expected locked payroll recalculation rejection.');
        } catch (ValidationException) {
            $this->assertSame('locked', $payroll->fresh()->status);
        }

        $payment = app(PayrollPaymentService::class)->record($payroll, [
            'amount' => $payroll->net_salary,
            'payment_date' => '2026-08-31 10:00:00',
            'payment_method' => 'bank_transfer',
        ], $context['actor']);
        $this->assertSame('completed', $payment->status);
        $this->assertSame('paid', $payroll->fresh()->status);
        $this->assertSame('0.00', $payroll->fresh()->remaining_amount);

        try {
            app(PayrollPaymentService::class)->record($payroll, [
                'amount' => '1', 'payment_date' => '2026-08-31 11:00:00', 'payment_method' => 'cash',
            ], $context['actor']);
            $this->fail('Expected duplicate/full payment rejection.');
        } catch (ValidationException) {
            $this->assertDatabaseCount('payroll_payments', 1);
        }
    }

    public function test_locked_payroll_changes_use_separate_adjustment_workflow(): void
    {
        $context = $this->payrollContext();
        $payroll = app(PayrollCalculationService::class)->calculate(
            $context['teacher'], 2026, 8, $context['year'], $context['actor']
        );
        $workflow = app(PayrollService::class);
        $payroll = $workflow->lock($workflow->approve($payroll, $context['actor']), $context['actor']);
        $originalNet = $payroll->net_salary;
        $adjustments = app(PayrollAdjustmentService::class);
        $adjustment = $adjustments->create(
            $payroll, $context['bonus'], '250000', 'Bổ sung sau chốt', $context['actor']
        );
        $adjustment = $adjustments->approve($adjustment, $context['actor']);
        $adjustment = $adjustments->lock($adjustment, $context['actor']);
        $adjustment = $adjustments->settle($adjustment, 'bank_transfer', 'ADJ-REF', $context['actor']);

        $this->assertSame('paid', $adjustment->status);
        $this->assertSame('250000.00', $adjustment->amount);
        $this->assertSame($originalNet, $payroll->fresh()->net_salary);
        $this->assertSame('locked', $payroll->fresh()->status);
    }

    /** @return array<string, mixed> */
    private function payrollContext(): array
    {
        $year = AcademicYear::create([
            'name' => '2026-2027', 'code' => '2026-2027',
            'starts_on' => '2026-08-01', 'ends_on' => '2027-05-31', 'is_current' => true,
        ]);
        $semester = Semester::create([
            'academic_year_id' => $year->id, 'number' => 1, 'name' => 'Học kỳ 1',
            'starts_on' => '2026-08-01', 'ends_on' => '2026-12-31', 'is_current' => true,
        ]);
        $grade = Grade::create(['level' => 10, 'name' => 'Khối 10']);
        $teacher = Teacher::create(['full_name' => 'Teacher Payroll', 'status' => 'active', 'specialization' => 'Toán']);
        $subject = Subject::create(['code' => 'MATH', 'name' => 'Toán', 'is_active' => true]);
        $class = SchoolClass::create([
            'academic_year_id' => $year->id, 'grade_id' => $grade->id,
            'code' => '10A1', 'name' => '10A1', 'status' => 'active',
        ]);
        TeachingAssignment::create([
            'academic_year_id' => $year->id, 'semester_id' => $semester->id,
            'class_id' => $class->id, 'subject_id' => $subject->id,
            'teacher_id' => $teacher->id, 'status' => 'active',
        ]);
        $actor = User::factory()->create();
        $base = $this->salaryComponent('BASE_SALARY', 'Lương cơ bản', 'earning', 'base_salary', 'fixed', true, true);
        $allowance = $this->salaryComponent('ALLOWANCE', 'Phụ cấp', 'earning', 'allowance', 'fixed', true, false);
        $teaching = $this->salaryComponent('EXTRA_TEACHING', 'Dạy thêm', 'earning', 'teaching_extra', 'per_unit', true, false);
        $bonus = $this->salaryComponent('BONUS', 'Thưởng', 'earning', 'bonus', 'manual', true, false);
        $otherDeduction = $this->salaryComponent('OTHER_DEDUCTION', 'Khấu trừ', 'deduction', 'other_deduction', 'manual', false, false);
        $profile = TeacherSalaryProfile::create([
            'profile_code' => 'PROFILE-2026', 'teacher_id' => $teacher->id,
            'effective_from' => '2026-01-01', 'status' => 'active', 'created_by' => $actor->id,
        ]);
        $baseConfig = $profile->configs()->create([
            'salary_component_id' => $base->id, 'scope_key' => 'GLOBAL',
            'fixed_amount' => '8000000', 'is_active' => true,
        ]);
        $profile->configs()->create([
            'salary_component_id' => $allowance->id, 'scope_key' => 'GLOBAL',
            'fixed_amount' => '1000000', 'is_active' => true,
        ]);
        $teachingConfig = $profile->configs()->create([
            'salary_component_id' => $teaching->id, 'subject_id' => $subject->id,
            'scope_key' => 'SUBJECT:'.$subject->id, 'rate_per_unit' => '100000', 'is_active' => true,
        ]);

        return compact(
            'year', 'semester', 'teacher', 'subject', 'class', 'actor', 'profile',
            'base', 'allowance', 'teaching', 'bonus', 'otherDeduction', 'baseConfig', 'teachingConfig'
        );
    }

    private function salaryComponent(
        string $code, string $name, string $type, string $category,
        string $calculationType, bool $taxable, bool $insuranceBase
    ): SalaryComponent {
        return SalaryComponent::create([
            'code' => $code, 'name' => $name, 'type' => $type, 'category' => $category,
            'calculation_type' => $calculationType, 'is_taxable' => $taxable,
            'is_insurance_base' => $insuranceBase, 'is_active' => true,
        ]);
    }

    /** @param array<string, mixed> $context */
    private function createApprovedTeachingExtra(array $context, string $periods, string $amount): TeachingExtraRecord
    {
        return TeachingExtraRecord::create([
            'record_code' => 'TE-TEST', 'record_key' => hash('sha256', 'teaching-test'),
            'teacher_id' => $context['teacher']->id, 'academic_year_id' => $context['year']->id,
            'semester_id' => $context['semester']->id, 'subject_id' => $context['subject']->id,
            'class_id' => $context['class']->id,
            'teaching_assignment_id' => TeachingAssignment::firstOrFail()->id,
            'teacher_salary_config_id' => $context['teachingConfig']->id,
            'teaching_year' => 2026, 'teaching_month' => 8,
            'number_of_periods' => $periods, 'rate_per_period' => '100000', 'total_amount' => $amount,
            'subject_code_snapshot' => 'MATH', 'subject_name_snapshot' => 'Toán',
            'class_code_snapshot' => '10A1', 'class_name_snapshot' => '10A1', 'status' => 'approved',
        ]);
    }

    /** @param array<string, mixed> $context */
    private function createInput(
        array $context,
        SalaryComponent $component,
        string $type,
        ?string $quantity,
        ?string $amount
    ): TeacherPayrollInput {
        return TeacherPayrollInput::create([
            'input_code' => 'PI-'.$component->code, 'teacher_id' => $context['teacher']->id,
            'academic_year_id' => $context['year']->id, 'salary_component_id' => $component->id,
            'input_year' => 2026, 'input_month' => 8, 'input_type' => $type,
            'quantity' => $quantity, 'input_amount' => $amount, 'status' => 'approved',
        ]);
    }
}

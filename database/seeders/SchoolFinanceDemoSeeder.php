<?php

namespace Database\Seeders;

use App\Models\FeeAssessment;
use App\Models\FeeCategory;
use App\Models\FeePayment;
use App\Models\FeeRate;
use App\Models\InsuranceConfig;
use App\Models\Payroll;
use App\Models\SalaryComponent;
use App\Models\TaxConfig;
use App\Models\Teacher;
use App\Models\TeacherPayrollInput;
use App\Models\TeacherSalaryProfile;
use App\Services\Finance\FeePaymentService;
use App\Services\Finance\PayrollCalculationService;
use App\Services\Finance\PayrollPaymentService;
use App\Services\Finance\PayrollService;
use App\Services\Finance\SalaryConfigurationService;
use App\Services\Finance\StudentFeeService;
use Database\Seeders\Support\SchoolDemo;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use RuntimeException;

class SchoolFinanceDemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([FeeCategorySeeder::class, SalaryComponentSeeder::class]);

        $year = SchoolDemo::academicYear();
        $semester = SchoolDemo::semester($year);
        $actor = SchoolDemo::actor();

        [$assessmentCount, $feePaymentCount] = $this->createStudentFees($year, $semester, $actor);
        [$profileCount, $payrollCount, $payrollPaymentCount] = $this->createTeacherPayrolls($year, $actor);

        $this->command?->info(sprintf(
            'Tài chính: %d khoản phải thu, %d giao dịch học phí, %d hồ sơ lương, %d bảng lương, %d giao dịch lương.',
            $assessmentCount,
            $feePaymentCount,
            $profileCount,
            $payrollCount,
            $payrollPaymentCount
        ));
    }

    private function createStudentFees($year, $semester, $actor): array
    {
        $category = FeeCategory::where('code', 'TUITION')->firstOrFail();
        $rate = FeeRate::firstOrCreate(
            ['code' => SchoolDemo::FEE_CODE_PREFIX.$year->code],
            [
                'fee_category_id' => $category->id,
                'academic_year_id' => $year->id,
                'name' => 'Học phí tháng - dữ liệu trường học mẫu',
                'period_type' => 'monthly',
                'amount' => '750000.00',
                'effective_from' => $year->starts_on,
                'effective_to' => $year->ends_on,
                'is_active' => true,
                'description' => SchoolDemo::MARKER.' Mức học phí mẫu.',
            ]
        );

        $feeService = app(StudentFeeService::class);
        $noticeIds = collect();
        $periodStart = Carbon::parse($year->starts_on)->startOfMonth();
        foreach (range(0, 2) as $offset) {
            $month = $periodStart->copy()->addMonths($offset);
            $assessmentDate = $month->copy()->startOfMonth()->max(Carbon::parse($year->starts_on));
            $dueDate = $month->copy()->day(15)->max($assessmentDate);
            $result = $feeService->generate([
                'academic_year_id' => $year->id,
                'semester_id' => $semester->id,
                'fee_category_id' => $category->id,
                'fee_rate_id' => $rate->id,
                'title' => SchoolDemo::MARKER.' Học phí tháng '.$month->format('m/Y'),
                'period_type' => 'monthly',
                'period_key' => $month->format('Y-m'),
                'charge_sequence' => 1,
                'assessment_date' => $assessmentDate->toDateString(),
                'due_date' => $dueDate->toDateString(),
                'scope_type' => 'all',
                'student_ids' => [],
                'note' => SchoolDemo::MARKER.' Khoản học phí mẫu phục vụ kiểm thử.',
            ], $actor);
            $noticeIds->push($result['notice']->id);
        }

        $assessments = FeeAssessment::whereIn('fee_notice_id', $noticeIds)->orderBy('id')->get();
        $paymentService = app(FeePaymentService::class);
        $paymentCount = 0;
        foreach ($assessments as $index => $assessment) {
            if ($index % 10 < 2 || ! $assessment->remaining_amount) {
                continue;
            }
            $paymentCode = sprintf('SCHOOL-DEMO-FP-%08d', $assessment->id);
            if (FeePayment::where('payment_code', $paymentCode)->exists()) {
                continue;
            }

            $amount = $index % 10 < 4
                ? number_format(((float) $assessment->remaining_amount) / 2, 2, '.', '')
                : (string) $assessment->remaining_amount;
            $payment = $paymentService->record($assessment, [
                'amount' => $amount,
                'payment_date' => Carbon::parse($assessment->assessment_date)->addDays(7)->setTime(9, 0),
                'payment_method' => $index % 3 === 0 ? 'cash' : 'bank_transfer',
                'transaction_code' => $paymentCode,
                'receipt_number' => 'BL-'.str_pad((string) $assessment->id, 8, '0', STR_PAD_LEFT),
                'note' => SchoolDemo::MARKER.' Thanh toán học phí mẫu.',
            ], $actor);
            $payment->update(['payment_code' => $paymentCode]);
            $paymentCount++;
        }

        return [$assessments->count(), $paymentCount];
    }

    private function createTeacherPayrolls($academicYear, $actor): array
    {
        $teachers = Teacher::where('teacher_code', 'like', SchoolDemo::TEACHER_CODE_PREFIX.'%')
            ->orderBy('id')
            ->get();
        $components = SalaryComponent::whereIn('code', ['BASE_SALARY', 'ALLOWANCE', 'BONUS'])
            ->get()
            ->keyBy('code');
        foreach (['BASE_SALARY', 'ALLOWANCE', 'BONUS'] as $code) {
            if (! $components->has($code)) {
                throw new RuntimeException("Thiếu thành phần lương {$code}.");
            }
        }

        $payrollYear = (int) now()->format('Y');
        $payrollMonth = (int) now()->format('n');
        $periodEnd = Carbon::create($payrollYear, $payrollMonth, 1)->endOfMonth();
        $taxEnabled = TaxConfig::where('tax_year', $payrollYear)
            ->where('is_active', true)
            ->whereDate('effective_from', '<=', $periodEnd)
            ->where(fn ($query) => $query->whereNull('effective_to')->orWhereDate('effective_to', '>=', $periodEnd))
            ->count() === 1;
        $insuranceEnabled = InsuranceConfig::where('is_active', true)
            ->whereDate('effective_from', '<=', $periodEnd)
            ->where(fn ($query) => $query->whereNull('effective_to')->orWhereDate('effective_to', '>=', $periodEnd))
            ->exists();

        $configurationService = app(SalaryConfigurationService::class);
        $calculator = app(PayrollCalculationService::class);
        $payrollService = app(PayrollService::class);
        $paymentService = app(PayrollPaymentService::class);
        $profileCount = 0;
        $payrollCount = 0;
        $paymentCount = 0;

        foreach ($teachers as $index => $teacher) {
            $profileCode = SchoolDemo::SALARY_PROFILE_PREFIX.$teacher->teacher_code;
            $profile = TeacherSalaryProfile::where('profile_code', $profileCode)->first();
            if (! $profile) {
                $baseSalary = 9000000 + (($index % 7) * 500000);
                $homeroomAllowance = $teacher->homeroomClasses()->where('academic_year_id', $academicYear->id)->exists();
                $profile = $configurationService->save([
                    'profile_code' => $profileCode,
                    'teacher_id' => $teacher->id,
                    'effective_from' => $academicYear->starts_on->toDateString(),
                    'effective_to' => null,
                    'dependent_count' => $index % 3,
                    'tax_enabled' => $taxEnabled,
                    'insurance_enabled' => $insuranceEnabled,
                    'status' => 'active',
                    'note' => SchoolDemo::MARKER.' Hồ sơ lương mẫu.',
                    'configs' => [
                        [
                            'salary_component_id' => $components['BASE_SALARY']->id,
                            'subject_id' => null,
                            'fixed_amount' => (string) $baseSalary,
                            'is_active' => true,
                            'note' => SchoolDemo::MARKER.' Lương cơ bản mẫu.',
                        ],
                        [
                            'salary_component_id' => $components['ALLOWANCE']->id,
                            'subject_id' => null,
                            'fixed_amount' => (string) ($homeroomAllowance ? 1200000 : 700000),
                            'is_active' => true,
                            'note' => SchoolDemo::MARKER.' Phụ cấp mẫu.',
                        ],
                    ],
                ], $actor);
                $profileCount++;
            }

            if ($index % 4 === 0) {
                TeacherPayrollInput::firstOrCreate(
                    ['input_code' => SchoolDemo::PAYROLL_INPUT_PREFIX.$teacher->teacher_code.'-'.$payrollYear.'-'.$payrollMonth],
                    [
                        'teacher_id' => $teacher->id,
                        'academic_year_id' => $academicYear->id,
                        'salary_component_id' => $components['BONUS']->id,
                        'input_year' => $payrollYear,
                        'input_month' => $payrollMonth,
                        'input_type' => 'bonus',
                        'input_amount' => (string) (300000 + (($index % 3) * 100000)),
                        'status' => 'approved',
                        'note' => SchoolDemo::MARKER.' Thưởng chuyên cần mẫu.',
                        'created_by' => $actor->id,
                        'approved_by' => $actor->id,
                        'approved_at' => now(),
                    ]
                );
            }

            if (Payroll::where('teacher_id', $teacher->id)
                ->where('payroll_year', $payrollYear)
                ->where('payroll_month', $payrollMonth)
                ->exists()) {
                continue;
            }

            $payroll = $calculator->calculate($teacher, $payrollYear, $payrollMonth, $academicYear, $actor);
            $payroll->update([
                'payroll_code' => SchoolDemo::PAYROLL_CODE_PREFIX.$teacher->teacher_code.'-'.$payrollYear.'-'.str_pad((string) $payrollMonth, 2, '0', STR_PAD_LEFT),
                'note' => SchoolDemo::MARKER.' Bảng lương mẫu.',
            ]);
            $payrollCount++;

            if ($index % 4 >= 1) {
                $payroll = $payrollService->approve($payroll, $actor);
            }
            if ($index % 4 >= 2) {
                $payroll = $payrollService->lock($payroll, $actor);
            }
            if ($index % 4 === 3) {
                $payment = $paymentService->record($payroll, [
                    'amount' => (string) $payroll->remaining_amount,
                    'payment_date' => now(),
                    'payment_method' => 'bank_transfer',
                    'receipt_number' => 'LUONG-'.str_pad((string) $payroll->id, 7, '0', STR_PAD_LEFT),
                    'note' => SchoolDemo::MARKER.' Thanh toán lương mẫu.',
                ], $actor);
                $payment->update(['payment_code' => 'SCHOOL-DEMO-PP-'.str_pad((string) $payment->id, 8, '0', STR_PAD_LEFT)]);
                $paymentCount++;
            }
        }

        return [$profileCount, $payrollCount, $paymentCount];
    }
}

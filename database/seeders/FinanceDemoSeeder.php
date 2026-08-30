<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\FeeAssessment;
use App\Models\FeeCategory;
use App\Models\FeePayment;
use App\Models\FeePolicy;
use App\Models\FeeRate;
use App\Models\InsuranceConfig;
use App\Models\Role;
use App\Models\SalaryComponent;
use App\Models\Semester;
use App\Models\Student;
use App\Models\StudentFeePolicy;
use App\Models\Subject;
use App\Models\TaxConfig;
use App\Models\Teacher;
use App\Models\TeacherPayrollInput;
use App\Models\TeacherSalaryConfig;
use App\Models\TeacherSalaryProfile;
use App\Models\TeachingAssignment;
use App\Models\TeachingExtraRecord;
use App\Models\User;
use App\Services\Finance\FeePaymentService;
use App\Services\Finance\PayrollCalculationService;
use App\Services\Finance\StudentFeeService;
use Illuminate\Database\Seeder;

class FinanceDemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([FeeCategorySeeder::class, SalaryComponentSeeder::class]);

        $actor = Role::where('slug', 'admin')->first()?->users()->first() ?? User::firstOrFail();
        $student = Student::where('status', 'studying')->orderBy('id')->firstOrFail();
        $teacher = Teacher::where('status', 'active')
            ->whereHas('assignments', fn ($query) => $query->where('status', 'active'))
            ->orderBy('id')
            ->firstOrFail();
        $year = AcademicYear::where('is_current', true)->firstOrFail();
        $semester = Semester::where('academic_year_id', $year->id)->orderBy('number')->firstOrFail();
        $enrollment = $student->enrollments()->where('academic_year_id', $year->id)->where('status', 'active')->firstOrFail();

        $this->seedFeeDemo($student, $year, $semester, $enrollment->class_id, $actor);
        $this->seedPayrollDemo($teacher, $year, $semester, $actor);

        $this->command?->info("Đã tạo dữ liệu demo tài chính cho học sinh {$student->student_code} và giáo viên {$teacher->teacher_code}.");
        $this->command?->info('Dữ liệu demo dùng mã DEMO-*; không cập nhật students/teachers hiện có.');
    }

    private function seedFeeDemo(Student $student, AcademicYear $year, Semester $semester, int $classId, User $actor): void
    {
        $category = FeeCategory::where('code', 'TUITION')->firstOrFail();
        $rate = FeeRate::firstOrCreate(
            ['code' => 'DEMO-TUITION-2026'],
            [
                'fee_category_id' => $category->id, 'academic_year_id' => $year->id,
                'name' => 'Học phí demo theo tháng', 'period_type' => 'monthly',
                'amount' => '500000', 'effective_from' => '2026-08-01',
                'effective_to' => '2027-05-31', 'is_active' => true,
                'description' => 'Mức mẫu để kiểm thử; không phải mức pháp lý.',
            ]
        );

        $policies = [
            'DEMO-NEAR-POOR' => ['Giảm 50% demo', 'percentage', '50', '2026-09-01', '2026-09-30'],
            'DEMO-FIXED-200K' => ['Giảm cố định 200.000 ₫ demo', 'fixed_amount', '200000', '2026-10-01', '2026-10-31'],
            'DEMO-EXEMPTION' => ['Miễn toàn bộ demo', 'full_exemption', '100', '2026-11-01', null],
        ];
        foreach ($policies as $code => [$name, $type, $value, $from, $to]) {
            $policy = FeePolicy::firstOrCreate(
                ['code' => $code],
                [
                    'academic_year_id' => $year->id, 'fee_category_id' => $category->id,
                    'name' => $name, 'reduction_type' => $type, 'reduction_value' => $value,
                    'application_mode' => 'exclusive', 'priority' => 100,
                    'effective_from' => $from, 'effective_to' => $to, 'status' => 'active',
                    'description' => 'Dữ liệu demo để kiểm thử chính sách; không phải quy định thực tế.',
                ]
            );
            StudentFeePolicy::firstOrCreate(
                ['student_id' => $student->id, 'fee_policy_id' => $policy->id, 'effective_from' => $from],
                ['effective_to' => $to, 'status' => 'active', 'assigned_by' => $actor->id]
            );
        }

        $feeService = app(StudentFeeService::class);
        $periods = [
            ['2026-08', '2026-08-01', '2026-08-31'],
            ['2026-09', '2026-09-01', '2026-09-30'],
            ['2026-10', '2026-10-01', '2026-10-31'],
            ['2026-11', '2026-11-01', '2026-11-30'],
        ];
        foreach ($periods as [$periodKey, $assessmentDate, $dueDate]) {
            $result = $feeService->generate([
                'academic_year_id' => $year->id, 'semester_id' => $semester->id,
                'class_id' => $classId, 'fee_category_id' => $category->id, 'fee_rate_id' => $rate->id,
                'title' => 'Học phí demo '.$periodKey, 'period_type' => 'monthly', 'period_key' => $periodKey,
                'charge_sequence' => 1, 'assessment_date' => $assessmentDate, 'due_date' => $dueDate,
                'scope_type' => 'selected', 'student_ids' => [$student->id],
                'note' => 'Dữ liệu demo tài chính.',
            ], $actor);

            $assessment = FeeAssessment::where('fee_notice_id', $result['notice']->id)
                ->where('student_id', $student->id)->firstOrFail();
            if ($periodKey === '2026-08') {
                $this->demoFeePayment($assessment, '200000', '2026-08-20 09:00:00', $actor, 'DEMO-FEE-AUG-P1');
            } elseif ($periodKey === '2026-09') {
                $this->demoFeePayment($assessment, '100000', '2026-09-10 09:00:00', $actor, 'DEMO-FEE-SEP-P1');
                $this->demoFeePayment($assessment->fresh(), '150000', '2026-09-20 09:00:00', $actor, 'DEMO-FEE-SEP-P2');
            }
        }
    }

    private function demoFeePayment(FeeAssessment $assessment, string $amount, string $date, User $actor, string $code): void
    {
        if (FeePayment::where('payment_code', $code)->exists()) {
            return;
        }

        $payment = app(FeePaymentService::class)->record($assessment, [
            'amount' => $amount, 'payment_date' => $date, 'payment_method' => 'bank_transfer',
            'receipt_number' => $code, 'note' => 'Thanh toán demo.',
        ], $actor);
        $payment->update(['payment_code' => $code]);
    }

    private function seedPayrollDemo(Teacher $teacher, AcademicYear $year, Semester $semester, User $actor): void
    {
        $assignment = TeachingAssignment::where('teacher_id', $teacher->id)
            ->where('academic_year_id', $year->id)->where('status', 'active')->with(['subject', 'class'])->firstOrFail();
        $components = SalaryComponent::whereIn('code', [
            'BASE_SALARY', 'ALLOWANCE', 'EXTRA_TEACHING', 'BONUS', 'UNPAID_LEAVE', 'OTHER_DEDUCTION',
        ])->get()->keyBy('code');

        $profile = TeacherSalaryProfile::firstOrCreate(
            ['profile_code' => 'DEMO-PROFILE-'.$teacher->teacher_code],
            [
                'teacher_id' => $teacher->id, 'effective_from' => '2026-01-01',
                'dependent_count' => 0, 'tax_enabled' => true, 'insurance_enabled' => true,
                'status' => 'active', 'created_by' => $actor->id,
            ]
        );
        $configs = [
            ['BASE_SALARY', null, 'GLOBAL', ['fixed_amount' => '8000000']],
            ['ALLOWANCE', null, 'GLOBAL', ['fixed_amount' => '1500000']],
            ['EXTRA_TEACHING', $assignment->subject_id, 'SUBJECT:'.$assignment->subject_id, ['rate_per_unit' => '100000']],
            ['UNPAID_LEAVE', null, 'GLOBAL', ['rate_per_unit' => '100000']],
        ];
        foreach ($configs as [$code, $subjectId, $scopeKey, $values]) {
            TeacherSalaryConfig::firstOrCreate(
                ['teacher_salary_profile_id' => $profile->id, 'salary_component_id' => $components[$code]->id, 'scope_key' => $scopeKey],
                ['subject_id' => $subjectId] + $values + ['is_active' => true]
            );
        }

        $tax = TaxConfig::firstOrCreate(
            ['code' => 'DEMO-TAX-2026'],
            [
                'name' => 'Cấu hình thuế demo', 'tax_year' => 2026,
                'personal_deduction' => '0', 'dependent_deduction' => '0',
                'effective_from' => '2026-01-01', 'effective_to' => '2026-12-31', 'is_active' => true,
            ]
        );
        $tax->brackets()->firstOrCreate(
            ['sequence' => 1],
            ['lower_bound' => '0', 'upper_bound' => null, 'rate_percentage' => '10', 'quick_deduction' => '0']
        );
        InsuranceConfig::firstOrCreate(
            ['code' => 'DEMO-INS-2026'],
            [
                'name' => 'Cấu hình bảo hiểm demo', 'employee_rate_percentage' => '10',
                'employer_rate_percentage' => '0', 'salary_base_type' => 'base_salary',
                'effective_from' => '2026-01-01', 'effective_to' => '2026-12-31', 'is_active' => true,
            ]
        );

        TeacherPayrollInput::firstOrCreate(
            ['input_code' => 'DEMO-BONUS-'.$teacher->teacher_code],
            [
                'teacher_id' => $teacher->id, 'academic_year_id' => $year->id,
                'salary_component_id' => $components['BONUS']->id, 'input_year' => 2026,
                'input_month' => 8, 'input_type' => 'bonus', 'input_amount' => '500000',
                'status' => 'approved', 'created_by' => $actor->id, 'note' => 'Thưởng demo.',
            ]
        );
        TeacherPayrollInput::firstOrCreate(
            ['input_code' => 'DEMO-LEAVE-'.$teacher->teacher_code],
            [
                'teacher_id' => $teacher->id, 'academic_year_id' => $year->id,
                'salary_component_id' => $components['UNPAID_LEAVE']->id, 'input_year' => 2026,
                'input_month' => 8, 'input_type' => 'unpaid_leave', 'quantity' => '2',
                'status' => 'approved', 'created_by' => $actor->id, 'note' => 'Nghỉ demo.',
            ]
        );
        TeacherPayrollInput::firstOrCreate(
            ['input_code' => 'DEMO-DEDUCTION-'.$teacher->teacher_code],
            [
                'teacher_id' => $teacher->id, 'academic_year_id' => $year->id,
                'salary_component_id' => $components['OTHER_DEDUCTION']->id, 'input_year' => 2026,
                'input_month' => 8, 'input_type' => 'manual_adjustment', 'input_amount' => '300000',
                'status' => 'approved', 'created_by' => $actor->id, 'note' => 'Khấu trừ demo.',
            ]
        );

        $recordKey = hash('sha256', 'DEMO-TEACHING-'.$teacher->teacher_code);
        TeachingExtraRecord::firstOrCreate(
            ['record_key' => $recordKey],
            [
                'record_code' => 'DEMO-TEACHING-'.$teacher->teacher_code,
                'teacher_id' => $teacher->id, 'academic_year_id' => $year->id,
                'semester_id' => $semester->id, 'subject_id' => $assignment->subject_id,
                'class_id' => $assignment->class_id, 'teaching_assignment_id' => $assignment->id,
                'teacher_salary_config_id' => TeacherSalaryConfig::where('teacher_salary_profile_id', $profile->id)
                    ->where('scope_key', 'SUBJECT:'.$assignment->subject_id)->value('id'),
                'teaching_year' => 2026, 'teaching_month' => 8,
                'number_of_periods' => '20', 'rate_per_period' => '100000', 'total_amount' => '2000000',
                'subject_code_snapshot' => $assignment->subject?->code, 'subject_name_snapshot' => $assignment->subject?->name,
                'class_code_snapshot' => $assignment->class?->code, 'class_name_snapshot' => $assignment->class?->name,
                'status' => 'approved', 'created_by' => $actor->id, 'note' => 'Dạy thêm demo.',
            ]
        );

        if (! \App\Models\Payroll::where('payroll_code', 'DEMO-PAYROLL-'.$teacher->teacher_code.'-2026-08')->exists()) {
            $payroll = app(PayrollCalculationService::class)->calculate($teacher, 2026, 8, $year, $actor);
            $payroll->update(['payroll_code' => 'DEMO-PAYROLL-'.$teacher->teacher_code.'-2026-08']);
        }
    }
}

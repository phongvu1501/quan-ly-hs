<?php

namespace Database\Seeders;

use App\Models\FeeAssessment;
use App\Models\FeeNotice;
use App\Models\FeePayment;
use App\Models\Payroll;
use App\Models\PayrollPayment;
use App\Models\TeacherSalaryProfile;
use Database\Seeders\Support\SchoolDemo;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SchoolDemoCleanupSeeder extends Seeder
{
    public function run(): void
    {
        $studentIds = DB::table('students')
            ->where('student_code', 'like', SchoolDemo::STUDENT_CODE_PREFIX.'%')
            ->pluck('id');
        $studentUserIds = DB::table('students')->whereIn('id', $studentIds)->whereNotNull('user_id')->pluck('user_id');
        $teacherIds = DB::table('teachers')
            ->where('teacher_code', 'like', SchoolDemo::TEACHER_CODE_PREFIX.'%')
            ->pluck('id');
        $teacherUserIds = DB::table('teachers')->whereIn('id', $teacherIds)->whereNotNull('user_id')->pluck('user_id');

        DB::transaction(function () use ($studentIds, $teacherIds, $studentUserIds, $teacherUserIds): void {
            $this->deleteFinanceDemo($studentIds, $teacherIds);
            $this->deleteAcademicDemo($studentIds, $teacherIds);
            $this->deletePeopleAndClasses($studentIds, $teacherIds, $studentUserIds, $teacherUserIds);
        });

        $this->command?->info(sprintf(
            'Đã dọn dữ liệu mẫu: %d học sinh và %d giáo viên demo.',
            $studentIds->count(),
            $teacherIds->count()
        ));
    }

    private function deleteFinanceDemo(Collection $studentIds, Collection $teacherIds): void
    {
        $noticeIds = DB::table('fee_notices')
            ->where('description', 'like', SchoolDemo::MARKER.'%')
            ->orWhere('title', 'like', SchoolDemo::MARKER.'%')
            ->pluck('id');
        $assessmentIds = DB::table('fee_assessments')
            ->whereIn('fee_notice_id', $noticeIds)
            ->when($studentIds->isNotEmpty(), fn ($query) => $query->orWhereIn('student_id', $studentIds))
            ->pluck('id');
        $feePaymentIds = DB::table('fee_payments')
            ->whereIn('fee_assessment_id', $assessmentIds)
            ->when($studentIds->isNotEmpty(), fn ($query) => $query->orWhereIn('student_id', $studentIds))
            ->pluck('id');

        $this->deleteAuditRows(FeePayment::class, $feePaymentIds);
        $this->deleteAuditRows(FeeAssessment::class, $assessmentIds);
        $this->deleteAuditRows(FeeNotice::class, $noticeIds);
        DB::table('fee_payments')->whereIn('id', $feePaymentIds)->delete();
        DB::table('fee_assessment_discounts')->whereIn('fee_assessment_id', $assessmentIds)->delete();
        DB::table('fee_assessments')->whereIn('id', $assessmentIds)->delete();
        DB::table('fee_notices')->whereIn('id', $noticeIds)->delete();
        DB::table('student_fee_policies')->whereIn('student_id', $studentIds)->delete();

        $rateIds = DB::table('fee_rates')->where('code', 'like', SchoolDemo::FEE_CODE_PREFIX.'%')->pluck('id');
        DB::table('fee_rates')->whereIn('id', $rateIds)
            ->whereNotExists(fn ($query) => $query->selectRaw('1')->from('fee_notices')->whereColumn('fee_notices.fee_rate_id', 'fee_rates.id'))
            ->delete();

        $payrollIds = DB::table('payrolls')
            ->where('payroll_code', 'like', SchoolDemo::PAYROLL_CODE_PREFIX.'%')
            ->when($teacherIds->isNotEmpty(), fn ($query) => $query->orWhereIn('teacher_id', $teacherIds))
            ->pluck('id');
        $payrollPaymentIds = DB::table('payroll_payments')->whereIn('payroll_id', $payrollIds)->pluck('id');
        $profileIds = DB::table('teacher_salary_profiles')
            ->where('profile_code', 'like', SchoolDemo::SALARY_PROFILE_PREFIX.'%')
            ->when($teacherIds->isNotEmpty(), fn ($query) => $query->orWhereIn('teacher_id', $teacherIds))
            ->pluck('id');

        $this->deleteAuditRows(PayrollPayment::class, $payrollPaymentIds);
        $this->deleteAuditRows(Payroll::class, $payrollIds);
        $this->deleteAuditRows(TeacherSalaryProfile::class, $profileIds);

        DB::table('teacher_payroll_inputs')
            ->whereIn('teacher_id', $teacherIds)
            ->orWhere('input_code', 'like', SchoolDemo::PAYROLL_INPUT_PREFIX.'%')
            ->delete();
        DB::table('teaching_extra_records')->whereIn('teacher_id', $teacherIds)->delete();
        DB::table('payroll_adjustments')->whereIn('payroll_id', $payrollIds)->delete();
        DB::table('payroll_payments')->whereIn('id', $payrollPaymentIds)->delete();
        DB::table('payroll_items')->whereIn('payroll_id', $payrollIds)->delete();
        DB::table('payrolls')->whereIn('id', $payrollIds)->delete();
        DB::table('teacher_salary_configs')->whereIn('teacher_salary_profile_id', $profileIds)->delete();
        DB::table('teacher_salary_profiles')->whereIn('id', $profileIds)->delete();
    }

    private function deleteAcademicDemo(Collection $studentIds, Collection $teacherIds): void
    {
        $examIds = DB::table('exams')->where('notes', 'like', SchoolDemo::MARKER.'%')->pluck('id');
        DB::table('exam_results')->whereIn('exam_id', $examIds)
            ->when($studentIds->isNotEmpty(), fn ($query) => $query->orWhereIn('student_id', $studentIds))
            ->delete();
        DB::table('exam_invigilations')->whereIn('exam_id', $examIds)
            ->when($teacherIds->isNotEmpty(), fn ($query) => $query->orWhereIn('teacher_id', $teacherIds))
            ->delete();
        DB::table('exams')->whereIn('id', $examIds)->delete();

        DB::table('scores')
            ->where('note', 'like', SchoolDemo::MARKER.'%')
            ->when($studentIds->isNotEmpty(), fn ($query) => $query->orWhereIn('student_id', $studentIds))
            ->delete();
        DB::table('subject_results')
            ->where('teacher_comment', 'like', SchoolDemo::MARKER.'%')
            ->when($studentIds->isNotEmpty(), fn ($query) => $query->orWhereIn('student_id', $studentIds))
            ->delete();
        DB::table('teacher_comments')
            ->where('content', 'like', SchoolDemo::MARKER.'%')
            ->when($studentIds->isNotEmpty(), fn ($query) => $query->orWhereIn('student_id', $studentIds))
            ->when($teacherIds->isNotEmpty(), fn ($query) => $query->orWhereIn('teacher_id', $teacherIds))
            ->delete();
        DB::table('conduct_evaluations')
            ->where('comment', 'like', SchoolDemo::MARKER.'%')
            ->when($studentIds->isNotEmpty(), fn ($query) => $query->orWhereIn('student_id', $studentIds))
            ->delete();
        DB::table('attendance_records')
            ->where('reason', 'like', SchoolDemo::MARKER.'%')
            ->when($studentIds->isNotEmpty(), fn ($query) => $query->orWhereIn('student_id', $studentIds))
            ->delete();
        DB::table('announcements')->where('title', 'like', SchoolDemo::MARKER.'%')->delete();
        DB::table('schedules')->where('notes', 'like', SchoolDemo::MARKER.'%')->delete();

        DB::table('classes')->whereIn('homeroom_teacher_id', $teacherIds)->update(['homeroom_teacher_id' => null]);
        DB::table('homeroom_assignments')->whereIn('teacher_id', $teacherIds)->delete();
        DB::table('teaching_assignments')->whereIn('teacher_id', $teacherIds)->delete();
        DB::table('teacher_subject')->whereIn('teacher_id', $teacherIds)->delete();
    }

    private function deletePeopleAndClasses(
        Collection $studentIds,
        Collection $teacherIds,
        Collection $studentUserIds,
        Collection $teacherUserIds
    ): void {
        $guardianIds = DB::table('guardians')->where('notes', 'like', SchoolDemo::MARKER.'%')->pluck('id');
        DB::table('guardian_student')->whereIn('student_id', $studentIds)->orWhereIn('guardian_id', $guardianIds)->delete();
        DB::table('guardians')->whereIn('id', $guardianIds)->delete();
        DB::table('enrollments')->whereIn('student_id', $studentIds)->delete();
        DB::table('students')->whereIn('id', $studentIds)->delete();
        DB::table('teachers')->whereIn('id', $teacherIds)->delete();

        $userIds = $studentUserIds->merge($teacherUserIds)->unique()->values();
        DB::table('role_user')->whereIn('user_id', $userIds)->delete();
        DB::table('personal_access_tokens')->where('tokenable_type', 'App\\Models\\User')->whereIn('tokenable_id', $userIds)->delete();
        DB::table('notifications')->where('notifiable_type', 'App\\Models\\User')->whereIn('notifiable_id', $userIds)->delete();
        DB::table('sessions')->whereIn('user_id', $userIds)->delete();
        DB::table('users')->whereIn('id', $userIds)->delete();

        $demoClasses = DB::table('classes')->where('code', 'like', SchoolDemo::CLASS_CODE_PREFIX.'%')->get();
        foreach ($demoClasses as $class) {
            $hasExternalData = DB::table('enrollments')->where('class_id', $class->id)->exists()
                || DB::table('teaching_assignments')->where('class_id', $class->id)->exists()
                || DB::table('schedules')->where('class_id', $class->id)->exists();
            if ($hasExternalData) {
                $this->command?->warn("Giữ lại lớp {$class->name} vì đã có dữ liệu ngoài bộ demo.");

                continue;
            }
            DB::table('classes')->where('id', $class->id)->delete();
        }
    }

    private function deleteAuditRows(string $modelClass, Collection $ids): void
    {
        if ($ids->isEmpty()) {
            return;
        }

        $type = (new $modelClass)->getMorphClass();
        DB::table('finance_activity_logs')
            ->where('auditable_type', $type)
            ->whereIn('auditable_id', $ids)
            ->delete();
    }
}

<?php

namespace App\Services\Finance;

use App\Models\AcademicYear;
use App\Models\SchoolClass;
use App\Models\Semester;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\TeacherSalaryConfig;
use App\Models\TeacherSalaryProfile;
use App\Models\TeachingAssignment;
use App\Models\TeachingExtraRecord;
use App\Models\User;
use App\Support\Decimal;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class TeachingExtraService
{
    public function __construct(private readonly FinanceAuditService $audit) {}

    /** @param array<string, mixed> $input */
    public function store(array $input, User $actor): TeachingExtraRecord
    {
        return DB::transaction(function () use ($input, $actor): TeachingExtraRecord {
            $teacher = Teacher::whereKey($input['teacher_id'])->where('status', 'active')->firstOrFail();
            $year = AcademicYear::findOrFail($input['academic_year_id']);
            $semester = Semester::whereKey($input['semester_id'])
                ->where('academic_year_id', $year->id)->firstOrFail();
            $subject = Subject::whereKey($input['subject_id'])->where('is_active', true)->firstOrFail();
            $class = SchoolClass::whereKey($input['class_id'])
                ->where('academic_year_id', $year->id)->firstOrFail();
            $assignment = TeachingAssignment::query()
                ->where('teacher_id', $teacher->id)
                ->where('academic_year_id', $year->id)
                ->where('semester_id', $semester->id)
                ->where('subject_id', $subject->id)
                ->where('class_id', $class->id)
                ->where('status', 'active')
                ->first();
            if (! $assignment) {
                throw ValidationException::withMessages([
                    'teacher_id' => 'Giáo viên không có phân công phù hợp với lớp, môn và học kỳ đã chọn.',
                ]);
            }

            $businessDate = Carbon::create(
                (int) $input['teaching_year'],
                (int) $input['teaching_month'],
                1,
                0,
                0,
                0,
                config('app.timezone')
            );
            $profile = TeacherSalaryProfile::query()
                ->where('teacher_id', $teacher->id)
                ->where('status', 'active')
                ->whereDate('effective_from', '<=', $businessDate->toDateString())
                ->where(fn ($query) => $query->whereNull('effective_to')
                    ->orWhereDate('effective_to', '>=', $businessDate->toDateString()))
                ->with(['configs.component'])
                ->first();
            if (! $profile) {
                throw ValidationException::withMessages(['teacher_id' => 'Giáo viên chưa có cấu hình lương hiệu lực.']);
            }

            $configs = $profile->configs->filter(fn (TeacherSalaryConfig $config) => $config->is_active
                && $config->component?->category === 'teaching_extra'
                && $config->component?->calculation_type === 'per_unit'
            );
            $salaryConfig = $configs->firstWhere('subject_id', $subject->id)
                ?? $configs->firstWhere('scope_key', 'GLOBAL');
            if (! $salaryConfig || $salaryConfig->rate_per_unit === null) {
                throw ValidationException::withMessages([
                    'subject_id' => 'Chưa có đơn giá dạy thêm hiệu lực cho giáo viên và môn học này.',
                ]);
            }

            $periods = Decimal::quantity($input['number_of_periods']);
            if (! Decimal::isPositive($periods)) {
                throw ValidationException::withMessages(['number_of_periods' => 'Số tiết phải lớn hơn 0.']);
            }
            $total = Decimal::multiply($salaryConfig->rate_per_unit, $periods);
            $recordKey = hash('sha256', implode('|', [
                $teacher->id, $year->id, $semester->id, $assignment->id,
                (int) $input['teaching_year'], (int) $input['teaching_month'],
            ]));
            $record = TeachingExtraRecord::query()->lockForUpdate()->where('record_key', $recordKey)->first();
            if ($record && $record->status !== 'draft') {
                throw ValidationException::withMessages([
                    'teaching_extra' => 'Bản ghi dạy thêm đã được duyệt hoặc hủy, không thể cập nhật trực tiếp.',
                ]);
            }

            $values = [
                'teacher_id' => $teacher->id, 'academic_year_id' => $year->id,
                'semester_id' => $semester->id, 'subject_id' => $subject->id,
                'class_id' => $class->id, 'teaching_assignment_id' => $assignment->id,
                'teacher_salary_config_id' => $salaryConfig->id,
                'teaching_year' => (int) $input['teaching_year'],
                'teaching_month' => (int) $input['teaching_month'],
                'number_of_periods' => $periods, 'rate_per_period' => $salaryConfig->rate_per_unit,
                'total_amount' => $total, 'subject_code_snapshot' => $subject->code,
                'subject_name_snapshot' => $subject->name, 'class_code_snapshot' => $class->code,
                'class_name_snapshot' => $class->name, 'status' => 'draft',
                'note' => $input['note'] ?? null, 'created_by' => $actor->id,
            ];
            if ($record) {
                $record->update($values);
            } else {
                $record = TeachingExtraRecord::create($values + [
                    'record_code' => 'TE-'.Str::upper((string) Str::ulid()),
                    'record_key' => $recordKey,
                ]);
            }

            $this->audit->record($record, 'teaching_extra.saved', $actor, newValues: [
                'number_of_periods' => $periods,
                'rate_per_period' => $salaryConfig->rate_per_unit,
                'total_amount' => $total,
            ]);

            return $record->fresh();
        });
    }

    public function approve(TeachingExtraRecord $record, User $actor): TeachingExtraRecord
    {
        return DB::transaction(function () use ($record, $actor): TeachingExtraRecord {
            $locked = TeachingExtraRecord::query()->lockForUpdate()->findOrFail($record->id);
            if ($locked->status !== 'draft') {
                throw ValidationException::withMessages(['teaching_extra' => 'Chỉ bản ghi nháp mới được duyệt.']);
            }
            $locked->update(['status' => 'approved', 'approved_by' => $actor->id, 'approved_at' => now()]);
            $this->audit->record($locked, 'teaching_extra.approved', $actor);

            return $locked->fresh();
        });
    }
}

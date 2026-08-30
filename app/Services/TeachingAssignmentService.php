<?php

namespace App\Services;

use App\Models\AttendanceRecord;
use App\Models\Exam;
use App\Models\Schedule;
use App\Models\SchoolClass;
use App\Models\Semester;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\TeacherComment;
use App\Models\TeachingAssignment;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TeachingAssignmentService
{
    /** @param array<string, mixed> $input */
    public function create(array $input): TeachingAssignment
    {
        $input['status'] ??= TeachingAssignment::STATUS_ACTIVE;

        return DB::transaction(function () use ($input): TeachingAssignment {
            $this->validateContext($input, true);
            $this->assertNotDuplicate($input);

            return TeachingAssignment::create($input);
        });
    }

    /** @param array<string, mixed> $input */
    public function update(TeachingAssignment $assignment, array $input): TeachingAssignment
    {
        return DB::transaction(function () use ($assignment, $input): TeachingAssignment {
            $locked = TeachingAssignment::query()->lockForUpdate()->findOrFail($assignment->id);
            $identityChanged = $this->identityChanged($locked, $input);
            if ($identityChanged && $this->hasHistory($locked)) {
                throw ValidationException::withMessages([
                    'assignment' => 'Phân công đã phát sinh dữ liệu liên quan. Chỉ được kết thúc phân công, không được thay đổi giáo viên, lớp, môn hoặc năm học.',
                ]);
            }

            if ($identityChanged || $input['status'] === TeachingAssignment::STATUS_ACTIVE) {
                $this->validateContext($input, $identityChanged);
            }
            $this->assertNotDuplicate($input, $locked);
            $locked->update($input);

            return $locked->fresh();
        });
    }

    /**
     * @return bool true when hard-deleted, false when retained and deactivated
     */
    public function deleteOrDeactivate(TeachingAssignment $assignment): bool
    {
        return DB::transaction(function () use ($assignment): bool {
            $locked = TeachingAssignment::query()->lockForUpdate()->findOrFail($assignment->id);
            if ($this->hasHistory($locked)) {
                $locked->update(['status' => TeachingAssignment::STATUS_INACTIVE]);

                return false;
            }
            $locked->delete();

            return true;
        });
    }

    public function hasHistory(TeachingAssignment $assignment): bool
    {
        if ($assignment->scores()->exists() || $assignment->teachingExtraRecords()->exists()) {
            return true;
        }

        $scope = [
            'academic_year_id' => $assignment->academic_year_id,
            'class_id' => $assignment->class_id,
            'subject_id' => $assignment->subject_id,
        ];
        if (Schedule::where($scope)->where('teacher_id', $assignment->teacher_id)->exists()) {
            return true;
        }
        if (AttendanceRecord::where('academic_year_id', $assignment->academic_year_id)
            ->where('class_id', $assignment->class_id)
            ->where(fn ($query) => $query->where('subject_id', $assignment->subject_id)->orWhereNull('subject_id'))
            ->exists()) {
            return true;
        }
        if (Exam::query()
            ->where('academic_year_id', $assignment->academic_year_id)
            ->where('class_id', $assignment->class_id)
            ->where('subject_id', $assignment->subject_id)
            ->exists()) {
            return true;
        }

        return TeacherComment::query()
            ->where('academic_year_id', $assignment->academic_year_id)
            ->where('teacher_id', $assignment->teacher_id)
            ->where('subject_id', $assignment->subject_id)
            ->whereHas('student.enrollments', fn ($query) => $query
                ->where('academic_year_id', $assignment->academic_year_id)
                ->where('class_id', $assignment->class_id))
            ->exists();
    }

    /** @param array<string, mixed> $input */
    private function validateContext(array $input, bool $requireActiveTeacher): void
    {
        $class = SchoolClass::findOrFail($input['class_id']);
        if ((int) $class->academic_year_id !== (int) $input['academic_year_id']) {
            throw ValidationException::withMessages(['class_id' => 'Lớp không thuộc năm học đã chọn.']);
        }
        if ($class->status !== 'active') {
            throw ValidationException::withMessages(['class_id' => 'Chỉ lớp đang hoạt động mới được tạo phân công.']);
        }

        $subject = Subject::findOrFail($input['subject_id']);
        if (! $subject->is_active) {
            throw ValidationException::withMessages(['subject_id' => 'Chỉ môn học đang sử dụng mới được tạo phân công.']);
        }
        if (! empty($input['semester_id']) && ! Semester::query()
            ->whereKey($input['semester_id'])
            ->where('academic_year_id', $input['academic_year_id'])
            ->exists()) {
            throw ValidationException::withMessages(['semester_id' => 'Học kỳ không thuộc năm học đã chọn.']);
        }

        $teacher = Teacher::findOrFail($input['teacher_id']);
        if (($requireActiveTeacher || $input['status'] === TeachingAssignment::STATUS_ACTIVE) && ! $teacher->isActive()) {
            throw ValidationException::withMessages([
                'teacher_id' => 'Giáo viên hiện không ở trạng thái "Đang công tác" nên không thể phân công giảng dạy.',
            ]);
        }
        $qualified = DB::table('teacher_subject')
            ->where('teacher_id', $teacher->id)
            ->where('subject_id', $subject->id)
            ->where('academic_year_id', $input['academic_year_id'])
            ->exists();
        if (! $qualified) {
            throw ValidationException::withMessages([
                'teacher_id' => 'Giáo viên chưa được khai báo đủ chuyên môn cho môn học trong năm học này.',
            ]);
        }
    }

    /** @param array<string, mixed> $input */
    private function assertNotDuplicate(array $input, ?TeachingAssignment $ignore = null): void
    {
        $duplicate = TeachingAssignment::query()
            ->where('academic_year_id', $input['academic_year_id'])
            ->where('class_id', $input['class_id'])
            ->where('subject_id', $input['subject_id'])
            ->where('teacher_id', $input['teacher_id'])
            ->when(
                empty($input['semester_id']),
                fn ($query) => $query->whereNull('semester_id'),
                fn ($query) => $query->where('semester_id', $input['semester_id'])
            )
            ->when($ignore, fn ($query) => $query->whereKeyNot($ignore->id))
            ->exists();
        if ($duplicate) {
            throw ValidationException::withMessages(['teacher_id' => 'Phân công này đã tồn tại.']);
        }
    }

    /** @param array<string, mixed> $input */
    private function identityChanged(TeachingAssignment $assignment, array $input): bool
    {
        foreach (['academic_year_id', 'semester_id', 'class_id', 'subject_id', 'teacher_id'] as $field) {
            if ((int) ($assignment->{$field} ?? 0) !== (int) ($input[$field] ?? 0)) {
                return true;
            }
        }

        return false;
    }
}

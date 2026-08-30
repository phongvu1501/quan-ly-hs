<?php

namespace App\Services;

use App\Models\HomeroomAssignment;
use App\Models\SchoolClass;
use App\Models\Teacher;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class HomeroomAssignmentService
{
    /** @param Collection<int, int> $classIds */
    public function syncTeacherYear(Teacher $teacher, int $academicYearId, Collection $classIds): void
    {
        if (! $teacher->isActive() && $classIds->isNotEmpty()) {
            throw ValidationException::withMessages([
                'homeroom_class_ids' => 'Chỉ giáo viên đang công tác mới được phân công chủ nhiệm.',
            ]);
        }
        if ($classIds->count() > 1) {
            throw ValidationException::withMessages([
                'homeroom_class_ids' => 'Một giáo viên chỉ được làm chủ nhiệm một lớp trong cùng năm học.',
            ]);
        }

        $classes = SchoolClass::query()
            ->whereIn('id', $classIds)
            ->where('academic_year_id', $academicYearId)
            ->where('status', 'active')
            ->lockForUpdate()
            ->get();
        if ($classes->count() !== $classIds->count()) {
            throw ValidationException::withMessages([
                'homeroom_class_ids' => 'Lớp chủ nhiệm phải đang hoạt động và thuộc năm học đã chọn.',
            ]);
        }

        $currentClasses = SchoolClass::query()
            ->where('academic_year_id', $academicYearId)
            ->where('homeroom_teacher_id', $teacher->id)
            ->lockForUpdate()
            ->get();
        foreach ($currentClasses->whereNotIn('id', $classIds) as $class) {
            $this->unassign($class, $teacher);
        }
        foreach ($classes as $class) {
            $this->assign($class, $teacher);
        }
    }

    public function assign(SchoolClass $class, Teacher $teacher): HomeroomAssignment
    {
        if (! $teacher->isActive()) {
            throw ValidationException::withMessages([
                'homeroom_teacher_id' => 'Chỉ giáo viên đang công tác mới được phân công chủ nhiệm.',
            ]);
        }
        if ($class->homeroom_teacher_id && (int) $class->homeroom_teacher_id !== (int) $teacher->id) {
            throw ValidationException::withMessages([
                'homeroom_teacher_id' => "Lớp {$class->name} đã có giáo viên chủ nhiệm.",
            ]);
        }
        $otherClassExists = SchoolClass::query()
            ->where('academic_year_id', $class->academic_year_id)
            ->where('homeroom_teacher_id', $teacher->id)
            ->whereKeyNot($class->id)
            ->exists();
        if ($otherClassExists) {
            throw ValidationException::withMessages([
                'homeroom_teacher_id' => 'Giáo viên này đã chủ nhiệm một lớp khác trong năm học.',
            ]);
        }

        $class->update(['homeroom_teacher_id' => $teacher->id]);
        $active = HomeroomAssignment::query()
            ->where('class_id', $class->id)
            ->where('teacher_id', $teacher->id)
            ->where('status', HomeroomAssignment::STATUS_ACTIVE)
            ->first();

        return $active ?: HomeroomAssignment::create([
            'teacher_id' => $teacher->id,
            'class_id' => $class->id,
            'academic_year_id' => $class->academic_year_id,
            'started_on' => today(),
            'status' => HomeroomAssignment::STATUS_ACTIVE,
        ]);
    }

    public function unassign(SchoolClass $class, Teacher $teacher): void
    {
        HomeroomAssignment::query()
            ->where('class_id', $class->id)
            ->where('teacher_id', $teacher->id)
            ->where('status', HomeroomAssignment::STATUS_ACTIVE)
            ->update([
                'status' => HomeroomAssignment::STATUS_INACTIVE,
                'ended_on' => today(),
            ]);
        if ((int) $class->homeroom_teacher_id === (int) $teacher->id) {
            $class->update(['homeroom_teacher_id' => null]);
        }
    }

    public function endAllForTeacher(Teacher $teacher): void
    {
        $teacher->homeroomClasses()->lockForUpdate()->get()->each(
            fn (SchoolClass $class) => $this->unassign($class, $teacher)
        );
    }
}

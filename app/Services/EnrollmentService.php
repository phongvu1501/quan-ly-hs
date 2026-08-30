<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\Enrollment;
use App\Models\SchoolClass;
use App\Models\Student;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EnrollmentService
{
    public function enroll(Student $student, ?int $academicYearId, ?int $classId): ?Enrollment
    {
        if (! $academicYearId && ! $classId) {
            return null;
        }
        if (! $student->isStudying()) {
            throw ValidationException::withMessages([
                'class_id' => 'Học sinh hiện không ở trạng thái "Đang học" nên không thể xếp lớp.',
            ]);
        }
        if (! $academicYearId || ! $classId) {
            throw ValidationException::withMessages([
                'class_id' => 'Hãy chọn đầy đủ năm học và lớp khi xếp lớp cho học sinh.',
            ]);
        }

        AcademicYear::findOrFail($academicYearId);
        $class = SchoolClass::query()
            ->whereKey($classId)
            ->where('academic_year_id', $academicYearId)
            ->where('status', 'active')
            ->first();
        if (! $class) {
            throw ValidationException::withMessages([
                'class_id' => 'Lớp phải thuộc đúng năm học và đang hoạt động.',
            ]);
        }

        return DB::transaction(function () use ($student, $academicYearId, $classId): Enrollment {
            $existing = Enrollment::query()
                ->where('student_id', $student->id)
                ->where('academic_year_id', $academicYearId)
                ->lockForUpdate()
                ->first();

            if ($existing) {
                if ((int) $existing->class_id !== $classId) {
                    throw ValidationException::withMessages([
                        'class_id' => 'Học sinh đã có lịch sử lớp trong năm học này. Không thể ghi đè sang lớp khác.',
                    ]);
                }
                if ($existing->status !== Enrollment::STATUS_ACTIVE) {
                    throw ValidationException::withMessages([
                        'class_id' => 'Enrollment của năm học này đã kết thúc và không thể tự động kích hoạt lại.',
                    ]);
                }

                return $existing;
            }

            return Enrollment::create([
                'student_id' => $student->id,
                'academic_year_id' => $academicYearId,
                'class_id' => $classId,
                'status' => Enrollment::STATUS_ACTIVE,
                'enrolled_on' => today(),
            ]);
        });
    }

    public function closeForTerminalStatus(Student $student, string $status, Carbon $effectiveDate): void
    {
        $enrollmentStatus = match ($status) {
            Student::STATUS_GRADUATED => Enrollment::STATUS_COMPLETED,
            Student::STATUS_DROPPED => Enrollment::STATUS_WITHDRAWN,
            Student::STATUS_SICK_LEAVE, Student::STATUS_RESERVED => Enrollment::STATUS_INACTIVE,
            default => null,
        };
        if (! $enrollmentStatus) {
            return;
        }

        $student->enrollments()
            ->where('status', Enrollment::STATUS_ACTIVE)
            ->update([
                'status' => $enrollmentStatus,
                'left_on' => $effectiveDate->toDateString(),
            ]);
    }
}

<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\AttendanceRecord;
use App\Models\Enrollment;
use App\Models\Grade;
use App\Models\Role;
use App\Models\Schedule;
use App\Models\SchoolClass;
use App\Models\Score;
use App\Models\Semester;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\TeachingAssignment;
use App\Models\User;
use App\Services\EnrollmentService;
use App\Services\StudentService;
use App\Services\TeacherService;
use App\Services\TeachingAssignmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class StudentTeacherLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_service_creates_and_synchronizes_linked_user(): void
    {
        Role::firstOrCreate(['slug' => 'student'], ['name' => 'Student']);
        $service = app(StudentService::class);
        $student = $service->create([
            'full_name' => 'Nguyen Van A',
            'email' => 'student-a@example.test',
            'status' => Student::STATUS_STUDYING,
            'admission_year' => '2026-2027',
        ]);

        $this->assertNotNull($student->user_id);
        $this->assertSame('Nguyen Van A', $student->user->name);

        $updated = $service->update($student, [
            'full_name' => 'Nguyen Van A Updated',
            'email' => 'student-a-updated@example.test',
            'status' => Student::STATUS_STUDYING,
            'admission_year' => '2026-2027',
        ]);
        $this->assertSame('Nguyen Van A Updated', $updated->user->name);
        $this->assertSame('student-a-updated@example.test', $updated->user->email);
    }

    public function test_non_studying_students_cannot_enroll_and_studying_students_can_enroll(): void
    {
        [$year, , $class] = $this->schoolContext();
        $service = app(EnrollmentService::class);
        foreach ([Student::STATUS_GRADUATED, Student::STATUS_SICK_LEAVE, Student::STATUS_RESERVED, Student::STATUS_DROPPED] as $status) {
            $student = Student::create(['full_name' => $status, 'status' => $status]);
            try {
                $service->enroll($student, $year->id, $class->id);
                $this->fail("Expected {$status} enrollment rejection.");
            } catch (ValidationException $exception) {
                $this->assertStringContainsString('không ở trạng thái', strtolower($exception->getMessage()));
            }
        }

        $student = Student::create(['full_name' => 'Studying', 'status' => Student::STATUS_STUDYING]);
        $enrollment = $service->enroll($student, $year->id, $class->id);
        $this->assertSame($class->id, $enrollment->class_id);
    }

    public function test_enrollment_cannot_be_reactivated_or_rewritten_to_another_class(): void
    {
        [$year, , $class, $otherClass] = $this->schoolContext();
        $student = Student::create(['full_name' => 'History Student', 'status' => Student::STATUS_STUDYING]);
        $enrollment = Enrollment::create([
            'student_id' => $student->id, 'academic_year_id' => $year->id,
            'class_id' => $class->id, 'status' => Enrollment::STATUS_INACTIVE,
            'enrolled_on' => '2026-08-20', 'left_on' => '2026-09-10',
        ]);

        try {
            app(EnrollmentService::class)->enroll($student, $year->id, $otherClass->id);
            $this->fail('Expected class rewrite rejection.');
        } catch (ValidationException $exception) {
            $this->assertStringContainsString('lịch sử lớp', strtolower($exception->getMessage()));
        }
        $this->assertSame($class->id, $enrollment->fresh()->class_id);
        $this->assertSame('2026-08-20', $enrollment->fresh()->enrolled_on->toDateString());
    }

    public function test_student_with_any_history_cannot_be_deleted_and_status_change_preserves_history(): void
    {
        [$year, $semester, $class, , $subject] = $this->schoolContext();
        $teacher = Teacher::create(['full_name' => 'Teacher', 'status' => Teacher::STATUS_ACTIVE]);
        $student = Student::create(['full_name' => 'History Student', 'status' => Student::STATUS_STUDYING]);
        $enrollment = Enrollment::create([
            'student_id' => $student->id, 'academic_year_id' => $year->id,
            'class_id' => $class->id, 'status' => Enrollment::STATUS_ACTIVE,
        ]);
        Score::create([
            'academic_year_id' => $year->id, 'semester_id' => $semester->id,
            'student_id' => $student->id, 'class_id' => $class->id,
            'subject_id' => $subject->id, 'teacher_id' => $teacher->id,
            'score_type' => 'midterm', 'score' => 8,
        ]);
        AttendanceRecord::create([
            'academic_year_id' => $year->id, 'class_id' => $class->id,
            'student_id' => $student->id, 'subject_id' => $subject->id,
            'attended_on' => '2026-09-01', 'status' => 'present',
        ]);

        try {
            app(StudentService::class)->delete($student);
            $this->fail('Expected student delete rejection.');
        } catch (ValidationException $exception) {
            $this->assertStringContainsString('không thể xóa học sinh', strtolower($exception->getMessage()));
        }

        app(StudentService::class)->update($student, [
            'full_name' => $student->full_name,
            'status' => Student::STATUS_DROPPED,
        ]);
        $this->assertDatabaseHas('students', ['id' => $student->id, 'status' => Student::STATUS_DROPPED]);
        $this->assertDatabaseHas('enrollments', ['id' => $enrollment->id, 'status' => Enrollment::STATUS_WITHDRAWN]);
        $this->assertDatabaseHas('scores', ['student_id' => $student->id]);
        $this->assertDatabaseHas('attendance_records', ['student_id' => $student->id]);
    }

    public function test_graduation_requires_the_grade_twelve_year_to_be_finished(): void
    {
        $year = AcademicYear::create([
            'name' => '2026-2027', 'code' => 'GRAD-2026-2027',
            'starts_on' => '2026-08-01', 'ends_on' => '2027-05-31', 'is_current' => true,
        ]);
        $service = app(StudentService::class);
        $gradeTwelveStudent = null;
        $gradeTwelveEnrollment = null;

        try {
            $service->create([
                'full_name' => 'Invalid Graduate',
                'status' => Student::STATUS_GRADUATED,
            ]);
            $this->fail('Expected direct graduated student creation rejection.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('status', $exception->errors());
        }

        Carbon::setTestNow('2026-09-01 08:00:00');
        try {
            foreach ([10, 11, 12] as $level) {
                $grade = Grade::create(['level' => $level, 'name' => "Khối {$level}"]);
                $class = SchoolClass::create([
                    'academic_year_id' => $year->id,
                    'grade_id' => $grade->id,
                    'code' => "GRAD-{$level}A1",
                    'name' => "{$level}A1",
                    'status' => 'active',
                ]);
                $student = Student::create([
                    'full_name' => "Student {$level}",
                    'status' => Student::STATUS_STUDYING,
                ]);
                $enrollment = Enrollment::create([
                    'student_id' => $student->id,
                    'academic_year_id' => $year->id,
                    'class_id' => $class->id,
                    'status' => Enrollment::STATUS_ACTIVE,
                ]);

                try {
                    $service->update($student, [
                        'full_name' => $student->full_name,
                        'status' => Student::STATUS_GRADUATED,
                    ]);
                    $this->fail("Expected grade {$level} graduation rejection.");
                } catch (ValidationException $exception) {
                    $this->assertArrayHasKey('status', $exception->errors());
                }

                $this->assertSame(Student::STATUS_STUDYING, $student->fresh()->status);
                $this->assertSame(Enrollment::STATUS_ACTIVE, $enrollment->fresh()->status);
                if ($level === 12) {
                    $gradeTwelveStudent = $student;
                    $gradeTwelveEnrollment = $enrollment;
                }
            }

            Carbon::setTestNow('2027-05-31 23:59:59');
            $this->assertFalse($service->canGraduate($gradeTwelveStudent));

            Carbon::setTestNow('2027-06-01 00:00:01');
            $this->assertTrue($service->canGraduate($gradeTwelveStudent));
            $service->update($gradeTwelveStudent, [
                'full_name' => $gradeTwelveStudent->full_name,
                'status' => Student::STATUS_GRADUATED,
            ]);

            $this->assertSame(Student::STATUS_GRADUATED, $gradeTwelveStudent->fresh()->status);
            $this->assertSame(Enrollment::STATUS_COMPLETED, $gradeTwelveEnrollment->fresh()->status);
            $this->assertSame('2027-06-01', $gradeTwelveEnrollment->fresh()->left_on->toDateString());
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_teacher_assignment_requires_active_qualified_teacher_and_duplicate_is_blocked(): void
    {
        [$year, $semester, $class, , $subject] = $this->schoolContext();
        $teacher = Teacher::create(['full_name' => 'Active Teacher', 'status' => Teacher::STATUS_ACTIVE]);
        DB::table('teacher_subject')->insert([
            'teacher_id' => $teacher->id, 'subject_id' => $subject->id,
            'academic_year_id' => $year->id, 'is_primary' => false,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $service = app(TeachingAssignmentService::class);
        $payload = [
            'academic_year_id' => $year->id, 'semester_id' => $semester->id,
            'class_id' => $class->id, 'subject_id' => $subject->id,
            'teacher_id' => $teacher->id, 'status' => TeachingAssignment::STATUS_ACTIVE,
        ];
        $assignment = $service->create($payload);
        $this->assertSame($teacher->id, $assignment->teacher_id);

        try {
            $service->create($payload);
            $this->fail('Expected duplicate assignment rejection.');
        } catch (ValidationException $exception) {
            $this->assertStringContainsString('đã tồn tại', strtolower($exception->getMessage()));
        }

        $inactive = Teacher::create(['full_name' => 'Inactive Teacher', 'status' => Teacher::STATUS_INACTIVE]);
        try {
            $service->create(array_merge($payload, ['teacher_id' => $inactive->id]));
            $this->fail('Expected inactive teacher rejection.');
        } catch (ValidationException $exception) {
            $this->assertStringContainsString('không thể phân công giảng dạy', $exception->getMessage());
        }
    }

    public function test_assignment_with_history_is_deactivated_and_teacher_status_keeps_history(): void
    {
        [$year, $semester, $class, , $subject] = $this->schoolContext();
        $teacher = Teacher::create(['full_name' => 'History Teacher', 'status' => Teacher::STATUS_ACTIVE]);
        DB::table('teacher_subject')->insert([
            'teacher_id' => $teacher->id, 'subject_id' => $subject->id,
            'academic_year_id' => $year->id, 'is_primary' => false,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $assignment = app(TeachingAssignmentService::class)->create([
            'academic_year_id' => $year->id, 'semester_id' => $semester->id,
            'class_id' => $class->id, 'subject_id' => $subject->id,
            'teacher_id' => $teacher->id, 'status' => TeachingAssignment::STATUS_ACTIVE,
        ]);
        Schedule::create([
            'academic_year_id' => $year->id, 'semester_id' => $semester->id,
            'class_id' => $class->id, 'subject_id' => $subject->id,
            'teacher_id' => $teacher->id, 'weekday' => 2, 'period' => 1,
        ]);

        $this->assertFalse(app(TeachingAssignmentService::class)->deleteOrDeactivate($assignment));
        $this->assertDatabaseHas('teaching_assignments', ['id' => $assignment->id, 'status' => TeachingAssignment::STATUS_INACTIVE]);
        app(TeacherService::class)->update($teacher, [
            'full_name' => $teacher->full_name,
            'status' => Teacher::STATUS_INACTIVE,
        ]);
        $this->assertDatabaseHas('teachers', ['id' => $teacher->id, 'status' => Teacher::STATUS_INACTIVE]);
        $this->assertDatabaseHas('teaching_assignments', ['id' => $assignment->id]);
        $this->assertDatabaseHas('schedules', ['teacher_id' => $teacher->id]);
    }

    public function test_student_and_teacher_crud_routes_are_protected_by_rbac(): void
    {
        $user = User::factory()->create();
        $student = Student::create(['full_name' => 'Student', 'status' => Student::STATUS_STUDYING]);
        $teacher = Teacher::create(['full_name' => 'Teacher', 'status' => Teacher::STATUS_ACTIVE]);

        $this->actingAs($user)->get(route('admin.school.students.index'))->assertForbidden();
        $this->actingAs($user)->get(route('admin.school.students.show', $student))->assertForbidden();
        $this->actingAs($user)->get(route('admin.school.teachers.index'))->assertForbidden();
        $this->actingAs($user)->get(route('admin.school.teachers.show', $teacher))->assertForbidden();
    }

    /** @return array{AcademicYear, Semester, SchoolClass, SchoolClass, Subject} */
    private function schoolContext(): array
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
        $class = SchoolClass::create([
            'academic_year_id' => $year->id, 'grade_id' => $grade->id,
            'code' => '10A1', 'name' => '10A1', 'status' => 'active',
        ]);
        $otherClass = SchoolClass::create([
            'academic_year_id' => $year->id, 'grade_id' => $grade->id,
            'code' => '10A2', 'name' => '10A2', 'status' => 'active',
        ]);
        $subject = Subject::create(['code' => 'MATH', 'name' => 'Toán', 'is_active' => true]);

        return [$year, $semester, $class, $otherClass, $subject];
    }
}

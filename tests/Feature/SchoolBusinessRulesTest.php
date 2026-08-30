<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Announcement;
use App\Models\FeeNotice;
use App\Models\Grade;
use App\Models\Permission;
use App\Models\Role;
use App\Models\SchoolClass;
use App\Models\Semester;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SchoolBusinessRulesTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_and_teacher_codes_are_generated_with_six_digits(): void
    {
        $student = Student::create(['full_name' => 'Student A', 'status' => 'studying']);
        $teacher = Teacher::create(['full_name' => 'Teacher A', 'status' => 'active']);

        $this->assertMatchesRegularExpression('/^HS\d{6}$/', $student->student_code);
        $this->assertMatchesRegularExpression('/^GV\d{6}$/', $teacher->teacher_code);
    }

    public function test_teacher_cannot_be_scheduled_for_two_classes_in_the_same_period(): void
    {
        [$year, $semester, $classA, $classB, $subject] = $this->schoolContext();
        $teacher = Teacher::create(['full_name' => 'Teacher A', 'status' => 'active']);
        DB::table('teacher_subject')->insert([
            'teacher_id' => $teacher->id,
            'subject_id' => $subject->id,
            'academic_year_id' => $year->id,
            'is_primary' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $actor = $this->userWithPermission('schedules.manage');
        $payload = [
            'academic_year_id' => $year->id,
            'semester_id' => $semester->id,
            'week_start' => '2026-08-24',
            'subject_id' => $subject->id,
            'teacher_id' => $teacher->id,
            'weekday' => 2,
            'period' => 1,
        ];

        $this->actingAs($actor)->post(route('admin.school.schedules.store'), $payload + [
            'class_id' => $classA->id,
        ])->assertRedirect();

        $this->post(route('admin.school.schedules.store'), $payload + [
            'class_id' => $classB->id,
        ])->assertSessionHasErrors('teacher_id');

        $this->assertDatabaseCount('schedules', 1);
    }

    public function test_student_only_sees_announcements_and_fees_for_their_audience_and_class(): void
    {
        [$year, $semester, $classA, $classB] = $this->schoolContext();
        $user = User::factory()->create();
        $role = Role::firstOrCreate(['slug' => 'student'], ['name' => 'Student']);
        $permissions = collect(['student.dashboard', 'student-fees.view'])->map(fn (string $slug) =>
            Permission::firstOrCreate(['slug' => $slug], ['name' => $slug])
        );
        $role->permissions()->syncWithoutDetaching($permissions->pluck('id'));
        $user->roles()->attach($role);
        $student = Student::create([
            'user_id' => $user->id,
            'full_name' => 'Student A',
            'status' => 'studying',
        ]);
        $student->enrollments()->create([
            'academic_year_id' => $year->id,
            'class_id' => $classA->id,
            'status' => 'active',
        ]);
        Announcement::create([
            'title' => 'Announcement for class A',
            'content' => 'Visible',
            'audience' => 'students',
            'class_id' => $classA->id,
            'is_published' => true,
        ]);
        Announcement::create([
            'title' => 'Teacher-only announcement',
            'content' => 'Hidden',
            'audience' => 'teachers',
            'class_id' => $classA->id,
            'is_published' => true,
        ]);
        FeeNotice::create([
            'academic_year_id' => $year->id,
            'semester_id' => $semester->id,
            'class_id' => $classA->id,
            'title' => 'Class A tuition',
            'amount' => 100000,
            'is_active' => true,
        ]);
        FeeNotice::create([
            'academic_year_id' => $year->id,
            'semester_id' => $semester->id,
            'class_id' => $classB->id,
            'title' => 'Class B tuition',
            'amount' => 200000,
            'is_active' => true,
        ]);

        $this->actingAs($user)->get(route('student.dashboard'))
            ->assertOk()
            ->assertSee('Announcement for class A')
            ->assertDontSee('Teacher-only announcement');
        $this->get(route('student.payments'))
            ->assertOk()
            ->assertSee('Class A tuition')
            ->assertDontSee('Class B tuition');
    }

    public function test_admin_dashboard_shows_operational_details_and_counts_unpaid_fee_notices(): void
    {
        [$year, $semester, $classA] = $this->schoolContext();
        $student = Student::create(['full_name' => 'Student A', 'status' => 'studying']);
        $student->enrollments()->create([
            'academic_year_id' => $year->id,
            'class_id' => $classA->id,
            'status' => 'active',
        ]);
        FeeNotice::create([
            'academic_year_id' => $year->id,
            'semester_id' => $semester->id,
            'title' => 'School tuition',
            'amount' => 100000,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->userWithPermission('school.dashboard'))
            ->get(route('admin.school.dashboard'));

        $response->assertOk()
            ->assertSee('operationsModal')
            ->assertSee('School tuition');
        $this->assertSame(100000.0, (float) $response->viewData('outstandingFees'));
    }

    private function schoolContext(): array
    {
        $year = AcademicYear::create([
            'name' => '2026-2027',
            'code' => '2026-2027',
            'starts_on' => '2026-08-01',
            'ends_on' => '2027-05-31',
            'is_current' => true,
        ]);
        $semester = Semester::create([
            'academic_year_id' => $year->id,
            'number' => 1,
            'name' => 'Semester 1',
            'starts_on' => '2026-08-01',
            'ends_on' => '2026-12-31',
            'is_current' => true,
        ]);
        $grade = Grade::create(['level' => 10, 'name' => 'Grade 10']);
        $classA = SchoolClass::create([
            'academic_year_id' => $year->id,
            'grade_id' => $grade->id,
            'code' => '10A1',
            'name' => '10A1',
            'status' => 'active',
        ]);
        $classB = SchoolClass::create([
            'academic_year_id' => $year->id,
            'grade_id' => $grade->id,
            'code' => '10A2',
            'name' => '10A2',
            'status' => 'active',
        ]);
        $subject = Subject::create(['code' => 'MATH', 'name' => 'Mathematics', 'is_active' => true]);

        return [$year, $semester, $classA, $classB, $subject];
    }

    private function userWithPermission(string $slug): User
    {
        $permission = Permission::firstOrCreate(['slug' => $slug], ['name' => $slug]);
        $role = Role::create(['name' => $slug, 'slug' => str($slug)->replace('.', '-')->toString()]);
        $role->permissions()->attach($permission);
        $user = User::factory()->create();
        $user->roles()->attach($role);

        return $user;
    }
}

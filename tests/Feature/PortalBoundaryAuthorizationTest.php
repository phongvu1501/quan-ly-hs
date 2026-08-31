<?php

namespace Tests\Feature;

use App\Models\Menu;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PortalBoundaryAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_role_admin_permissions_do_not_open_back_office_urls(): void
    {
        $role = Role::firstOrCreate(['slug' => 'student'], ['name' => 'Student']);
        $role->permissions()->sync($this->permissionIds([
            'student.dashboard',
            'student-fees.view',
            'admin.access',
            'school.dashboard',
            'finance.dashboard',
            'students.view',
            'fees.view',
            'roles.view',
        ]));
        $user = User::factory()->create();
        $user->roles()->attach($role);
        Student::create([
            'user_id' => $user->id,
            'student_code' => 'BOUNDARY-HS',
            'full_name' => 'Boundary Student',
            'status' => Student::STATUS_STUDYING,
        ]);
        $menu = Menu::create([
            'name' => 'Hidden workspace',
            'slug' => 'hidden-workspace',
            'is_active' => true,
        ]);
        $role->menus()->attach($menu);

        $this->actingAs($user)->get(route('dashboard'))
            ->assertRedirect(route('student.dashboard'));
        $this->get(route('student.dashboard'))->assertOk();
        $this->get(route('admin.school.students.index'))->assertForbidden();
        $this->get(route('admin.finance.fees.index'))->assertForbidden();
        $this->get(route('admin.roles.index'))->assertForbidden();
        $this->get(route('dynamic.menus.show', $menu))->assertForbidden();
    }

    public function test_teacher_role_admin_permissions_do_not_open_back_office_urls(): void
    {
        $role = Role::firstOrCreate(['slug' => 'teacher'], ['name' => 'Teacher']);
        $role->permissions()->sync($this->permissionIds([
            'teacher.dashboard',
            'scores.view',
            'attendance.view',
            'fees.view',
            'admin.access',
            'school.dashboard',
            'finance.dashboard',
        ]));
        $user = User::factory()->create();
        $user->roles()->attach($role);
        Teacher::create([
            'user_id' => $user->id,
            'teacher_code' => 'BOUNDARY-GV',
            'full_name' => 'Boundary Teacher',
            'status' => Teacher::STATUS_ACTIVE,
        ]);

        $this->actingAs($user)->get(route('dashboard'))
            ->assertRedirect(route('teacher.subject.dashboard'));
        $this->get(route('teacher.subject.dashboard'))->assertOk();
        $this->get(route('admin.school.scores.index'))->assertForbidden();
        $this->get(route('admin.school.attendance.index'))->assertForbidden();
        $this->get(route('admin.finance.fees.index'))->assertForbidden();
    }

    public function test_separate_back_office_role_explicitly_unlocks_admin_urls(): void
    {
        $studentRole = Role::firstOrCreate(['slug' => 'student'], ['name' => 'Student']);
        $studentRole->permissions()->sync($this->permissionIds([
            'student.dashboard',
            'students.view',
        ]));
        $staffRole = Role::create(['slug' => 'school-staff', 'name' => 'School staff']);
        $staffRole->permissions()->sync($this->permissionIds(['school.dashboard']));
        $user = User::factory()->create();
        $user->roles()->attach([$studentRole->id, $staffRole->id]);
        Student::create([
            'user_id' => $user->id,
            'student_code' => 'BOUNDARY-STAFF-HS',
            'full_name' => 'Student Staff',
            'status' => Student::STATUS_STUDYING,
        ]);

        $this->actingAs($user)->get(route('dashboard'))
            ->assertRedirect(route('admin.school.dashboard'));
        $this->get(route('admin.school.students.index'))->assertOk();
    }

    /** @param list<string> $slugs @return list<int> */
    private function permissionIds(array $slugs): array
    {
        return collect($slugs)->map(
            fn (string $slug) => Permission::firstOrCreate(['slug' => $slug], ['name' => $slug])->id
        )->all();
    }
}

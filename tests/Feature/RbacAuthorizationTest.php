<?php

namespace Tests\Feature;

use App\Models\Menu;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Teacher;
use App\Models\AcademicYear;
use App\Models\Grade;
use App\Models\SchoolClass;
use App\Models\Semester;
use App\Models\Subject;
use App\Models\TeachingAssignment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RbacAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login_for_protected_admin_routes(): void
    {
        $this->get(route('admin.roles.index'))
            ->assertRedirect(route('login'))
            ->assertSessionHas('error', 'Vui lòng đăng nhập.');
    }

    public function test_authenticated_user_without_permission_is_forbidden(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('admin.roles.index'))
            ->assertForbidden();
    }

    public function test_dashboard_redirect_uses_the_exact_dashboard_permission(): void
    {
        $schoolDashboard = Permission::firstOrCreate(
            ['slug' => 'school.dashboard'],
            ['name' => 'School dashboard']
        );
        $financeDashboard = Permission::firstOrCreate(
            ['slug' => 'finance.dashboard'],
            ['name' => 'Finance dashboard']
        );

        $secretaryRole = Role::create(['name' => 'Thu ky', 'slug' => 'secretary-dashboard']);
        $secretaryRole->permissions()->attach($schoolDashboard);
        $secretary = User::factory()->create();
        $secretary->roles()->attach($secretaryRole);

        $this->actingAs($secretary)
            ->get(route('dashboard'))
            ->assertRedirect(route('admin.school.dashboard'));

        $financeRole = Role::create(['name' => 'Finance', 'slug' => 'finance-dashboard']);
        $financeRole->permissions()->attach($financeDashboard);
        $financeUser = User::factory()->create();
        $financeUser->roles()->attach($financeRole);

        $this->actingAs($financeUser)
            ->get(route('dashboard'))
            ->assertRedirect(route('admin.finance.dashboard'));
    }

    public function test_manage_permission_can_access_the_matching_view_route(): void
    {
        $permission = Permission::firstOrCreate([
            'slug' => 'roles.manage',
        ], [
            'name' => 'Manage roles',
        ]);
        $role = Role::create([
            'name' => 'Administrator',
            'slug' => 'administrator',
        ]);
        $role->permissions()->attach($permission);

        $user = User::factory()->create();
        $user->roles()->attach($role);

        $this->actingAs($user)
            ->get(route('admin.roles.index'))
            ->assertOk();
    }

    public function test_updating_a_role_with_no_permissions_revokes_old_permissions(): void
    {
        $editPermission = Permission::firstOrCreate(
            ['slug' => 'roles.edit'],
            ['name' => 'Edit roles']
        );
        $managePermission = Permission::firstOrCreate(
            ['slug' => 'roles.manage'],
            ['name' => 'Manage roles']
        );
        $oldPermission = Permission::create([
            'name' => 'Old permission',
            'slug' => 'reports.manage',
        ]);

        $actorRole = Role::create(['name' => 'Actor', 'slug' => 'actor']);
        $actorRole->permissions()->attach([$editPermission->id, $managePermission->id]);
        $actor = User::factory()->create();
        $actor->roles()->attach($actorRole);

        $targetRole = Role::create(['name' => 'Target', 'slug' => 'target']);
        $targetRole->permissions()->attach($oldPermission);

        $this->actingAs($actor)
            ->put(route('admin.roles.update', $targetRole), [
                'name' => $targetRole->name,
                'slug' => $targetRole->slug,
                'description' => '',
            ])
            ->assertRedirect(route('admin.roles.index'));

        $this->assertDatabaseMissing('role_permission', [
            'role_id' => $targetRole->id,
            'permission_id' => $oldPermission->id,
        ]);
    }

    public function test_role_edit_groups_every_permission_once_and_keeps_custom_permissions_visible(): void
    {
        $editPermission = Permission::firstOrCreate(
            ['slug' => 'roles.edit'],
            ['name' => 'Sửa vai trò']
        );
        $studentView = Permission::firstOrCreate(
            ['slug' => 'students.view'],
            ['name' => 'Xem học sinh']
        );
        $studentDashboard = Permission::firstOrCreate(
            ['slug' => 'student.dashboard'],
            ['name' => 'Truy cập cổng học sinh']
        );
        $feePermission = Permission::firstOrCreate(
            ['slug' => 'fees.generate'],
            ['name' => 'Phát sinh học phí']
        );
        $payrollPermission = Permission::firstOrCreate(
            ['slug' => 'payrolls.approve'],
            ['name' => 'Duyệt bảng lương']
        );
        $customPermission = Permission::create([
            'name' => 'Xuất báo cáo tùy chỉnh',
            'slug' => 'reports.export',
        ]);

        $actorRole = Role::create(['name' => 'Actor', 'slug' => 'actor']);
        $actorRole->permissions()->attach($editPermission);
        $actor = User::factory()->create();
        $actor->roles()->attach($actorRole);

        $targetRole = Role::create(['name' => 'Target', 'slug' => 'target']);
        $targetRole->permissions()->attach([$studentView->id, $feePermission->id]);

        $response = $this->actingAs($actor)->get(route('admin.roles.edit', $targetRole));

        $response->assertOk()
            ->assertSee('data-permission-group="students"', false)
            ->assertSee('data-permission-group="student-fees"', false)
            ->assertSee('data-permission-group="teacher-payroll"', false)
            ->assertSee('data-permission-group="other"', false)
            ->assertSee('Học sinh')
            ->assertSee('Học phí học sinh')
            ->assertSee('Lương giáo viên')
            ->assertSee('Quyền khác')
            ->assertSee('permission-group-toggle')
            ->assertSee('permission-select-all');

        $html = $response->getContent();
        foreach (Permission::pluck('id') as $permissionId) {
            preg_match_all(
                '/class="permission-item"[^>]*value="'.preg_quote((string) $permissionId, '/').'"/s',
                $html,
                $matches
            );
            $this->assertCount(1, $matches[0], "Permission {$permissionId} must appear exactly once.");
        }

        $this->assertStringContainsString((string) $studentDashboard->id, $html);
        $this->assertStringContainsString((string) $payrollPermission->id, $html);
        $this->assertStringContainsString((string) $customPermission->id, $html);
    }

    public function test_granular_permissions_remain_effective_after_manage_is_revoked(): void
    {
        $manage = Permission::firstOrCreate(['slug' => 'teachers.manage'], ['name' => 'Manage teachers']);
        $create = Permission::firstOrCreate(['slug' => 'teachers.create'], ['name' => 'Create teachers']);
        $edit = Permission::firstOrCreate(['slug' => 'teachers.edit'], ['name' => 'Edit teachers']);
        $role = Role::create(['name' => 'School admin', 'slug' => 'school-admin']);
        $role->permissions()->attach([$manage->id, $create->id, $edit->id]);
        $user = User::factory()->create();
        $user->roles()->attach($role);
        $teacher = Teacher::create([
            'full_name' => 'Test Teacher',
            'status' => 'active',
        ]);

        $this->actingAs($user)->get(route('admin.school.teachers.index'))->assertOk();

        $role->permissions()->detach($manage);

        $this->get(route('admin.school.teachers.index'))->assertForbidden();
        $this->get(route('admin.school.teachers.create'))->assertOk();
        $this->get(route('admin.school.teachers.edit', $teacher))->assertOk();
    }

    public function test_role_permission_sync_enforces_view_only_assignments_and_direct_urls_are_forbidden(): void
    {
        $view = Permission::firstOrCreate(['slug' => 'assignments.view'], ['name' => 'View assignments']);
        $create = Permission::firstOrCreate(['slug' => 'assignments.create'], ['name' => 'Create assignments']);
        $edit = Permission::firstOrCreate(['slug' => 'assignments.edit'], ['name' => 'Edit assignments']);
        $delete = Permission::firstOrCreate(['slug' => 'assignments.delete'], ['name' => 'Delete assignments']);
        $manage = Permission::firstOrCreate(['slug' => 'assignments.manage'], ['name' => 'Manage assignments']);

        $targetRole = Role::create(['name' => 'Thu ky', 'slug' => 'secretary']);
        $targetRole->permissions()->sync([$view->id, $create->id, $edit->id, $delete->id, $manage->id]);
        $targetUser = User::factory()->create();
        $targetUser->roles()->attach($targetRole);

        $roleEditorPermission = Permission::firstOrCreate(['slug' => 'roles.edit'], ['name' => 'Edit roles']);
        $roleEditor = Role::create(['name' => 'Role editor', 'slug' => 'role-editor']);
        $roleEditor->permissions()->attach($roleEditorPermission);
        $actor = User::factory()->create();
        $actor->roles()->attach($roleEditor);

        [$year, $semester, $class, $subject, $teacher] = $this->assignmentContext();
        $assignment = TeachingAssignment::create([
            'academic_year_id' => $year->id,
            'semester_id' => $semester->id,
            'class_id' => $class->id,
            'subject_id' => $subject->id,
            'teacher_id' => $teacher->id,
            'status' => 'active',
        ]);

        $this->actingAs($actor)->put(route('admin.roles.update', $targetRole), [
            'name' => $targetRole->name,
            'slug' => $targetRole->slug,
            'description' => '',
            'permissions' => [$view->id],
            'menus' => [],
        ])->assertRedirect(route('admin.roles.index'));

        $this->assertDatabaseHas('role_permission', ['role_id' => $targetRole->id, 'permission_id' => $view->id]);
        foreach ([$create, $edit, $delete, $manage] as $permission) {
            $this->assertDatabaseMissing('role_permission', ['role_id' => $targetRole->id, 'permission_id' => $permission->id]);
        }
        $this->assertTrue($targetUser->hasPermission('assignments.view'));
        $this->assertFalse($targetUser->hasPermission('assignments.create'));
        $this->assertFalse($targetUser->hasPermission('assignments.edit'));
        $this->assertFalse($targetUser->hasPermission('assignments.delete'));
        $this->assertFalse($targetUser->hasPermission('assignments.manage'));

        $response = $this->actingAs($targetUser)->get(route('admin.school.assignments.index'));
        $response->assertOk()
            ->assertDontSee('Lưu phân công')
            ->assertDontSee(route('admin.school.assignments.edit', $assignment))
            ->assertDontSee(route('admin.school.assignments.destroy', $assignment));

        $this->get(route('admin.school.assignments.edit', $assignment))->assertForbidden();
        $this->get(route('admin.school.assignments.create'))->assertForbidden();
        $this->post(route('admin.school.assignments.store'), [])->assertForbidden();
        $this->put(route('admin.school.assignments.update', $assignment), [])->assertForbidden();
        $this->delete(route('admin.school.assignments.destroy', $assignment))->assertForbidden();
    }

    public function test_role_create_renders_the_same_grouped_permission_matrix_as_edit(): void
    {
        $permission = Permission::firstOrCreate(['slug' => 'assignments.view'], ['name' => 'View assignments']);
        $createPermission = Permission::firstOrCreate(['slug' => 'roles.create'], ['name' => 'Create roles']);
        $role = Role::create(['name' => 'Role creator', 'slug' => 'role-creator']);
        $role->permissions()->attach($createPermission);
        $user = User::factory()->create();
        $user->roles()->attach($role);

        $response = $this->actingAs($user)->get(route('admin.roles.create'));
        $response->assertOk()
            ->assertSee('role-permission-matrix-create', false)
            ->assertSee('permission-select-all-create', false)
            ->assertSee('data-permission-group', false)
            ->assertSee('data-permission-slug="assignments.view"', false)
            ->assertSee('resource.manage', false)
            ->assertSee($permission->name);
    }

    public function test_permission_changes_are_visible_without_cache_clear(): void
    {
        $view = Permission::firstOrCreate(['slug' => 'assignments.view'], ['name' => 'View assignments']);
        $edit = Permission::firstOrCreate(['slug' => 'assignments.edit'], ['name' => 'Edit assignments']);
        $role = Role::create(['name' => 'Dynamic permissions', 'slug' => 'dynamic-permissions']);
        $user = User::factory()->create();
        $user->roles()->attach($role);

        $this->assertFalse($user->hasPermission('assignments.view'));
        $role->permissions()->sync([$view->id]);
        $this->assertTrue($user->hasPermission('assignments.view'));
        $this->assertFalse($user->hasPermission('assignments.edit'));
        $role->permissions()->sync([$edit->id]);
        $this->assertFalse($user->hasPermission('assignments.view'));
        $this->assertTrue($user->hasPermission('assignments.edit'));
    }

    public function test_permission_matrix_does_not_infer_manage_from_crud_permissions(): void
    {
        $actions = ['view', 'create', 'edit', 'delete', 'manage'];

        foreach ($actions as $grantedAction) {
            $permission = Permission::firstOrCreate(
                ['slug' => "students.{$grantedAction}"],
                ['name' => "Students {$grantedAction}"]
            );
            $role = Role::create([
                'name' => "Students {$grantedAction}",
                'slug' => "students-{$grantedAction}",
            ]);
            $role->permissions()->attach($permission);
            $user = User::factory()->create();
            $user->roles()->attach($role);

            foreach ($actions as $requiredAction) {
                $expected = $grantedAction === 'manage' || $grantedAction === $requiredAction;
                $this->assertSame(
                    $expected,
                    $user->hasPermission("students.{$requiredAction}"),
                    "students.{$grantedAction} checking students.{$requiredAction}"
                );
            }
        }
    }

    public function test_permission_without_dot_is_checked_exactly(): void
    {
        $permission = Permission::create(['name' => 'Legacy manage', 'slug' => 'legacy.manage']);
        $role = Role::create(['name' => 'Legacy', 'slug' => 'legacy']);
        $role->permissions()->attach($permission);
        $user = User::factory()->create();
        $user->roles()->attach($role);

        $this->assertFalse($user->hasPermission('legacy'));
        $this->assertTrue($user->hasPermission('legacy.manage'));
    }

    public function test_sidebar_shows_parent_when_only_a_child_menu_is_assigned(): void
    {
        $parent = Menu::create([
            'name' => 'Kho hàng',
            'slug' => 'warehouse',
            'icon' => 'warehouse',
            'is_active' => true,
        ]);
        $child = Menu::create([
            'parent_id' => $parent->id,
            'name' => 'Phiếu nhập',
            'slug' => 'stock-receipts',
            'is_active' => true,
        ]);
        $role = Role::create(['name' => 'Keeper', 'slug' => 'keeper']);
        $role->menus()->attach($child);
        $user = User::factory()->create();
        $user->roles()->attach($role);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Kho hàng')
            ->assertSee('Phiếu nhập');

        $role->menus()->detach($child);

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('Kho hàng')
            ->assertDontSee('Phiếu nhập');
    }

    public function test_menu_manage_permission_does_not_bypass_dynamic_menu_assignment(): void
    {
        $permission = Permission::firstOrCreate([
            'slug' => 'menus.manage',
        ], [
            'name' => 'Manage menus',
        ]);
        $role = Role::create(['name' => 'Menu manager', 'slug' => 'menu-manager']);
        $role->permissions()->attach($permission);
        $user = User::factory()->create();
        $user->roles()->attach($role);
        Menu::create([
            'name' => 'Báo cáo',
            'slug' => 'reports',
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('Báo cáo');

        $role->menus()->attach(Menu::where('slug', 'reports')->firstOrFail());

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Báo cáo');
    }

    public function test_dynamic_menu_uses_required_resource_action_permissions(): void
    {
        $view = Permission::firstOrCreate(['slug' => 'brands.view'], ['name' => 'View brands']);
        $create = Permission::firstOrCreate(['slug' => 'brands.create'], ['name' => 'Create brands']);
        $role = Role::create(['name' => 'Brand viewer', 'slug' => 'brand-viewer']);
        $role->permissions()->attach($view);
        $user = User::factory()->create();
        $user->roles()->attach($role);
        $menu = Menu::create([
            'name' => 'Thương hiệu',
            'slug' => 'brands',
            'required_permission' => 'brands.view',
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->get(route('dynamic.menus.show', $menu))
            ->assertOk();
        $this->get(route('dynamic.menus.records.create', $menu))->assertForbidden();

        $role->permissions()->attach($create);

        $this->get(route('dynamic.menus.records.create', $menu))->assertOk();
    }

    public function test_dynamic_menu_route_can_create_a_record_without_a_custom_controller(): void
    {
        $menu = Menu::create([
            'name' => 'Thương hiệu',
            'slug' => 'brands',
            'route' => 'a.route.that.does.not.exist',
            'is_active' => true,
        ]);
        $role = Role::create(['name' => 'Editor', 'slug' => 'editor']);
        $role->menus()->attach($menu);
        $user = User::factory()->create();
        $user->roles()->attach($role);

        $this->actingAs($user)
            ->get($menu->url())
            ->assertOk()
            ->assertSee('Thương hiệu');

        $this->post(route('dynamic.menus.records.store', ['menu' => $menu->slug]), [
            'title' => 'Local Brand',
            'code' => 'BR-001',
            'is_active' => '1',
        ])->assertRedirect(route('dynamic.menus.show', ['menu' => $menu->slug]));

        $this->assertDatabaseHas('dynamic_records', [
            'menu_id' => $menu->id,
            'title' => 'Local Brand',
            'code' => 'BR-001',
        ]);
    }

    /** @return array{AcademicYear, Semester, SchoolClass, Subject, Teacher} */
    private function assignmentContext(): array
    {
        $year = AcademicYear::create([
            'name' => '2026-2027', 'code' => '2026-2027',
            'starts_on' => '2026-08-01', 'ends_on' => '2027-05-31', 'is_current' => true,
        ]);
        $semester = Semester::create([
            'academic_year_id' => $year->id, 'number' => 1, 'name' => 'Semester 1',
            'starts_on' => '2026-08-01', 'ends_on' => '2026-12-31', 'is_current' => true,
        ]);
        $grade = Grade::create(['level' => 10, 'name' => 'Grade 10']);
        $class = SchoolClass::create([
            'academic_year_id' => $year->id, 'grade_id' => $grade->id,
            'code' => '10A1', 'name' => '10A1', 'status' => 'active',
        ]);
        $subject = Subject::create(['code' => 'MATH', 'name' => 'Mathematics', 'is_active' => true]);
        $teacher = Teacher::create(['full_name' => 'Assignment Teacher', 'status' => 'active']);

        return [$year, $semester, $class, $subject, $teacher];
    }
}

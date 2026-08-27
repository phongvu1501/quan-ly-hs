<?php

namespace Tests\Feature;

use App\Models\Menu;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RbacAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login_for_protected_admin_routes(): void
    {
        $this->get(route('admin.roles.index'))
            ->assertRedirect(route('login'));
    }

    public function test_authenticated_user_without_permission_is_forbidden(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('admin.roles.index'))
            ->assertForbidden();
    }

    public function test_user_with_permission_can_access_the_matching_admin_resource(): void
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

    public function test_revoking_manage_permission_blocks_direct_feature_urls_immediately(): void
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
        $this->get(route('admin.school.teachers.create'))->assertForbidden();
        $this->get(route('admin.school.teachers.edit', $teacher))->assertForbidden();
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

    public function test_menu_manager_sees_a_new_active_parent_without_manual_role_assignment(): void
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
            ->assertSee('Báo cáo');
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
}

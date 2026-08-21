<?php

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $permissions = [
            'admin.access' => 'Truy cập khu vực quản trị',
            'users.manage' => 'Xem danh sách tài khoản',
            'users.create' => 'Thêm tài khoản',
            'users.edit' => 'Cập nhật tài khoản và vai trò',
            'users.delete' => 'Xoá tài khoản',
            'roles.manage' => 'Xem danh sách vai trò',
            'roles.create' => 'Thêm vai trò',
            'roles.edit' => 'Cập nhật vai trò',
            'roles.delete' => 'Xoá vai trò',
            'permissions.manage' => 'Xem danh sách quyền',
            'permissions.create' => 'Thêm quyền',
            'permissions.edit' => 'Cập nhật quyền',
            'permissions.delete' => 'Xoá quyền',
            'menus.manage' => 'Xem cấu hình menu',
            'menus.create' => 'Thêm menu',
            'menus.edit' => 'Cập nhật menu',
            'menus.delete' => 'Xoá menu',
        ];

        $ids = collect($permissions)->map(function (string $name, string $slug) {
            return Permission::firstOrCreate(
                ['slug' => $slug],
                ['name' => $name, 'description' => $name]
            )->id;
        });

        Role::where('slug', 'admin')->each(
            fn (Role $role) => $role->permissions()->syncWithoutDetaching($ids)
        );
    }

    public function down(): void
    {
        // RBAC data may have been reassigned after this migration. Do not
        // remove live permissions during rollback.
    }
};

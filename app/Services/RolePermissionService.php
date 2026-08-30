<?php

namespace App\Services;

use App\Models\Menu;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class RolePermissionService
{
    /** @param array<string, mixed> $attributes @param array<int, mixed> $permissionIds @param array<int, mixed> $menuIds */
    public function create(array $attributes, array $permissionIds, array $menuIds): Role
    {
        return DB::transaction(function () use ($attributes, $permissionIds, $menuIds): Role {
            $role = Role::create(Arr::only($attributes, ['name', 'slug', 'description']));
            $this->sync($role, $permissionIds, $menuIds);

            return $role->fresh(['permissions', 'menus']);
        });
    }

    /** @param array<string, mixed> $attributes @param array<int, mixed> $permissionIds @param array<int, mixed> $menuIds */
    public function update(Role $role, array $attributes, array $permissionIds, array $menuIds): Role
    {
        return DB::transaction(function () use ($role, $attributes, $permissionIds, $menuIds): Role {
            $locked = Role::query()->lockForUpdate()->findOrFail($role->id);
            $locked->update(Arr::only($attributes, ['name', 'slug', 'description']));
            $this->sync($locked, $permissionIds, $menuIds);

            return $locked->fresh(['permissions', 'menus']);
        });
    }

    /** @param array<int, mixed> $permissionIds @param array<int, mixed> $menuIds */
    private function sync(Role $role, array $permissionIds, array $menuIds): void
    {
        $permissionIds = $this->normalizeIds($permissionIds);
        $menuIds = $this->normalizeIds($menuIds);

        $existingPermissionIds = Permission::query()->whereIn('id', $permissionIds)->pluck('id')->map(fn ($id) => (int) $id)->all();
        if (count($existingPermissionIds) !== count($permissionIds)) {
            throw ValidationException::withMessages(['permissions' => 'Danh sách permission không hợp lệ.']);
        }
        $existingMenuIds = Menu::query()->whereIn('id', $menuIds)->pluck('id')->map(fn ($id) => (int) $id)->all();
        if (count($existingMenuIds) !== count($menuIds)) {
            throw ValidationException::withMessages(['menus' => 'Danh sách menu không hợp lệ.']);
        }

        // sync() is intentionally exact: omitted checkboxes revoke mappings.
        $role->permissions()->sync($permissionIds);
        $role->menus()->sync($menuIds);
    }

    /** @param array<int, mixed> $ids @return list<int> */
    private function normalizeIds(array $ids): array
    {
        return collect($ids)
            ->filter(fn (mixed $id) => filter_var($id, FILTER_VALIDATE_INT) !== false)
            ->map(fn (mixed $id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }
}

<?php

namespace App\Services;

use App\Models\Menu;
use App\Models\User;
use Illuminate\Support\Collection;

class MenuService
{
    public function forUser(User $user): Collection
    {
        $roles = $user->roles()->with('permissions:id,slug')->get();
        $roleIds = $roles->pluck('id');
        $permissionSlugs = $roles
            ->flatMap(fn ($role) => $role->permissions->pluck('slug'))
            ->unique()
            ->values();
        $canManageMenus = $permissionSlugs->contains('menus.manage');

        $menus = Menu::query()
            ->where('is_active', true)
            ->with('roles:id,slug')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $visibleIds = $menus
            ->filter(
                fn (Menu $menu) => $canManageMenus
                    || $menu->isVisibleTo($roleIds, $permissionSlugs)
            )
            ->pluck('id');

        // Retain every ancestor so a permitted child always has a container.
        do {
            $count = $visibleIds->count();
            $parentIds = $menus
                ->whereIn('id', $visibleIds)
                ->pluck('parent_id')
                ->filter();
            $visibleIds = $visibleIds->merge($parentIds)->unique()->values();
        } while ($visibleIds->count() !== $count);

        return $this->tree($menus->whereIn('id', $visibleIds), null);
    }

    private function tree(Collection $menus, ?int $parentId): Collection
    {
        return $menus
            ->where('parent_id', $parentId)
            ->sortBy(fn (Menu $menu) => [$menu->sort_order, $menu->id])
            ->values()
            ->each(function (Menu $menu) use ($menus): void {
                $menu->setRelation('visibleChildren', $this->tree($menus, $menu->id));
            });
    }
}

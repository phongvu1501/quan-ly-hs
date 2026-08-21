<?php

namespace App\Http\Controllers;

use App\Models\DynamicRecord;
use App\Models\Menu;
use App\Models\Permission;
use Illuminate\Http\Request;

class DynamicMenuController extends Controller
{
    public function show(Menu $menu)
    {
        $this->authorizeMenu($menu);

        $records = $menu->records()
            ->latest()
            ->paginate(12);

        return view('dynamic.menu', compact('menu', 'records'));
    }

    public function store(Request $request, Menu $menu)
    {
        $this->authorizeMenu($menu, 'create');

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:5000'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $menu->records()->create([
            ...$validated,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return to_route('dynamic.menus.show', ['menu' => $menu->slug])
            ->with('success', 'Đã thêm bản ghi.');
    }

    public function create(Menu $menu)
    {
        $this->authorizeMenu($menu, 'create');

        return view('dynamic.record-form', [
            'menu' => $menu,
            'record' => null,
        ]);
    }

    public function edit(Menu $menu, DynamicRecord $record)
    {
        $this->ensureRecordBelongsToMenu($menu, $record);
        $this->authorizeMenu($menu, 'edit');

        return view('dynamic.record-form', compact('menu', 'record'));
    }

    public function update(Request $request, Menu $menu, DynamicRecord $record)
    {
        $this->ensureRecordBelongsToMenu($menu, $record);
        $this->authorizeMenu($menu, 'edit');

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:5000'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $record->update([
            ...$validated,
            'is_active' => $request->boolean('is_active'),
        ]);

        return to_route('dynamic.menus.show', ['menu' => $menu->slug])
            ->with('success', 'Đã cập nhật bản ghi.');
    }

    public function destroy(Menu $menu, DynamicRecord $record)
    {
        $this->ensureRecordBelongsToMenu($menu, $record);
        $this->authorizeMenu($menu, 'delete');
        $record->delete();

        return back()->with('success', 'Đã xoá bản ghi.');
    }

    private function ensureRecordBelongsToMenu(Menu $menu, DynamicRecord $record): void
    {
        abort_unless($record->menu_id === $menu->id, 404);
    }

    private function authorizeMenu(Menu $menu, string $action = 'view'): void
    {
        $user = request()->user();
        $roleIds = $user->roles()->pluck('roles.id');
        $permissionSlugs = $user->permissionSlugs();
        $menu->loadMissing('roles:id');
        $canManageMenus = $permissionSlugs->contains('menus.manage');

        abort_unless(
            $menu->is_active
                && ($canManageMenus || $menu->isVisibleTo($roleIds, $permissionSlugs)),
            403
        );

        // Optional conventional permissions make CRUD stricter when they
        // exist; otherwise the role-menu assignment is the access grant.
        if ($action !== 'view') {
            $permission = "{$menu->slug}.{$action}";
            if (Permission::where('slug', $permission)->exists()) {
                abort_unless($user->hasPermission($permission), 403);
            }
        }
    }
}

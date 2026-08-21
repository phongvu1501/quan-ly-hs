<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Menu;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->input('q', ''));
        $roles = Role::withCount([
            'users',
            'permissions',
            'menus',
        ])->when($search !== '', fn ($query) => $query->where(function ($query) use ($search): void {
            $query->where('name', 'like', "%{$search}%")
                ->orWhere('slug', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%");
        }))->orderBy('id', 'asc')->paginate(10)->withQueryString();

        return view('admin.roles.index', compact('roles'));
    }

    public function create()
    {
        $permissions = Permission::orderBy('name')->get();
        $menus = Menu::with('parent')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('admin.roles.create', compact('permissions', 'menus'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:roles,slug',
            'description' => 'nullable|string',
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,id',
            'menus' => 'nullable|array',
            'menus.*' => 'exists:menus,id',
        ]);

        $role = Role::create([
            'name' => $validated['name'],
            'slug' => $validated['slug'],
            'description' => $validated['description'] ?? null,
        ]);

        $role->permissions()->sync(
            $validated['permissions'] ?? []
        );
        $role->menus()->sync($validated['menus'] ?? []);

        return redirect()
            ->route('admin.roles.index')
            ->with('success', 'Tạo role thành công.');
    }

    public function edit(Role $role)
    {
        $permissions = Permission::orderBy('name')->get();
        $menus = Menu::with('parent')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $role->load(['permissions', 'menus']);

        return view('admin.roles.edit', compact(
            'role',
            'permissions',
            'menus'
        ));
    }

    public function update(Request $request, Role $role)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:roles,slug,'.$role->id,
            'description' => 'nullable|string',
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,id',
            'menus' => 'nullable|array',
            'menus.*' => 'exists:menus,id',
        ]);

        $role->update([
            'name' => $validated['name'],
            'slug' => $validated['slug'],
            'description' => $validated['description'] ?? null,
        ]);

        $role->permissions()->sync(
            $validated['permissions'] ?? []
        );
        $role->menus()->sync($validated['menus'] ?? []);

        return redirect()
            ->route('admin.roles.index')
            ->with('success', 'Cập nhật role thành công.');
    }

    public function destroy(Role $role)
    {
        if ($role->users()->exists()) {
            return back()->with(
                'error',
                'Không thể xóa role đang được sử dụng.'
            );
        }

        $role->permissions()->detach();
        $role->menus()->detach();
        $role->delete();

        return back()->with(
            'success',
            'Xóa role thành công.'
        );
    }
}

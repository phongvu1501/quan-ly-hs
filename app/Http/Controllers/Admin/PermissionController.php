<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Http\Request;

class PermissionController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->input('q', ''));
        $group = trim((string) $request->input('group', ''));
        $action = trim((string) $request->input('action', ''));
        $roleId = $request->filled('role_id') ? (int) $request->input('role_id') : null;
        $assignment = trim((string) $request->input('assignment', ''));
        $permissions = Permission::withCount('roles')
            ->when($search !== '', fn($query) => $query->where(function ($query) use ($search): void {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            }))
            ->when($group !== '', fn($query) => $query->where(function ($query) use ($group): void {
                $query->where('slug', $group)->orWhere('slug', 'like', "{$group}.%");
            }))
            ->when($action !== '', fn($query) => $query->where('slug', 'like', "%.{$action}"))
            ->when($roleId, fn($query) => $query->whereHas('roles', fn($role) => $role->whereKey($roleId)))
            ->when($assignment === 'assigned', fn($query) => $query->whereHas('roles'))
            ->when($assignment === 'unassigned', fn($query) => $query->whereDoesntHave('roles'))
            ->orderBy('id', 'asc')
            ->paginate(10)
            ->withQueryString();

        $allSlugs = Permission::orderBy('slug')->pluck('slug');

        return view('admin.permissions.index', [
            'permissions' => $permissions,
            'roles' => Role::orderBy('name')->get(),
            'groups' => $allSlugs->map(fn(string $slug) => explode('.', $slug, 2)[0])->unique()->values(),
            'actions' => $allSlugs->filter(fn(string $slug) => str_contains($slug, '.'))
                ->map(fn(string $slug) => str($slug)->afterLast('.')->toString())
                ->unique()
                ->sort()
                ->values(),
        ]);
    }

    public function create()
    {
        return view('admin.permissions.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:permissions,slug',
            'description' => 'nullable|string',
        ]);

        Permission::create($validated);

        return redirect()
            ->route('admin.permissions.index')
            ->with('success', 'Tạo permission thành công.');
    }

    public function edit(Permission $permission)
    {
        return view(
            'admin.permissions.edit',
            compact('permission')
        );
    }

    public function update(
        Request $request,
        Permission $permission
    ) {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:permissions,slug,' . $permission->id,
            'description' => 'nullable|string',
        ]);

        $permission->update($validated);

        return redirect()
            ->route('admin.permissions.index')
            ->with('success', 'Cập nhật permission thành công.');
    }

    public function destroy(Permission $permission)
    {
        $permission->roles()->detach();

        $permission->delete();

        return back()->with(
            'success',
            'Xóa permission thành công.'
        );
    }
}

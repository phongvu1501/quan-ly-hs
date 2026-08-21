<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use Illuminate\Http\Request;

class PermissionController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->input('q', ''));
        $permissions = Permission::withCount('roles')
            ->when($search !== '', fn ($query) => $query->where(function ($query) use ($search): void {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            }))
            ->orderBy('id', 'asc')
            ->paginate(15)
            ->withQueryString();

        return view(
            'admin.permissions.index',
            compact('permissions')
        );
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

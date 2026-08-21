<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Menu;
use App\Models\Permission;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MenuController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->input('q', ''));
        $menus = Menu::with([
            'parent',
            'roles',
        ])
            ->orderBy('sort_order')
            ->orderBy('id')
            ->when($search !== '', fn ($query) => $query->where(function ($query) use ($search): void {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%")
                    ->orWhere('route', 'like', "%{$search}%")
                    ->orWhere('required_permission', 'like', "%{$search}%");
            }))
            ->paginate(10)
            ->withQueryString();

        return view(
            'admin.menus.index',
            compact('menus')
        );
    }

    public function create()
    {
        $parents = Menu::whereNull('parent_id')
            ->orderBy('sort_order')
            ->get();

        $permissions = Permission::orderBy('slug')->get();

        return view(
            'admin.menus.create',
            compact('parents', 'permissions')
        );
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'parent_id' => 'nullable|exists:menus,id',
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:menus,slug',
            'route' => 'nullable|string|max:255',
            'icon' => 'nullable|string|max:255',
            'required_permission' => 'nullable|string|max:255|exists:permissions,slug',
            'sort_order' => 'nullable|integer',
            'is_active' => 'nullable|boolean',
        ]);

        Menu::create([
            'parent_id' => $validated['parent_id'] ?? null,
            'name' => $validated['name'],
            'slug' => $validated['slug'],
            'route' => $validated['route'] ?? null,
            'icon' => $validated['icon'] ?? null,
            'required_permission' => $validated['required_permission'] ?? null,
            'sort_order' => $validated['sort_order'] ?? 0,
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()
            ->route('admin.menus.index')
            ->with('success', 'Tạo menu thành công.');
    }

    public function edit(Menu $menu)
    {
        $parents = Menu::whereNull('parent_id')
            ->where('id', '!=', $menu->id)
            ->orderBy('sort_order')
            ->get();

        $permissions = Permission::orderBy('slug')->get();

        return view(
            'admin.menus.edit',
            compact('menu', 'parents', 'permissions')
        );
    }

    public function update(Request $request, Menu $menu)
    {
        $validated = $request->validate([
            'parent_id' => [
                'nullable',
                'exists:menus,id',
                Rule::notIn([$menu->id, ...$this->descendantIds($menu)]),
            ],
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:menus,slug,'.$menu->id,
            'route' => 'nullable|string|max:255',
            'icon' => 'nullable|string|max:255',
            'required_permission' => 'nullable|string|max:255|exists:permissions,slug',
            'sort_order' => 'nullable|integer',
            'is_active' => 'nullable|boolean',
        ]);

        $menu->update([
            'parent_id' => $validated['parent_id'] ?? null,
            'name' => $validated['name'],
            'slug' => $validated['slug'],
            'route' => $validated['route'] ?? null,
            'icon' => $validated['icon'] ?? null,
            'required_permission' => $validated['required_permission'] ?? null,
            'sort_order' => $validated['sort_order'] ?? 0,
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()
            ->route('admin.menus.index')
            ->with('success', 'Cập nhật menu thành công.');
    }

    public function destroy(Menu $menu)
    {
        if ($menu->children()->exists()) {
            return back()->with(
                'error',
                'Không thể xóa menu đang có menu con.'
            );
        }

        $menu->roles()->detach();

        $menu->delete();

        return back()->with(
            'success',
            'Xóa menu thành công.'
        );
    }

    private function descendantIds(Menu $menu): array
    {
        $ids = [];
        $pending = [$menu->id];

        while ($pending !== []) {
            $children = Menu::whereIn('parent_id', $pending)->pluck('id')->all();
            $ids = [...$ids, ...$children];
            $pending = $children;
        }

        return $ids;
    }
}

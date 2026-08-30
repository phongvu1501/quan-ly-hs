<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Menu;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use App\Services\RolePermissionService;

class RoleController extends Controller
{
    /** @var list<array{key: string, label: string, icon: string, slugs?: list<string>, prefixes?: list<string>}> */
    private const PERMISSION_GROUPS = [
        [
            'key' => 'system',
            'label' => 'Quản trị hệ thống',
            'icon' => 'shield-check',
            'slugs' => ['admin.access'],
            'prefixes' => ['roles.', 'users.', 'permissions.', 'menus.'],
        ],
        [
            'key' => 'school-overview',
            'label' => 'Tổng quan trường học',
            'icon' => 'layout-dashboard',
            'slugs' => ['school.dashboard'],
        ],
        [
            'key' => 'students',
            'label' => 'Học sinh',
            'icon' => 'graduation-cap',
            'slugs' => ['student.dashboard'],
            'prefixes' => ['students.'],
        ],
        [
            'key' => 'teachers',
            'label' => 'Giáo viên',
            'icon' => 'presentation',
            'slugs' => ['teacher.dashboard', 'homeroom.dashboard'],
            'prefixes' => ['teachers.'],
        ],
        [
            'key' => 'grades',
            'label' => 'Khối lớp',
            'icon' => 'layers-3',
            'prefixes' => ['grades.'],
        ],
        [
            'key' => 'classes',
            'label' => 'Lớp học',
            'icon' => 'school',
            'prefixes' => ['classes.'],
        ],
        [
            'key' => 'subjects',
            'label' => 'Môn học',
            'icon' => 'book-open',
            'prefixes' => ['subjects.'],
        ],
        [
            'key' => 'assignments',
            'label' => 'Phân công giảng dạy',
            'icon' => 'network',
            'prefixes' => ['assignments.'],
        ],
        [
            'key' => 'scores',
            'label' => 'Điểm học sinh',
            'icon' => 'notebook-pen',
            'prefixes' => ['scores.'],
        ],
        [
            'key' => 'attendance',
            'label' => 'Điểm danh',
            'icon' => 'clipboard-check',
            'prefixes' => ['attendance.'],
        ],
        [
            'key' => 'schedules',
            'label' => 'Thời khóa biểu',
            'icon' => 'calendar-days',
            'prefixes' => ['schedules.'],
        ],
        [
            'key' => 'exams',
            'label' => 'Kỳ thi',
            'icon' => 'file-text',
            'prefixes' => ['exams.'],
        ],
        [
            'key' => 'exam-invigilations',
            'label' => 'Phân công coi thi',
            'icon' => 'users-round',
            'prefixes' => ['exam-invigilations.'],
        ],
        [
            'key' => 'announcements',
            'label' => 'Thông báo',
            'icon' => 'megaphone',
            'prefixes' => ['announcements.'],
        ],
        [
            'key' => 'finance-overview',
            'label' => 'Tổng quan tài chính',
            'icon' => 'landmark',
            'slugs' => ['finance.dashboard'],
        ],
        [
            'key' => 'student-fees',
            'label' => 'Học phí học sinh',
            'icon' => 'receipt-text',
            'slugs' => ['student-fees.view'],
            'prefixes' => [
                'fees.', 'fee-categories.', 'fee-rates.', 'fee-policies.',
                'student-fee-policies.',
            ],
        ],
        [
            'key' => 'teacher-payroll',
            'label' => 'Lương giáo viên',
            'icon' => 'badge-dollar-sign',
            'prefixes' => [
                'salary-components.', 'salary-configs.', 'payroll-inputs.',
                'teaching-extra.', 'tax-configs.', 'insurance-configs.', 'payrolls.',
            ],
        ],
    ];

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
        $permissionGroups = $this->groupPermissions($permissions);
        $menus = Menu::with('parent')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('admin.roles.create', compact('permissionGroups', 'menus'));
    }

    public function store(Request $request, RolePermissionService $rolePermissions)
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

        $rolePermissions->create($validated, $validated['permissions'] ?? [], $validated['menus'] ?? []);

        return redirect()
            ->route('admin.roles.index')
            ->with('success', 'Tạo role thành công.');
    }

    public function edit(Role $role)
    {
        $permissions = Permission::orderBy('name')->get();
        $permissionGroups = $this->groupPermissions($permissions);
        $menus = Menu::with('parent')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $role->load(['permissions', 'menus']);

        return view('admin.roles.edit', compact(
            'role',
            'permissionGroups',
            'menus'
        ));
    }

    public function update(Request $request, Role $role, RolePermissionService $rolePermissions)
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

        $rolePermissions->update($role, $validated, $validated['permissions'] ?? [], $validated['menus'] ?? []);

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

    /**
     * @param  Collection<int, Permission>  $permissions
     * @return list<array{key: string, label: string, icon: string, permissions: Collection<int, Permission>}>
     */
    private function groupPermissions(Collection $permissions): array
    {
        $remaining = $permissions->keyBy('id');
        $groups = [];

        foreach (self::PERMISSION_GROUPS as $definition) {
            $groupPermissions = $remaining
                ->filter(fn (Permission $permission): bool => $this->permissionMatchesGroup($permission, $definition))
                ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
                ->values();

            if ($groupPermissions->isEmpty()) {
                continue;
            }

            $remaining->forget($groupPermissions->pluck('id')->all());
            $groups[] = [
                'key' => $definition['key'],
                'label' => $definition['label'],
                'icon' => $definition['icon'],
                'permissions' => $groupPermissions,
            ];
        }

        if ($remaining->isNotEmpty()) {
            $groups[] = [
                'key' => 'other',
                'label' => 'Quyền khác',
                'icon' => 'circle-ellipsis',
                'permissions' => $remaining->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)->values(),
            ];
        }

        return $groups;
    }

    /** @param array{slugs?: list<string>, prefixes?: list<string>} $definition */
    private function permissionMatchesGroup(Permission $permission, array $definition): bool
    {
        if (in_array($permission->slug, $definition['slugs'] ?? [], true)) {
            return true;
        }

        return collect($definition['prefixes'] ?? [])
            ->contains(fn (string $prefix): bool => Str::startsWith($permission->slug, $prefix));
    }
}

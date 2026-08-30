<?php

namespace Database\Seeders;

use App\Models\Menu;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PermissionSeeder extends Seeder
{
    /** @var array<string, array{0: string, 1: string, 2?: string}> */
    private const RESOURCES = [
        'roles' => ['vai trò', 'vai trò'],
        'users' => ['người dùng', 'tài khoản người dùng'],
        'permissions' => ['quyền', 'quyền truy cập'],
        'menus' => ['menu', 'menu hệ thống'],
        'students' => ['học sinh', 'học sinh'],
        'teachers' => ['giáo viên', 'giáo viên'],
        'grades' => ['khối lớp', 'khối lớp'],
        'subjects' => ['môn học', 'môn học'],
        'classes' => ['lớp học', 'lớp học'],
        'assignments' => ['phân công giảng dạy', 'phân công giảng dạy'],
        'scores' => ['điểm', 'điểm học sinh', 'Nhập điểm'],
        'attendance' => ['điểm danh', 'dữ liệu điểm danh', 'Điểm danh học sinh'],
        'schedules' => ['thời khóa biểu', 'thời khóa biểu'],
        'exams' => ['kỳ thi', 'kỳ thi'],
        'exam-invigilations' => ['phân công coi thi', 'phân công coi thi'],
        'announcements' => ['thông báo', 'thông báo'],
        'fees' => ['học phí', 'khoản thu học phí'],
        'fee-categories' => ['danh mục khoản thu', 'danh mục khoản thu'],
        'fee-rates' => ['mức thu', 'mức thu học phí'],
        'fee-policies' => ['chính sách miễn giảm', 'chính sách miễn giảm'],
        'student-fee-policies' => ['diện miễn giảm học sinh', 'diện miễn giảm học sinh'],
        'salary-components' => ['thành phần lương', 'thành phần lương'],
        'salary-configs' => ['cấu hình lương', 'cấu hình lương giáo viên'],
        'payroll-inputs' => ['đầu vào bảng lương', 'dữ liệu đầu vào bảng lương'],
        'teaching-extra' => ['dạy thêm', 'bản ghi dạy thêm'],
        'tax-configs' => ['cấu hình thuế', 'cấu hình thuế'],
        'insurance-configs' => ['cấu hình bảo hiểm', 'cấu hình bảo hiểm'],
        'payrolls' => ['bảng lương', 'bảng lương giáo viên'],
    ];

    /** @var array<string, array{name: string, description: string}> */
    private const STANDALONE_PERMISSIONS = [
        'admin.access' => [
            'name' => 'Truy cập trang quản trị',
            'description' => 'Cho phép truy cập khu vực quản trị của hệ thống.',
        ],
        'school.dashboard' => [
            'name' => 'Truy cập bảng điều khiển trường học',
            'description' => 'Cho phép xem bảng điều khiển và thông tin tổng quan của trường học.',
        ],
        'teacher.dashboard' => [
            'name' => 'Truy cập cổng giáo viên',
            'description' => 'Cho phép truy cập bảng điều khiển dành cho giáo viên.',
        ],
        'homeroom.dashboard' => [
            'name' => 'Truy cập cổng giáo viên chủ nhiệm',
            'description' => 'Cho phép truy cập bảng điều khiển dành cho giáo viên chủ nhiệm.',
        ],
        'student.dashboard' => [
            'name' => 'Truy cập cổng học sinh',
            'description' => 'Cho phép truy cập bảng điều khiển dành cho học sinh.',
        ],
        'finance.dashboard' => [
            'name' => 'Xem tổng quan tài chính',
            'description' => 'Cho phép xem dashboard học phí và bảng lương.',
        ],
        'student-fees.view' => [
            'name' => 'Xem học phí cá nhân',
            'description' => 'Cho phép học sinh xem dữ liệu học phí của chính mình.',
        ],
        'fees.generate' => ['name' => 'Phát sinh học phí', 'description' => 'Cho phép generate khoản phải thu học sinh.'],
        'fees.pay' => ['name' => 'Thu học phí', 'description' => 'Cho phép ghi nhận thanh toán học phí.'],
        'fees.cancel' => ['name' => 'Hủy giao dịch học phí', 'description' => 'Cho phép hủy khoản phải thu hoặc thanh toán.'],
        'fees.report' => ['name' => 'Xem báo cáo học phí', 'description' => 'Cho phép xem báo cáo học phí.'],
        'teaching-extra.approve' => ['name' => 'Duyệt dạy thêm', 'description' => 'Cho phép duyệt số tiết dạy thêm.'],
        'payrolls.calculate' => ['name' => 'Tính bảng lương', 'description' => 'Cho phép generate và tính bảng lương.'],
        'payrolls.approve' => ['name' => 'Duyệt bảng lương', 'description' => 'Cho phép duyệt bảng lương đã tính.'],
        'payrolls.lock' => ['name' => 'Khóa bảng lương', 'description' => 'Cho phép chốt và khóa bảng lương.'],
        'payrolls.pay' => ['name' => 'Thanh toán lương', 'description' => 'Cho phép ghi nhận thanh toán lương.'],
        'payrolls.cancel' => ['name' => 'Hủy bảng lương', 'description' => 'Cho phép hủy bảng lương hoặc giao dịch lương.'],
        'payrolls.adjust' => ['name' => 'Điều chỉnh bảng lương', 'description' => 'Cho phép tạo adjustment cho bảng lương đã khóa.'],
        'payrolls.report' => ['name' => 'Xem báo cáo lương', 'description' => 'Cho phép xem báo cáo bảng lương.'],
    ];

    public function run(): void
    {
        DB::transaction(function (): void {
            $permissionIds = collect($this->definitions())
                ->map(function (array $attributes, string $slug): int {
                    return Permission::updateOrCreate(
                        ['slug' => $slug],
                        $attributes
                    )->id;
                });

            $this->mergeLegacyPermission('create.permission', 'permissions.create');
            $this->mergeLegacyPermission('subject_teacher.dashboard', 'teacher.dashboard');

            Role::where('slug', 'admin')->each(
                fn (Role $role) => $role->permissions()->syncWithoutDetaching($permissionIds->values())
            );

            $teacherPermissions = Permission::whereIn('slug', [
                'teacher.dashboard',
                'scores.view',
                'scores.create',
                'scores.edit',
                'attendance.view',
                'attendance.create',
                'attendance.edit',
                'announcements.view',
            ])->pluck('id');

            Role::whereIn('slug', ['teacher', 'homeroom_teacher', 'subject_teacher'])
                ->each(function (Role $role) use ($teacherPermissions): void {
                    $role->permissions()->syncWithoutDetaching($teacherPermissions);
                    $role->permissions()->detach(
                        Permission::whereIn('slug', ['scores.manage', 'attendance.manage'])->pluck('id')
                    );
                });

            Role::where('slug', 'homeroom_teacher')->each(function (Role $role): void {
                $role->permissions()->syncWithoutDetaching(
                    Permission::where('slug', 'homeroom.dashboard')->pluck('id')
                );
            });

            Role::where('slug', 'student')->each(function (Role $role): void {
                $role->permissions()->syncWithoutDetaching(
                    Permission::whereIn('slug', [
                        'student.dashboard',
                        'scores.view',
                        'announcements.view',
                        'student-fees.view',
                    ])->pluck('id')
                );
                $role->permissions()->detach(
                    Permission::where('slug', 'fees.view')->pluck('id')
                );
            });
        });
    }

    /** @return array<string, array{name: string, description: string}> */
    public function definitions(): array
    {
        $definitions = self::STANDALONE_PERMISSIONS;

        foreach (self::RESOURCES as $resource => $resourceData) {
            [$label, $descriptionObject] = $resourceData;
            $createName = $resourceData[2] ?? null;
            $definitions["{$resource}.view"] = [
                'name' => "Xem {$label}",
                'description' => "Cho phép xem danh sách và thông tin {$descriptionObject}.",
            ];
            $definitions["{$resource}.create"] = [
                'name' => $createName ?? "Thêm {$label}",
                'description' => "Cho phép tạo và thêm {$descriptionObject} mới vào hệ thống.",
            ];
            $definitions["{$resource}.edit"] = [
                'name' => "Sửa {$label}",
                'description' => "Cho phép chỉnh sửa và cập nhật {$descriptionObject}.",
            ];
            $definitions["{$resource}.delete"] = [
                'name' => "Xóa {$label}",
                'description' => "Cho phép xóa {$descriptionObject} khỏi hệ thống.",
            ];
            $definitions["{$resource}.manage"] = [
                'name' => "Quản lý {$label}",
                'description' => "Cho phép toàn quyền xem, tạo, sửa và xóa {$descriptionObject}.",
            ];
        }

        return $definitions;
    }

    private function mergeLegacyPermission(string $legacySlug, string $targetSlug): void
    {
        $legacy = Permission::where('slug', $legacySlug)->first();
        $target = Permission::where('slug', $targetSlug)->firstOrFail();

        if (! $legacy || $legacy->is($target)) {
            return;
        }

        $roleMappings = DB::table('role_permission')
            ->where('permission_id', $legacy->id)
            ->pluck('role_id')
            ->map(fn (int $roleId): array => [
                'role_id' => $roleId,
                'permission_id' => $target->id,
            ])
            ->all();

        if ($roleMappings !== []) {
            DB::table('role_permission')->insertOrIgnore($roleMappings);
        }

        Menu::where('required_permission', $legacySlug)
            ->update(['required_permission' => $targetSlug]);

        $legacy->delete();
    }
}

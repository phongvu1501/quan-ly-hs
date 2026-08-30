<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\FeeAssessment;
use App\Models\FeeCategory;
use App\Models\FeeNotice;
use App\Models\Payroll;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinanceAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthorized_user_cannot_view_create_pay_approve_or_lock(): void
    {
        $user = User::factory()->create();
        [$assessment, $payroll] = $this->financeRecords();

        $this->actingAs($user)->get(route('admin.finance.dashboard'))->assertForbidden();
        $this->actingAs($user)->get(route('admin.finance.fees.index'))->assertForbidden();
        $this->get(route('admin.finance.fee-categories.create'))->assertForbidden();
        $this->post(route('admin.finance.fees.payments.store', $assessment), [
            'amount' => '100', 'payment_date' => '2026-08-28 10:00:00', 'payment_method' => 'cash',
        ])->assertForbidden();
        $this->post(route('admin.finance.payrolls.approve', $payroll))->assertForbidden();
        $this->post(route('admin.finance.payrolls.lock', $payroll))->assertForbidden();

        $this->assertDatabaseCount('fee_payments', 0);
        $this->assertSame('calculated', $payroll->fresh()->status);
    }

    public function test_authorized_finance_pages_render_with_empty_finance_data(): void
    {
        $slugs = [
            'finance.dashboard', 'fees.view', 'fees.report', 'fee-categories.view',
            'fee-rates.view', 'fee-policies.view', 'student-fee-policies.view',
            'payrolls.view', 'payrolls.report', 'salary-components.view',
            'salary-configs.view', 'payroll-inputs.view', 'teaching-extra.view',
            'tax-configs.view', 'insurance-configs.view',
        ];
        $permissions = collect($slugs)->map(fn (string $slug) => Permission::firstOrCreate(
            ['slug' => $slug], ['name' => $slug]
        ));
        $role = Role::create(['slug' => 'finance-viewer', 'name' => 'Finance Viewer']);
        $role->permissions()->attach($permissions->pluck('id'));
        $user = User::factory()->create();
        $user->roles()->attach($role);

        $routes = [
            'admin.finance.dashboard', 'admin.finance.fees.index',
            'admin.finance.fee-categories.index', 'admin.finance.fee-rates.index',
            'admin.finance.fee-policies.index', 'admin.finance.student-fee-policies.index',
            'admin.finance.payrolls.index', 'admin.finance.salary-components.index',
            'admin.finance.salary-profiles.index', 'admin.finance.payroll-inputs.index',
            'admin.finance.teaching-extra.index', 'admin.finance.tax-configs.index',
            'admin.finance.insurance-configs.index', 'admin.finance.reports.fees',
            'admin.finance.reports.payroll',
        ];

        foreach ($routes as $routeName) {
            $this->actingAs($user)->get(route($routeName))->assertOk();
        }
    }

    public function test_finance_dashboard_is_hidden_and_forbidden_without_its_exact_permission(): void
    {
        $feesView = Permission::firstOrCreate(
            ['slug' => 'fees.view'],
            ['name' => 'View fees']
        );
        $role = Role::create(['slug' => 'secretary', 'name' => 'Thu ky']);
        $role->permissions()->attach($feesView);
        $user = User::factory()->create();
        $user->roles()->attach($role);

        $response = $this->actingAs($user)->get(route('admin.finance.fees.index'));

        $response->assertOk()
            ->assertSee('data-sidebar-key="finance"', false)
            ->assertDontSee('href="'.route('admin.finance.dashboard').'"', false)
            ->assertDontSee('Tổng quan tài chính');
        $this->get(route('admin.finance.dashboard'))->assertForbidden();
    }

    public function test_report_only_permission_keeps_finance_navigation_visible(): void
    {
        $report = Permission::firstOrCreate(
            ['slug' => 'fees.report'],
            ['name' => 'View fee report']
        );
        $role = Role::create(['slug' => 'fee-reporter', 'name' => 'Fee Reporter']);
        $role->permissions()->attach($report);
        $user = User::factory()->create();
        $user->roles()->attach($role);

        $this->actingAs($user)
            ->get(route('admin.finance.reports.fees'))
            ->assertOk()
            ->assertSee('data-sidebar-key="finance"', false)
            ->assertSee(route('admin.finance.reports.fees'))
            ->assertDontSee('href="'.route('admin.finance.dashboard').'"', false);
    }

    /** @return array{FeeAssessment, Payroll} */
    private function financeRecords(): array
    {
        $year = AcademicYear::create([
            'name' => '2026-2027', 'code' => '2026-2027',
            'starts_on' => '2026-08-01', 'ends_on' => '2027-05-31', 'is_current' => true,
        ]);
        $student = Student::create(['full_name' => 'Student', 'status' => 'studying']);
        $category = FeeCategory::create(['code' => 'TUITION', 'name' => 'Học phí', 'type' => 'tuition']);
        $notice = FeeNotice::create([
            'notice_code' => 'FN-TEST', 'generation_key' => hash('sha256', 'notice'),
            'academic_year_id' => $year->id, 'fee_category_id' => $category->id,
            'title' => 'Học phí', 'amount' => '500000', 'period_type' => 'monthly',
            'period_key' => '2026-09', 'charge_sequence' => 1,
            'assessment_date' => '2026-09-01', 'scope_type' => 'selected',
            'is_active' => true, 'status' => 'generated',
        ]);
        $assessment = FeeAssessment::create([
            'assessment_code' => 'FA-TEST', 'fee_notice_id' => $notice->id,
            'student_id' => $student->id, 'academic_year_id' => $year->id,
            'fee_category_id' => $category->id, 'period_type' => 'monthly',
            'period_key' => '2026-09', 'charge_sequence' => 1,
            'assessment_date' => '2026-09-01', 'student_code_snapshot' => $student->student_code,
            'student_name_snapshot' => $student->full_name,
            'fee_category_code_snapshot' => $category->code,
            'fee_category_name_snapshot' => $category->name,
            'fee_notice_title_snapshot' => $notice->title,
            'original_amount' => '500000', 'discount_amount' => '0', 'final_amount' => '500000',
            'paid_amount' => '0', 'remaining_amount' => '500000', 'status' => 'pending',
        ]);
        $teacher = Teacher::create(['full_name' => 'Teacher', 'status' => 'active']);
        $payroll = Payroll::create([
            'payroll_code' => 'PR-TEST', 'teacher_id' => $teacher->id,
            'payroll_year' => 2026, 'payroll_month' => 8,
            'teacher_code_snapshot' => $teacher->teacher_code,
            'teacher_name_snapshot' => $teacher->full_name,
            'gross_salary' => '1000000', 'total_deductions' => '0',
            'net_salary' => '1000000', 'paid_amount' => '0',
            'remaining_amount' => '1000000', 'status' => 'calculated',
        ]);

        return [$assessment, $payroll];
    }
}

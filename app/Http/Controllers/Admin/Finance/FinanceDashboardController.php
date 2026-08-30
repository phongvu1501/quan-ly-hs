<?php

namespace App\Http\Controllers\Admin\Finance;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\FeeAssessment;
use App\Models\FeeCategory;
use App\Models\Payroll;
use App\Models\SchoolClass;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class FinanceDashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        $feeQuery = $this->feeQuery($request);
        $payrollQuery = $this->payrollQuery($request);

        return view('admin.finance.dashboard', [
            'feeStats' => [
                'receivable' => (string) (clone $feeQuery)->sum('final_amount'),
                'collected' => (string) (clone $feeQuery)->sum('paid_amount'),
                'remaining' => (string) (clone $feeQuery)->sum('remaining_amount'),
                'discount' => (string) (clone $feeQuery)->sum('discount_amount'),
                'fullyPaidStudents' => (clone $feeQuery)->where('paid_amount', '>', 0)
                    ->where('remaining_amount', 0)->distinct()->count('student_id'),
                'partialStudents' => (clone $feeQuery)->where('paid_amount', '>', 0)
                    ->where('remaining_amount', '>', 0)->distinct()->count('student_id'),
                'unpaidStudents' => (clone $feeQuery)->where('paid_amount', 0)
                    ->where('remaining_amount', '>', 0)->distinct()->count('student_id'),
                'overdue' => (clone $feeQuery)->whereNotNull('due_date')->whereDate('due_date', '<', today())
                    ->where('remaining_amount', '>', 0)->where('status', '!=', 'cancelled')->count(),
            ],
            'payrollStats' => [
                'gross' => (string) (clone $payrollQuery)->sum('gross_salary'),
                'deductions' => (string) (clone $payrollQuery)->sum('total_deductions'),
                'tax' => (string) (clone $payrollQuery)->sum('total_tax'),
                'insurance' => (string) (clone $payrollQuery)->sum('total_insurance'),
                'teachingExtra' => (string) (clone $payrollQuery)->sum('total_teaching_extra'),
                'allowances' => (string) (clone $payrollQuery)->sum('total_allowances'),
                'net' => (string) (clone $payrollQuery)->sum('net_salary'),
                'paidTeachers' => (clone $payrollQuery)->where('status', 'paid')->count(),
                'unpaidTeachers' => (clone $payrollQuery)->whereNotIn('status', ['paid', 'cancelled'])->count(),
            ],
            'years' => AcademicYear::orderByDesc('starts_on')->get(),
            'categories' => FeeCategory::where('is_active', true)->orderBy('name')->get(),
            'classes' => SchoolClass::with('academicYear')->where('status', 'active')->orderBy('name')->get(),
        ]);
    }

    private function feeQuery(Request $request): Builder
    {
        return FeeAssessment::query()
            ->when($request->filled('academic_year_id'), fn ($query) => $query->where('academic_year_id', $request->integer('academic_year_id')))
            ->when($request->filled('semester_id'), fn ($query) => $query->where('semester_id', $request->integer('semester_id')))
            ->when($request->filled('class_id'), fn ($query) => $query->where('class_id', $request->integer('class_id')))
            ->when($request->filled('fee_category_id'), fn ($query) => $query->where('fee_category_id', $request->integer('fee_category_id')))
            ->when($request->filled('month'), fn ($query) => $query->where('period_key', $request->string('month')->toString()))
            ->when($request->filled('fee_status'), fn ($query) => $query->where('status', $request->input('fee_status')))
            ->where('status', '!=', 'cancelled');
    }

    private function payrollQuery(Request $request): Builder
    {
        return Payroll::query()
            ->when($request->filled('payroll_year'), fn ($query) => $query->where('payroll_year', $request->integer('payroll_year')))
            ->when($request->filled('payroll_month'), fn ($query) => $query->where('payroll_month', $request->integer('payroll_month')))
            ->when($request->filled('payroll_status'), fn ($query) => $query->where('status', $request->input('payroll_status')))
            ->when($request->filled('specialization'), fn ($query) => $query->where('specialization_snapshot', $request->input('specialization')))
            ->where('status', '!=', 'cancelled');
    }
}

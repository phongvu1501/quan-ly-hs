<?php

namespace App\Http\Controllers\Admin\Finance;

use App\Http\Controllers\Controller;
use App\Models\FeeAssessment;
use App\Models\Payroll;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FinanceReportController extends Controller
{
    public function fees(Request $request)
    {
        $rows = FeeAssessment::query()
            ->select('period_key', 'fee_category_name_snapshot', DB::raw('SUM(original_amount) original_amount'),
                DB::raw('SUM(discount_amount) discount_amount'), DB::raw('SUM(final_amount) final_amount'),
                DB::raw('SUM(paid_amount) paid_amount'), DB::raw('SUM(remaining_amount) remaining_amount'))
            ->where('status', '!=', 'cancelled')
            ->when($request->filled('academic_year_id'), fn ($query) => $query->where('academic_year_id', $request->integer('academic_year_id')))
            ->groupBy('period_key', 'fee_category_name_snapshot')
            ->orderByDesc('period_key')->paginate(30)->withQueryString();

        return view('admin.finance.reports.fees', compact('rows'));
    }

    public function payroll(Request $request)
    {
        $rows = Payroll::query()
            ->select('payroll_year', 'payroll_month', 'specialization_snapshot',
                DB::raw('SUM(gross_salary) gross_salary'), DB::raw('SUM(total_allowances) allowances'),
                DB::raw('SUM(total_teaching_extra) teaching_extra'), DB::raw('SUM(total_tax) tax'),
                DB::raw('SUM(total_insurance) insurance'), DB::raw('SUM(total_deductions) deductions'),
                DB::raw('SUM(net_salary) net_salary'))
            ->where('status', '!=', 'cancelled')
            ->when($request->filled('year'), fn ($query) => $query->where('payroll_year', $request->integer('year')))
            ->when($request->filled('month'), fn ($query) => $query->where('payroll_month', $request->integer('month')))
            ->groupBy('payroll_year', 'payroll_month', 'specialization_snapshot')
            ->orderByDesc('payroll_year')->orderByDesc('payroll_month')->paginate(30)->withQueryString();

        return view('admin.finance.reports.payroll', compact('rows'));
    }
}

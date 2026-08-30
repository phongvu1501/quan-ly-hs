<?php

namespace App\Http\Controllers\Admin\Finance;

use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\CancelFinanceRequest;
use App\Http\Requests\Finance\GeneratePayrollRequest;
use App\Http\Requests\Finance\PayrollAdjustmentRequest;
use App\Models\AcademicYear;
use App\Models\Payroll;
use App\Models\PayrollAdjustment;
use App\Models\SalaryComponent;
use App\Models\Teacher;
use App\Services\Finance\PayrollAdjustmentService;
use App\Services\Finance\PayrollCalculationService;
use App\Services\Finance\PayrollService;
use Illuminate\Http\Request;

class PayrollController extends Controller
{
    public function index(Request $request)
    {
        $payrolls = Payroll::with('teacher')
            ->when($request->filled('q'), fn ($query) => $query->where(fn ($query) => $query
                ->where('teacher_code_snapshot', 'like', '%'.$request->input('q').'%')
                ->orWhere('teacher_name_snapshot', 'like', '%'.$request->input('q').'%')
                ->orWhere('payroll_code', 'like', '%'.$request->input('q').'%')))
            ->when($request->filled('year'), fn ($query) => $query->where('payroll_year', $request->integer('year')))
            ->when($request->filled('month'), fn ($query) => $query->where('payroll_month', $request->integer('month')))
            ->when($request->filled('specialization'), fn ($query) => $query->where('specialization_snapshot', $request->input('specialization')))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->input('status')))
            ->latest('payroll_year')->latest('payroll_month')->paginate(25)->withQueryString();

        return view('admin.finance.payrolls.index', [
            'payrolls' => $payrolls,
            'specializations' => Teacher::whereNotNull('specialization')->distinct()->orderBy('specialization')->pluck('specialization'),
        ]);
    }

    public function create()
    {
        return view('admin.finance.payrolls.generate', [
            'teachers' => Teacher::where('status', 'active')->orderBy('full_name')->get(),
            'academicYears' => AcademicYear::orderByDesc('starts_on')->get(),
        ]);
    }

    public function show(Payroll $payroll)
    {
        $payroll->load(['teacher', 'items', 'payments.createdBy', 'adjustments', 'salaryProfile']);

        return view('admin.finance.payrolls.show', [
            'payroll' => $payroll,
            'adjustmentComponents' => SalaryComponent::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function generate(GeneratePayrollRequest $request, PayrollService $service)
    {
        $data = $request->validated();
        $academicYear = ! empty($data['academic_year_id']) ? AcademicYear::findOrFail($data['academic_year_id']) : null;
        $result = $service->generate(
            (int) $data['payroll_year'], (int) $data['payroll_month'], $academicYear,
            array_map('intval', $data['teacher_ids'] ?? []), $request->user()
        );
        $message = "Đã tạo {$result['created']} payroll; bỏ qua {$result['skipped']}; lỗi {$result['failed']}.";

        return to_route('admin.finance.payrolls.index')->with($result['failed'] > 0 ? 'warning' : 'success', $message);
    }

    public function calculate(Payroll $payroll, PayrollCalculationService $service, Request $request)
    {
        $service->calculate($payroll->teacher, $payroll->payroll_year, $payroll->payroll_month, $payroll->academicYear, $request->user());

        return back()->with('success', 'Đã tính lại payroll từ dữ liệu nguồn.');
    }

    public function approve(Payroll $payroll, PayrollService $service, Request $request)
    {
        $service->approve($payroll, $request->user());

        return back()->with('success', 'Đã duyệt payroll.');
    }

    public function lock(Payroll $payroll, PayrollService $service, Request $request)
    {
        $service->lock($payroll, $request->user());

        return back()->with('success', 'Đã khóa payroll; snapshot không thể sửa trực tiếp.');
    }

    public function cancel(CancelFinanceRequest $request, Payroll $payroll, PayrollService $service)
    {
        $service->cancel($payroll, $request->validated('reason'), $request->user());

        return back()->with('success', 'Đã hủy payroll; lịch sử được giữ nguyên.');
    }

    public function adjustment(PayrollAdjustmentRequest $request, Payroll $payroll, PayrollAdjustmentService $service)
    {
        $component = SalaryComponent::findOrFail($request->integer('salary_component_id'));
        $service->create($payroll, $component, $request->validated('amount'), $request->validated('reason'), $request->user());

        return back()->with('success', 'Đã tạo adjustment riêng, không sửa payroll đã khóa.');
    }

    public function approveAdjustment(PayrollAdjustment $payrollAdjustment, PayrollAdjustmentService $service, Request $request)
    {
        $service->approve($payrollAdjustment, $request->user());

        return back()->with('success', 'Đã duyệt adjustment.');
    }

    public function lockAdjustment(PayrollAdjustment $payrollAdjustment, PayrollAdjustmentService $service, Request $request)
    {
        $service->lock($payrollAdjustment, $request->user());

        return back()->with('success', 'Đã khóa adjustment.');
    }

    public function settleAdjustment(PayrollAdjustment $payrollAdjustment, PayrollAdjustmentService $service, Request $request)
    {
        $data = $request->validate([
            'settlement_method' => ['required', 'in:cash,bank_transfer,online,other'],
            'settlement_reference' => ['nullable', 'string', 'max:100'],
        ]);
        $service->settle($payrollAdjustment, $data['settlement_method'], $data['settlement_reference'] ?? null, $request->user());

        return back()->with('success', 'Đã quyết toán adjustment.');
    }

    public function cancelAdjustment(CancelFinanceRequest $request, PayrollAdjustment $payrollAdjustment, PayrollAdjustmentService $service)
    {
        $service->cancel($payrollAdjustment, $request->validated('reason'), $request->user());

        return back()->with('success', 'Đã hủy adjustment.');
    }
}

<?php

namespace App\Http\Controllers\Admin\Finance;

use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\TeacherPayrollInputRequest;
use App\Models\AcademicYear;
use App\Models\SalaryComponent;
use App\Models\Teacher;
use App\Models\TeacherPayrollInput;
use App\Services\Finance\PayrollInputService;
use Illuminate\Http\Request;

class PayrollInputController extends Controller
{
    public function index(Request $request)
    {
        $inputs = TeacherPayrollInput::with(['teacher', 'component'])
            ->when($request->filled('q'), fn ($query) => $query->whereHas('teacher', fn ($teacher) => $teacher
                ->where('teacher_code', 'like', '%'.$request->input('q').'%')
                ->orWhere('full_name', 'like', '%'.$request->input('q').'%')))
            ->when($request->filled('year'), fn ($query) => $query->where('input_year', $request->integer('year')))
            ->when($request->filled('month'), fn ($query) => $query->where('input_month', $request->integer('month')))
            ->latest()->paginate(20)->withQueryString();

        return view('admin.finance.payroll-inputs.index', compact('inputs'));
    }

    public function create()
    {
        return view('admin.finance.payroll-inputs.form', $this->formData());
    }

    public function edit(TeacherPayrollInput $payrollInput)
    {
        return view('admin.finance.payroll-inputs.form', $this->formData($payrollInput));
    }

    public function store(TeacherPayrollInputRequest $request, PayrollInputService $service)
    {
        $service->save($request->validated(), $request->user());

        return to_route('admin.finance.payroll-inputs.index')->with('success', 'Đã lưu dữ liệu đầu vào payroll.');
    }

    public function update(TeacherPayrollInputRequest $request, TeacherPayrollInput $payrollInput, PayrollInputService $service)
    {
        $service->save($request->validated(), $request->user(), $payrollInput);

        return to_route('admin.finance.payroll-inputs.index')->with('success', 'Đã cập nhật dữ liệu đầu vào payroll.');
    }

    private function formData(?TeacherPayrollInput $payrollInput = null): array
    {
        return [
            'payrollInput' => $payrollInput,
            'teachers' => Teacher::orderBy('full_name')->get(),
            'components' => SalaryComponent::where('is_active', true)->orderBy('name')->get(),
            'years' => AcademicYear::orderByDesc('starts_on')->get(),
        ];
    }
}

<?php

namespace App\Http\Controllers\Admin\Finance;

use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\CancelFinanceRequest;
use App\Http\Requests\Finance\GenerateFeeRequest;
use App\Models\AcademicYear;
use App\Models\FeeAssessment;
use App\Models\FeeCategory;
use App\Models\FeePayment;
use App\Models\FeeRate;
use App\Models\SchoolClass;
use App\Models\Semester;
use App\Models\Student;
use App\Services\Finance\StudentFeeService;
use Illuminate\Http\Request;

class FeeAssessmentController extends Controller
{
    public function index(Request $request)
    {
        $assessments = FeeAssessment::with(['student', 'academicYear', 'semester', 'class', 'category'])
            ->when($request->filled('q'), fn ($query) => $query->where(fn ($query) => $query
                ->where('student_code_snapshot', 'like', '%'.$request->input('q').'%')
                ->orWhere('student_name_snapshot', 'like', '%'.$request->input('q').'%')
                ->orWhere('assessment_code', 'like', '%'.$request->input('q').'%')))
            ->when($request->filled('academic_year_id'), fn ($query) => $query->where('academic_year_id', $request->integer('academic_year_id')))
            ->when($request->filled('semester_id'), fn ($query) => $query->where('semester_id', $request->integer('semester_id')))
            ->when($request->filled('class_id'), fn ($query) => $query->where('class_id', $request->integer('class_id')))
            ->when($request->filled('fee_category_id'), fn ($query) => $query->where('fee_category_id', $request->integer('fee_category_id')))
            ->when($request->filled('month'), fn ($query) => $query->where('period_key', $request->input('month')))
            ->when($request->input('status') === 'overdue', fn ($query) => $query
                ->whereDate('due_date', '<', today())->where('remaining_amount', '>', 0)->where('status', '!=', 'cancelled'))
            ->when($request->filled('status') && $request->input('status') !== 'overdue', fn ($query) => $query->where('status', $request->input('status')))
            ->latest('assessment_date')->latest('id')->paginate(25)->withQueryString();

        return view('admin.finance.fees.index', $this->filterData() + compact('assessments'));
    }

    public function show(FeeAssessment $feeAssessment)
    {
        $feeAssessment->load(['discounts', 'payments.createdBy', 'academicYear', 'semester', 'class', 'category', 'generatedBy']);

        return view('admin.finance.fees.show', compact('feeAssessment'));
    }

    public function studentLedger(Student $student)
    {
        $assessments = $student->feeAssessments()->with(['category', 'academicYear', 'semester', 'payments'])->latest('assessment_date')->paginate(20);
        $legacyPayments = FeePayment::with(['feeNotice.academicYear', 'feeNotice.semester'])
            ->where('student_id', $student->id)->whereNull('fee_assessment_id')->latest('paid_at')->get();

        return view('admin.finance.fees.student-ledger', compact('student', 'assessments', 'legacyPayments'));
    }

    public function create()
    {
        return view('admin.finance.fees.generate', $this->filterData() + [
            'rates' => FeeRate::with(['category', 'academicYear'])->where('is_active', true)->orderBy('name')->get(),
            'students' => Student::where('status', 'studying')->orderBy('full_name')->limit(500)->get(),
        ]);
    }

    public function generate(GenerateFeeRequest $request, StudentFeeService $service)
    {
        $result = $service->generate($request->validated(), $request->user());

        return to_route('admin.finance.fees.index')->with(
            'success',
            "Đã tạo {$result['created']} khoản phải thu; bỏ qua {$result['skipped']} khoản đã tồn tại."
        );
    }

    public function cancel(CancelFinanceRequest $request, FeeAssessment $feeAssessment, StudentFeeService $service)
    {
        $service->cancelAssessment($feeAssessment, $request->validated('reason'), $request->user());

        return back()->with('success', 'Đã hủy khoản phải thu; lịch sử được giữ nguyên.');
    }

    private function filterData(): array
    {
        return [
            'years' => AcademicYear::orderByDesc('starts_on')->get(),
            'semesters' => Semester::with('academicYear')->orderByDesc('id')->get(),
            'classes' => SchoolClass::with('academicYear')->where('status', 'active')->orderBy('name')->get(),
            'categories' => FeeCategory::where('is_active', true)->orderBy('name')->get(),
        ];
    }
}

<?php

namespace App\Http\Controllers\Admin\Finance;

use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\StudentFeePolicyRequest;
use App\Models\FeePolicy;
use App\Models\Student;
use App\Models\StudentFeePolicy;
use App\Services\Finance\FinanceAuditService;
use Illuminate\Http\Request;

class StudentFeePolicyController extends Controller
{
    public function index(Request $request)
    {
        $assignments = StudentFeePolicy::with(['student', 'policy.category', 'policy.academicYear'])
            ->when($request->filled('q'), fn ($query) => $query->whereHas('student', fn ($student) => $student
                ->where('student_code', 'like', '%'.$request->input('q').'%')
                ->orWhere('full_name', 'like', '%'.$request->input('q').'%')))
            ->when($request->filled('fee_policy_id'), fn ($query) => $query->where('fee_policy_id', $request->integer('fee_policy_id')))
            ->latest('effective_from')->paginate(20)->withQueryString();

        return view('admin.finance.student-fee-policies.index', [
            'assignments' => $assignments,
            'policies' => FeePolicy::where('status', 'active')->orderBy('name')->get(),
        ]);
    }

    public function create()
    {
        return view('admin.finance.student-fee-policies.form', $this->formData());
    }

    public function edit(StudentFeePolicy $studentFeePolicy)
    {
        return view('admin.finance.student-fee-policies.form', $this->formData($studentFeePolicy));
    }

    public function store(StudentFeePolicyRequest $request, FinanceAuditService $audit)
    {
        $assignment = StudentFeePolicy::create($request->validated() + ['assigned_by' => $request->user()->id]);
        $audit->record($assignment, 'student_fee_policy.assigned', $request->user(), newValues: $assignment->toArray());

        return to_route('admin.finance.student-fee-policies.index')->with('success', 'Đã gán chính sách cho học sinh.');
    }

    public function update(StudentFeePolicyRequest $request, StudentFeePolicy $studentFeePolicy, FinanceAuditService $audit)
    {
        $old = $studentFeePolicy->toArray();
        $studentFeePolicy->update($request->validated() + ['assigned_by' => $request->user()->id]);
        $audit->record($studentFeePolicy, 'student_fee_policy.updated', $request->user(), $old, $studentFeePolicy->fresh()->toArray());

        return to_route('admin.finance.student-fee-policies.index')->with('success', 'Đã cập nhật thời gian áp dụng policy.');
    }

    public function destroy(StudentFeePolicy $studentFeePolicy, Request $request, FinanceAuditService $audit)
    {
        $studentFeePolicy->update(['status' => 'inactive']);
        $audit->record($studentFeePolicy, 'student_fee_policy.deactivated', $request->user());

        return back()->with('success', 'Đã ngừng policy của học sinh.');
    }

    private function formData(?StudentFeePolicy $studentFeePolicy = null): array
    {
        return [
            'studentFeePolicy' => $studentFeePolicy,
            'students' => Student::where('status', 'studying')->orderBy('full_name')->limit(500)->get(),
            'policies' => FeePolicy::with(['category', 'academicYear'])->where('status', 'active')->orderBy('name')->get(),
        ];
    }
}

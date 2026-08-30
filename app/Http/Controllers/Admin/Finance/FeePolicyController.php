<?php

namespace App\Http\Controllers\Admin\Finance;

use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\FeePolicyRequest;
use App\Models\AcademicYear;
use App\Models\FeeCategory;
use App\Models\FeePolicy;
use App\Services\Finance\FinanceAuditService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class FeePolicyController extends Controller
{
    public function index(Request $request)
    {
        $policies = FeePolicy::with(['academicYear', 'category'])->withCount('studentAssignments')
            ->when($request->filled('q'), fn ($query) => $query->where(fn ($query) => $query
                ->where('code', 'like', '%'.$request->input('q').'%')
                ->orWhere('name', 'like', '%'.$request->input('q').'%')))
            ->when($request->filled('academic_year_id'), fn ($query) => $query->where('academic_year_id', $request->integer('academic_year_id')))
            ->orderByDesc('priority')->paginate(20)->withQueryString();

        return view('admin.finance.fee-policies.index', [
            'policies' => $policies,
            'years' => AcademicYear::orderByDesc('starts_on')->get(),
        ]);
    }

    public function create()
    {
        return view('admin.finance.fee-policies.form', $this->formData());
    }

    public function edit(FeePolicy $feePolicy)
    {
        return view('admin.finance.fee-policies.form', $this->formData($feePolicy));
    }

    public function store(FeePolicyRequest $request, FinanceAuditService $audit)
    {
        $policy = FeePolicy::create($request->validated());
        $audit->record($policy, 'fee_policy.created', $request->user(), newValues: $policy->toArray());

        return to_route('admin.finance.fee-policies.index')->with('success', 'Đã tạo chính sách miễn giảm.');
    }

    public function update(FeePolicyRequest $request, FeePolicy $feePolicy, FinanceAuditService $audit)
    {
        if ($feePolicy->discountSnapshots()->exists()) {
            throw ValidationException::withMessages(['fee_policy' => 'Policy đã được snapshot; hãy ngừng hiệu lực và tạo policy mới.']);
        }
        $old = $feePolicy->toArray();
        $feePolicy->update($request->validated());
        $audit->record($feePolicy, 'fee_policy.updated', $request->user(), $old, $feePolicy->fresh()->toArray());

        return to_route('admin.finance.fee-policies.index')->with('success', 'Đã cập nhật chính sách miễn giảm.');
    }

    public function destroy(FeePolicy $feePolicy, Request $request, FinanceAuditService $audit)
    {
        $feePolicy->update(['status' => 'inactive']);
        $audit->record($feePolicy, 'fee_policy.deactivated', $request->user());

        return back()->with('success', 'Đã ngừng hiệu lực policy; lịch sử được giữ nguyên.');
    }

    private function formData(?FeePolicy $feePolicy = null): array
    {
        return [
            'feePolicy' => $feePolicy,
            'categories' => FeeCategory::where('is_active', true)->orderBy('name')->get(),
            'years' => AcademicYear::orderByDesc('starts_on')->get(),
        ];
    }
}

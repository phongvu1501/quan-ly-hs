<?php

namespace App\Http\Controllers\Admin\Finance;

use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\FeeCategoryRequest;
use App\Models\FeeCategory;
use App\Services\Finance\FinanceAuditService;
use Illuminate\Http\Request;

class FeeCategoryController extends Controller
{
    public function index(Request $request)
    {
        $categories = FeeCategory::withCount(['rates', 'policies', 'assessments'])
            ->when($request->filled('q'), fn ($query) => $query->where(fn ($query) => $query
                ->where('code', 'like', '%'.$request->input('q').'%')
                ->orWhere('name', 'like', '%'.$request->input('q').'%')))
            ->orderBy('name')->paginate(20)->withQueryString();

        return view('admin.finance.fee-categories.index', compact('categories'));
    }

    public function create()
    {
        return view('admin.finance.fee-categories.form', ['feeCategory' => null]);
    }

    public function edit(FeeCategory $feeCategory)
    {
        return view('admin.finance.fee-categories.form', compact('feeCategory'));
    }

    public function store(FeeCategoryRequest $request, FinanceAuditService $audit)
    {
        $category = FeeCategory::create($request->validated() + ['is_active' => $request->boolean('is_active')]);
        $audit->record($category, 'fee_category.created', $request->user(), newValues: $category->toArray());

        return to_route('admin.finance.fee-categories.index')->with('success', 'Đã tạo danh mục khoản thu.');
    }

    public function update(FeeCategoryRequest $request, FeeCategory $feeCategory, FinanceAuditService $audit)
    {
        $old = $feeCategory->toArray();
        $feeCategory->update($request->validated() + ['is_active' => $request->boolean('is_active')]);
        $audit->record($feeCategory, 'fee_category.updated', $request->user(), $old, $feeCategory->fresh()->toArray());

        return to_route('admin.finance.fee-categories.index')->with('success', 'Đã cập nhật danh mục khoản thu.');
    }

    public function destroy(FeeCategory $feeCategory, Request $request, FinanceAuditService $audit)
    {
        $feeCategory->update(['is_active' => false]);
        $audit->record($feeCategory, 'fee_category.deactivated', $request->user());

        return back()->with('success', 'Đã ngừng sử dụng danh mục khoản thu; dữ liệu lịch sử được giữ nguyên.');
    }
}

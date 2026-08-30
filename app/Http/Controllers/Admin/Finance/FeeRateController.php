<?php

namespace App\Http\Controllers\Admin\Finance;

use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\FeeRateRequest;
use App\Models\AcademicYear;
use App\Models\FeeCategory;
use App\Models\FeeRate;
use App\Services\Finance\FinanceAuditService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class FeeRateController extends Controller
{
    public function index(Request $request)
    {
        $rates = FeeRate::with(['category', 'academicYear'])->withCount('notices')
            ->when($request->filled('q'), fn ($query) => $query->where(fn ($query) => $query
                ->where('code', 'like', '%'.$request->input('q').'%')
                ->orWhere('name', 'like', '%'.$request->input('q').'%')))
            ->when($request->filled('academic_year_id'), fn ($query) => $query->where('academic_year_id', $request->integer('academic_year_id')))
            ->latest('effective_from')->paginate(20)->withQueryString();

        return view('admin.finance.fee-rates.index', ['rates' => $rates, 'years' => AcademicYear::orderByDesc('starts_on')->get()]);
    }

    public function create()
    {
        return view('admin.finance.fee-rates.form', $this->formData());
    }

    public function edit(FeeRate $feeRate)
    {
        return view('admin.finance.fee-rates.form', $this->formData($feeRate));
    }

    public function store(FeeRateRequest $request, FinanceAuditService $audit)
    {
        $rate = FeeRate::create($request->validated() + ['is_active' => $request->boolean('is_active')]);
        $audit->record($rate, 'fee_rate.created', $request->user(), newValues: $rate->toArray());

        return to_route('admin.finance.fee-rates.index')->with('success', 'Đã tạo mức thu.');
    }

    public function update(FeeRateRequest $request, FeeRate $feeRate, FinanceAuditService $audit)
    {
        if ($feeRate->notices()->exists()) {
            throw ValidationException::withMessages(['fee_rate' => 'Mức thu đã phát sinh dữ liệu; hãy tạo phiên bản mới.']);
        }
        $old = $feeRate->toArray();
        $feeRate->update($request->validated() + ['is_active' => $request->boolean('is_active')]);
        $audit->record($feeRate, 'fee_rate.updated', $request->user(), $old, $feeRate->fresh()->toArray());

        return to_route('admin.finance.fee-rates.index')->with('success', 'Đã cập nhật mức thu.');
    }

    public function destroy(FeeRate $feeRate, Request $request, FinanceAuditService $audit)
    {
        $feeRate->update(['is_active' => false]);
        $audit->record($feeRate, 'fee_rate.deactivated', $request->user());

        return back()->with('success', 'Đã ngừng sử dụng mức thu.');
    }

    private function formData(?FeeRate $feeRate = null): array
    {
        return ['feeRate' => $feeRate, 'categories' => FeeCategory::orderBy('name')->get(), 'years' => AcademicYear::orderByDesc('starts_on')->get()];
    }
}

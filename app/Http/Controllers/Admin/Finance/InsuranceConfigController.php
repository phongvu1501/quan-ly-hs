<?php

namespace App\Http\Controllers\Admin\Finance;

use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\InsuranceConfigRequest;
use App\Models\InsuranceConfig;
use App\Services\Finance\FinanceAuditService;
use Illuminate\Http\Request;

class InsuranceConfigController extends Controller
{
    public function index(Request $request)
    {
        $configs = InsuranceConfig::query()
            ->when($request->filled('q'), fn ($query) => $query->where(fn ($query) => $query
                ->where('code', 'like', '%'.$request->input('q').'%')
                ->orWhere('name', 'like', '%'.$request->input('q').'%')))
            ->latest('effective_from')->paginate(20)->withQueryString();

        return view('admin.finance.insurance-configs.index', compact('configs'));
    }

    public function create()
    {
        return view('admin.finance.insurance-configs.form', ['insuranceConfig' => null]);
    }

    public function edit(InsuranceConfig $insuranceConfig)
    {
        return view('admin.finance.insurance-configs.form', compact('insuranceConfig'));
    }

    public function store(InsuranceConfigRequest $request, FinanceAuditService $audit)
    {
        $config = InsuranceConfig::create($request->validated() + ['is_active' => $request->boolean('is_active')]);
        $audit->record($config, 'insurance_config.created', $request->user(), newValues: $config->toArray());

        return to_route('admin.finance.insurance-configs.index')->with('success', 'Đã tạo cấu hình bảo hiểm.');
    }

    public function update(InsuranceConfigRequest $request, InsuranceConfig $insuranceConfig, FinanceAuditService $audit)
    {
        $old = $insuranceConfig->toArray();
        $insuranceConfig->update($request->validated() + ['is_active' => $request->boolean('is_active')]);
        $audit->record($insuranceConfig, 'insurance_config.updated', $request->user(), $old, $insuranceConfig->fresh()->toArray());

        return to_route('admin.finance.insurance-configs.index')->with('success', 'Đã cập nhật cấu hình bảo hiểm.');
    }

    public function destroy(InsuranceConfig $insuranceConfig, Request $request, FinanceAuditService $audit)
    {
        $insuranceConfig->update(['is_active' => false]);
        $audit->record($insuranceConfig, 'insurance_config.deactivated', $request->user());

        return back()->with('success', 'Đã ngừng hiệu lực cấu hình bảo hiểm.');
    }
}

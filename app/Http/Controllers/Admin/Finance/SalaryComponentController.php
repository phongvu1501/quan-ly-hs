<?php

namespace App\Http\Controllers\Admin\Finance;

use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\SalaryComponentRequest;
use App\Models\SalaryComponent;
use App\Services\Finance\FinanceAuditService;
use Illuminate\Http\Request;

class SalaryComponentController extends Controller
{
    public function index(Request $request)
    {
        $components = SalaryComponent::withCount(['salaryConfigs', 'payrollItems'])
            ->when($request->filled('q'), fn ($query) => $query->where(fn ($query) => $query
                ->where('code', 'like', '%'.$request->input('q').'%')
                ->orWhere('name', 'like', '%'.$request->input('q').'%')))
            ->when($request->filled('type'), fn ($query) => $query->where('type', $request->input('type')))
            ->orderBy('type')->orderBy('name')->paginate(20)->withQueryString();

        return view('admin.finance.salary-components.index', compact('components'));
    }

    public function create()
    {
        return view('admin.finance.salary-components.form', ['salaryComponent' => null]);
    }

    public function edit(SalaryComponent $salaryComponent)
    {
        return view('admin.finance.salary-components.form', compact('salaryComponent'));
    }

    public function store(SalaryComponentRequest $request, FinanceAuditService $audit)
    {
        $component = SalaryComponent::create($this->data($request));
        $audit->record($component, 'salary_component.created', $request->user(), newValues: $component->toArray());

        return to_route('admin.finance.salary-components.index')->with('success', 'Đã tạo thành phần lương.');
    }

    public function update(SalaryComponentRequest $request, SalaryComponent $salaryComponent, FinanceAuditService $audit)
    {
        $old = $salaryComponent->toArray();
        $salaryComponent->update($this->data($request));
        $audit->record($salaryComponent, 'salary_component.updated', $request->user(), $old, $salaryComponent->fresh()->toArray());

        return to_route('admin.finance.salary-components.index')->with('success', 'Đã cập nhật thành phần lương.');
    }

    public function destroy(SalaryComponent $salaryComponent, Request $request, FinanceAuditService $audit)
    {
        $salaryComponent->update(['is_active' => false]);
        $audit->record($salaryComponent, 'salary_component.deactivated', $request->user());

        return back()->with('success', 'Đã ngừng sử dụng component; snapshot cũ được giữ nguyên.');
    }

    private function data(SalaryComponentRequest $request): array
    {
        return $request->validated() + [
            'is_taxable' => $request->boolean('is_taxable'),
            'is_insurance_base' => $request->boolean('is_insurance_base'),
            'is_active' => $request->boolean('is_active'),
        ];
    }
}

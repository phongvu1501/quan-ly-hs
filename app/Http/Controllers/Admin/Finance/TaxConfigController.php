<?php

namespace App\Http\Controllers\Admin\Finance;

use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\TaxConfigRequest;
use App\Models\TaxConfig;
use App\Services\Finance\FinanceAuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TaxConfigController extends Controller
{
    public function index(Request $request)
    {
        $configs = TaxConfig::withCount('brackets')
            ->when($request->filled('q'), fn ($query) => $query->where(fn ($query) => $query
                ->where('code', 'like', '%'.$request->input('q').'%')
                ->orWhere('name', 'like', '%'.$request->input('q').'%')))
            ->latest('effective_from')->paginate(20)->withQueryString();

        return view('admin.finance.tax-configs.index', compact('configs'));
    }

    public function create()
    {
        return view('admin.finance.tax-configs.form', ['taxConfig' => null]);
    }

    public function edit(TaxConfig $taxConfig)
    {
        return view('admin.finance.tax-configs.form', ['taxConfig' => $taxConfig->load('brackets')]);
    }

    public function store(TaxConfigRequest $request, FinanceAuditService $audit)
    {
        $config = $this->save($request->validated());
        $audit->record($config, 'tax_config.created', $request->user(), newValues: $config->load('brackets')->toArray());

        return to_route('admin.finance.tax-configs.index')->with('success', 'Đã tạo cấu hình thuế.');
    }

    public function update(TaxConfigRequest $request, TaxConfig $taxConfig, FinanceAuditService $audit)
    {
        $old = $taxConfig->load('brackets')->toArray();
        $config = $this->save($request->validated(), $taxConfig);
        $audit->record($config, 'tax_config.updated', $request->user(), $old, $config->load('brackets')->toArray());

        return to_route('admin.finance.tax-configs.index')->with('success', 'Đã cập nhật cấu hình thuế chưa sử dụng.');
    }

    public function destroy(TaxConfig $taxConfig, Request $request, FinanceAuditService $audit)
    {
        $taxConfig->update(['is_active' => false]);
        $audit->record($taxConfig, 'tax_config.deactivated', $request->user());

        return back()->with('success', 'Đã ngừng hiệu lực cấu hình thuế.');
    }

    /** @param array<string, mixed> $data */
    private function save(array $data, ?TaxConfig $taxConfig = null): TaxConfig
    {
        return DB::transaction(function () use ($data, $taxConfig): TaxConfig {
            $brackets = $data['brackets'];
            unset($data['brackets']);
            if ($taxConfig) {
                $taxConfig->update($data);
                $taxConfig->brackets()->delete();
            } else {
                $taxConfig = TaxConfig::create($data);
            }
            $taxConfig->brackets()->createMany($brackets);

            return $taxConfig;
        });
    }
}

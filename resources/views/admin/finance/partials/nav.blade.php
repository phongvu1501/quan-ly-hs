<div class="d-flex flex-wrap gap-2 mb-4">
    @if (auth()->user()->hasPermission('finance.dashboard'))
        <a class="btn btn-sm btn-light" href="{{ route('admin.finance.dashboard') }}"><i
                data-lucide="layout-dashboard"></i> Tổng quan</a>
    @endif
    @if (auth()->user()->hasPermission('fees.view'))
        <a class="btn btn-sm btn-light" href="{{ route('admin.finance.fees.index') }}"><i data-lucide="receipt-text"></i>
            Công nợ</a>
    @endif
    @if (auth()->user()->hasPermission('fee-policies.view'))
        <a class="btn btn-sm btn-light" href="{{ route('admin.finance.fee-policies.index') }}"><i
                data-lucide="badge-percent"></i> Miễn giảm</a>
    @endif
    @if (auth()->user()->hasPermission('payrolls.view'))
        <a class="btn btn-sm btn-light" href="{{ route('admin.finance.payrolls.index') }}"><i
                data-lucide="badge-dollar-sign"></i> Bảng lương</a>
    @endif
    @if (auth()->user()->hasPermission('salary-configs.view'))
        <a class="btn btn-sm btn-light" href="{{ route('admin.finance.salary-profiles.index') }}"><i
                data-lucide="settings-2"></i> Cấu hình lương</a>
    @endif
</div>

@extends('admin.layouts.app')
@section('title', 'Cấu hình bảo hiểm')
@section('content')<div class="page-heading page-heading-inline">
        <div>
            <p class="eyebrow">Thuế và bảo hiểm</p>
            <h1>Cấu hình bảo hiểm</h1>
        </div>
        @if (auth()->user()->hasPermission('insurance-configs.create'))
            <a href="{{ route('admin.finance.insurance-configs.create') }}" class="btn btn-primary"><i data-lucide="plus"></i>
                Thêm cấu hình</a>
        @endif
    </div>
    @include('admin.finance.partials.nav')<div class="alert alert-info">Tỷ lệ do admin cấu hình, không được hard-code trong payroll.
    </div>
    <div class="card table-card">
        <div class="table-responsive">
            <table class="table mb-0">
                <thead>
                    <tr>
                        <th>Mã / tên</th>
                        <th>Tỷ lệ NLĐ</th>
                        <th>Tỷ lệ đơn vị</th>
                        <th>Nền lương</th>
                        <th>Hiệu lực</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($configs as $c)
                        <tr>
                            <td><strong>{{ $c->name }}</strong><small
                                    class="d-block"><code>{{ $c->code }}</code></small></td>
                            <td>{{ $c->employee_rate_percentage }}%</td>
                            <td>{{ $c->employer_rate_percentage }}%</td>
                            <td>{{ $c->salary_base_type }}</td>
                            <td>{{ $c->effective_from?->format('d/m/Y') }} - {{ $c->effective_to?->format('d/m/Y') ?: '...' }}
                            </td>
                            <td>{{ $c->is_active ? 'active' : 'inactive' }}</td>
                            <td>
                                @if (auth()->user()->hasPermission('insurance-configs.edit'))
                                    <a href="{{ route('admin.finance.insurance-configs.edit', $c) }}"
                                        class="btn btn-sm btn-light"><i data-lucide="pencil"></i></a>
                                @endif
                            </td>
                    </tr>@empty<tr>
                            <td colspan="7" class="empty-state">Chưa có cấu hình.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($configs->hasPages())
            <div class="p-3 border-top">{{ $configs->links('pagination::bootstrap-5') }}</div>
        @endif
</div>@endsection

@extends('admin.layouts.app')
@section('title', 'Thành phần lương')
@section('content')<div class="page-heading page-heading-inline">
        <div>
            <p class="eyebrow">Cấu hình lương</p>
            <h1>Thành phần lương</h1>
        </div>
        @if (auth()->user()->hasPermission('salary-components.create'))
            <a href="{{ route('admin.finance.salary-components.create') }}" class="btn btn-primary"><i data-lucide="plus"></i>
                Thêm thành phần</a>
        @endif
    </div>
    @include('admin.finance.partials.nav')<div class="card table-card">
        <div class="table-responsive">
            <table class="table mb-0">
                <thead>
                    <tr>
                        <th>Mã</th>
                        <th>Tên</th>
                        <th>Loại</th>
                        <th>Nhóm</th>
                        <th>Cách tính</th>
                        <th>Thuế / bảo hiểm</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($components as $c)
                        <tr>
                            <td><code>{{ $c->code }}</code></td>
                            <td><strong>{{ $c->name }}</strong></td>
                            <td>{{ \App\Support\FinanceLabels::componentType($c->type) }}</td>
                            <td>{{ \App\Support\FinanceLabels::category($c->category) }}</td>
                            <td>{{ \App\Support\FinanceLabels::calculationType($c->calculation_type) }}</td>
                            <td>{{ $c->is_taxable ? 'Chịu thuế' : '-' }} / {{ $c->is_insurance_base ? 'Làm nền bảo hiểm' : '-' }}
                            </td>
                            <td>
                                @if (auth()->user()->hasPermission('salary-components.edit'))
                                    <a href="{{ route('admin.finance.salary-components.edit', $c) }}"
                                        class="btn btn-sm btn-light"><i data-lucide="pencil"></i></a>
                                @endif
                            </td>
                    </tr>@empty<tr>
                            <td colspan="7" class="empty-state">Chưa có thành phần lương.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($components->hasPages())
            <div class="p-3 border-top">{{ $components->links('pagination::bootstrap-5') }}</div>
        @endif
</div>@endsection

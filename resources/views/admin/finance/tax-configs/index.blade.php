@extends('admin.layouts.app')
@section('title', 'Cấu hình thuế')
@section('content')<div class="page-heading page-heading-inline">
        <div>
            <p class="eyebrow">Thuế và bảo hiểm</p>
            <h1>Cấu hình thuế</h1>
        </div>
        @if (auth()->user()->hasPermission('tax-configs.create'))
            <a href="{{ route('admin.finance.tax-configs.create') }}" class="btn btn-primary"><i data-lucide="plus"></i> Thêm
                cấu hình</a>
        @endif
    </div>
    @include('admin.finance.partials.nav')<div class="alert alert-info">Các mức thuế do admin cấu hình; hệ thống không mặc định đây là
        quy định pháp luật hiện hành.</div>
    <div class="card table-card">
        <div class="table-responsive">
            <table class="table mb-0">
                <thead>
                    <tr>
                        <th>Mã / tên</th>
                        <th>Năm</th>
                        <th>Giảm trừ cá nhân</th>
                        <th>Giảm trừ phụ thuộc</th>
                        <th>Bậc</th>
                        <th>Hiệu lực</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($configs as $c)
                        <tr>
                            <td><strong>{{ $c->name }}</strong><small
                                    class="d-block"><code>{{ $c->code }}</code></small></td>
                            <td>{{ $c->tax_year }}</td>
                            <td>{{ number_format((int) $c->personal_deduction, 0, ',', '.') }} ₫</td>
                            <td>{{ number_format((int) $c->dependent_deduction, 0, ',', '.') }} ₫</td>
                            <td>{{ $c->brackets_count }}</td>
                            <td>{{ $c->effective_from?->format('d/m/Y') }} - {{ $c->effective_to?->format('d/m/Y') ?: '...' }}
                            </td>
                            <td>
                                @if (auth()->user()->hasPermission('tax-configs.edit'))
                                    <a href="{{ route('admin.finance.tax-configs.edit', $c) }}"
                                        class="btn btn-sm btn-light"><i data-lucide="pencil"></i></a>
                                @endif
                            </td>
                    </tr>@empty<tr>
                            <td colspan="7" class="empty-state">Chưa có cấu hình thuế.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($configs->hasPages())
            <div class="p-3 border-top">{{ $configs->links('pagination::bootstrap-5') }}</div>
        @endif
</div>@endsection

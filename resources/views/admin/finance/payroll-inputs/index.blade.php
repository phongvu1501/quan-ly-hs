@extends('admin.layouts.app')
@section('title', 'Đầu vào bảng lương')
@section('content')<div class="page-heading page-heading-inline">
        <div>
            <p class="eyebrow">Dữ liệu đầu vào lương</p>
            <h1>Dữ liệu đầu vào theo tháng</h1>
        </div>
        @if (auth()->user()->hasPermission('payroll-inputs.create'))
            <a href="{{ route('admin.finance.payroll-inputs.create') }}" class="btn btn-primary"><i data-lucide="plus"></i>
                Thêm dữ liệu</a>
        @endif
    </div>
    @include('admin.finance.partials.nav')<div class="card table-card">
        <div class="table-responsive">
            <table class="table mb-0">
                <thead>
                    <tr>
                        <th>Giáo viên</th>
                        <th>Kỳ</th>
                        <th>Loại dữ liệu</th>
                        <th>Thành phần</th>
                        <th>Số lượng</th>
                        <th>Số tiền nhập</th>
                        <th>Trạng thái</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($inputs as $i)
                        <tr>
                            <td><strong>{{ $i->teacher?->full_name }}</strong><small
                                    class="d-block">{{ $i->teacher?->teacher_code }}</small></td>
                            <td>{{ str_pad($i->input_month, 2, '0', STR_PAD_LEFT) }}/{{ $i->input_year }}</td>
                            <td>{{ \App\Support\FinanceLabels::inputType($i->input_type) }}</td>
                            <td>{{ $i->component?->name }}</td>
                            <td>{{ $i->quantity ?: '-' }}</td>
                            <td>{{ $i->input_amount !== null ? number_format((int) $i->input_amount, 0, ',', '.') . ' ₫' : 'Do hệ thống tính' }}
                            </td>
                            <td>{{ \App\Support\FinanceLabels::inputStatus($i->status) }}</td>
                            <td>
                                @if ($i->status === 'draft' && auth()->user()->hasPermission('payroll-inputs.edit'))
                                    <a href="{{ route('admin.finance.payroll-inputs.edit', $i) }}"
                                        class="btn btn-sm btn-light"><i data-lucide="pencil"></i></a>
                                @endif
                            </td>
                    </tr>@empty<tr>
                            <td colspan="8" class="empty-state">Chưa có dữ liệu đầu vào.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($inputs->hasPages())
            <div class="p-3 border-top">{{ $inputs->links('pagination::bootstrap-5') }}</div>
        @endif
</div>@endsection

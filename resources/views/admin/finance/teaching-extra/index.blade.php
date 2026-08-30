@extends('admin.layouts.app')
@section('title', 'Dạy thêm')
@section('content')<div class="page-heading page-heading-inline">
        <div>
            <p class="eyebrow">Dữ liệu đầu vào lương</p>
            <h1>Dạy thêm theo tháng</h1>
        </div>
        @if (auth()->user()->hasPermission('teaching-extra.create'))
            <a href="{{ route('admin.finance.teaching-extra.create') }}" class="btn btn-primary"><i data-lucide="plus"></i> Ghi
                nhận tiết dạy</a>
        @endif
    </div>
    @include('admin.finance.partials.nav')<div class="card table-card">
        <div class="table-responsive">
            <table class="table mb-0">
                <thead>
                    <tr>
                        <th>Giáo viên</th>
                        <th>Kỳ</th>
                        <th>Môn / lớp</th>
                        <th>Số tiết</th>
                        <th>Đơn giá</th>
                        <th>Thành tiền</th>
                        <th>Trạng thái</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($records as $r)
                        <tr>
                            <td><strong>{{ $r->teacher?->full_name }}</strong><small
                                    class="d-block">{{ $r->teacher?->teacher_code }}</small></td>
                            <td>{{ str_pad($r->teaching_month, 2, '0', STR_PAD_LEFT) }}/{{ $r->teaching_year }}</td>
                            <td>{{ $r->subject_name_snapshot }}<small class="d-block">{{ $r->class_name_snapshot }}</small>
                            </td>
                            <td>{{ $r->number_of_periods }}</td>
                            <td>{{ number_format((int) $r->rate_per_period, 0, ',', '.') }} ₫</td>
                            <td>{{ number_format((int) $r->total_amount, 0, ',', '.') }} ₫</td>
                            <td>{{ \App\Support\FinanceLabels::inputStatus($r->status) }}</td>
                            <td>
                                @if ($r->status === 'draft' && auth()->user()->hasPermission('teaching-extra.approve'))
                                    <form method="POST" action="{{ route('admin.finance.teaching-extra.approve', $r) }}">
                                        @csrf<button class="btn btn-sm btn-primary"><i data-lucide="check"></i>
                                            Duyệt</button></form>
                                @endif
                            </td>
                    </tr>@empty<tr>
                            <td colspan="8" class="empty-state">Chưa có dữ liệu dạy thêm.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($records->hasPages())
            <div class="p-3 border-top">{{ $records->links('pagination::bootstrap-5') }}</div>
        @endif
</div>@endsection

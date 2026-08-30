@extends('admin.layouts.app')
@section('title', 'Báo cáo lương')
@section('content')<div class="page-heading">
        <p class="eyebrow">Báo cáo tài chính</p>
        <h1>Báo cáo lương</h1>
    </div>@include('admin.finance.partials.nav')<div class="card table-card">
        <div class="table-responsive">
            <table class="table mb-0">
                <thead>
                    <tr>
                        <th>Kỳ</th>
                        <th>Bộ môn</th>
                        <th>Gross</th>
                        <th>Phụ cấp</th>
                        <th>Dạy thêm</th>
                        <th>Thuế</th>
                        <th>Bảo hiểm</th>
                        <th>Khấu trừ</th>
                        <th>Net</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rows as $r)
                        <tr>
                            <td>{{ str_pad($r->payroll_month, 2, '0', STR_PAD_LEFT) }}/{{ $r->payroll_year }}</td>
                            <td>{{ $r->specialization_snapshot ?: 'Chưa phân loại' }}</td>
                            <td>{{ number_format((int) $r->gross_salary, 0, ',', '.') }} ₫</td>
                            <td>{{ number_format((int) $r->allowances, 0, ',', '.') }} ₫</td>
                            <td>{{ number_format((int) $r->teaching_extra, 0, ',', '.') }} ₫</td>
                            <td>{{ number_format((int) $r->tax, 0, ',', '.') }} ₫</td>
                            <td>{{ number_format((int) $r->insurance, 0, ',', '.') }} ₫</td>
                            <td>{{ number_format((int) $r->deductions, 0, ',', '.') }} ₫</td>
                            <td>{{ number_format((int) $r->net_salary, 0, ',', '.') }} ₫</td>
                    </tr>@empty<tr>
                            <td colspan="9" class="empty-state">Chưa có dữ liệu.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($rows->hasPages())
            <div class="p-3 border-top">{{ $rows->links('pagination::bootstrap-5') }}</div>
        @endif
</div>@endsection

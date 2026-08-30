@extends('admin.layouts.app')
@section('title', 'Báo cáo học phí')
@section('content')<div class="page-heading">
        <p class="eyebrow">Báo cáo tài chính mới</p>
        <h1>Báo cáo học phí</h1>
        <p class="text-secondary">Không bao gồm fee payment legacy có fee_assessment_id NULL.</p>
    </div>@include('admin.finance.partials.nav')<div class="card table-card">
        <div class="table-responsive">
            <table class="table mb-0">
                <thead>
                    <tr>
                        <th>Kỳ</th>
                        <th>Khoản thu</th>
                        <th>Gốc</th>
                        <th>Miễn giảm</th>
                        <th>Phải thu</th>
                        <th>Đã thu</th>
                        <th>Còn nợ</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rows as $r)
                        <tr>
                            <td>{{ $r->period_key }}</td>
                            <td>{{ $r->fee_category_name_snapshot }}</td>
                            <td>{{ number_format((int) $r->original_amount, 0, ',', '.') }} ₫</td>
                            <td>{{ number_format((int) $r->discount_amount, 0, ',', '.') }} ₫</td>
                            <td>{{ number_format((int) $r->final_amount, 0, ',', '.') }} ₫</td>
                            <td>{{ number_format((int) $r->paid_amount, 0, ',', '.') }} ₫</td>
                            <td>{{ number_format((int) $r->remaining_amount, 0, ',', '.') }} ₫</td>
                    </tr>@empty<tr>
                            <td colspan="7" class="empty-state">Chưa có dữ liệu.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($rows->hasPages())
            <div class="p-3 border-top">{{ $rows->links('pagination::bootstrap-5') }}</div>
        @endif
</div>@endsection

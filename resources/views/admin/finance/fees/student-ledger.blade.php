@extends('admin.layouts.app')
@section('title', 'Tài chính học sinh')
@section('content')
    <div class="page-heading page-heading-inline">
        <div>
            <p class="eyebrow">Hồ sơ tài chính</p>
            <h1>{{ $student->full_name }}</h1>
            <p class="text-secondary mb-0">{{ $student->student_code }}</p>
        </div><a href="{{ route('admin.finance.fees.index') }}" class="btn btn-light"><i data-lucide="arrow-left"></i> Công
            nợ</a>
    </div>
    <h2 class="panel-title mb-3">Giao dịch tài chính mới</h2>
    <div class="card table-card mb-4">
        <div class="table-responsive">
            <table class="table mb-0">
                <thead>
                    <tr>
                        <th>Khoản thu</th>
                        <th>Kỳ</th>
                        <th>Phải thu</th>
                        <th>Đã thu</th>
                        <th>Còn nợ</th>
                        <th>Trạng thái</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($assessments as $a)
                        <tr>
                            <td>{{ $a->fee_category_name_snapshot }}<small
                                    class="d-block text-secondary">{{ $a->assessment_code }}</small></td>
                            <td>{{ $a->period_key }}</td>
                            <td>{{ number_format((int) $a->final_amount, 0, ',', '.') }} ₫</td>
                            <td>{{ number_format((int) $a->paid_amount, 0, ',', '.') }} ₫</td>
                            <td>{{ number_format((int) $a->remaining_amount, 0, ',', '.') }} ₫</td>
                            <td>{{ \App\Support\FinanceLabels::feeStatus($a->status) }}</td>
                            <td><a href="{{ route('admin.finance.fees.show', $a) }}" class="btn btn-sm btn-light"><i
                                        data-lucide="eye"></i></a></td>
                    </tr>@empty<tr>
                            <td colspan="7" class="empty-state">Chưa có khoản phải thu mới.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($assessments->hasPages())
            <div class="p-3 border-top">{{ $assessments->links('pagination::bootstrap-5') }}</div>
        @endif
    </div>
    <div class="d-flex align-items-center gap-2 mb-3">
        <h2 class="panel-title mb-0">Legacy transactions</h2><span class="badge text-bg-warning">Không tính vào báo cáo
            mới</span>
    </div>
    <div class="card table-card">
        <div class="table-responsive">
            <table class="table mb-0">
                <thead>
                    <tr>
                        <th>Khoản cũ</th>
                        <th>Số tiền</th>
                        <th>Trạng thái gốc</th>
                        <th>Thời điểm gốc</th>
                        <th>Cảnh báo</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($legacyPayments as $p)
                        @php($warning = ($p->status !== 'paid' && $p->paid_at) || ($p->feeNotice?->semester && $p->feeNotice?->due_on && $p->feeNotice->due_on->lt($p->feeNotice->semester->starts_on)))
                        <tr>
                            <td>{{ $p->feeNotice?->title ?: 'Khoản legacy không tồn tại master' }}<small
                                    class="d-block text-secondary">Legacy #{{ $p->id }}</small></td>
                            <td>{{ number_format((int) $p->amount, 0, ',', '.') }} ₫</td>
                            <td>{{ $p->status === 'paid' ? 'Đã thanh toán' : ($p->status === 'pending' ? 'Chờ xác nhận' : ($p->status === 'unpaid' ? 'Chưa thanh toán' : 'Đã hoàn tiền')) }}
                            </td>
                            <td>{{ $p->paid_at?->format('d/m/Y H:i') ?: '-' }}</td>
                            <td>
                                @if ($warning)
                                <span class="badge text-bg-warning">Legacy / Cảnh báo dữ liệu</span>@else<span
                                        class="badge text-bg-secondary">Giao dịch legacy</span>
                                @endif
                            </td>
                    </tr>@empty<tr>
                            <td colspan="5" class="empty-state">Không có dữ liệu legacy.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection

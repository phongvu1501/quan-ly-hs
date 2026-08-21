@extends('admin.layouts.app')

@section('title', 'Lịch sử đóng tiền')

@section('content')
    <div class="page-heading page-heading-inline">
        <div>
            <p class="eyebrow">Tài chính học sinh</p>
            <h1>Lịch sử đóng tiền</h1>
            <p class="text-secondary mb-0">{{ $student->full_name }} · {{ $student->student_code }} · {{ $year?->name }}</p>
        </div><a href="{{ route('student.dashboard') }}" class="btn btn-light"><i data-lucide="arrow-left"></i> Về cổng học
            sinh</a>
    </div>
    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="overview-item"><span class="overview-icon"><i data-lucide="wallet-cards"></i></span>
                <h2>Tổng đã đóng</h2>
                <p class="fs-3 fw-bold text-dark mb-0">
                    {{ number_format((float) $payments->where('status', 'paid')->sum('amount'), 0, ',', '.') }} đ</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="overview-item"><span class="overview-icon"><i data-lucide="receipt"></i></span>
                <h2>Số giao dịch</h2>
                <p class="fs-3 fw-bold text-dark mb-0">{{ $payments->where('status', 'paid')->count() }}</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="overview-item"><span class="overview-icon"><i data-lucide="clock-3"></i></span>
                <h2>Khoản đang chờ</h2>
                <p class="fs-3 fw-bold text-dark mb-0">
                    {{ $notices->filter(fn($notice) => !$notice->payments->first() || $notice->payments->first()->status !== 'paid')->count() }}
                </p>
            </div>
        </div>
    </div>
    <div class="card table-card mb-4">
        <div class="card-body border-bottom">
            <h2 class="panel-title mb-0">Khoản thu hiện tại</h2>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Khoản thu</th>
                        <th>Hạn đóng</th>
                        <th>Số tiền</th>
                        <th>Trạng thái</th>
                        <th>Ngày đóng</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($notices as $notice)
                        @php($payment = $notice->payments->first())<tr>
                            <td><strong>{{ $notice->title }}</strong><small
                                    class="d-block text-secondary">{{ $notice->description }}</small></td>
                            <td>{{ $notice->due_on?->format('d/m/Y') ?: '-' }}</td>
                            <td>{{ number_format((float) ($payment?->amount ?: $notice->amount), 0, ',', '.') }} đ</td>
                            <td><span
                                    class="status-dot {{ $payment?->status === 'paid' ? 'status-on' : 'status-off' }}">{{ $payment?->status === 'paid' ? 'Đã đóng' : 'Chưa đóng' }}</span>
                            </td>
                            <td>{{ $payment?->paid_at?->format('d/m/Y H:i') ?: '-' }}</td>
                    </tr>@empty<tr>
                            <td colspan="5" class="empty-state">Không có khoản thu hiện tại.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="card table-card">
        <div class="card-body border-bottom">
            <h2 class="panel-title mb-0">Lịch sử giao dịch</h2>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Thời gian</th>
                        <th>Khoản thu</th>
                        <th>Mã giao dịch</th>
                        <th>Số tiền</th>
                        <th>Trạng thái</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($payments as $payment)
                        <tr>
                            <td>{{ $payment->paid_at?->format('d/m/Y H:i') ?: '-' }}</td>
                            <td><strong>{{ $payment->feeNotice?->title ?: 'Khoản thu đã xóa' }}</strong><small
                                    class="d-block text-secondary">{{ $payment->feeNotice?->academicYear?->name }}</small>
                            </td>
                            <td><code>{{ $payment->transaction_code ?: '-' }}</code></td>
                            <td>{{ number_format((float) $payment->amount, 0, ',', '.') }} đ</td>
                            <td><span
                                    class="status-dot {{ $payment->status === 'paid' ? 'status-on' : 'status-off' }}">{{ ['paid' => 'Đã thanh toán', 'pending' => 'Chờ xác nhận', 'unpaid' => 'Chưa thanh toán', 'refunded' => 'Đã hoàn tiền'][$payment->status] ?? $payment->status }}</span>
                            </td>
                    </tr>@empty<tr>
                            <td colspan="5" class="empty-state">Chưa có lịch sử giao dịch.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection

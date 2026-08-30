@extends('admin.layouts.app')
@section('title', 'Chi tiết khoản phải thu')
@section('content')
    <div class="page-heading page-heading-inline">
        <div>
            <p class="eyebrow">Giao dịch tài chính mới</p>
            <h1>{{ $feeAssessment->student_name_snapshot }}</h1>
            <p class="text-secondary mb-0">{{ $feeAssessment->assessment_code }} ·
                {{ $feeAssessment->fee_notice_title_snapshot }}</p>
        </div><a href="{{ route('admin.finance.fees.index') }}" class="btn btn-light"><i data-lucide="arrow-left"></i> Công
            nợ</a>
    </div>
    <div class="row g-3 mb-4">
        @foreach (['original_amount' => 'Mức gốc', 'discount_amount' => 'Miễn giảm', 'final_amount' => 'Phải thu', 'paid_amount' => 'Đã thu', 'remaining_amount' => 'Còn nợ'] as $field => $label)
            <div class="col-sm-6 col-xl">
                <div class="overview-item">
                    <h2>{{ $label }}</h2>
                    <p class="fs-5 fw-bold mb-0">{{ number_format((int) $feeAssessment->$field, 0, ',', '.') }} ₫</p>
                </div>
            </div>
        @endforeach
    </div>
    <div class="row g-4">
        <div class="col-xl-7">
            <div class="card table-card mb-4">
                <div class="card-body border-bottom">
                    <h2 class="panel-title mb-0">Snapshot miễn giảm</h2>
                </div>
                <div class="table-responsive">
                    <table class="table mb-0">
                        <thead>
                            <tr>
                                <th>Chính sách</th>
                                <th>Loại</th>
                                <th>Giá trị</th>
                                <th>Giảm</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($feeAssessment->discounts as $d)
                                <tr>
                                    <td><strong>{{ $d->policy_name }}</strong><small
                                            class="d-block text-secondary">{{ $d->policy_code }} · ưu tiên
                                            {{ $d->priority }}</small></td>
                                    <td>{{ \App\Support\FinanceLabels::reductionType($d->reduction_type) }} /
                                        {{ \App\Support\FinanceLabels::applicationMode($d->application_mode) }}</td>
                                    <td>{{ $d->reduction_value }}</td>
                                    <td>{{ number_format((int) $d->calculated_discount, 0, ',', '.') }} ₫</td>
                            </tr>@empty<tr>
                                    <td colspan="4" class="empty-state">Không áp dụng miễn giảm.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card table-card">
                <div class="card-body border-bottom">
                    <h2 class="panel-title mb-0">Lịch sử thanh toán mới</h2>
                </div>
                <div class="table-responsive">
                    <table class="table mb-0">
                        <thead>
                            <tr>
                                <th>Mã</th>
                                <th>Ngày</th>
                                <th>Số tiền</th>
                                <th>Phương thức</th>
                                <th>Người thu</th>
                                <th>Trạng thái</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($feeAssessment->payments->sortByDesc('payment_date') as $p)
                                <tr>
                                    <td><code>{{ $p->payment_code }}</code></td>
                                    <td>{{ $p->payment_date?->format('d/m/Y H:i') }}</td>
                                    <td>{{ number_format((int) $p->amount, 0, ',', '.') }} ₫</td>
                                    <td>{{ $p->payment_method }}</td>
                                    <td>{{ $p->createdBy?->name ?: '-' }}</td>
                                    <td>{{ $p->status }}</td>
                            </tr>@empty<tr>
                                    <td colspan="6" class="empty-state">Chưa có thanh toán.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-xl-5">
            @if (auth()->user()->hasPermission('fees.pay') && !in_array($feeAssessment->status, ['paid', 'cancelled']))
                <div class="card form-card mb-4">
                    <div class="card-body">
                        <h2 class="panel-title">Ghi nhận thanh toán</h2>
                        <form method="POST" action="{{ route('admin.finance.fees.payments.store', $feeAssessment) }}">@csrf
                            <div class="mb-3"><label class="form-label">Số tiền</label><input type="number"
                                    name="amount" min="0.01" step="0.01"
                                    max="{{ $feeAssessment->remaining_amount }}" class="form-control" required></div>
                            <div class="mb-3"><label class="form-label">Ngày thu</label><input type="datetime-local"
                                    name="payment_date" value="{{ now()->format('Y-m-d\TH:i') }}" class="form-control"
                                    required></div>
                            <div class="mb-3"><label class="form-label">Phương thức</label><select name="payment_method"
                                    class="form-select">
                                    @foreach (['cash' => 'Tiền mặt', 'bank_transfer' => 'Chuyển khoản', 'online' => 'Online', 'other' => 'Khác'] as $v => $l)
                                        <option value="{{ $v }}">{{ $l }}</option>
                                    @endforeach
                                </select></div>
                            <div class="mb-3"><label class="form-label">Số biên lai</label><input name="receipt_number"
                                    class="form-control"></div><button class="btn btn-primary w-100"><i
                                    data-lucide="badge-dollar-sign"></i> Thu tiền</button>
                        </form>
                    </div>
                </div>
            @endif
            <div class="card">
                <div class="card-body">
                    <h2 class="panel-title">Thông tin snapshot</h2>
                    <dl class="row mb-0">
                        <dt class="col-5">Học sinh</dt>
                        <dd class="col-7">{{ $feeAssessment->student_code_snapshot }}</dd>
                        <dt class="col-5">Lớp</dt>
                        <dd class="col-7">{{ $feeAssessment->class_name_snapshot ?: '-' }}</dd>
                        <dt class="col-5">Khoản thu</dt>
                        <dd class="col-7">{{ $feeAssessment->fee_category_name_snapshot }}</dd>
                        <dt class="col-5">Kỳ</dt>
                        <dd class="col-7">{{ $feeAssessment->period_key }}</dd>
                        <dt class="col-5">Trạng thái</dt>
                        <dd class="col-7">{{ \App\Support\FinanceLabels::feeStatus($feeAssessment->status) }}</dd>
                    </dl>
                </div>
            </div>
            @if (auth()->user()->hasPermission('fees.cancel') && !in_array($feeAssessment->status, ['paid', 'cancelled']))
                <form method="POST" action="{{ route('admin.finance.fees.cancel', $feeAssessment) }}" class="mt-3">@csrf
                    <div class="input-group"><input name="reason" class="form-control" placeholder="Lý do hủy"
                            required><button class="btn btn-outline-danger"><i data-lucide="ban"></i> Hủy khoản thu</button>
                    </div>
                </form>
            @endif
        </div>
    </div>
@endsection

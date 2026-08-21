@extends('admin.layouts.app')

@section('title', 'Lịch sử đóng tiền học sinh')

@section('content')
    <div class="page-heading page-heading-inline">
        <div>
            <p class="eyebrow">Tài chính học sinh</p>
            <h1>{{ $student->full_name }}</h1>
            <p class="text-secondary mb-0">{{ $student->student_code }} · Lịch sử đóng tiền qua các năm học</p>
        </div><a href="{{ route('admin.school.students.index') }}" class="btn btn-light"><i data-lucide="arrow-left"></i> Danh
            sách học sinh</a>
    </div>
    <div class="row g-4">
        <div class="col-xl-4">
            <div class="card form-card">
                <div class="card-body">
                    <div class="section-label">Cập nhật khoản đóng</div>
                    <form action="{{ route('admin.school.students.payments.store', $student) }}" method="POST">@csrf
                        <div class="mb-3"><label class="form-label" for="fee_notice_id">Khoản thu</label><select
                                id="fee_notice_id" name="fee_notice_id" class="form-select" required>
                                <option value="">Chọn khoản thu</option>
                                @foreach ($notices as $notice)
                                    <option value="{{ $notice->id }}" data-amount="{{ $notice->amount }}"
                                        @selected(old('fee_notice_id') == $notice->id)>{{ $notice->title }} ·
                                        {{ $notice->academicYear?->name ?: $year?->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3"><label class="form-label" for="amount">Số tiền</label><input id="amount"
                                type="number" min="0" step="1000" name="amount" class="form-control"
                                value="{{ old('amount') }}" required></div>
                        <div class="mb-3"><label class="form-label" for="status">Trạng thái</label><select
                                id="status" name="status" class="form-select" required>
                                @foreach (['paid' => 'Đã thanh toán', 'pending' => 'Chờ xác nhận', 'unpaid' => 'Chưa thanh toán', 'refunded' => 'Đã hoàn tiền'] as $value => $label)
                                    <option value="{{ $value }}" @selected(old('status', 'paid') === $value)>{{ $label }}
                                    </option>
                                @endforeach
                            </select></div>
                        <div class="mb-3"><label class="form-label" for="paid_at">Thời gian thanh toán</label><input
                                id="paid_at" type="datetime-local" name="paid_at" class="form-control"
                                value="{{ old('paid_at') }}"></div>
                        <div class="mb-3"><label class="form-label" for="transaction_code">Mã giao dịch/biên
                                lai</label><input id="transaction_code" name="transaction_code" class="form-control"
                                value="{{ old('transaction_code') }}"></div>
                        <div class="mb-3"><label class="form-label" for="note">Ghi chú</label>
                            <textarea id="note" name="note" class="form-control" rows="3">{{ old('note') }}</textarea>
                        </div>
                        <button class="btn btn-primary w-100"><i data-lucide="save"></i> Lưu khoản đóng</button>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-xl-8">
            <div class="card table-card">
                <div class="card-body border-bottom d-flex justify-content-between">
                    <h2 class="panel-title mb-0">Lịch sử giao dịch</h2>
                    <strong>{{ number_format((float) $student->payments->where('status', 'paid')->sum('amount'), 0, ',', '.') }}
                        đ đã đóng</strong>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Ngày</th>
                                <th>Khoản thu</th>
                                <th>Năm học</th>
                                <th>Mã giao dịch</th>
                                <th>Số tiền</th>
                                <th>Trạng thái</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($student->payments->sortByDesc('paid_at') as $payment)
                                <tr>
                                    <td>{{ $payment->paid_at?->format('d/m/Y H:i') ?: '-' }}</td>
                                    <td><strong>{{ $payment->feeNotice?->title ?: '-' }}</strong></td>
                                    <td>{{ $payment->feeNotice?->academicYear?->name ?: '-' }}</td>
                                    <td><code>{{ $payment->transaction_code ?: '-' }}</code></td>
                                    <td>{{ number_format((float) $payment->amount, 0, ',', '.') }} đ</td>
                                    <td><span
                                            class="status-dot {{ $payment->status === 'paid' ? 'status-on' : 'status-off' }}">{{ ['paid' => 'Đã thanh toán', 'pending' => 'Chờ xác nhận', 'unpaid' => 'Chưa thanh toán', 'refunded' => 'Đã hoàn tiền'][$payment->status] ?? $payment->status }}</span>
                                    </td>
                            </tr>@empty<tr>
                                    <td colspan="6" class="empty-state">Học sinh chưa có giao dịch.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const notice = document.getElementById('fee_notice_id');
            const amount = document.getElementById('amount');
            notice.addEventListener('change', function() {
                const selected = notice.selectedOptions[0];
                if (selected?.dataset.amount) amount.value = Math.round(Number(selected.dataset.amount));
            });
        });
    </script>
@endsection

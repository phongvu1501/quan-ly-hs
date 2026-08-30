@extends('admin.layouts.app')
@section('title', 'Chi tiết bảng lương')
@section('content')<div class="page-heading page-heading-inline">
        <div>
            <p class="eyebrow">Bản chụp bảng lương</p>
            <h1>{{ $payroll->teacher_name_snapshot }}</h1>
            <p class="text-secondary mb-0">{{ $payroll->payroll_code }} ·
                {{ str_pad($payroll->payroll_month, 2, '0', STR_PAD_LEFT) }}/{{ $payroll->payroll_year }} ·
                {{ \App\Support\FinanceLabels::payrollStatus($payroll->status) }}</p>
        </div><a href="{{ route('admin.finance.payrolls.index') }}" class="btn btn-light"><i data-lucide="arrow-left"></i> Bảng
            lương</a>
    </div>
    <div class="d-flex flex-wrap gap-2 mb-4">
        @if (in_array($payroll->status, ['draft', 'calculated']) && auth()->user()->hasPermission('payrolls.calculate'))
            <form method="POST" action="{{ route('admin.finance.payrolls.calculate', $payroll) }}">@csrf<button
                    class="btn btn-light"><i data-lucide="calculator"></i> Tính lại</button></form>
            @endif @if ($payroll->status === 'calculated' && auth()->user()->hasPermission('payrolls.approve'))
                <form method="POST" action="{{ route('admin.finance.payrolls.approve', $payroll) }}">@csrf<button
                        class="btn btn-primary"><i data-lucide="check"></i> Duyệt</button></form>
                @endif @if ($payroll->status === 'approved' && auth()->user()->hasPermission('payrolls.lock'))
                    <form method="POST" action="{{ route('admin.finance.payrolls.lock', $payroll) }}">@csrf<button
                            class="btn btn-primary"><i data-lucide="lock"></i> Chốt bảng lương</button></form>
                @endif
    </div>
    <div class="row g-3 mb-4">
        @foreach (['gross_salary' => 'Gross', 'total_deductions' => 'Khấu trừ', 'net_salary' => 'Net', 'paid_amount' => 'Đã trả', 'remaining_amount' => 'Còn trả'] as $f => $l)
            <div class="col-sm-6 col-xl">
                <div class="overview-item">
                    <h2>{{ $l }}</h2>
                    <p class="fs-5 fw-bold mb-0">{{ number_format((int) $payroll->$f, 0, ',', '.') }} ₫</p>
                </div>
            </div>
        @endforeach
    </div>
    <div class="card table-card mb-4">
        <div class="card-body border-bottom">
            <h2 class="panel-title mb-0">Chi tiết hình thành lương</h2>
        </div>
        <div class="table-responsive">
            <table class="table mb-0">
                <thead>
                    <tr>
                        <th>Thành phần</th>
                        <th>Loại</th>
                        <th>Cách tính</th>
                        <th>Số lượng</th>
                        <th>Đơn giá / %</th>
                        <th>Môn / lớp</th>
                        <th>Số tiền</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($payroll->items->sortBy('display_order') as $i)
                        <tr>
                            <td><strong>{{ $i->component_name }}</strong><small
                                    class="d-block"><code>{{ $i->component_code }}</code></small></td>
                            <td>{{ \App\Support\FinanceLabels::componentType($i->type) }} /
                                {{ \App\Support\FinanceLabels::category($i->category) }}</td>
                            <td>{{ \App\Support\FinanceLabels::calculationType($i->calculation_type) }}</td>
                            <td>{{ $i->quantity ?: '-' }}</td>
                            <td>{{ $i->rate !== null ? number_format((int) $i->rate, 0, ',', '.') . ' ₫' : ($i->percentage_value !== null ? $i->percentage_value . '%' : '-') }}
                            </td>
                            <td>{{ $i->subject_name_snapshot ?: '-' }}<small
                                    class="d-block">{{ $i->class_name_snapshot }}</small></td>
                            <td>{{ number_format((int) $i->amount, 0, ',', '.') }} ₫</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    <div class="row g-4">
        <div class="col-xl-7">
            <div class="card table-card">
                <div class="card-body border-bottom">
                    <h2 class="panel-title mb-0">Thanh toán lương</h2>
                </div>
                <div class="table-responsive">
                    <table class="table mb-0">
                        <thead>
                            <tr>
                                <th>Mã</th>
                                <th>Ngày</th>
                                <th>Số tiền</th>
                                <th>Phương thức</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($payroll->payments as $p)
                                <tr>
                                    <td><code>{{ $p->payment_code }}</code></td>
                                    <td>{{ $p->payment_date?->format('d/m/Y H:i') }}</td>
                                    <td>{{ number_format((int) $p->amount, 0, ',', '.') }} ₫</td>
                                    <td>{{ $p->payment_method }}</td>
                                    <td>{{ $p->status }}</td>
                            </tr>@empty<tr>
                                    <td colspan="5" class="empty-state">Chưa thanh toán.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-xl-5">
            @if ($payroll->status === 'locked' && auth()->user()->hasPermission('payrolls.pay'))
                <form method="POST" action="{{ route('admin.finance.payrolls.payments.store', $payroll) }}"
                    class="card form-card mb-4">@csrf<div class="card-body">
                        <h2 class="panel-title">Thanh toán</h2>
                        <div class="mb-3"><label class="form-label">Số tiền</label><input name="amount"
                                value="{{ $payroll->remaining_amount }}" class="form-control"></div>
                        <div class="mb-3"><label class="form-label">Ngày</label><input type="datetime-local"
                                name="payment_date" value="{{ now()->format('Y-m-d\TH:i') }}" class="form-control"></div>
                        <div class="mb-3"><label class="form-label">Phương thức</label><select name="payment_method"
                                class="form-select">
                                <option value="bank_transfer">Chuyển khoản</option>
                                <option value="cash">Tiền mặt</option>
                                <option value="other">Khác</option>
                            </select></div><button class="btn btn-primary w-100"><i data-lucide="badge-dollar-sign"></i> Ghi
                            nhận</button>
                    </div>
                </form>
                @endif @if (in_array($payroll->status, ['locked', 'paid']) && auth()->user()->hasPermission('payrolls.adjust'))
                    <form method="POST" action="{{ route('admin.finance.payrolls.adjustments.store', $payroll) }}"
                        class="card form-card">@csrf<div class="card-body">
                            <h2 class="panel-title">Adjustment</h2><select name="salary_component_id"
                                class="form-select mb-2">
                                @foreach ($adjustmentComponents as $c)
                                    <option value="{{ $c->id }}">{{ $c->name }}</option>
                                @endforeach
                            </select>
                            <input name="amount" class="form-control mb-2" placeholder="Số tiền">
                            <textarea name="reason" class="form-control mb-2" placeholder="Lý do" required></textarea><button class="btn btn-light w-100"><i data-lucide="file-plus-2"></i>
                                Tạo adjustment riêng</button>
                        </div>
                    </form>
                @endif
        </div>
    </div>
    <div class="card table-card mt-4">
        <div class="card-body border-bottom">
            <h2 class="panel-title mb-0">Adjustment history</h2>
        </div>
        <div class="table-responsive">
            <table class="table mb-0">
                <thead>
                    <tr>
                        <th>Mã</th>
                        <th>Component</th>
                        <th>Loại</th>
                        <th>Số tiền</th>
                        <th>Lý do</th>
                        <th>Status</th>
                        <th>Workflow</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($payroll->adjustments as $a)
                        <tr>
                            <td><code>{{ $a->adjustment_code }}</code></td>
                            <td>{{ $a->component_name_snapshot }}</td>
                            <td>{{ $a->type }}</td>
                            <td>{{ number_format((int) $a->amount, 0, ',', '.') }} ₫</td>
                            <td>{{ $a->reason }}</td>
                            <td>{{ $a->status }}</td>
                            <td>
                                @if (auth()->user()->hasPermission('payrolls.adjust'))
                                    <div class="d-flex gap-1">
                                        @if ($a->status === 'draft')
                                            <form method="POST"
                                                action="{{ route('admin.finance.payroll-adjustments.approve', $a) }}">
                                                @csrf<button class="btn btn-sm btn-light">Duyệt</button></form>
                                        @elseif($a->status === 'approved')
                                            <form method="POST"
                                                action="{{ route('admin.finance.payroll-adjustments.lock', $a) }}">
                                                @csrf<button class="btn btn-sm btn-light">Khóa</button></form>
                                        @elseif($a->status === 'locked')
                                            <form method="POST"
                                                action="{{ route('admin.finance.payroll-adjustments.settle', $a) }}">
                                                @csrf<input type="hidden" name="settlement_method"
                                                    value="bank_transfer"><button class="btn btn-sm btn-primary">Quyết
                                                    toán</button></form>
                                        @endif
                                    </div>
                                @endif
                            </td>
                    </tr>@empty<tr>
                            <td colspan="7" class="empty-state">Chưa có adjustment.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection

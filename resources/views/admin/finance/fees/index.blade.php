@extends('admin.layouts.app')
@section('title', 'Công nợ học sinh')
@section('content')
    <div class="page-heading page-heading-inline">
        <div>
            <p class="eyebrow">Tài chính học sinh</p>
            <h1>Công nợ học sinh</h1>
            <p class="text-secondary mb-0">Chỉ gồm giao dịch mới dựa trên fee assessment.</p>
        </div>
        @if (auth()->user()->hasPermission('fees.generate'))
            <a href="{{ route('admin.finance.fees.generate.create') }}" class="btn btn-primary"><i data-lucide="plus"></i> Phát
                sinh học phí</a>
        @endif
    </div>
    @include('admin.finance.partials.nav')
    <form method="GET" class="card filter-card mb-4">
        <div class="card-body row g-3 align-items-end">
            <div class="col-md-3"><label class="form-label">Học sinh</label><input name="q" value="{{ request('q') }}"
                    class="form-control" placeholder="Mã hoặc họ tên"></div>
            <div class="col-md-2"><label class="form-label">Năm học</label><select name="academic_year_id"
                    class="form-select">
                    <option value="">Tất cả</option>
                    @foreach ($years as $year)
                        <option value="{{ $year->id }}" @selected(request('academic_year_id') == $year->id)>{{ $year->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2"><label class="form-label">Tháng</label><input type="month" name="month"
                    value="{{ request('month') }}" class="form-control"></div>
            <div class="col-md-2"><label class="form-label">Trạng thái</label><select name="status" class="form-select">
                    <option value="">Tất cả</option>
                    @foreach (['pending' => 'Chưa đóng', 'partially_paid' => 'Đóng thiếu', 'paid' => 'Đã tất toán', 'overdue' => 'Quá hạn', 'cancelled' => 'Đã hủy'] as $v => $l)
                        <option value="{{ $v }}" @selected(request('status') === $v)>{{ $l }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3"><button class="btn btn-primary"><i data-lucide="filter"></i> Lọc</button> <a
                    href="{{ route('admin.finance.fees.index') }}" class="btn btn-light">Đặt lại</a></div>
        </div>
    </form>
    <div class="card table-card">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Học sinh</th>
                        <th>Lớp / khoản thu</th>
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
                            <td><a
                                    href="{{ route('admin.finance.fees.students.show', $a->student_id) }}"><strong>{{ $a->student_name_snapshot }}</strong></a><small
                                    class="d-block text-secondary">{{ $a->student_code_snapshot }}</small></td>
                            <td>{{ $a->class_name_snapshot ?: '-' }}<small
                                    class="d-block text-secondary">{{ $a->fee_category_name_snapshot }}</small></td>
                            <td>{{ $a->period_key }}<small class="d-block text-secondary">Hạn
                                    {{ $a->due_date?->format('d/m/Y') ?: '-' }}</small></td>
                            <td>{{ number_format((int) $a->final_amount, 0, ',', '.') }} ₫</td>
                            <td>{{ number_format((int) $a->paid_amount, 0, ',', '.') }} ₫</td>
                            <td><strong>{{ number_format((int) $a->remaining_amount, 0, ',', '.') }} ₫</strong></td>
                            <td><span
                                    class="status-dot {{ $a->status === 'paid' ? 'status-on' : 'status-off' }}">{{ \App\Support\FinanceLabels::feeStatus($a->status) }}</span>
                            </td>
                            <td><a class="btn btn-sm btn-light" href="{{ route('admin.finance.fees.show', $a) }}"
                                    title="Chi tiết"><i data-lucide="eye"></i></a></td>
                        </tr>
                    @empty<tr>
                            <td colspan="8" class="empty-state">Chưa có fee assessment.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($assessments->hasPages())
            <div class="p-3 border-top">{{ $assessments->links('pagination::bootstrap-5') }}</div>
        @endif
    </div>
    <div class="alert alert-warning mt-4"><strong>Hệ thống tách biệt:</strong> dữ liệu cũ không được tính trong bảng trên. <a
            href="{{ route('admin.school.fees.index') }}">Mở lịch sử khoản thu hệ thống</a>.</div>
@endsection

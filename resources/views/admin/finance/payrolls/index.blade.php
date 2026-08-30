@extends('admin.layouts.app')
@section('title', 'Bảng lương giáo viên')
@section('content')<div class="page-heading page-heading-inline">
        <div>
            <p class="eyebrow">Lương giáo viên</p>
            <h1>Bảng lương tháng</h1>
        </div>
        @if (auth()->user()->hasPermission('payrolls.calculate'))
            <a href="{{ route('admin.finance.payrolls.generate.create') }}" class="btn btn-primary"><i data-lucide="play"></i>
                Tạo bảng lương</a>
        @endif
    </div>
    @include('admin.finance.partials.nav')<form method="GET" class="card filter-card mb-4">
        <div class="card-body row g-3 align-items-end">
            <div class="col-md-3"><label class="form-label">Giáo viên</label><input name="q" value="{{ request('q') }}"
                    class="form-control" placeholder="Mã hoặc họ tên"></div>
            <div class="col-md-2"><label class="form-label">Năm</label><input name="year" type="number"
                    value="{{ request('year', now()->year) }}" class="form-control"></div>
            <div class="col-md-2"><label class="form-label">Tháng</label><input name="month" type="number" min="1"
                    max="12" value="{{ request('month', now()->month) }}" class="form-control"></div>
            <div class="col-md-2"><label class="form-label">Trạng thái</label><select name="status" class="form-select">
                    <option value="">Tất cả</option>
                    @foreach (['draft', 'calculated', 'approved', 'locked', 'paid', 'cancelled'] as $v)
                        <option value="{{ $v }}" @selected(request('status') === $v)>
                            {{ \App\Support\FinanceLabels::payrollStatus($v) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3"><button class="btn btn-primary"><i data-lucide="filter"></i> Lọc</button></div>
        </div>
    </form>
    <div class="card table-card">
        <div class="table-responsive">
            <table class="table mb-0">
                <thead>
                    <tr>
                        <th>Giáo viên</th>
                        <th>Kỳ</th>
                        <th>Lương cứng</th>
                        <th>Phụ cấp</th>
                        <th>Dạy thêm</th>
                        <th>Tổng thu nhập</th>
                        <th>Khấu trừ</th>
                        <th>Thực nhận</th>
                        <th>Trạng thái</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($payrolls as $p)
                        <tr>
                            <td><strong>{{ $p->teacher_name_snapshot }}</strong><small
                                    class="d-block">{{ $p->teacher_code_snapshot }} ·
                                    {{ $p->specialization_snapshot }}</small></td>
                            <td>{{ str_pad($p->payroll_month, 2, '0', STR_PAD_LEFT) }}/{{ $p->payroll_year }}</td>
                            <td>{{ number_format((int) $p->base_salary, 0, ',', '.') }} ₫</td>
                            <td>{{ number_format((int) $p->total_allowances, 0, ',', '.') }} ₫</td>
                            <td>{{ number_format((int) $p->total_teaching_extra, 0, ',', '.') }} ₫</td>
                            <td>{{ number_format((int) $p->gross_salary, 0, ',', '.') }} ₫</td>
                            <td>{{ number_format((int) $p->total_deductions, 0, ',', '.') }} ₫</td>
                            <td><strong>{{ number_format((int) $p->net_salary, 0, ',', '.') }} ₫</strong></td>
                            <td>{{ \App\Support\FinanceLabels::payrollStatus($p->status) }}</td>
                            <td><a href="{{ route('admin.finance.payrolls.show', $p) }}" class="btn btn-sm btn-light"><i
                                        data-lucide="eye"></i></a></td>
                    </tr>@empty<tr>
                            <td colspan="10" class="empty-state">Chưa có bảng lương.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($payrolls->hasPages())
            <div class="p-3 border-top">{{ $payrolls->links('pagination::bootstrap-5') }}</div>
        @endif
</div>@endsection

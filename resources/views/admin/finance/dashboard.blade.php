@extends('admin.layouts.app')
@section('title', 'Tổng quan tài chính')
@section('content')
    <div class="page-heading">
        <p class="eyebrow">Tài chính nhà trường</p>
        <h1>Tổng quan tài chính</h1>
    </div>
    @include('admin.finance.partials.nav')
    <form method="GET" class="card filter-card mb-4">
        <div class="card-body row g-3 align-items-end">
            <div class="col-md-3"><label class="form-label">Năm học</label><select name="academic_year_id" class="form-select">
                    <option value="">Tất cả</option>
                    @foreach ($years as $year)
                        <option value="{{ $year->id }}" @selected(request('academic_year_id') == $year->id)>{{ $year->name }}</option>
                    @endforeach
                </select></div>
            <div class="col-md-2"><label class="form-label">Tháng phí</label><input type="month" name="month"
                    value="{{ request('month') }}" class="form-control"></div>
            <div class="col-md-2"><label class="form-label">Tháng lương</label><input type="number" name="payroll_month"
                    min="1" max="12" value="{{ request('payroll_month', now()->month) }}" class="form-control">
            </div>
            <div class="col-md-2"><label class="form-label">Năm lương</label><input type="number" name="payroll_year"
                    value="{{ request('payroll_year', now()->year) }}" class="form-control"></div>
            <div class="col-md-3"><button class="btn btn-primary"><i data-lucide="filter"></i> Áp dụng</button></div>
        </div>
    </form>
    <h2 class="panel-title mb-3">Học phí</h2>
    <div class="row g-3 mb-4">
        @foreach (['receivable' => 'Phải thu', 'collected' => 'Đã thu', 'remaining' => 'Còn nợ', 'discount' => 'Miễn giảm'] as $key => $label)
            <div class="col-sm-6 col-xl-3">
                <div class="overview-item">
                    <h2>{{ $label }}</h2>
                    <p class="fs-4 fw-bold mb-0">{{ number_format((int) $feeStats[$key], 0, ',', '.') }} ₫</p>
                </div>
            </div>
        @endforeach
    </div>
    <div class="row g-3 mb-5">
        @foreach (['fullyPaidStudents' => 'Học sinh đóng đủ', 'partialStudents' => 'Đóng thiếu', 'unpaidStudents' => 'Chưa đóng', 'overdue' => 'Khoản quá hạn'] as $key => $label)
            <div class="col-sm-6 col-xl-3">
                <div class="overview-item">
                    <h2>{{ $label }}</h2>
                    <p class="fs-4 fw-bold mb-0">{{ $feeStats[$key] }}</p>
                </div>
            </div>
        @endforeach
    </div>
    <h2 class="panel-title mb-3">Lương giáo viên</h2>
    <div class="row g-3">
        @foreach (['gross' => 'Gross', 'deductions' => 'Khấu trừ', 'tax' => 'Thuế', 'insurance' => 'Bảo hiểm', 'teachingExtra' => 'Dạy thêm', 'allowances' => 'Phụ cấp', 'net' => 'Thực nhận'] as $key => $label)
            <div class="col-sm-6 col-xl-3">
                <div class="overview-item">
                    <h2>{{ $label }}</h2>
                    <p class="fs-5 fw-bold mb-0">{{ number_format((int) $payrollStats[$key], 0, ',', '.') }} ₫</p>
                </div>
            </div>
        @endforeach
        <div class="col-sm-6 col-xl-3">
            <div class="overview-item">
                <h2>Đã nhận / chưa nhận</h2>
                <p class="fs-5 fw-bold mb-0">{{ $payrollStats['paidTeachers'] }} / {{ $payrollStats['unpaidTeachers'] }}
                </p>
            </div>
        </div>
    </div>
@endsection

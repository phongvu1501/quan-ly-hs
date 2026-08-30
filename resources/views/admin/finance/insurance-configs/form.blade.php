@extends('admin.layouts.app')
@php($editing = $insuranceConfig !== null)
@section('title', $editing ? 'Sửa bảo hiểm' : 'Thêm bảo hiểm')
@section('content')<div class="page-heading">
        <p class="eyebrow">Bảo hiểm</p>
        <h1>{{ $editing ? 'Sửa cấu hình bảo hiểm' : 'Thêm cấu hình bảo hiểm' }}</h1>
    </div>
    <form method="POST"
        action="{{ $editing ? route('admin.finance.insurance-configs.update', $insuranceConfig) : route('admin.finance.insurance-configs.store') }}"
        class="card form-card">@csrf @if ($editing)
            @method('PUT')
        @endif
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3"><label class="form-label">Mã</label><input name="code"
                        value="{{ old('code', $insuranceConfig?->code) }}" class="form-control" required></div>
                <div class="col-md-5"><label class="form-label">Tên</label><input name="name"
                        value="{{ old('name', $insuranceConfig?->name) }}" class="form-control" required></div>
                <div class="col-md-2"><label class="form-label">Tỷ lệ NLĐ %</label><input name="employee_rate_percentage"
                        value="{{ old('employee_rate_percentage', $insuranceConfig?->employee_rate_percentage ?? 0) }}"
                        class="form-control"></div>
                <div class="col-md-2"><label class="form-label">Tỷ lệ đơn vị %</label><input name="employer_rate_percentage"
                        value="{{ old('employer_rate_percentage', $insuranceConfig?->employer_rate_percentage ?? 0) }}"
                        class="form-control"></div>
                <div class="col-md-4"><label class="form-label">Nền lương</label><select name="salary_base_type"
                        class="form-select">
                        @foreach (['base_salary', 'gross', 'insurance_earnings', 'fixed'] as $v)
                            <option @selected(old('salary_base_type', $insuranceConfig?->salary_base_type) === $v)>{{ $v }}</option>
                        @endforeach
                    </select></div>
                <div class="col-md-4"><label class="form-label">Nền cố định</label><input name="fixed_salary_base"
                        value="{{ old('fixed_salary_base', $insuranceConfig?->fixed_salary_base) }}" class="form-control">
                </div>
                <div class="col-md-4"><label class="form-label">Nền tối đa</label><input name="maximum_salary_base"
                        value="{{ old('maximum_salary_base', $insuranceConfig?->maximum_salary_base) }}"
                        class="form-control"></div>
                <div class="col-md-4"><label class="form-label">Từ ngày</label><input type="date" name="effective_from"
                        value="{{ old('effective_from', $insuranceConfig?->effective_from?->toDateString()) }}"
                        class="form-control"></div>
                <div class="col-md-4"><label class="form-label">Đến ngày</label><input type="date" name="effective_to"
                        value="{{ old('effective_to', $insuranceConfig?->effective_to?->toDateString()) }}"
                        class="form-control"></div>
                <div class="col-md-4"><label class="form-check mt-4"><input type="checkbox" name="is_active" value="1"
                            class="form-check-input" @checked(old('is_active', $insuranceConfig?->is_active ?? true))> Active</label></div>
            </div>
            <div class="form-actions"><button class="btn btn-primary"><i data-lucide="save"></i> Lưu</button></div>
        </div>
</form>@endsection

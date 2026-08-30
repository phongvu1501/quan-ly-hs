@extends('admin.layouts.app')
@php($editing = $salaryComponent !== null)
@section('title', $editing ? 'Sửa thành phần lương' : 'Thêm thành phần lương')
@section('content')<div class="page-heading">
        <p class="eyebrow">Cấu hình lương</p>
        <h1>{{ $editing ? 'Sửa thành phần lương' : 'Thêm thành phần lương' }}</h1>
    </div>
    <form method="POST"
        action="{{ $editing ? route('admin.finance.salary-components.update', $salaryComponent) : route('admin.finance.salary-components.store') }}"
        class="card form-card">@csrf @if ($editing)
            @method('PUT')
        @endif
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4"><label class="form-label">Mã</label><input name="code"
                        value="{{ old('code', $salaryComponent?->code) }}" class="form-control" required></div>
                <div class="col-md-8"><label class="form-label">Tên</label><input name="name"
                        value="{{ old('name', $salaryComponent?->name) }}" class="form-control" required></div>
                <div class="col-md-4"><label class="form-label">Loại</label><select name="type" class="form-select">
                        <option value="earning" @selected(old('type', $salaryComponent?->type) === 'earning')>Thu nhập</option>
                        <option value="deduction" @selected(old('type', $salaryComponent?->type) === 'deduction')>Khấu trừ</option>
                    </select></div>
                <div class="col-md-4"><label class="form-label">Nhóm</label><input name="category"
                        value="{{ old('category', $salaryComponent?->category) }}" class="form-control"
                        placeholder="Ví dụ: phụ cấp, thưởng" required></div>
                <div class="col-md-4"><label class="form-label">Cách tính</label><select name="calculation_type"
                        class="form-select">
                        <option value="fixed">Số tiền cố định</option>
                        <option value="percentage">Theo phần trăm</option>
                        <option value="per_unit">Theo đơn vị</option>
                        <option value="manual">Nhập thủ công</option>
                    </select></div>
                <div class="col-md-4"><label class="form-check"><input type="checkbox" name="is_taxable" value="1"
                            class="form-check-input" @checked(old('is_taxable', $salaryComponent?->is_taxable))> Chịu thuế</label></div>
                <div class="col-md-4"><label class="form-check"><input type="checkbox" name="is_insurance_base"
                            value="1" class="form-check-input" @checked(old('is_insurance_base', $salaryComponent?->is_insurance_base))> Làm nền bảo hiểm</label>
                </div>
                <div class="col-md-4"><label class="form-check"><input type="checkbox" name="is_active" value="1"
                            class="form-check-input" @checked(old('is_active', $salaryComponent?->is_active ?? true))> Đang sử dụng</label></div>
            </div>
            <div class="form-actions"><button class="btn btn-primary"><i data-lucide="save"></i> Lưu</button></div>
        </div>
</form>@endsection

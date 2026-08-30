@extends('admin.layouts.app')
@php($editing = $feePolicy !== null)
@section('title', $editing ? 'Sửa chính sách' : 'Thêm chính sách')
@section('content')<div class="page-heading">
        <p class="eyebrow">Cấu hình học phí</p>
        <h1>{{ $editing ? 'Sửa chính sách' : 'Thêm chính sách' }}</h1>
    </div>
    <form method="POST"
        action="{{ $editing ? route('admin.finance.fee-policies.update', $feePolicy) : route('admin.finance.fee-policies.store') }}"
        class="card form-card">@csrf @if ($editing)
            @method('PUT')
        @endif
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4"><label class="form-label">Năm học</label><select name="academic_year_id"
                        class="form-select">
                        @foreach ($years as $y)
                            <option value="{{ $y->id }}" @selected(old('academic_year_id', $feePolicy?->academic_year_id) == $y->id)>{{ $y->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4"><label class="form-label">Khoản thu</label><select name="fee_category_id"
                        class="form-select">
                        <option value="">Tất cả</option>
                        @foreach ($categories as $c)
                            <option value="{{ $c->id }}" @selected(old('fee_category_id', $feePolicy?->fee_category_id) == $c->id)>{{ $c->name }}</option>
                        @endforeach
                    </select></div>
                <div class="col-md-4"><label class="form-label">Mã</label><input name="code"
                        value="{{ old('code', $feePolicy?->code) }}" class="form-control" required></div>
                <div class="col-md-6"><label class="form-label">Tên</label><input name="name"
                        value="{{ old('name', $feePolicy?->name) }}" class="form-control" required></div>
                <div class="col-md-3"><label class="form-label">Loại giảm</label><select name="reduction_type"
                        class="form-select">
                        <option value="percentage" @selected(old('reduction_type', $feePolicy?->reduction_type) === 'percentage')>Theo phần trăm</option>
                        <option value="fixed_amount" @selected(old('reduction_type', $feePolicy?->reduction_type) === 'fixed_amount')>Số tiền cố định</option>
                        <option value="full_exemption" @selected(old('reduction_type', $feePolicy?->reduction_type) === 'full_exemption')>Miễn toàn bộ</option>
                    </select></div>
                <div class="col-md-3"><label class="form-label">Giá trị</label><input type="number" step="0.0001"
                        min="0" name="reduction_value"
                        value="{{ old('reduction_value', $feePolicy?->reduction_value) }}" class="form-control" required>
                </div>
                <div class="col-md-3"><label class="form-label">Chế độ áp dụng</label><select name="application_mode"
                        class="form-select">
                        <option value="exclusive" @selected(old('application_mode', $feePolicy?->application_mode) === 'exclusive')>Chính sách ưu tiên</option>
                        <option value="cumulative" @selected(old('application_mode', $feePolicy?->application_mode) === 'cumulative')>Cho phép cộng dồn</option>
                    </select></div>
                <div class="col-md-3"><label class="form-label">Mức ưu tiên</label><input type="number" name="priority"
                        value="{{ old('priority', $feePolicy?->priority ?? 0) }}" class="form-control"></div>
                <div class="col-md-3"><label class="form-label">Từ ngày</label><input type="date" name="effective_from"
                        value="{{ old('effective_from', $feePolicy?->effective_from?->toDateString()) }}"
                        class="form-control" required></div>
                <div class="col-md-3"><label class="form-label">Đến ngày</label><input type="date" name="effective_to"
                        value="{{ old('effective_to', $feePolicy?->effective_to?->toDateString()) }}" class="form-control">
                </div>
                <div class="col-md-3"><label class="form-label">Trạng thái</label><select name="status"
                        class="form-select">
                        <option value="active">Đang áp dụng</option>
                        <option value="inactive" @selected(old('status', $feePolicy?->status) === 'inactive')>Ngừng áp dụng</option>
                    </select></div>
            </div>
            <div class="form-actions"><button class="btn btn-primary"><i data-lucide="save"></i> Lưu chính sách</button>
            </div>
        </div>
</form>@endsection

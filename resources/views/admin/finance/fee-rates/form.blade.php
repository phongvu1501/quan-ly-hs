@extends('admin.layouts.app')
@php($editing = $feeRate !== null)
@section('title', $editing ? 'Sửa mức thu' : 'Thêm mức thu')
@section('content')<div class="page-heading">
        <p class="eyebrow">Cấu hình học phí</p>
        <h1>{{ $editing ? 'Sửa mức thu' : 'Thêm mức thu' }}</h1>
    </div>
    <form method="POST"
        action="{{ $editing ? route('admin.finance.fee-rates.update', $feeRate) : route('admin.finance.fee-rates.store') }}"
        class="card form-card">@csrf @if ($editing)
            @method('PUT')
        @endif
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4"><label class="form-label">Danh mục</label><select name="fee_category_id"
                        class="form-select" required>
                        @foreach ($categories as $c)
                            <option value="{{ $c->id }}" @selected(old('fee_category_id', $feeRate?->fee_category_id) == $c->id)>{{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4"><label class="form-label">Năm học</label><select name="academic_year_id"
                        class="form-select" required>
                        @foreach ($years as $y)
                            <option value="{{ $y->id }}" @selected(old('academic_year_id', $feeRate?->academic_year_id) == $y->id)>{{ $y->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4"><label class="form-label">Chu kỳ</label><select name="period_type"
                        class="form-select">
                        @foreach (['monthly', 'semester', 'annual', 'one_time'] as $v)
                            <option @selected(old('period_type', $feeRate?->period_type) === $v)>{{ $v }}</option>
                        @endforeach
                    </select></div>
                <div class="col-md-4"><label class="form-label">Mã</label><input name="code"
                        value="{{ old('code', $feeRate?->code) }}" class="form-control" required></div>
                <div class="col-md-5"><label class="form-label">Tên</label><input name="name"
                        value="{{ old('name', $feeRate?->name) }}" class="form-control" required></div>
                <div class="col-md-3"><label class="form-label">Số tiền</label><input type="number" step="0.01"
                        min="0" name="amount" value="{{ old('amount', $feeRate?->amount) }}" class="form-control"
                        required></div>
                <div class="col-md-3"><label class="form-label">Từ ngày</label><input type="date" name="effective_from"
                        value="{{ old('effective_from', $feeRate?->effective_from?->toDateString()) }}" class="form-control"
                        required></div>
                <div class="col-md-3"><label class="form-label">Đến ngày</label><input type="date" name="effective_to"
                        value="{{ old('effective_to', $feeRate?->effective_to?->toDateString()) }}" class="form-control">
                </div>
                <div class="col-md-3"><label class="form-check mt-4"><input type="checkbox" name="is_active" value="1"
                            class="form-check-input" @checked(old('is_active', $feeRate?->is_active ?? true))> Đang dùng</label></div>
            </div>
            <div class="form-actions"><button class="btn btn-primary"><i data-lucide="save"></i> Lưu</button></div>
        </div>
</form>@endsection

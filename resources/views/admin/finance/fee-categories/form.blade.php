@extends('admin.layouts.app')
@php($editing = $feeCategory !== null)
@section('title', $editing ? 'Sửa danh mục' : 'Thêm danh mục')
@section('content')<div class="page-heading">
        <p class="eyebrow">Cấu hình học phí</p>
        <h1>{{ $editing ? 'Sửa danh mục' : 'Thêm danh mục' }}</h1>
    </div>
    <form method="POST"
        action="{{ $editing ? route('admin.finance.fee-categories.update', $feeCategory) : route('admin.finance.fee-categories.store') }}"
        class="card form-card">@csrf @if ($editing)
            @method('PUT')
        @endif
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4"><label class="form-label">Mã</label><input name="code"
                        value="{{ old('code', $feeCategory?->code) }}" class="form-control" required></div>
                <div class="col-md-8"><label class="form-label">Tên</label><input name="name"
                        value="{{ old('name', $feeCategory?->name) }}" class="form-control" required></div>
                <div class="col-md-4"><label class="form-label">Loại</label><input name="type"
                        value="{{ old('type', $feeCategory?->type ?? 'other') }}" class="form-control" required></div>
                <div class="col-12"><label class="form-label">Mô tả</label>
                    <textarea name="description" class="form-control">{{ old('description', $feeCategory?->description) }}</textarea>
                </div>
                <div class="col-12"><label class="form-check"><input type="checkbox" class="form-check-input"
                            name="is_active" value="1" @checked(old('is_active', $feeCategory?->is_active ?? true))><span class="form-check-label">Đang
                            sử dụng</span></label></div>
            </div>
            <div class="form-actions"><button class="btn btn-primary"><i data-lucide="save"></i> Lưu</button><a
                    class="btn btn-light" href="{{ route('admin.finance.fee-categories.index') }}">Hủy</a></div>
        </div>
</form>@endsection

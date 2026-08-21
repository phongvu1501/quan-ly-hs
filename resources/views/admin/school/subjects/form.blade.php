@extends('admin.layouts.app')
@php($isEditing = isset($subject))
@section('title', $isEditing ? 'Sửa môn học' : 'Thêm môn học')
@section('content')<div class="page-heading">
        <div>
            <p class="eyebrow">Chương trình học</p>
            <h1>{{ $isEditing ? 'Sửa môn học' : 'Thêm môn học' }}</h1>
        </div>
    </div>
    <form action="{{ $isEditing ? route('admin.school.subjects.update', $subject) : route('admin.school.subjects.store') }}"
        method="POST">@csrf @if ($isEditing)
            @method('PUT')
        @endif
        <div class="card form-card">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4"><label class="form-label">Mã môn</label><input name="code" class="form-control"
                            value="{{ old('code', $subject?->code) }}" required></div>
                    <div class="col-md-8"><label class="form-label">Tên môn</label><input name="name"
                            class="form-control" value="{{ old('name', $subject?->name) }}" required></div>
                    <div class="col-md-6"><label class="form-label">Tên viết tắt</label><input name="short_name"
                            class="form-control" value="{{ old('short_name', $subject?->short_name) }}"></div>
                    <div class="col-md-3"><label class="form-label">Tiết/tuần</label><input type="number"
                            name="weekly_periods" class="form-control"
                            value="{{ old('weekly_periods', $subject?->weekly_periods ?: 2) }}" min="1"
                            max="20" required></div>
                    <div class="col-md-3 d-flex align-items-end"><label class="form-check mb-2"><input type="checkbox"
                                name="is_active" value="1" class="form-check-input" @checked(old('is_active', $subject?->is_active ?? true))><span
                                class="form-check-label">Đang sử dụng</span></label></div>
                    <div class="col-12"><label class="form-label">Mô tả</label>
                        <textarea name="description" rows="3" class="form-control">{{ old('description', $subject?->description) }}</textarea>
                    </div>
                </div>
                <div class="form-actions"><button class="btn btn-primary"><i data-lucide="save"></i> Lưu môn học</button><a
                        href="{{ route('admin.school.subjects.index') }}" class="btn btn-light">Hủy</a></div>
            </div>
        </div>
</form>@endsection

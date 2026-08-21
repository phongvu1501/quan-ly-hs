@extends('admin.layouts.app')
@php($isEditing = isset($grade))
@section('title', $isEditing ? 'Sửa khối lớp' : 'Thêm khối lớp')
@section('content')<div class="page-heading">
        <div>
            <p class="eyebrow">Cấu trúc đào tạo</p>
            <h1>{{ $isEditing ? 'Sửa khối lớp' : 'Thêm khối lớp' }}</h1>
        </div>
    </div>
    <form action="{{ $isEditing ? route('admin.school.grades.update', $grade) : route('admin.school.grades.store') }}"
        method="POST">@csrf @if ($isEditing)
            @method('PUT')
        @endif
        <div class="card form-card">
            <div class="card-body">
                <div class="mb-3"><label class="form-label">Cấp khối</label><input type="number" name="level"
                        class="form-control" value="{{ old('level', $grade?->level) }}" min="10" max="12"
                        required></div>
                <div class="mb-3"><label class="form-label">Tên khối</label><input name="name" class="form-control"
                        value="{{ old('name', $grade?->name) }}" required></div>
                <div class="mb-3"><label class="form-label">Mô tả</label>
                    <textarea name="description" rows="3" class="form-control">{{ old('description', $grade?->description) }}</textarea>
                </div>
                <div class="form-actions"><button class="btn btn-primary"><i data-lucide="save"></i> Lưu khối lớp</button><a
                        href="{{ route('admin.school.grades.index') }}" class="btn btn-light">Hủy</a></div>
            </div>
        </div>
</form>@endsection

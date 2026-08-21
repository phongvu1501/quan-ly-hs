@extends('admin.layouts.app')

@section('title', 'Sửa permission')

@section('content')
    <div class="page-heading">
        <div>
            <p class="eyebrow">RBAC</p>
            <h1>Sửa permission</h1>
            <p class="text-secondary mb-0">Đổi slug sẽ ảnh hưởng các middleware đang sử dụng slug đó.</p>
        </div>
    </div>
    <form action="{{ route('admin.permissions.update', $permission) }}" method="POST">
        @csrf @method('PUT')
        <div class="card form-card">
            <div class="card-body">
                <div class="mb-3"><label class="form-label" for="name">Tên permission</label><input id="name"
                        name="name" class="form-control" value="{{ old('name', $permission->name) }}" required></div>
                <div class="mb-3"><label class="form-label" for="slug">Slug</label><input id="slug"
                        name="slug" class="form-control" value="{{ old('slug', $permission->slug) }}" required></div>
                <div class="mb-3"><label class="form-label" for="description">Mô tả</label>
                    <textarea id="description" name="description" rows="3" class="form-control">{{ old('description', $permission->description) }}</textarea>
                </div>
                <div class="form-actions"><button class="btn btn-primary" type="submit"><i data-lucide="save"></i> Lưu thay
                        đổi</button><a href="{{ route('admin.permissions.index') }}" class="btn btn-light">Huỷ</a></div>
            </div>
        </div>
    </form>
@endsection

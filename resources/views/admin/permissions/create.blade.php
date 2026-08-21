@extends('admin.layouts.app')

@section('title', 'Thêm permission')

@section('content')
    <div class="page-heading">
        <div>
            <p class="eyebrow">RBAC</p>
            <h1>Thêm permission</h1>
            <p class="text-secondary mb-0">Slug được dùng trong middleware và điều kiện hiển thị.</p>
        </div>
    </div>
    <form action="{{ route('admin.permissions.store') }}" method="POST">
        @csrf
        <div class="card form-card">
            <div class="card-body">
                <div class="mb-3"><label class="form-label" for="name">Tên permission</label><input id="name"
                        name="name" class="form-control" value="{{ old('name') }}" placeholder="Xem sản phẩm" required>
                </div>
                <div class="mb-3"><label class="form-label" for="slug">Slug</label><input id="slug"
                        name="slug" class="form-control" value="{{ old('slug') }}" placeholder="products.view"
                        required></div>
                <div class="mb-3"><label class="form-label" for="description">Mô tả</label>
                    <textarea id="description" name="description" rows="3" class="form-control">{{ old('description') }}</textarea>
                </div>
                <div class="form-actions"><button class="btn btn-primary" type="submit"><i data-lucide="save"></i> Lưu
                        permission</button><a href="{{ route('admin.permissions.index') }}" class="btn btn-light">Huỷ</a>
                </div>
            </div>
        </div>
    </form>
@endsection

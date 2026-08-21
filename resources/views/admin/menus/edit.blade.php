@extends('admin.layouts.app')

@section('title', 'Sửa menu')

@section('content')
<div class="page-heading">
    <div>
        <p class="eyebrow">Điều hướng</p>
        <h1>Sửa menu</h1>
        <p class="text-secondary mb-0">Cấu trúc con sẽ được giữ nguyên khi cập nhật.</p>
    </div>
</div>

<form action="{{ route('admin.menus.update', $menu) }}" method="POST">
    @csrf
    @method('PUT')
    <div class="card form-card">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label" for="name">Tên menu</label>
                    <input id="name" name="name" class="form-control" value="{{ old('name', $menu->name) }}" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="slug">Slug</label>
                    <input id="slug" name="slug" class="form-control" value="{{ old('slug', $menu->slug) }}" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="route">Route (không bắt buộc)</label>
                    <input id="route" name="route" class="form-control" value="{{ old('route', $menu->route) }}" placeholder="products.index hoặc /products">
                    <div class="form-text">Tên route chưa tồn tại sẽ tự chuyển đến CRUD động.</div>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="icon">Icon Lucide</label>
                    <input id="icon" name="icon" class="form-control" value="{{ old('icon', $menu->icon) }}" placeholder="package">
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="parent_id">Menu cha</label>
                    <select id="parent_id" name="parent_id" class="form-select">
                        <option value="">Menu gốc</option>
                        @foreach($parents as $parent)
                            <option value="{{ $parent->id }}" @selected($menu->parent_id == $parent->id)>{{ $parent->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="sort_order">Thứ tự</label>
                    <input id="sort_order" type="number" name="sort_order" class="form-control" value="{{ old('sort_order', $menu->sort_order) }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="required_permission">Quyền cần có</label>
                    <select id="required_permission" name="required_permission" class="form-select">
                        <option value="">Chỉ dùng gán menu cho role</option>
                        @foreach($permissions as $permission)
                            <option value="{{ $permission->slug }}" @selected(old('required_permission', $menu->required_permission) === $permission->slug)>{{ $permission->slug }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <label class="form-check mt-3">
                <input type="checkbox" name="is_active" value="1" class="form-check-input" @checked(old('is_active', $menu->is_active))>
                <span class="form-check-label">Hiển thị menu</span>
            </label>
            <div class="form-actions">
                <button class="btn btn-primary" type="submit"><i data-lucide="save"></i> Lưu thay đổi</button>
                <a href="{{ route('admin.menus.index') }}" class="btn btn-light">Huỷ</a>
            </div>
        </div>
    </div>
</form>
@endsection

@extends('admin.layouts.app')

@section('title', 'Sửa vai trò')

@section('content')
    @php
        $rolePermissions = $role->permissions->pluck('id')->all();
        $roleMenus = $role->menus->pluck('id')->all();
    @endphp
    <div class="page-heading">
        <div>
            <p class="eyebrow">RBAC / Vai trò</p>
            <h1>Sửa vai trò</h1>
            <p class="text-secondary mb-0">Thay đổi được áp dụng ngay sau khi lưu.</p>
        </div>
    </div>

    <form action="{{ route('admin.roles.update', $role) }}" method="POST">
        @csrf
        @method('PUT')
        <div class="card form-card">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" for="name">Tên vai trò</label>
                        <input id="name" name="name" class="form-control" value="{{ old('name', $role->name) }}"
                            required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="slug">Slug</label>
                        <input id="slug" name="slug" class="form-control" value="{{ old('slug', $role->slug) }}"
                            required>
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="description">Mô tả</label>
                        <textarea id="description" name="description" class="form-control" rows="2">{{ old('description', $role->description) }}</textarea>
                    </div>
                </div>

                <div class="permission-section">
                    <div class="section-label">Quyền truy cập</div>
                    <div class="row g-2">
                        @foreach ($permissions as $permission)
                            <div class="col-lg-4 col-md-6">
                                <label class="check-tile">
                                    <input type="checkbox" name="permissions[]" value="{{ $permission->id }}"
                                        @checked(in_array($permission->id, old('permissions', $rolePermissions)))>
                                    <span>
                                        <strong>{{ $permission->name }}</strong>
                                        <small>{{ $permission->slug }}</small>
                                    </span>
                                </label>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="permission-section">
                    <div class="section-label">Menu được hiển thị</div>
                    <p class="text-secondary small">Bỏ chọn sẽ gỡ liên kết trong <code>role_menu</code> ngay lập tức.</p>
                    <div class="row g-2">
                        @foreach ($menus as $menu)
                            <div class="col-lg-4 col-md-6">
                                <label class="check-tile {{ $menu->parent_id ? 'nested-option' : '' }}">
                                    <input type="checkbox" name="menus[]" value="{{ $menu->id }}"
                                        @checked(in_array($menu->id, old('menus', $roleMenus)))>
                                    <span>
                                        <strong>{{ $menu->parent_id ? '↳ ' : '' }}{{ $menu->name }}</strong>
                                        <small>{{ $menu->slug }}</small>
                                    </span>
                                </label>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="form-actions">
                    <button class="btn btn-primary" type="submit"><i data-lucide="save"></i> Lưu thay đổi</button>
                    <a href="{{ route('admin.roles.index') }}" class="btn btn-light">Huỷ</a>
                </div>
            </div>
        </div>
    </form>
@endsection

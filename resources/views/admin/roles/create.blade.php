@extends('admin.layouts.app')

@section('title', 'Thêm vai trò')

@section('content')
    <div class="page-heading">
        <div>
            <p class="eyebrow">RBAC</p>
            <h1>Thêm vai trò</h1>
            <p class="text-secondary mb-0">Chọn quyền và menu mà vai trò được sử dụng.</p>
        </div>
    </div>

    <form action="{{ route('admin.roles.store') }}" method="POST">
        @csrf
        <div class="card form-card">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" for="name">Tên vai trò</label>
                        <input id="name" name="name" class="form-control" value="{{ old('name') }}" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="slug">Slug</label>
                        <input id="slug" name="slug" class="form-control" value="{{ old('slug') }}"
                            placeholder="warehouse.manager" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="description">Mô tả</label>
                        <textarea id="description" name="description" class="form-control" rows="2">{{ old('description') }}</textarea>
                    </div>
                </div>

                @include('admin.roles.partials.permission-matrix', [
                    'selectedPermissionIds' => collect(old('permissions', []))->map(fn ($id) => (int) $id)->all(),
                ])

                <div class="permission-section">
                    <div class="section-label">Menu được hiển thị</div>
                    <p class="text-secondary small">Menu cha sẽ tự xuất hiện khi ít nhất một menu con được cấp.</p>
                    <div class="row g-2">
                        @foreach ($menus as $menu)
                            <div class="col-lg-4 col-md-6">
                                <label class="check-tile {{ $menu->parent_id ? 'nested-option' : '' }}">
                                    <input type="checkbox" name="menus[]" value="{{ $menu->id }}"
                                        @checked(in_array($menu->id, old('menus', [])))>
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
                    <button class="btn btn-primary" type="submit"><i data-lucide="save"></i> Lưu vai trò</button>
                    <a href="{{ route('admin.roles.index') }}" class="btn btn-light">Huỷ</a>
                </div>
            </div>
        </div>
    </form>
@endsection

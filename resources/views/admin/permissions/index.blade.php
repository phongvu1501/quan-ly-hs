@extends('admin.layouts.app')

@section('title', 'Quyền truy cập')

@section('content')
    <div class="page-heading page-heading-inline">
        <div>
            <p class="eyebrow">Quyền truy cập</p>
            <h1>Quyền truy cập</h1>
            <p class="text-secondary mb-0">Permission kiểm soát route và các thao tác được hiển thị.</p>
        </div>
        @if (auth()->user()->hasPermission('permissions.create'))
            <a href="{{ route('admin.permissions.create') }}" class="btn btn-primary" data-modal-title="Thêm permission"><i
                    data-lucide="plus"></i> Thêm permission</a>
        @endif
    </div>

    <div class="card form-card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.permissions.index') }}" class="row g-3 align-items-end">
                <div class="col-md-6 col-xl-3"><label class="form-label" for="permission-group">Nhóm chức
                        năng</label><select id="permission-group" name="group" class="form-select">
                        <option value="">Tất cả nhóm</option>
                        @foreach ($groups as $value)
                            <option value="{{ $value }}" @selected(request('group') === $value)>{{ $value }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6 col-xl-2"><label class="form-label" for="permission-action">Hành động</label><select
                        id="permission-action" name="action" class="form-select">
                        <option value="">Tất cả</option>
                        @foreach ($actions as $value)
                            <option value="{{ $value }}" @selected(request('action') === $value)>{{ $value }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6 col-xl-3"><label class="form-label" for="permission-role">Role được cấp</label><select
                        id="permission-role" name="role_id" class="form-select">
                        <option value="">Tất cả role</option>
                        @foreach ($roles as $role)
                            <option value="{{ $role->id }}" @selected(request('role_id') == $role->id)>{{ $role->name }}</option>
                        @endforeach
                    </select></div>
                <div class="col-md-6 col-xl-2"><label class="form-label" for="permission-assignment">Trạng thái
                        gán</label><select id="permission-assignment" name="assignment" class="form-select">
                        <option value="">Tất cả</option>
                        <option value="assigned" @selected(request('assignment') === 'assigned')>Đã gán role</option>
                        <option value="unassigned" @selected(request('assignment') === 'unassigned')>Chưa gán role</option>
                    </select></div>
                @if (request()->filled('q'))
                    <input type="hidden" name="q" value="{{ request('q') }}">
                @endif
                <div class="col-md-6 col-xl-1"><button class="btn btn-primary w-100" type="submit" title="Lọc"><i
                            data-lucide="filter"></i><span class="visually-hidden">Lọc</span>Lọc</button></div>
                <div class="col-md-6 col-xl-1"><a class="btn btn-light w-100" href="{{ route('admin.permissions.index') }}"
                        title="Đặt lại"><i data-lucide="rotate-ccw"></i><span class="visually-hidden"></span>Đặt lại</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card table-card">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Permission</th>
                        <th>Slug</th>
                        <th>Mô tả</th>
                        <th>Số role</th>
                        <th class="text-end">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($permissions as $permission)
                        <tr>
                            <td><strong>{{ $permission->name }}</strong></td>
                            <td><code>{{ $permission->slug }}</code></td>
                            <td class="text-secondary">{{ $permission->description ?: '-' }}</td>
                            <td>{{ $permission->roles_count }}</td>
                            <td class="text-end">
                                @if (auth()->user()->hasPermission('permissions.edit'))
                                    <a href="{{ route('admin.permissions.edit', $permission) }}"
                                        class="btn btn-sm btn-light" data-modal-title="Sửa permission" title="Sửa"><i
                                            data-lucide="pencil"></i></a>
                                @endif
                                @if (auth()->user()->hasPermission('permissions.delete'))
                                    <form action="{{ route('admin.permissions.destroy', $permission) }}" method="POST"
                                        class="d-inline" data-modal-title="Xóa permission"
                                        data-confirm="Bạn có chắc muốn xóa permission này không?">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger" title="Xoá"><i
                                                data-lucide="trash-2"></i></button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="empty-state">Chưa có permission.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($permissions->hasPages())
            <div class="p-3 border-top">{{ $permissions->links('pagination::bootstrap-5') }}</div>
        @endif
    </div>
@endsection

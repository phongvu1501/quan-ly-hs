@extends('admin.layouts.app')

@section('title', 'Quyền truy cập')

@section('content')
    <div class="page-heading page-heading-inline">
        <div>
            <p class="eyebrow">RBAC</p>
            <h1>Quyền truy cập</h1>
            <p class="text-secondary mb-0">Permission kiểm soát route và các thao tác được hiển thị.</p>
        </div>
        @if (auth()->user()->hasPermission('permissions.create'))
            <a href="{{ route('admin.permissions.create') }}" class="btn btn-primary" data-modal-title="Thêm permission"><i
                    data-lucide="plus"></i> Thêm permission</a>
        @endif
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

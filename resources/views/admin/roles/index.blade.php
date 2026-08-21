@extends('admin.layouts.app')

@section('title', 'Vai trò')

@section('content')
    <div class="page-heading page-heading-inline">
        <div>
            <p class="eyebrow">RBAC</p>
            <h1>Vai trò</h1>
            <p class="text-secondary mb-0">Một vai trò có thể chứa nhiều permission và nhiều menu.</p>
        </div>
        @if (auth()->user()->hasPermission('roles.create'))
            <a href="{{ route('admin.roles.create') }}" class="btn btn-primary" data-modal-title="Thêm vai trò"><i
                    data-lucide="plus"></i> Thêm vai trò</a>
        @endif
    </div>

    <div class="card table-card">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Vai trò</th>
                        <th>Slug</th>
                        <th>Tài khoản</th>
                        <th>Permission</th>
                        <th>Menu</th>
                        <th class="text-end">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($roles as $role)
                        <tr>
                            <td><strong>{{ $role->name }}</strong><small
                                    class="d-block text-secondary">{{ $role->description }}</small></td>
                            <td><code>{{ $role->slug }}</code></td>
                            <td>{{ $role->users_count }}</td>
                            <td>{{ $role->permissions_count }}</td>
                            <td>{{ $role->menus_count }}</td>
                            <td class="text-end">
                                @if (auth()->user()->hasPermission('roles.edit'))
                                    <a href="{{ route('admin.roles.edit', $role) }}" class="btn btn-sm btn-light"
                                        data-modal-title="Sửa vai trò" title="Sửa"><i data-lucide="pencil"></i></a>
                                @endif
                                @if (auth()->user()->hasPermission('roles.delete'))
                                    <form action="{{ route('admin.roles.destroy', $role) }}" method="POST" class="d-inline"
                                        data-modal-title="Xóa vai trò"
                                        data-confirm="Bạn có chắc muốn xóa vai trò này không?">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger" title="Xoá"><i
                                                data-lucide="trash-2"></i></button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="empty-state">Chưa có vai trò.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($roles->hasPages())
            <div class="p-3 border-top">{{ $roles->links('pagination::bootstrap-5') }}</div>
        @endif
    </div>
@endsection

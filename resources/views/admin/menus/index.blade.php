@extends('admin.layouts.app')

@section('title', 'Điều hướng')

@section('content')
    <div class="page-heading page-heading-inline">
        <div>
            <p class="eyebrow">Cấu hình hệ thống</p>
            <h1>Điều hướng</h1>
            <p class="text-secondary mb-0">Menu cha/con trong DB sẽ tự dựng thành cây ở sidebar.</p>
        </div>
        @if (auth()->user()->hasPermission('menus.create'))
            <a href="{{ route('admin.menus.create') }}" class="btn btn-primary" data-modal-title="Thêm menu"><i
                    data-lucide="plus"></i> Thêm menu</a>
        @endif
    </div>

    <div class="card table-card">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Menu</th>
                        <th>Slug</th>
                        <th>Route</th>
                        <th>Quyền cần có</th>
                        <th>Trạng thái</th>
                        <th class="text-end">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($menus as $menu)
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-2"
                                    style="padding-left: {{ $menu->parent_id ? '24px' : '0' }}">
                                    @if ($menu->parent_id)
                                    <span class="text-secondary">↳</span>@else<i data-lucide="folder-tree"
                                            class="text-primary"></i>
                                    @endif
                                    <strong>{{ $menu->name }}</strong>
                                </div>
                            </td>
                            <td><code>{{ $menu->slug }}</code></td>
                            <td><code>{{ $menu->route ?: 'CRUD động' }}</code></td>
                            <td>{{ $menu->required_permission ?: 'Gán trực tiếp theo role' }}</td>
                            <td><span
                                    class="status-dot {{ $menu->is_active ? 'status-on' : 'status-off' }}">{{ $menu->is_active ? 'Đang hiển thị' : 'Đã tắt' }}</span>
                            </td>
                            <td class="text-end">
                                @if (auth()->user()->hasPermission('menus.edit'))
                                    <a href="{{ route('admin.menus.edit', $menu) }}" class="btn btn-sm btn-light"
                                        data-modal-title="Sửa menu" title="Sửa"><i data-lucide="pencil"></i></a>
                                @endif
                                @if (auth()->user()->hasPermission('menus.delete'))
                                    <form action="{{ route('admin.menus.destroy', $menu) }}" method="POST" class="d-inline"
                                        data-modal-title="Xóa menu" data-confirm="Bạn có chắc muốn xóa menu này không?">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger" title="Xoá"><i
                                                data-lucide="trash-2"></i></button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="empty-state">Chưa có menu.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($menus->hasPages())
            <div class="p-3 border-top">{{ $menus->links('pagination::bootstrap-5') }}</div>
        @endif
    </div>
@endsection

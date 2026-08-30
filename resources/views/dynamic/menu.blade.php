@extends('admin.layouts.app')

@section('title', $menu->name)

@php
    $createPermission = $menu->permissionForAction('create');
    $editPermission = $menu->permissionForAction('edit');
    $deletePermission = $menu->permissionForAction('delete');
    $canCreate = ! $createPermission || auth()->user()->hasPermission($createPermission);
    $canEdit = ! $editPermission || auth()->user()->hasPermission($editPermission);
    $canDelete = ! $deletePermission || auth()->user()->hasPermission($deletePermission);
@endphp

@section('content')
    <div class="page-heading page-heading-inline">
        <div>
            <p class="eyebrow">Workspace động</p>
            <h1>{{ $menu->name }}</h1>
            <p class="text-secondary mb-0">Dữ liệu được lưu riêng cho menu <code>{{ $menu->slug }}</code>.</p>
        </div>
        @if ($canCreate)
            <a href="{{ route('dynamic.menus.records.create', ['menu' => $menu->slug]) }}" class="btn btn-primary"
                data-modal-title="Thêm bản ghi"><i data-lucide="plus"></i> Thêm bản ghi</a>
        @endif
    </div>

    <div class="card table-card">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Nội dung</th>
                        <th>Mã</th>
                        <th>Trạng thái</th>
                        <th class="text-end">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($records as $record)
                        <tr>
                            <td><strong>{{ $record->title }}</strong><small
                                    class="d-block text-secondary">{{ \Illuminate\Support\Str::limit($record->description, 90) }}</small>
                            </td>
                            <td><code>{{ $record->code ?: '-' }}</code></td>
                            <td><span
                                    class="status-dot {{ $record->is_active ? 'status-on' : 'status-off' }}">{{ $record->is_active ? 'Đang dùng' : 'Tắt' }}</span>
                            </td>
                            <td class="text-end">
                                @if ($canEdit)
                                    <a href="{{ route('dynamic.menus.records.edit', ['menu' => $menu->slug, 'record' => $record]) }}"
                                        class="btn btn-sm btn-light" data-modal-title="Sửa bản ghi" title="Sửa"><i
                                            data-lucide="pencil"></i></a>
                                @endif
                                @if ($canDelete)
                                    <form
                                        action="{{ route('dynamic.menus.records.destroy', ['menu' => $menu->slug, 'record' => $record]) }}"
                                        method="POST" class="d-inline" data-modal-title="Xóa bản ghi"
                                        data-confirm="Bạn có chắc muốn xóa bản ghi này không?">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger" title="Xoá"><i
                                                data-lucide="trash-2"></i></button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="empty-state">Chưa có dữ liệu.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($records->hasPages())
            <div class="p-3 border-top">{{ $records->links('pagination::bootstrap-5') }}</div>
        @endif
    </div>
@endsection

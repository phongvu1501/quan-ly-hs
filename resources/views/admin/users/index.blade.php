@extends('admin.layouts.app')

@section('title', 'Tài khoản')

@section('content')
    <div class="page-heading page-heading-inline">
        <div>
            <p class="eyebrow">RBAC / User</p>
            <h1>Tài khoản</h1>
            <p class="text-secondary mb-0">Gán role để kiểm soát quyền và menu theo từng tài khoản.</p>
        </div>
        @if (auth()->user()->hasPermission('users.create'))
            <a href="{{ route('admin.users.create') }}" class="btn btn-primary" data-modal-title="Thêm tài khoản"><i
                    data-lucide="user-plus"></i> Thêm tài khoản</a>
        @endif
    </div>

    <div class="card table-card">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Tài khoản</th>
                        <th>Email</th>
                        <th>Vai trò</th>
                        <th>Ngày tạo</th>
                        <th class="text-end">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $user)
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    @if ($user->avatar_path)
                                        <img src="{{ asset('storage/' . $user->avatar_path) }}" alt=""
                                            class="rounded-circle"
                                        style="width:34px;height:34px;object-fit:cover">@else<span
                                            class="account-avatar"
                                            style="width:34px;height:34px">{{ strtoupper(substr($user->name, 0, 1)) }}</span>
                                    @endif
                                    <strong>
                                        {{ $user->name }}</strong>
                                </div>
                            </td>
                            <td>{{ $user->email }}@if ($user->teacherProfile)
                                    <small class="d-block text-secondary">GV:
                                        {{ $user->teacherProfile->teacher_code }}</small>
                                    @endif @if ($user->studentProfile)
                                        <small class="d-block text-secondary">HS:
                                            {{ $user->studentProfile->student_code }}</small>
                                    @endif
                            </td>
                            <td>
                                @forelse($user->roles as $role)
                                    <span class="badge text-bg-light me-1">{{ $role->name }}</span>
                                @empty
                                    <span class="text-secondary">Chưa gán role</span>
                                @endforelse
                            </td>
                            <td>{{ $user->created_at?->format('d/m/Y') }}</td>
                            <td class="text-end">
                                @if (auth()->user()->hasPermission('users.edit'))
                                    <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-sm btn-light"
                                        data-modal-title="Sửa tài khoản" title="Sửa"><i data-lucide="pencil"></i></a>
                                @endif
                                @if (auth()->user()->hasPermission('users.delete') && !$user->is(auth()->user()))
                                    <form action="{{ route('admin.users.destroy', $user) }}" method="POST"
                                        class="d-inline" data-modal-title="Xóa tài khoản"
                                        data-confirm="Bạn có chắc muốn xóa tài khoản này không?">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger" title="Xoá"><i
                                                data-lucide="trash-2"></i></button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="empty-state">Chưa có tài khoản.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($users->hasPages())
            <div class="p-3 border-top">{{ $users->links('pagination::bootstrap-5') }}</div>
        @endif
    </div>
@endsection

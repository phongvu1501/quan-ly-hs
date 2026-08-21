@extends('admin.layouts.app')
@section('title', 'Giáo viên')
@section('content')
    <div class="page-heading page-heading-inline">
        <div>
            <p class="eyebrow">Nhân sự giảng dạy</p>
            <h1>Giáo viên</h1>
            <p class="text-secondary mb-0">Phân biệt giáo viên chủ nhiệm và giáo viên bộ môn qua phân công.</p>
        </div>
        @if (auth()->user()->hasPermission('teachers.create'))
            <a href="{{ route('admin.school.teachers.create') }}" class="btn btn-primary" data-modal-title="Thêm giáo viên"><i
                    data-lucide="user-plus"></i> Thêm giáo viên</a>
        @endif
    </div>
    <div class="card filter-card mb-4"><div class="card-body">
        <div class="d-flex align-items-center justify-content-between gap-3 mb-3"><div><strong>Bộ lọc giáo viên</strong><small class="d-block text-secondary">Lọc theo trạng thái công tác.</small></div><a class="btn btn-sm btn-light" href="{{ route('admin.school.teachers.index') }}" title="Đặt lại bộ lọc"><i data-lucide="rotate-ccw"></i><span class="visually-hidden">Đặt lại</span></a></div>
        <form method="GET" class="row g-3">
            @if (request('q')) <input type="hidden" name="q" value="{{ request('q') }}"> @endif
            <div class="col-md-4 col-xl-3"><label class="form-label" for="teacher-status">Trạng thái</label><select id="teacher-status" name="status" class="form-select"><option value="">Tất cả trạng thái</option>@foreach($teacherStatuses as $value => $label)<option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>@endforeach</select></div>
            <div class="col-md-4 col-xl-2 d-flex align-items-end"><button class="btn btn-primary w-100" type="submit"><i data-lucide="filter"></i> Lọc</button></div>
        </form>
    </div></div>
    <div class="card table-card">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Mã</th>
                        <th>Giáo viên</th>
                        <th>Chuyên môn</th>
                        <th>Chủ nhiệm</th>
                        <th>Phân công bộ môn</th>
                        <th>Trạng thái</th>
                        <th class="text-end">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($teachers as $teacher)
                        <tr>
                            <td><code>{{ $teacher->teacher_code }}</code></td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    @if ($teacher->avatar_path)
                                        <img src="{{ asset('storage/' . $teacher->avatar_path) }}" alt=""
                                            class="rounded-circle" style="width:36px;height:36px;object-fit:cover">
                                    @endif
                                    <strong>
                                        {{ $teacher->full_name }}</strong>
                                </div><small
                                    class="d-block text-secondary">{{ $teacher->email ?: $teacher->phone ?: '-' }}</small>
                            </td>
                            <td>{{ $teacher->specialization ?: '-' }}</td>
                            <td>{{ $teacher->homeroom_classes_count }}</td>
                            <td>{{ $teacher->assignments_count }}</td>
                            <td><span
                                    class="status-dot {{ $teacher->status === 'active' ? 'status-on' : 'status-off' }}">{{ ['active' => 'Đang công tác', 'on_leave' => 'Tạm nghỉ', 'inactive' => 'Ngừng công tác'][$teacher->status] ?? $teacher->status }}</span>
                            </td>
                            <td class="text-end">
                                <a href="{{ route('admin.school.teachers.history', $teacher) }}"
                                    data-modal-url="{{ route('admin.school.teachers.history', $teacher) }}" data-modal-readonly="true" data-modal-title="Chi tiết giáo viên"
                                    class="btn btn-sm btn-light" title="Xem lịch sử công tác"><i data-lucide="history"></i></a>
                                @if (auth()->user()->hasPermission('teachers.edit'))
                                    <a href="{{ route('admin.school.teachers.edit', $teacher) }}"
                                        class="btn btn-sm btn-light" data-modal-title="Sửa giáo viên" title="Sửa"><i
                                            data-lucide="pencil"></i></a>
                                    @endif @if (auth()->user()->hasPermission('teachers.delete'))
                                        <form action="{{ route('admin.school.teachers.destroy', $teacher) }}"
                                            method="POST" class="d-inline" data-modal-title="Xóa giáo viên"
                                            data-confirm="Giáo viên đang có phân công sẽ không thể xóa. Tiếp tục?">@csrf
                                            @method('DELETE')<button class="btn btn-sm btn-outline-danger" title="Xóa"><i
                                                    data-lucide="trash-2"></i></button></form>
                                    @endif
                            </td>
                    </tr>@empty<tr>
                            <td colspan="7" class="empty-state">Chưa có giáo viên.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($teachers->hasPages())
            <div class="p-3 border-top">{{ $teachers->links('pagination::bootstrap-5') }}</div>
        @endif
    </div>
@endsection

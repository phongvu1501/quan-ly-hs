@extends('admin.layouts.app')
@section('title', 'Môn học')
@section('content')<div class="page-heading page-heading-inline">
        <div>
            <p class="eyebrow">Chương trình học</p>
            <h1>Môn học</h1>
            <p class="text-secondary mb-0">Danh mục môn học và số tiết tiêu chuẩn trong tuần.</p>
        </div>
        @if (auth()->user()->hasPermission('subjects.create'))
            <a href="{{ route('admin.school.subjects.create') }}" class="btn btn-primary" data-modal-title="Thêm môn học"><i
                    data-lucide="plus"></i> Thêm môn học</a>
        @endif
    </div>
    <div class="card filter-card mb-4"><div class="card-body">
        <div class="d-flex align-items-center justify-content-between gap-3 mb-3"><div><strong>Bộ lọc môn học</strong><small class="d-block text-secondary">Lọc theo trạng thái sử dụng.</small></div><a class="btn btn-sm btn-light" href="{{ route('admin.school.subjects.index') }}" title="Đặt lại bộ lọc"><i data-lucide="rotate-ccw"></i><span class="visually-hidden">Đặt lại</span></a></div>
        <form method="GET" class="row g-3">
            @if (request('q')) <input type="hidden" name="q" value="{{ request('q') }}"> @endif
            <div class="col-md-4 col-xl-3"><label class="form-label" for="subject-active">Trạng thái</label><select id="subject-active" name="active" class="form-select"><option value="">Tất cả trạng thái</option>@foreach($subjectStatuses as $value => $label)<option value="{{ $value }}" @selected(request('active') === $value)>{{ $label }}</option>@endforeach</select></div>
            <div class="col-md-4 col-xl-2 d-flex align-items-end"><button class="btn btn-primary w-100" type="submit"><i data-lucide="filter"></i> Lọc</button></div>
        </form>
    </div></div>
    <div class="card table-card">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Mã</th>
                        <th>Môn học</th>
                        <th>Tiết/tuần</th>
                        <th>Phân công</th>
                        <th>Trạng thái</th>
                        <th class="text-end">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($subjects as $subject)
                        <tr>
                            <td><code>{{ $subject->code }}</code></td>
                            <td><strong>{{ $subject->name }}</strong><small
                                    class="d-block text-secondary">{{ $subject->short_name }}</small></td>
                            <td>{{ $subject->weekly_periods }}</td>
                            <td>
                                @php($subjectTeachers = $subject->assignments->pluck('teacher')->filter()->unique('id'))
                                @forelse ($subjectTeachers as $teacher)
                                    <div class="d-flex align-items-center justify-content-between gap-2 mb-1">
                                        <span>{{ $teacher->full_name }}</span>
                                        <a href="{{ route('admin.school.subjects.teachers.show', [$subject, $teacher]) }}" data-modal-url="{{ route('admin.school.subjects.teachers.show', [$subject, $teacher]) }}" data-modal-readonly="true" data-modal-title="Chi tiết {{ $teacher->full_name }}" class="btn btn-sm btn-light py-0 px-1" title="Chi tiết giáo viên"><i data-lucide="eye"></i><span class="visually-hidden">Chi tiết</span></a>
                                    </div>
                                @empty
                                    <span class="text-secondary">Chưa phân công</span>
                                @endforelse
                            </td>
                            <td><span
                                    class="status-dot {{ $subject->is_active ? 'status-on' : 'status-off' }}">{{ $subject->is_active ? 'Đang dùng' : 'Tạm ẩn' }}</span>
                            </td>
                            <td class="text-end">
                                @if (auth()->user()->hasPermission('subjects.edit'))
                                    <a href="{{ route('admin.school.subjects.edit', $subject) }}"
                                        class="btn btn-sm btn-light" data-modal-title="Sửa môn học" title="Sửa"><i
                                            data-lucide="pencil"></i></a>
                                @endif
                                @if (auth()->user()->hasPermission('subjects.delete'))
                                    <form action="{{ route('admin.school.subjects.destroy', $subject) }}" method="POST"
                                        class="d-inline" data-modal-title="Xóa môn học"
                                        data-confirm="Môn học có phân công sẽ không thể xóa.">@csrf @method('DELETE')<button
                                            class="btn btn-sm btn-outline-danger" title="Xóa"><i
                                                data-lucide="trash-2"></i></button></form>
                                @endif
                            </td>
                    </tr>@empty<tr>
                            <td colspan="6" class="empty-state">Chưa có môn học.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($subjects->hasPages())
            <div class="p-3 border-top">{{ $subjects->links('pagination::bootstrap-5') }}</div>
        @endif
</div>@endsection

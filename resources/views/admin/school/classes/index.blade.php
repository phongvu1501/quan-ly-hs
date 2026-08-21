@extends('admin.layouts.app')
@section('title', 'Lớp học')
@section('content')<div class="page-heading page-heading-inline">
        <div>
            <p class="eyebrow">Tổ chức lớp</p>
            <h1>Lớp học</h1>
            <p class="text-secondary mb-0">Lớp được quản lý theo từng năm học, khối và giáo viên chủ nhiệm.</p>
        </div><a href="{{ route('admin.school.classes.create') }}" class="btn btn-primary" data-modal-title="Thêm lớp học"><i
                data-lucide="plus"></i> Thêm lớp học</a>
    </div>
    <div class="card filter-card mb-4"><div class="card-body">
        <div class="d-flex align-items-center justify-content-between gap-3 mb-3"><div><strong>Bộ lọc lớp học</strong><small class="d-block text-secondary">Lọc theo năm học, khối và trạng thái.</small></div><a class="btn btn-sm btn-light" href="{{ route('admin.school.classes.index') }}" title="Đặt lại bộ lọc"><i data-lucide="rotate-ccw"></i><span class="visually-hidden">Đặt lại</span></a></div>
        <form method="GET" class="row g-3">
            @if (request('q')) <input type="hidden" name="q" value="{{ request('q') }}"> @endif
            <div class="col-md-4 col-xl-3"><label class="form-label" for="class-year">Năm học</label><select id="class-year" name="academic_year_id" class="form-select"><option value="">Tất cả năm học</option>@foreach($years as $year)<option value="{{ $year->id }}" @selected(request('academic_year_id') == $year->id)>{{ $year->name }}</option>@endforeach</select></div>
            <div class="col-md-4 col-xl-3"><label class="form-label" for="class-grade">Khối</label><select id="class-grade" name="grade_id" class="form-select"><option value="">Tất cả khối</option>@foreach($grades as $grade)<option value="{{ $grade->id }}" @selected(request('grade_id') == $grade->id)>{{ $grade->name }}</option>@endforeach</select></div>
            <div class="col-md-4 col-xl-3"><label class="form-label" for="class-status">Trạng thái</label><select id="class-status" name="status" class="form-select"><option value="">Tất cả trạng thái</option>@foreach($classStatuses as $value => $label)<option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>@endforeach</select></div>
            <div class="col-md-4 col-xl-2 d-flex align-items-end"><button class="btn btn-primary w-100" type="submit"><i data-lucide="filter"></i> Lọc</button></div>
        </form>
    </div></div>
    <div class="card table-card">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Lớp</th>
                        <th>Năm học</th>
                        <th>Khối</th>
                        <th>Giáo viên chủ nhiệm</th>
                        <th>Sĩ số</th>
                        <th>Phòng</th>
                        <th class="text-end">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($classes as $class)
                        <tr>
                            <td><strong>{{ $class->name }}</strong><small
                                    class="d-block text-secondary"><code>{{ $class->code }}</code></small></td>
                            <td>{{ $class->academicYear?->name }}</td>
                            <td>{{ $class->grade?->name }}</td>
                            <td>{{ $class->homeroomTeacher?->full_name ?: 'Chưa phân công' }}</td>
                            <td>{{ $class->enrollments_count }} / {{ $class->capacity }}</td>
                            <td>{{ $class->room ?: '-' }}</td>
                            <td class="text-end"><a href="{{ route('admin.school.classes.edit', $class) }}"
                                    class="btn btn-sm btn-light" data-modal-title="Sửa lớp học" title="Sửa"><i
                                        data-lucide="pencil"></i></a>
                                <form action="{{ route('admin.school.classes.destroy', $class) }}" method="POST"
                                    class="d-inline" data-modal-title="Xóa lớp học"
                                    data-confirm="Lớp có học sinh sẽ không thể xóa.">@csrf @method('DELETE')<button
                                        class="btn btn-sm btn-outline-danger" title="Xóa"><i
                                            data-lucide="trash-2"></i></button></form>
                            </td>
                    </tr>@empty<tr>
                            <td colspan="7" class="empty-state">Chưa có lớp học.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($classes->hasPages())
            <div class="p-3 border-top">{{ $classes->links('pagination::bootstrap-5') }}</div>
        @endif
</div>@endsection

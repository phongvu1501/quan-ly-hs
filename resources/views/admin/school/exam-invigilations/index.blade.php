@extends('admin.layouts.app')

@section('title', 'Phân công coi thi')

@section('content')
    <div class="page-heading page-heading-inline">
        <div>
            <p class="eyebrow">Kiểm tra và thi</p>
            <h1>Phân công coi thi</h1>
            <p class="text-secondary mb-0">Phân công giáo viên theo từng kỳ thi, ngày và ca thi.</p>
        </div>
        @if (auth()->user()->hasPermission('exam-invigilations.create'))
            <a href="{{ route('admin.school.exam-invigilations.create') }}" class="btn btn-primary" data-modal-title="Thêm phân công coi thi"><i data-lucide="user-plus"></i> Phân công coi thi</a>
        @endif
    </div>

    <div class="card filter-card mb-4"><div class="card-body">
        <form method="GET" class="row g-3">
            @if (request('q')) <input type="hidden" name="q" value="{{ request('q') }}"> @endif
            <div class="col-md-3"><label class="form-label" for="invigilation-year">Năm học</label><select id="invigilation-year" name="academic_year_id" class="form-select"><option value="">Tất cả năm học</option>@foreach($years as $year)<option value="{{ $year->id }}" @selected(request('academic_year_id') == $year->id)>{{ $year->name }}</option>@endforeach</select></div>
            <div class="col-md-3"><label class="form-label" for="invigilation-teacher">Giáo viên</label><select id="invigilation-teacher" name="teacher_id" class="form-select"><option value="">Tất cả giáo viên</option>@foreach($teachers as $teacher)<option value="{{ $teacher->id }}" @selected(request('teacher_id') == $teacher->id)>{{ $teacher->full_name }}</option>@endforeach</select></div>
            <div class="col-md-2"><label class="form-label">Từ ngày</label><input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}"></div>
            <div class="col-md-2"><label class="form-label">Đến ngày</label><input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}"></div>
            <div class="col-md-2 d-flex align-items-end"><button class="btn btn-primary w-100" type="submit"><i data-lucide="filter"></i> Lọc</button></div>
        </form>
    </div></div>

    <div class="card table-card"><div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead><tr><th>Ngày/giờ thi</th><th>Kỳ thi</th><th>Môn/lớp</th><th>Giáo viên</th><th>Vai trò</th><th>Phòng</th><th class="text-end">Thao tác</th></tr></thead>
            <tbody>
                @forelse($invigilations as $assignment)
                    <tr>
                        <td><strong>{{ $assignment->exam?->exam_date?->format('d/m/Y') }}</strong><small class="d-block text-secondary">{{ $assignment->exam?->starts_at ?: '--' }} - {{ $assignment->exam?->ends_at ?: '--' }}</small></td>
                        <td>{{ $assignment->exam?->name }}<small class="d-block text-secondary">{{ $assignment->exam?->semester?->name }}</small></td>
                        <td>{{ $assignment->exam?->subject?->name }}<small class="d-block text-secondary">{{ $assignment->exam?->class?->name ?: 'Toàn khối' }}</small></td>
                        <td><strong>{{ $assignment->teacher?->full_name }}</strong><small class="d-block text-secondary">{{ $assignment->teacher?->teacher_code }}</small></td>
                        <td>{{ $roles[$assignment->role] ?? $assignment->role }}</td>
                        <td>{{ $assignment->exam?->room ?: '-' }}</td>
                        <td class="text-end">
                            @if (auth()->user()->hasPermission('exam-invigilations.edit'))
                                <a href="{{ route('admin.school.exam-invigilations.edit', $assignment) }}" class="btn btn-sm btn-light" data-modal-title="Sửa phân công coi thi" title="Sửa"><i data-lucide="pencil"></i></a>
                            @endif
                            @if (auth()->user()->hasPermission('exam-invigilations.delete'))
                                <form action="{{ route('admin.school.exam-invigilations.destroy', $assignment) }}" method="POST" class="d-inline" data-modal-title="Xóa phân công coi thi" data-confirm="Xóa phân công coi thi này?">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger" title="Xóa"><i data-lucide="trash-2"></i></button></form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="empty-state">Chưa có phân công coi thi.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>@if($invigilations->hasPages())<div class="p-3 border-top">{{ $invigilations->links('pagination::bootstrap-5') }}</div>@endif</div>
@endsection

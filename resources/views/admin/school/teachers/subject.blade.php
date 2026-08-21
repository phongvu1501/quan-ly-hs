@extends('admin.layouts.app')

@section('title', 'Giáo viên bộ môn')

@section('content')
    <div class="page-heading">
        <div>
            <p class="eyebrow">Phân công giáo viên</p>
            <h1>Giáo viên bộ môn</h1>
            <p class="text-secondary mb-0">Chi tiết môn học và lớp đang được phân công cho từng giáo viên.</p>
        </div>
    </div>

    <div class="card filter-card mb-4"><div class="card-body">
        <form method="GET" class="row g-3">
            @if (request('q')) <input type="hidden" name="q" value="{{ request('q') }}"> @endif
            <div class="col-md-5 col-xl-3">
                <label class="form-label" for="subject-teacher-year">Năm học</label>
                <select id="subject-teacher-year" name="academic_year_id" class="form-select">
                    <option value="">Tất cả năm học</option>
                    @foreach ($years as $year)
                        <option value="{{ $year->id }}" @selected(request('academic_year_id') == $year->id)>{{ $year->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3 col-xl-2 d-flex align-items-end">
                <button class="btn btn-primary w-100" type="submit"><i data-lucide="filter"></i> Lọc</button>
            </div>
        </form>
    </div></div>

    <div class="card table-card">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead><tr><th>Giáo viên</th><th>Môn giảng dạy</th><th>Lớp được phân công</th><th>Trạng thái</th><th class="text-end">Thao tác</th></tr></thead>
                <tbody>
                    @forelse ($teachers as $teacher)
                        <tr>
                            <td><strong>{{ $teacher->full_name }}</strong><small class="d-block text-secondary">{{ $teacher->teacher_code }}</small></td>
                            <td>{{ $teacher->assignments->pluck('subject.name')->filter()->unique()->join(', ') ?: '-' }}</td>
                            <td>{{ $teacher->assignments->pluck('class.name')->filter()->unique()->join(', ') ?: '-' }}</td>
                            <td><span class="status-dot {{ $teacher->status === 'active' ? 'status-on' : 'status-off' }}">{{ ['active' => 'Đang công tác', 'on_leave' => 'Tạm nghỉ', 'inactive' => 'Ngừng công tác'][$teacher->status] ?? $teacher->status }}</span></td>
                            <td class="text-end"><a href="{{ route('admin.school.teachers.history', $teacher) }}" data-modal-url="{{ route('admin.school.teachers.history', $teacher) }}" data-modal-title="Chi tiết giáo viên bộ môn" class="btn btn-sm btn-light" title="Chi tiết"><i data-lucide="eye"></i></a></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="empty-state">Chưa có giáo viên bộ môn phù hợp.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($teachers->hasPages())<div class="p-3 border-top">{{ $teachers->links('pagination::bootstrap-5') }}</div>@endif
    </div>
@endsection

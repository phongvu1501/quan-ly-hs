@extends('admin.layouts.app')

@section('title', 'Giáo viên chủ nhiệm')

@section('content')
    <div class="page-heading">
        <div>
            <p class="eyebrow">Phân công giáo viên</p>
            <h1>Giáo viên chủ nhiệm</h1>
            <p class="text-secondary mb-0">Danh sách giáo viên và lớp chủ nhiệm theo từng năm học.</p>
        </div>
    </div>

    <div class="card filter-card mb-4"><div class="card-body">
        <form method="GET" class="row g-3">
            @if (request('q')) <input type="hidden" name="q" value="{{ request('q') }}"> @endif
            <div class="col-md-5 col-xl-3">
                <label class="form-label" for="homeroom-year">Năm học</label>
                <select id="homeroom-year" name="academic_year_id" class="form-select">
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
                <thead><tr><th>Giáo viên</th><th>Lớp chủ nhiệm</th><th>Năm học</th><th>Trạng thái</th><th class="text-end">Thao tác</th></tr></thead>
                <tbody>
                    @forelse ($teachers as $teacher)
                        @foreach ($teacher->homeroomClasses as $class)
                            <tr>
                                <td><strong>{{ $teacher->full_name }}</strong><small class="d-block text-secondary">{{ $teacher->teacher_code }}</small></td>
                                <td><strong>{{ $class->name }}</strong><small class="d-block text-secondary">{{ $class->grade?->name }} · {{ $class->room ?: 'Chưa có phòng' }}</small></td>
                                <td>{{ $class->academicYear?->name }}</td>
                                <td><span class="status-dot {{ $teacher->status === 'active' ? 'status-on' : 'status-off' }}">{{ ['active' => 'Đang công tác', 'on_leave' => 'Tạm nghỉ', 'inactive' => 'Ngừng công tác'][$teacher->status] ?? $teacher->status }}</span></td>
                                <td class="text-end"><a href="{{ route('admin.school.teachers.history', $teacher) }}" data-modal-url="{{ route('admin.school.teachers.history', $teacher) }}" data-modal-title="Chi tiết giáo viên chủ nhiệm" class="btn btn-sm btn-light" title="Chi tiết"><i data-lucide="eye"></i></a></td>
                            </tr>
                        @endforeach
                    @empty
                        <tr><td colspan="5" class="empty-state">Chưa có giáo viên chủ nhiệm phù hợp.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($teachers->hasPages())<div class="p-3 border-top">{{ $teachers->links('pagination::bootstrap-5') }}</div>@endif
    </div>
@endsection

@extends('admin.layouts.app')

@section('title', 'Điểm danh')

@section('content')
    <div class="page-heading">
        <div><p class="eyebrow">Theo dõi chuyên cần</p><h1>Điểm danh</h1><p class="text-secondary mb-0">Lọc lịch sử có mặt, vắng, đi muộn và nghỉ có phép theo khoảng thời gian.</p></div>
    </div>
    <div class="card filter-card mb-4"><div class="card-body">
        <div class="d-flex align-items-center justify-content-between gap-3 mb-3"><div><strong>Bộ lọc điểm danh</strong><small class="d-block text-secondary">Có thể lọc theo ngày, lớp, môn và trạng thái.</small></div><a class="btn btn-sm btn-light" href="{{ route('admin.school.attendance.index') }}" title="Đặt lại bộ lọc"><i data-lucide="rotate-ccw"></i><span class="visually-hidden">Đặt lại</span></a></div>
        <form method="GET" class="row g-3">
            @if (request('q')) <input type="hidden" name="q" value="{{ request('q') }}"> @endif
            <div class="col-md-4 col-xl-2"><label class="form-label" for="attendance-from">Từ ngày</label><input id="attendance-from" type="date" name="date_from" class="form-control" value="{{ request('date_from', $dateFrom?->format('Y-m-d')) }}"></div>
            <div class="col-md-4 col-xl-2"><label class="form-label" for="attendance-to">Đến ngày</label><input id="attendance-to" type="date" name="date_to" class="form-control" value="{{ request('date_to', $dateTo?->format('Y-m-d')) }}"></div>
            <div class="col-md-4 col-xl-2"><label class="form-label" for="attendance-year">Năm học</label><select id="attendance-year" name="academic_year_id" class="form-select"><option value="">Tất cả năm học</option>@foreach($years as $year)<option value="{{ $year->id }}" @selected(request('academic_year_id') == $year->id)>{{ $year->name }}</option>@endforeach</select></div>
            <div class="col-md-4 col-xl-2"><label class="form-label" for="attendance-class">Lớp</label><select id="attendance-class" name="class_id" class="form-select"><option value="">Tất cả lớp</option>@foreach($classes as $class)<option value="{{ $class->id }}" data-year="{{ $class->academic_year_id }}" @selected(request('class_id') == $class->id)>{{ $class->name }} · {{ $class->academicYear?->name }}</option>@endforeach</select></div>
            <div class="col-md-4 col-xl-2"><label class="form-label" for="attendance-grade">Khối</label><select id="attendance-grade" name="grade_id" class="form-select"><option value="">Tất cả khối</option>@foreach($grades as $grade)<option value="{{ $grade->id }}" @selected(request('grade_id') == $grade->id)>Khối {{ $grade->level }}</option>@endforeach</select></div>
            <div class="col-md-4 col-xl-2"><label class="form-label" for="attendance-subject">Môn học</label><select id="attendance-subject" name="subject_id" class="form-select"><option value="">Đầu buổi hoặc tất cả môn</option>@foreach($subjects as $subject)<option value="{{ $subject->id }}" @selected(request('subject_id') == $subject->id)>{{ $subject->name }}</option>@endforeach</select></div>
            <div class="col-md-4 col-xl-2"><label class="form-label" for="attendance-status">Trạng thái</label><select id="attendance-status" name="status" class="form-select"><option value="">Tất cả trạng thái</option>@foreach($attendanceStatuses as $value => $label)<option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>@endforeach</select></div>
            <div class="col-md-4 col-xl-2 d-flex align-items-end"><button class="btn btn-primary w-100" type="submit"><i data-lucide="filter"></i> Lọc</button></div>
        </form>
    </div></div>
    <div class="card table-card"><div class="table-responsive"><table class="table table-hover align-middle mb-0"><thead><tr><th>Ngày</th><th>Học sinh</th><th>Lớp</th><th>Môn</th><th>Trạng thái</th><th>Lý do</th></tr></thead><tbody>
        @forelse($records as $record)
            <tr><td>{{ $record->attended_on?->format('d/m/Y') }}</td><td><strong>{{ $record->student?->full_name }}</strong><small class="d-block text-secondary">{{ $record->student?->student_code }}</small></td><td>{{ $record->class?->name }}</td><td>{{ $record->subject?->name ?: 'Đầu buổi' }}</td><td><span class="status-dot {{ $record->status === 'present' ? 'status-on' : 'status-off' }}">{{ $attendanceStatuses[$record->status] ?? $record->status }}</span></td><td>{{ $record->reason ?: '-' }}</td></tr>
        @empty
            <tr><td colspan="6" class="empty-state">Chưa có dữ liệu điểm danh phù hợp.</td></tr>
        @endforelse
    </tbody></table></div>
    @if ($records->hasPages())<div class="p-3 border-top">{{ $records->links('pagination::bootstrap-5') }}</div>@endif
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const year = document.getElementById('attendance-year');
            const classSelect = document.getElementById('attendance-class');
            if (!year || !classSelect) return;
            const refresh = () => {
                [...classSelect.options].forEach((option, index) => { if (index) option.hidden = Boolean(year.value) && option.dataset.year !== year.value; });
                if (classSelect.selectedOptions[0]?.hidden) classSelect.value = '';
            };
            year.addEventListener('change', refresh); refresh();
        });
    </script>
@endpush

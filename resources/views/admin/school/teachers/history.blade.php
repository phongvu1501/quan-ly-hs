@extends('admin.layouts.app')

@section('title', 'Lịch sử công tác giáo viên')

@section('content')
    <div class="page-heading page-heading-inline">
        <div>
            <p class="eyebrow">Hồ sơ nhân sự</p>
            <h1>Lịch sử công tác · {{ $teacher->full_name }}</h1>
            <p class="text-secondary mb-0">{{ $teacher->teacher_code }} · Môn, lớp và thời khóa biểu theo từng năm học.</p>
        </div>
        <a href="{{ route('admin.school.teachers.index') }}" class="btn btn-light"><i data-lucide="arrow-left"></i> Danh sách</a>
    </div>

    <div class="row g-3 mb-4"><div class="col-md-4"><div class="card h-100"><div class="card-body"><small class="text-secondary">Số năm có dữ liệu</small><strong class="d-block fs-3">{{ $years->count() }}</strong></div></div></div><div class="col-md-4"><div class="card h-100"><div class="card-body"><small class="text-secondary">Tổng phân công</small><strong class="d-block fs-3">{{ $assignments->count() }}</strong></div></div></div><div class="col-md-4"><div class="card h-100"><div class="card-body"><small class="text-secondary">Lớp từng chủ nhiệm</small><strong class="d-block fs-3">{{ $homeroomClasses->count() }}</strong></div></div></div></div>

    @forelse($years as $year)
        @php($yearAssignments = $assignments->where('academic_year_id', $year->id))
        @php($yearHomeroom = $homeroomClasses->where('academic_year_id', $year->id))
        @php($yearSchedules = $schedules->where('academic_year_id', $year->id))
        @php($yearInvigilations = $examInvigilations->filter(fn ($assignment) => $assignment->exam?->academic_year_id == $year->id))
        @php($yearSubjects = $subjectYears[$year->id] ?? collect())
        <section class="card table-card mb-4">
            <div class="card-body border-bottom"><h2 class="panel-title mb-1">Năm học {{ $year->name }}</h2><span class="text-secondary">{{ $yearAssignments->count() }} phân công · {{ $yearHomeroom->count() }} lớp chủ nhiệm</span></div>
            <div class="card-body">
                <div class="row g-4">
                    <div class="col-xl-7"><h3 class="section-label">Phân công giảng dạy</h3><div class="table-responsive"><table class="table table-sm align-middle"><thead><tr><th>Lớp</th><th>Môn</th><th>Học kỳ</th><th>Trạng thái</th></tr></thead><tbody>@forelse($yearAssignments as $assignment)<tr><td>{{ $assignment->class?->name ?: '-' }}<small class="d-block text-secondary">{{ $assignment->class?->grade?->name }}</small></td><td>{{ $assignment->subject?->name ?: '-' }}</td><td>{{ $assignment->semester?->name ?: 'Cả năm' }}</td><td>{{ $assignment->status === 'active' ? 'Đang phân công' : $assignment->status }}</td></tr>@empty<tr><td colspan="4" class="text-secondary">Chưa có phân công.</td></tr>@endforelse</tbody></table></div></div>
                    <div class="col-xl-5"><h3 class="section-label">Lớp chủ nhiệm và môn</h3>@forelse($yearHomeroom as $class)<div class="border-bottom py-2"><strong>{{ $class->name }}</strong><small class="d-block text-secondary">{{ $class->grade?->name }} · {{ $class->room ?: 'Chưa có phòng' }}</small></div>@empty<p class="text-secondary">Không chủ nhiệm trong năm này.</p>@endforelse<div class="d-flex flex-wrap gap-2 mt-3">@foreach($yearSubjects as $subject)<span class="badge text-bg-light">{{ $subject->name }}</span>@endforeach</div></div>
                </div>
                <h3 class="section-label mt-4">Thời khóa biểu đã lưu</h3><div class="table-responsive"><table class="table table-sm align-middle"><thead><tr><th>Học kỳ</th><th>Lớp</th><th>Môn</th><th>Thứ / tiết</th><th>Phòng</th></tr></thead><tbody>@forelse($yearSchedules as $schedule)<tr><td>{{ $schedule->semester?->name ?: '-' }}</td><td>{{ $schedule->class?->name ?: '-' }}</td><td>{{ $schedule->subject?->name ?: '-' }}</td><td>Thứ {{ $schedule->weekday }} · Tiết {{ $schedule->period }}</td><td>{{ $schedule->room ?: '-' }}</td></tr>@empty<tr><td colspan="5" class="text-secondary">Chưa có lịch dạy.</td></tr>@endforelse</tbody></table></div>
                <h3 class="section-label mt-4">Phân công coi thi</h3><div class="table-responsive"><table class="table table-sm align-middle"><thead><tr><th>Ngày</th><th>Kỳ thi</th><th>Môn / lớp</th><th>Thời gian</th><th>Vai trò</th></tr></thead><tbody>@forelse($yearInvigilations as $invigilation)<tr><td>{{ $invigilation->exam?->exam_date?->format('d/m/Y') }}</td><td>{{ $invigilation->exam?->name }}</td><td>{{ $invigilation->exam?->subject?->name }} · {{ $invigilation->exam?->class?->name ?: 'Toàn khối' }}</td><td>{{ $invigilation->exam?->starts_at ?: '--' }} - {{ $invigilation->exam?->ends_at ?: '--' }}</td><td>{{ ['chief' => 'Cán bộ coi thi 1', 'invigilator' => 'Cán bộ coi thi 2', 'reserve' => 'Giáo viên dự phòng'][$invigilation->role] ?? $invigilation->role }}</td></tr>@empty<tr><td colspan="5" class="text-secondary">Chưa có lịch coi thi.</td></tr>@endforelse</tbody></table></div>
            </div>
        </section>
    @empty
        <div class="card"><div class="empty-state">Chưa có dữ liệu công tác theo năm học.</div></div>
    @endforelse
@endsection

@extends('admin.layouts.app')

@section('title', 'Điểm')

@section('content')
    @php($scoreTypeLabels = ['oral' => 'Miệng', 'fifteen_minute' => '15 phút', 'one_period' => '1 tiết', 'midterm' => 'Giữa kỳ', 'final' => 'Cuối kỳ'])
    <div class="page-heading">
        <div>
            <p class="eyebrow">Kết quả học tập</p>
            <h1>Sổ điểm</h1>
            <p class="text-secondary mb-0">Mỗi học sinh chỉ hiển thị một dòng theo lớp, môn và học kỳ. Mở chi tiết để xem toàn bộ điểm.</p>
        </div>
    </div>

    <div class="card filter-card mb-4">
        <div class="card-body">
            <div class="d-flex align-items-center justify-content-between gap-3 mb-3">
                <div><strong>Bộ lọc điểm</strong><small class="d-block text-secondary">Kết hợp nhiều điều kiện để thu hẹp danh sách.</small></div>
                <a class="btn btn-sm btn-light" href="{{ route('admin.school.scores.index') }}" title="Đặt lại bộ lọc"><i data-lucide="rotate-ccw"></i><span class="visually-hidden">Đặt lại</span></a>
            </div>
            <form method="GET" class="row g-3">
                @if (request('q')) <input type="hidden" name="q" value="{{ request('q') }}"> @endif
                <div class="col-md-4 col-xl-2"><label class="form-label" for="score-year">Năm học</label><select id="score-year" name="academic_year_id" class="form-select"><option value="">Tất cả năm học</option>@foreach($years as $year)<option value="{{ $year->id }}" @selected(request('academic_year_id') == $year->id)>{{ $year->name }}</option>@endforeach</select></div>
                <div class="col-md-4 col-xl-2"><label class="form-label" for="score-semester">Học kỳ</label><select id="score-semester" name="semester_id" class="form-select"><option value="">Tất cả học kỳ</option>@foreach($semesters as $semester)<option value="{{ $semester->id }}" data-year="{{ $semester->academic_year_id }}" @selected(request('semester_id') == $semester->id)>{{ $semester->academicYear?->name }} · {{ $semester->name }}</option>@endforeach</select></div>
                <div class="col-md-4 col-xl-2"><label class="form-label" for="score-class">Lớp</label><select id="score-class" name="class_id" class="form-select"><option value="">Tất cả lớp</option>@foreach($classes as $class)<option value="{{ $class->id }}" data-year="{{ $class->academic_year_id }}" @selected(request('class_id') == $class->id)>{{ $class->name }} · {{ $class->academicYear?->name }}</option>@endforeach</select></div>
                <div class="col-md-4 col-xl-2"><label class="form-label" for="score-grade">Khối</label><select id="score-grade" name="grade_id" class="form-select"><option value="">Tất cả khối</option>@foreach($grades as $grade)<option value="{{ $grade->id }}" @selected(request('grade_id') == $grade->id)>Khối {{ $grade->level }}</option>@endforeach</select></div>
                <div class="col-md-4 col-xl-2"><label class="form-label" for="score-subject">Môn học</label><select id="score-subject" name="subject_id" class="form-select"><option value="">Tất cả môn</option>@foreach($subjects as $subject)<option value="{{ $subject->id }}" @selected(request('subject_id') == $subject->id)>{{ $subject->name }}</option>@endforeach</select></div>
                <div class="col-md-4 col-xl-2"><label class="form-label" for="score-type">Loại điểm</label><select id="score-type" name="score_type" class="form-select"><option value="">Tất cả loại điểm</option>@foreach($scoreTypes as $value => $label)<option value="{{ $value }}" @selected(request('score_type') === $value)>{{ $label }}</option>@endforeach</select></div>
                <div class="col-md-4 col-xl-2"><label class="form-label" for="score-from">Từ ngày</label><input id="score-from" type="date" name="date_from" class="form-control" value="{{ request('date_from') }}"></div>
                <div class="col-md-4 col-xl-2"><label class="form-label" for="score-to">Đến ngày</label><input id="score-to" type="date" name="date_to" class="form-control" value="{{ request('date_to') }}"></div>
                <div class="col-md-4 col-xl-2 d-flex align-items-end"><button class="btn btn-primary w-100" type="submit"><i data-lucide="filter"></i> Lọc điểm</button></div>
            </form>
        </div>
    </div>

    <div class="card table-card">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead><tr><th>Học sinh</th><th>Lớp</th><th>Môn học</th><th>Học kỳ</th><th>Điểm</th><th>Ngày gần nhất</th><th class="text-end">Chi tiết</th></tr></thead>
                <tbody>
                    @forelse($scores as $group)
                        @php($modalId = 'scoreDetailModal-' . $group->student?->id . '-' . $group->subject?->id . '-' . $group->semester?->id)
                        <tr>
                            <td><strong>{{ $group->student?->full_name }}</strong><small class="d-block text-secondary">{{ $group->student?->student_code }}</small></td>
                            <td>{{ $group->class?->name ?: '-' }}</td>
                            <td>{{ $group->subject?->name ?: '-' }}</td>
                            <td>{{ $group->semester?->name ?: '-' }}<small class="d-block text-secondary">{{ $group->academicYear?->name }}</small></td>
                            <td><span class="badge text-bg-light">{{ $group->scores->count() }} điểm</span><strong class="d-block mt-1">TB {{ number_format((float) $group->scores->avg('score'), 2) }}</strong></td>
                            <td>{{ $group->scores->first()?->assessed_on?->format('d/m/Y') ?: '-' }}</td>
                            <td class="text-end"><button type="button" class="btn btn-sm btn-light" data-bs-toggle="modal" data-bs-target="#{{ $modalId }}" title="Xem chi tiết điểm"><i data-lucide="list-checks"></i><span class="visually-hidden">Xem chi tiết điểm</span></button></td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="empty-state">Chưa có điểm phù hợp với bộ lọc.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($scores->hasPages())<div class="p-3 border-top">{{ $scores->links('pagination::bootstrap-5') }}</div>@endif
    </div>

    @foreach($scores as $group)
        @php($modalId = 'scoreDetailModal-' . $group->student?->id . '-' . $group->subject?->id . '-' . $group->semester?->id)
        <div class="modal fade" id="{{ $modalId }}" tabindex="-1" aria-labelledby="{{ $modalId }}-title" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable"><div class="modal-content">
                <div class="modal-header"><div><h2 class="modal-title fs-5" id="{{ $modalId }}-title">Chi tiết điểm · {{ $group->student?->full_name }}</h2><small class="text-secondary">{{ $group->class?->name }} · {{ $group->subject?->name }} · {{ $group->semester?->name }}</small></div><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button></div>
                <div class="modal-body"><div class="table-responsive"><table class="table align-middle mb-0"><thead><tr><th>Loại điểm</th><th>Điểm</th><th>Ngày</th><th>Ghi chú</th></tr></thead><tbody>
                    @foreach($group->scores as $score)<tr><td>{{ $scoreTypeLabels[$score->score_type] ?? $score->score_type }}</td><td><strong>{{ number_format((float) $score->score, 2) }}</strong></td><td>{{ $score->assessed_on?->format('d/m/Y') ?: '-' }}</td><td>{{ $score->note ?: '-' }}</td></tr>@endforeach
                </tbody></table></div></div>
                <div class="modal-footer"><span class="text-secondary me-auto">{{ $group->scores->count() }} điểm · Trung bình {{ number_format((float) $group->scores->avg('score'), 2) }}</span><button type="button" class="btn btn-light" data-bs-dismiss="modal">Đóng</button></div>
            </div></div>
        </div>
    @endforeach
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const year = document.getElementById('score-year');
            const semester = document.getElementById('score-semester');
            const classSelect = document.getElementById('score-class');
            if (!year || !semester || !classSelect) return;
            const refresh = (select, yearId) => {
                [...select.options].forEach((option, index) => {
                    if (index === 0) return;
                    option.hidden = Boolean(yearId) && option.dataset.year !== yearId;
                });
                if (select.selectedOptions[0]?.hidden) select.value = '';
            };
            const apply = () => { refresh(semester, year.value); refresh(classSelect, year.value); };
            year.addEventListener('change', apply);
            apply();
        });
    </script>
@endpush

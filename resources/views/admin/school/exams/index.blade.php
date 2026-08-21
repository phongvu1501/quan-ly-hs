@extends('admin.layouts.app')
@section('title', 'Lịch thi')
@section('content')<div class="page-heading page-heading-inline">
        <div>
            <p class="eyebrow">Kiểm tra và thi</p>
            <h1>Lịch thi</h1>
            <p class="text-secondary mb-0">Quản lý lịch giữa kỳ, cuối kỳ và các bài kiểm tra tập trung.</p>
        </div><a href="{{ route('admin.school.exams.create') }}" class="btn btn-primary" data-modal-title="Thêm lịch thi"><i
                data-lucide="plus"></i> Thêm lịch thi</a>
    </div>
    <div class="card filter-card mb-4"><div class="card-body">
        <div class="d-flex align-items-center justify-content-between gap-3 mb-3"><div><strong>Bộ lọc lịch thi</strong><small class="d-block text-secondary">Lọc theo năm học, học kỳ, môn, lớp, loại và ngày thi.</small></div><a class="btn btn-sm btn-light" href="{{ route('admin.school.exams.index') }}" title="Đặt lại bộ lọc"><i data-lucide="rotate-ccw"></i><span class="visually-hidden">Đặt lại</span></a></div>
        <form method="GET" class="row g-3">
            @if (request('q')) <input type="hidden" name="q" value="{{ request('q') }}"> @endif
            <div class="col-md-4 col-xl-2"><label class="form-label" for="exam-year">Năm học</label><select id="exam-year" name="academic_year_id" class="form-select"><option value="">Tất cả năm học</option>@foreach($years as $year)<option value="{{ $year->id }}" @selected(request('academic_year_id') == $year->id)>{{ $year->name }}</option>@endforeach</select></div>
            <div class="col-md-4 col-xl-2"><label class="form-label" for="exam-semester">Học kỳ</label><select id="exam-semester" name="semester_id" class="form-select"><option value="">Tất cả học kỳ</option>@foreach($semesters as $semester)<option value="{{ $semester->id }}" data-year="{{ $semester->academic_year_id }}" @selected(request('semester_id') == $semester->id)>{{ $semester->academicYear?->name }} · {{ $semester->name }}</option>@endforeach</select></div>
            <div class="col-md-4 col-xl-2"><label class="form-label" for="exam-class">Lớp</label><select id="exam-class" name="class_id" class="form-select"><option value="">Toàn khối hoặc tất cả lớp</option>@foreach($classes as $class)<option value="{{ $class->id }}" data-year="{{ $class->academic_year_id }}" @selected(request('class_id') == $class->id)>{{ $class->name }} · {{ $class->academicYear?->name }}</option>@endforeach</select></div>
            <div class="col-md-4 col-xl-2"><label class="form-label" for="exam-subject">Môn học</label><select id="exam-subject" name="subject_id" class="form-select"><option value="">Tất cả môn</option>@foreach($subjects as $subject)<option value="{{ $subject->id }}" @selected(request('subject_id') == $subject->id)>{{ $subject->name }}</option>@endforeach</select></div>
            <div class="col-md-4 col-xl-2"><label class="form-label" for="exam-type">Loại kỳ thi</label><select id="exam-type" name="exam_type" class="form-select"><option value="">Tất cả loại</option>@foreach($examTypes as $value => $label)<option value="{{ $value }}" @selected(request('exam_type') === $value)>{{ $label }}</option>@endforeach</select></div>
            <div class="col-md-4 col-xl-2"><label class="form-label" for="exam-from">Từ ngày</label><input id="exam-from" type="date" name="date_from" class="form-control" value="{{ request('date_from') }}"></div>
            <div class="col-md-4 col-xl-2"><label class="form-label" for="exam-to">Đến ngày</label><input id="exam-to" type="date" name="date_to" class="form-control" value="{{ request('date_to') }}"></div>
            <div class="col-md-4 col-xl-2 d-flex align-items-end"><button class="btn btn-primary w-100" type="submit"><i data-lucide="filter"></i> Lọc</button></div>
        </form>
    </div></div>
    <div class="card table-card">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Ngày</th>
                        <th>Kỳ thi</th>
                        <th>Môn</th>
                        <th>Lớp/khối</th>
                        <th>Thời gian</th>
                        <th>Phòng</th>
                        <th>Giáo viên coi thi</th>
                        <th class="text-end">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($exams as $exam)
                        <tr>
                            <td><strong>{{ $exam->exam_date?->format('d/m/Y') }}</strong><small
                                    class="d-block text-secondary">{{ $exam->semester?->name }}</small></td>
                            <td>{{ $exam->name }}<small class="d-block text-secondary">{{ $exam->exam_type }}</small></td>
                            <td>{{ $exam->subject?->name }}</td>
                            <td>{{ $exam->class?->name ?: 'Toàn khối' }}</td>
                            <td>{{ $exam->starts_at ?: '--' }} - {{ $exam->ends_at ?: '--' }}</td>
                            <td>{{ $exam->room ?: '-' }}</td>
                            <td>
                                @forelse($exam->invigilations as $invigilation)
                                    <span class="d-block">{{ $invigilation->teacher?->full_name }}</span>
                                @empty
                                    <span class="text-secondary">Chưa phân công</span>
                                @endforelse
                                <a href="{{ route('admin.school.exam-invigilations.create', ['exam_id' => $exam->id]) }}" data-modal-url="{{ route('admin.school.exam-invigilations.create', ['exam_id' => $exam->id]) }}" data-modal-title="Phân công coi thi" class="btn btn-sm btn-light mt-1" title="Phân công coi thi"><i data-lucide="user-plus"></i><span class="visually-hidden">Phân công</span></a>
                            </td>
                            <td class="text-end">
                                <a href="{{ route('admin.school.exams.edit', $exam) }}"
                                    class="btn btn-sm btn-light" data-modal-title="Sửa lịch thi" title="Sửa"><i
                                        data-lucide="pencil"></i></a>
                                <form action="{{ route('admin.school.exams.destroy', $exam) }}" method="POST"
                                    data-modal-title="Xóa lịch thi" data-confirm="Xóa lịch thi này?">@csrf
                                    @method('DELETE')<button class="btn btn-sm btn-outline-danger" title="Xóa"><i
                                            data-lucide="trash-2"></i></button></form>
                            </td>
                    </tr>@empty<tr>
                            <td colspan="8" class="empty-state">Chưa có lịch thi.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($exams->hasPages())
            <div class="p-3 border-top">{{ $exams->links('pagination::bootstrap-5') }}</div>
        @endif
</div>@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const year = document.getElementById('exam-year');
            const semester = document.getElementById('exam-semester');
            const classSelect = document.getElementById('exam-class');
            if (!year || !semester || !classSelect) return;
            const refresh = (select) => { [...select.options].forEach((option, index) => { if (index) option.hidden = Boolean(year.value) && option.dataset.year !== year.value; }); if (select.selectedOptions[0]?.hidden) select.value = ''; };
            const apply = () => { refresh(semester); refresh(classSelect); };
            year.addEventListener('change', apply); apply();
        });
    </script>
@endpush

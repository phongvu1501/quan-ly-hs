@extends('admin.layouts.app')

@section('title', 'Phân công bộ môn')

@section('content')
    <div class="page-heading">
        <div>
            <p class="eyebrow">Giáo viên bộ môn</p>
            <h1>Phân công giáo viên</h1>
            <p class="text-secondary mb-0">Gắn giáo viên với môn học, lớp và học kỳ để nhập điểm, điểm danh và nhận xét.</p>
        </div>
    </div>
    @if ($classes->isEmpty() || $teachers->isEmpty())
        <div class="alert alert-warning">Cần có ít nhất một lớp và một giáo viên trước khi phân công. <a
                href="{{ route('admin.school.teachers.create') }}" data-modal-title="Thêm giáo viên">Thêm giáo viên</a> hoặc
            <a href="{{ route('admin.school.classes.create') }}" data-modal-title="Thêm lớp học">thêm lớp học</a>.</div>
    @endif
    <div class="card filter-card mb-4"><div class="card-body">
        <div class="d-flex align-items-center justify-content-between gap-3 mb-3"><div><strong>Bộ lọc phân công</strong><small class="d-block text-secondary">Chọn năm học, học kỳ, lớp, môn hoặc giáo viên.</small></div><a class="btn btn-sm btn-light" href="{{ route('admin.school.assignments.index') }}" title="Đặt lại bộ lọc"><i data-lucide="rotate-ccw"></i><span class="visually-hidden">Đặt lại</span></a></div>
        <form method="GET" class="row g-3">
            @if (request('q')) <input type="hidden" name="q" value="{{ request('q') }}"> @endif
            <div class="col-md-4 col-xl-2"><label class="form-label" for="assignment-filter-year">Năm học</label><select id="assignment-filter-year" name="academic_year_id" class="form-select"><option value="">Tất cả năm học</option>@foreach($years as $year)<option value="{{ $year->id }}" @selected(request('academic_year_id') == $year->id)>{{ $year->name }}</option>@endforeach</select></div>
            <div class="col-md-4 col-xl-2"><label class="form-label" for="assignment-filter-semester">Học kỳ</label><select id="assignment-filter-semester" name="semester_id" class="form-select"><option value="">Tất cả học kỳ</option>@foreach($semesters as $semester)<option value="{{ $semester->id }}" data-year="{{ $semester->academic_year_id }}" @selected(request('semester_id') == $semester->id)>{{ $semester->academicYear?->name }} · {{ $semester->name }}</option>@endforeach</select></div>
            <div class="col-md-4 col-xl-2"><label class="form-label" for="assignment-filter-class">Lớp</label><select id="assignment-filter-class" name="class_id" class="form-select"><option value="">Tất cả lớp</option>@foreach($classes as $class)<option value="{{ $class->id }}" data-year="{{ $class->academic_year_id }}" @selected(request('class_id') == $class->id)>{{ $class->name }} · {{ $class->academicYear?->name }}</option>@endforeach</select></div>
            <div class="col-md-4 col-xl-2"><label class="form-label" for="assignment-filter-subject">Môn học</label><select id="assignment-filter-subject" name="subject_id" class="form-select"><option value="">Tất cả môn</option>@foreach($subjects as $subject)<option value="{{ $subject->id }}" @selected(request('subject_id') == $subject->id)>{{ $subject->name }}</option>@endforeach</select></div>
            <div class="col-md-4 col-xl-2"><label class="form-label" for="assignment-filter-teacher">Giáo viên</label><select id="assignment-filter-teacher" name="teacher_id" class="form-select"><option value="">Tất cả giáo viên</option>@foreach($teachers as $teacher)<option value="{{ $teacher->id }}" @selected(request('teacher_id') == $teacher->id)>{{ $teacher->full_name }}</option>@endforeach</select></div>
            <div class="col-md-4 col-xl-2 d-flex align-items-end"><button class="btn btn-primary w-100" type="submit"><i data-lucide="filter"></i> Lọc</button></div>
        </form>
    </div></div>
    <div class="row g-4">
        <div class="col-xl-4">
            <div class="card form-card">
                <div class="card-body">
                    <div class="section-label">Thêm phân công</div>
                    <form action="{{ route('admin.school.assignments.store') }}" method="POST" id="assignment-form">@csrf
                        <div class="mb-3"><label class="form-label" for="assignment-year">Năm học</label><select
                                name="academic_year_id" id="assignment-year" class="form-select" required>
                                <option value="">Chọn năm học</option>
                                @foreach ($years as $year)
                                    <option value="{{ $year->id }}" @selected($year->id == $currentYearId)>{{ $year->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3"><label class="form-label" for="assignment-semester">Học kỳ</label><select
                                name="semester_id" id="assignment-semester" class="form-select">
                                <option value="">Cả năm</option>
                                @foreach ($semesters as $semester)
                                    <option value="{{ $semester->id }}" data-year="{{ $semester->academic_year_id }}">
                                        {{ $semester->academicYear?->name }} · {{ $semester->name }}</option>
                                @endforeach
                            </select>
                            <div class="form-text" id="assignment-semester-help"></div>
                        </div>
                        <div class="mb-3"><label class="form-label" for="assignment-class">Lớp</label><select
                                name="class_id" id="assignment-class" class="form-select" required>
                                <option value="">Chọn lớp</option>
                                @foreach ($classes as $class)
                                    <option value="{{ $class->id }}" data-year="{{ $class->academic_year_id }}">
                                        {{ $class->name }} · {{ $class->grade?->name }} ·
                                        {{ $class->academicYear?->name }}</option>
                                @endforeach
                            </select>
                            <div class="form-text" id="assignment-class-help"></div>
                        </div>
                        <div class="mb-3"><label class="form-label" for="assignment-subject">Môn học</label><select
                                name="subject_id" id="assignment-subject" class="form-select" required>
                                <option value="">Chọn môn học</option>
                                @foreach ($subjects as $subject)
                                    <option value="{{ $subject->id }}">{{ $subject->name }} · {{ $subject->code }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3"><label class="form-label" for="assignment-teacher">Giáo viên bộ
                                môn</label><select name="teacher_id" id="assignment-teacher" class="form-select" required>
                                <option value="">Chọn giáo viên</option>
                                @foreach ($teachers as $teacher)
                                    <option value="{{ $teacher->id }}">
                                        {{ $teacher->full_name }} · {{ $teacher->teacher_code }}</option>
                                @endforeach
                            </select>
                            <div class="form-text" id="assignment-teacher-help">Danh sách gồm tất cả giáo viên đang công tác tại trường.</div>
                        </div>
                        <button class="btn btn-primary w-100"><i data-lucide="link"></i> Lưu phân công</button>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-xl-8">
            <div class="card table-card">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Lớp</th>
                                <th>Môn học</th>
                                <th>Giáo viên</th>
                                <th>Học kỳ</th>
                                <th class="text-end">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($assignments as $assignment)
                                <tr>
                                    <td><strong>{{ $assignment->class?->name }}</strong><small
                                            class="d-block text-secondary">{{ $assignment->academicYear?->name }}</small>
                                    </td>
                                    <td>{{ $assignment->subject?->name }}</td>
                                    <td>{{ $assignment->teacher?->full_name }}</td>
                                    <td>{{ $assignment->semester?->name ?: 'Cả năm' }}</td>
                                    <td class="text-end">
                                        <form action="{{ route('admin.school.assignments.destroy', $assignment) }}"
                                            method="POST" data-modal-title="Xóa phân công"
                                            data-confirm="Xóa phân công này?">@csrf @method('DELETE')<button
                                                class="btn btn-sm btn-outline-danger" title="Xóa"><i
                                                    data-lucide="trash-2"></i></button></form>
                                    </td>
                            </tr>@empty<tr>
                                    <td colspan="5" class="empty-state">Chưa có phân công.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if ($assignments->hasPages())
                    <div class="p-3 border-top">{{ $assignments->links('pagination::bootstrap-5') }}</div>
                @endif
            </div>
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const year = document.getElementById('assignment-year');
            const semester = document.getElementById('assignment-semester');
            const subject = document.getElementById('assignment-subject');
            const classSelect = document.getElementById('assignment-class');
            const teacher = document.getElementById('assignment-teacher');
            const help = document.getElementById('assignment-teacher-help');
            const semesterHelp = document.getElementById('assignment-semester-help');
            const classHelp = document.getElementById('assignment-class-help');
            if (!year || !semester || !subject || !classSelect || !teacher) return;
            const filterOptions = (select, predicate) => {
                let count = 0;
                [...select.options].forEach((option, index) => {
                    if (index === 0 && !option.value) return;
                    option.hidden = !predicate(option);
                    if (!option.hidden) count++;
                });
                if (select.selectedOptions[0]?.hidden) select.value = '';
                return count;
            };
            const refresh = () => {
                const yearId = year.value;
                const semesterCount = filterOptions(semester, option => option.dataset.year === yearId);
                const classCount = filterOptions(classSelect, option => option.dataset.year === yearId);
                semesterHelp.textContent = semesterCount ? '' :
                    'Chưa có học kỳ cho năm học này. Hãy tạo năm học trước.';
                classHelp.textContent = classCount ? '' :
                    'Chưa có lớp trong năm học này. Hãy tạo lớp học trước.';
                [...teacher.options].forEach(option => option.hidden = false);
                help.textContent = 'Danh sách gồm tất cả giáo viên đang công tác tại trường.';
            };
            year.addEventListener('change', refresh);
            subject.addEventListener('change', refresh);
            refresh();
        });
    </script>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const year = document.getElementById('assignment-filter-year');
            const semester = document.getElementById('assignment-filter-semester');
            const classSelect = document.getElementById('assignment-filter-class');
            if (!year || !semester || !classSelect) return;
            const refresh = (select) => {
                [...select.options].forEach((option, index) => { if (index) option.hidden = Boolean(year.value) && option.dataset.year !== year.value; });
                if (select.selectedOptions[0]?.hidden) select.value = '';
            };
            const apply = () => { refresh(semester); refresh(classSelect); };
            year.addEventListener('change', apply); apply();
        });
    </script>
@endpush

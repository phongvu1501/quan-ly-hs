@extends('admin.layouts.app')

@section('title', 'Thời khóa biểu')

@push('styles')
    <style>
        .schedule-filter-card {
            margin-bottom: 20px;
            padding: 16px;
            border: 1px solid var(--line);
            border-radius: 8px;
            background: var(--surface);
        }

        .schedule-filter-grid {
            display: grid;
            grid-template-columns: minmax(220px, 1.4fr) repeat(3, minmax(145px, .8fr)) auto;
            gap: 10px;
            align-items: end;
        }

        .schedule-filter-grid label {
            display: block;
            margin-bottom: 5px;
            color: var(--muted);
            font-size: 11px;
            font-weight: 750;
            letter-spacing: .04em;
            text-transform: uppercase;
        }

        .schedule-filter-grid .search-control {
            position: relative;
        }

        .schedule-filter-grid .search-control>svg {
            position: absolute;
            top: 11px;
            left: 11px;
            width: 16px;
            color: var(--muted);
            pointer-events: none;
        }

        .schedule-filter-grid .search-control input {
            padding-left: 34px;
        }

        .schedule-filter-actions {
            display: flex;
            gap: 7px;
        }

        .schedule-filter-actions .btn {
            min-height: 38px;
            white-space: nowrap;
        }

        .schedule-summary {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 14px;
            color: var(--muted);
            font-size: 12px;
        }

        .schedule-summary span {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 5px 9px;
            border: 1px solid var(--line);
            border-radius: 999px;
            background: #fbfcfe;
        }

        .schedule-summary strong {
            color: var(--ink);
        }

        .class-schedule {
            margin-bottom: 18px;
            overflow: hidden;
            border: 1px solid var(--line);
            border-radius: 8px;
            background: var(--surface);
        }

        .class-schedule-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            padding: 15px 18px;
            border-bottom: 1px solid var(--line);
        }

        .class-schedule-title {
            display: flex;
            align-items: center;
            gap: 11px;
            min-width: 0;
        }

        .class-schedule-mark {
            display: grid;
            width: 34px;
            height: 34px;
            flex: 0 0 auto;
            place-items: center;
            border-radius: 7px;
            background: var(--accent-soft);
            color: var(--accent);
        }

        .class-schedule-title h2 {
            margin: 0;
            font-size: 16px;
            font-weight: 750;
        }

        .class-schedule-title p {
            margin: 2px 0 0;
            color: var(--muted);
            font-size: 12px;
        }

        .class-schedule-count {
            flex: 0 0 auto;
            color: var(--muted);
            font-size: 12px;
            font-weight: 650;
        }

        .schedule-grid-wrap {
            overflow-x: auto;
        }

        .schedule-grid {
            width: 100%;
            min-width: 880px;
            table-layout: fixed;
            border-collapse: collapse;
        }

        .schedule-grid th,
        .schedule-grid td {
            border-right: 1px solid var(--line);
            border-bottom: 1px solid var(--line);
        }

        .schedule-grid th:last-child,
        .schedule-grid td:last-child {
            border-right: 0;
        }

        .schedule-grid tbody tr:last-child td {
            border-bottom: 0;
        }

        .schedule-grid thead th {
            height: 42px;
            padding: 7px 9px;
            background: #f8fafc;
            color: var(--muted);
            font-size: 11px;
            font-weight: 750;
            letter-spacing: .03em;
            text-align: left;
            text-transform: uppercase;
        }

        .schedule-grid thead th:first-child {
            width: 74px;
            text-align: center;
        }

        .schedule-grid tbody td {
            height: 92px;
            padding: 6px;
            vertical-align: top;
        }

        .schedule-grid .period-cell {
            padding: 13px 5px;
            background: #fbfcfe;
            color: var(--muted);
            font-size: 12px;
            font-weight: 750;
            text-align: center;
        }

        .schedule-slot {
            display: grid;
            align-content: start;
            gap: 4px;
            width: 100%;
            min-height: 78px;
            padding: 9px 9px 8px 10px;
            border-top: 0;
            border-right: 0;
            border-bottom: 0;
            border-left: 3px solid #5aa6a5;
            border-radius: 4px;
            background: #eef8f7;
            font-family: inherit;
            text-align: left;
            cursor: pointer;
        }

        .schedule-slot:hover {
            filter: brightness(.975);
        }

        .schedule-slot strong {
            overflow: hidden;
            color: var(--ink);
            font-size: 12px;
            font-weight: 750;
            line-height: 1.25;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .schedule-slot span,
        .schedule-slot small {
            overflow: hidden;
            color: var(--muted);
            font-size: 11px;
            line-height: 1.25;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .schedule-slot small {
            color: #4d7b7b;
        }

        .schedule-slot.tone-1 {
            border-left-color: #5b86c5;
            background: #f0f5fb;
        }

        .schedule-slot.tone-2 {
            border-left-color: #c68a32;
            background: #fff8eb;
        }

        .schedule-slot.tone-3 {
            border-left-color: #b86b68;
            background: #fff3f2;
        }

        .schedule-slot.tone-4 {
            border-left-color: #6a9a72;
            background: #f1f8f2;
        }

        .schedule-add-slot {
            display: grid;
            width: 100%;
            min-height: 78px;
            place-items: center;
            border: 1px dashed transparent;
            border-radius: 4px;
            background: transparent;
            color: transparent;
        }

        .schedule-add-slot svg {
            width: 17px;
            height: 17px;
        }

        .schedule-grid td:hover .schedule-add-slot {
            border-color: #b8d6d5;
            background: #f5fbfb;
            color: var(--accent);
        }

        .schedule-editor-modal .modal-content {
            border: 0;
            border-radius: 8px;
        }

        .schedule-editor-modal .modal-header,
        .schedule-editor-modal .modal-footer {
            border-color: var(--line);
        }

        .schedule-editor-modal .modal-title {
            font-size: 17px;
            font-weight: 750;
        }

        .schedule-editor-modal .modal-footer {
            justify-content: flex-end;
        }

        .schedule-editor-modal .modal-footer .delete-form {
            margin-right: auto;
        }

        .schedule-empty {
            padding: 48px 20px;
            border: 1px dashed #cbd5e1;
            border-radius: 8px;
            background: #fff;
            color: var(--muted);
            text-align: center;
        }

        .schedule-empty svg {
            width: 25px;
            margin-bottom: 9px;
            color: var(--accent);
        }

        @media (max-width: 1100px) {
            .schedule-filter-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .schedule-filter-grid .search-field {
                grid-column: 1 / -1;
            }

            .schedule-filter-actions {
                justify-content: flex-start;
            }
        }

        @media (max-width: 640px) {
            .schedule-filter-card {
                padding: 12px;
            }

            .schedule-filter-grid {
                grid-template-columns: 1fr;
            }

            .schedule-filter-grid .search-field {
                grid-column: auto;
            }

            .class-schedule-header {
                align-items: flex-start;
                flex-direction: column;
                gap: 8px;
                padding: 13px 14px;
            }
        }
    </style>
@endpush

@section('content')
    @php
        $weekdays = [1 => 'Thứ 2', 2 => 'Thứ 3', 3 => 'Thứ 4', 4 => 'Thứ 5', 5 => 'Thứ 6', 6 => 'Thứ 7'];
        $periods = range(1, max(8, (int) $schedules->max('period')));
        $groupedSchedules = $schedules->groupBy('class_id');
        $displayClasses = request()->filled('q')
            ? $classes->whereIn('id', $schedules->pluck('class_id')->unique())
            : ($selectedClassId
                ? $classes->where('id', $selectedClassId)
                : $classes);
        $periodTimes = [
            1 => '07:00',
            2 => '07:50',
            3 => '08:40',
            4 => '09:40',
            5 => '10:30',
            6 => '13:30',
            7 => '14:20',
            8 => '15:10',
        ];
    @endphp

    <div class="page-heading page-heading-inline">
        <div>
            <p class="eyebrow">Lịch học</p>
            <h1>Thời khóa biểu theo lớp</h1>
            <p class="text-secondary mb-0">Mỗi lớp có một bảng riêng để theo dõi môn, giáo viên và phòng học theo từng tiết.
            </p>
        </div>
        <button class="btn btn-primary" type="button" data-bs-toggle="modal" data-bs-target="#scheduleEditorModal"
            data-schedule-mode="create">
            <i data-lucide="calendar-plus"></i> Xếp tiết học
        </button>
    </div>

    <div class="schedule-filter-card">
        <form method="GET" class="schedule-filter-grid">
            <div class="search-field">
                <label for="schedule-search">Tìm kiếm</label>
                <div class="search-control">
                    <i data-lucide="search" aria-hidden="true"></i>
                    <input id="schedule-search" type="search" name="q" value="{{ request('q') }}"
                        class="form-control" placeholder="Môn, lớp, giáo viên hoặc phòng" autocomplete="off">
                </div>
            </div>
            <div>
                <label for="schedule-year">Năm học</label>
                <select id="schedule-year" name="academic_year_id" class="form-select">
                    @foreach ($years as $academicYear)
                        <option value="{{ $academicYear->id }}" @selected($academicYear->id == $selectedYearId)>{{ $academicYear->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="schedule-semester">Học kỳ</label>
                <select id="schedule-semester" name="semester_id" class="form-select">
                    <option value="">Tất cả học kỳ</option>
                    @foreach ($semesters as $semester)
                        <option value="{{ $semester->id }}" @selected($semester->id == $selectedSemesterId)>{{ $semester->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="schedule-class">Lớp học</label>
                <select id="schedule-class" name="class_id" class="form-select">
                    <option value="">Tất cả lớp</option>
                    @foreach ($classes as $class)
                        <option value="{{ $class->id }}" @selected($class->id == $selectedClassId)>{{ $class->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="schedule-filter-actions">
                <button class="btn btn-primary" type="submit"><i data-lucide="filter"></i> Lọc</button>
                <a class="btn btn-light" href="{{ route('admin.school.schedules.index') }}" title="Đặt lại bộ lọc"><i
                        data-lucide="rotate-ccw"></i><span class="visually-hidden">Đặt lại</span></a>
            </div>
        </form>
        <div class="schedule-summary">
            <span><strong>{{ $displayClasses->count() }}</strong> lớp đang hiển thị</span>
            <span><strong>{{ $schedules->count() }}</strong> tiết học</span>
            <span><strong>{{ $year?->name ?: 'Chưa chọn năm học' }}</strong>{{ $semesters->firstWhere('id', $selectedSemesterId)?->name ? ' · ' . $semesters->firstWhere('id', $selectedSemesterId)->name : '' }}</span>
        </div>
    </div>

    @forelse($displayClasses as $schoolClass)
        @php
            $classSchedules = $schedules->where('class_id', $schoolClass->id);
            $slotMap = $classSchedules->keyBy(fn($item) => $item->weekday . '-' . $item->period);
        @endphp
        <section class="class-schedule" aria-labelledby="class-schedule-{{ $schoolClass?->id }}">
            <header class="class-schedule-header">
                <div class="class-schedule-title">
                    <span class="class-schedule-mark"><i data-lucide="users-round"></i></span>
                    <div>
                        <h2 id="class-schedule-{{ $schoolClass?->id }}">{{ $schoolClass?->name ?: 'Lớp chưa xác định' }}
                        </h2>
                        <p>{{ $schoolClass?->grade?->name ?: 'Chưa có khối' }} ·
                            {{ $schoolClass?->room ?: 'Chưa có phòng cố định' }}</p>
                    </div>
                </div>
                <span class="class-schedule-count">{{ $classSchedules->count() }} tiết / tuần</span>
            </header>
            <div class="schedule-grid-wrap">
                <table class="schedule-grid">
                    <thead>
                        <tr>
                            <th>Tiết</th>
                            @foreach ($weekdays as $weekday)
                                <th>{{ $weekday }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($periods as $period)
                            <tr>
                                <td class="period-cell">{{ $period }}<small
                                        class="d-block fw-normal">{{ $periodTimes[$period] ?? '' }}</small></td>
                                @foreach ($weekdays as $weekdayNumber => $weekday)
                                    @php
                                        $item = $slotMap->get($weekdayNumber . '-' . $period);
                                    @endphp
                                    <td>
                                        @if ($item)
                                            <button type="button"
                                                class="schedule-slot schedule-slot-edit tone-{{ $item->subject_id % 5 }}"
                                                data-bs-toggle="modal" data-bs-target="#scheduleEditorModal"
                                                data-schedule-mode="edit" data-schedule-id="{{ $item->id }}"
                                                data-semester-id="{{ $item->semester_id }}"
                                                data-class-id="{{ $item->class_id }}"
                                                data-subject-id="{{ $item->subject_id }}"
                                                data-teacher-id="{{ $item->teacher_id }}"
                                                data-weekday="{{ $item->weekday }}" data-period="{{ $item->period }}"
                                                data-room="{{ $item->room }}" data-notes="{{ $item->notes }}"
                                                title="Sửa tiết học">
                                                <strong
                                                    title="{{ $item->subject?->name }}">{{ $item->subject?->short_name ?: $item->subject?->name }}</strong>
                                                <span
                                                    title="{{ $item->teacher?->full_name }}">{{ $item->teacher?->full_name ?: 'Chưa phân công GV' }}</span>
                                                <small>{{ $item->room ?: 'Chưa có phòng' }}</small>
                                            </button>
                                        @else
                                            <button type="button" class="schedule-add-slot" data-bs-toggle="modal"
                                                data-bs-target="#scheduleEditorModal" data-schedule-mode="create"
                                                data-class-id="{{ $schoolClass->id }}"
                                                data-semester-id="{{ $selectedSemesterId }}"
                                                data-weekday="{{ $weekdayNumber }}" data-period="{{ $period }}"
                                                title="Thêm tiết học"><i data-lucide="plus"></i><span
                                                    class="visually-hidden">Thêm tiết học</span></button>
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    @empty
        <div class="schedule-empty">
            <i data-lucide="calendar-x-2"></i>
            <strong class="d-block mb-1">Chưa có thời khóa biểu phù hợp</strong>
            <span>Hãy thử đổi năm học, học kỳ, lớp hoặc từ khóa tìm kiếm.</span>
        </div>
    @endforelse

    <div class="modal fade schedule-editor-modal" id="scheduleEditorModal" tabindex="-1"
        aria-labelledby="scheduleEditorTitle" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="modal-title" id="scheduleEditorTitle">Xếp tiết học</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                </div>
                <form id="scheduleEditorForm" method="POST" action="{{ route('admin.school.schedules.store') }}">
                    @csrf
                    <input type="hidden" name="_method" id="scheduleEditorMethod" value="PUT" disabled>
                    <input type="hidden" name="academic_year_id" id="scheduleEditorYear"
                        value="{{ $selectedYearId }}">
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" for="scheduleEditorSemester">Học kỳ</label>
                                <select class="form-select" id="scheduleEditorSemester" name="semester_id" required>
                                    <option value="">Chọn học kỳ</option>
                                    @foreach ($semesters as $semester)
                                        <option value="{{ $semester->id }}">{{ $semester->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="scheduleEditorClass">Lớp học</label>
                                <select class="form-select" id="scheduleEditorClass" name="class_id" required>
                                    <option value="">Chọn lớp</option>
                                    @foreach ($classes as $class)
                                        <option value="{{ $class->id }}">{{ $class->name }} ·
                                            {{ $class->grade?->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="scheduleEditorSubject">Môn học</label>
                                <select class="form-select" id="scheduleEditorSubject" name="subject_id" required>
                                    <option value="">Chọn môn học</option>
                                    @foreach ($subjects as $subject)
                                        <option value="{{ $subject->id }}">{{ $subject->name }} · {{ $subject->code }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="scheduleEditorTeacher">Giáo viên</label>
                                <select class="form-select" id="scheduleEditorTeacher" name="teacher_id">
                                    <option value="">Chưa phân công</option>
                                    @foreach ($teachers as $teacher)
                                        <option value="{{ $teacher->id }}"
                                            data-subjects="{{ $teacher->subjects->filter(fn($subject) => (int) $subject->pivot->academic_year_id === (int) $selectedYearId)->pluck('id')->join(',') }}">
                                            {{ $teacher->full_name }} · {{ $teacher->teacher_code }}</option>
                                    @endforeach
                                </select>
                                <div class="form-text">Danh sách được lọc theo môn đã khai báo của giáo viên.</div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="scheduleEditorWeekday">Thứ</label>
                                <select class="form-select" id="scheduleEditorWeekday" name="weekday" required>
                                    @foreach ($weekdays as $weekdayNumber => $weekday)
                                        <option value="{{ $weekdayNumber }}">{{ $weekday }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="scheduleEditorPeriod">Tiết</label>
                                <select class="form-select" id="scheduleEditorPeriod" name="period" required>
                                    @foreach ($periods as $period)
                                        <option value="{{ $period }}">Tiết {{ $period }} ·
                                            {{ $periodTimes[$period] ?? '' }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="scheduleEditorRoom">Phòng học</label>
                                <input class="form-control" id="scheduleEditorRoom" name="room" maxlength="50"
                                    placeholder="Ví dụ: A101">
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="scheduleEditorNotes">Ghi chú</label>
                                <textarea class="form-control" id="scheduleEditorNotes" name="notes" rows="2" maxlength="1000"
                                    placeholder="Không bắt buộc"></textarea>
                            </div>
                        </div>
                    </div>
                </form>
                <div class="modal-footer">
                    <form id="scheduleDeleteForm" class="delete-form d-none" method="POST" data-native-delete>
                        @csrf
                        @method('DELETE')
                        <button class="btn btn-outline-danger" type="submit"><i data-lucide="trash-2"></i> Xóa
                            tiết</button>
                    </form>
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-primary" form="scheduleEditorForm"><i data-lucide="save"></i>
                        Lưu tiết học</button>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const modal = document.getElementById('scheduleEditorModal');
                const editor = document.getElementById('scheduleEditorForm');
                const title = document.getElementById('scheduleEditorTitle');
                const method = document.getElementById('scheduleEditorMethod');
                const deleteForm = document.getElementById('scheduleDeleteForm');
                const subject = document.getElementById('scheduleEditorSubject');
                const teacher = document.getElementById('scheduleEditorTeacher');
                if (!modal || !editor || !title || !method || !deleteForm || !subject || !teacher) return;

                const storeUrl = @json(route('admin.school.schedules.store'));
                const updateUrl = @json(route('admin.school.schedules.update', ['schedule' => '__SCHEDULE__']));
                const deleteUrl = @json(route('admin.school.schedules.destroy', ['schedule' => '__SCHEDULE__']));
                const field = (name) => document.getElementById('scheduleEditor' + name);

                const refreshTeachers = () => {
                    const subjectId = subject.value;
                    [...teacher.options].forEach((option, index) => {
                        if (index === 0) return;
                        const subjects = (option.dataset.subjects || '').split(',').filter(Boolean);
                        option.hidden = Boolean(subjectId) && !subjects.includes(subjectId);
                    });
                    if (teacher.selectedOptions[0]?.hidden) teacher.value = '';
                };

                const openEditor = (trigger) => {
                    const mode = trigger?.dataset.scheduleMode || 'create';
                    const values = {
                        Semester: trigger?.dataset.semesterId || '{{ $selectedSemesterId }}',
                        Class: trigger?.dataset.classId || '{{ $selectedClassId }}',
                        Subject: trigger?.dataset.subjectId || '',
                        Teacher: trigger?.dataset.teacherId || '',
                        Weekday: trigger?.dataset.weekday || '1',
                        Period: trigger?.dataset.period || '1',
                        Room: trigger?.dataset.room || '',
                        Notes: trigger?.dataset.notes || '',
                    };
                    field('Semester').value = values.Semester;
                    field('Class').value = values.Class;
                    field('Subject').value = values.Subject;
                    field('Teacher').value = values.Teacher;
                    field('Weekday').value = values.Weekday;
                    field('Period').value = values.Period;
                    field('Room').value = values.Room;
                    field('Notes').value = values.Notes;
                    refreshTeachers();

                    if (mode === 'edit' && trigger?.dataset.scheduleId) {
                        title.textContent = 'Sửa tiết học';
                        editor.action = updateUrl.replace('__SCHEDULE__', trigger.dataset.scheduleId);
                        method.disabled = false;
                        deleteForm.action = deleteUrl.replace('__SCHEDULE__', trigger.dataset.scheduleId);
                        deleteForm.classList.remove('d-none');
                    } else {
                        title.textContent = 'Xếp tiết học';
                        editor.action = storeUrl;
                        method.disabled = true;
                        deleteForm.classList.add('d-none');
                        deleteForm.removeAttribute('action');
                    }
                };

                document.querySelectorAll('[data-schedule-mode]').forEach((trigger) => {
                    trigger.addEventListener('click', () => openEditor(trigger));
                });
                subject.addEventListener('change', refreshTeachers);
                modal.addEventListener('show.bs.modal', () => {
                    const trigger = document.activeElement?.closest('[data-schedule-mode]');
                    openEditor(trigger);
                });
            });
        </script>
    @endpush
@endsection

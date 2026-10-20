@extends('admin.layouts.app')

@section('title', 'Điểm danh lớp')

@php
    $canCreateAttendance = auth()->user()->hasPermission('attendance.create');
    $canEditAttendance = auth()->user()->hasPermission('attendance.edit');
    $hasMissingAttendance = $students->contains(fn($enrollment) => !isset($records[$enrollment->student_id]));
@endphp

@section('content')
    <div class="page-heading page-heading-inline">
        <div>
            <p class="eyebrow">Chuyên cần</p>
            <h1>Điểm danh lớp</h1>
            <p class="text-secondary mb-0">Ghi nhận chuyên cần đầu buổi cho lớp chủ nhiệm hoặc lớp đang giảng dạy.</p>
        </div>
        <form method="GET" class="d-flex gap-2"><select name="class_id" class="form-select">
                @foreach ($classes as $item)
                    <option value="{{ $item->id }}" @selected($class?->id === $item->id)>{{ $item->name }}</option>
                @endforeach
            </select>
            <input id="attendance-date-filter" type="date" name="date" class="form-control"
                value="{{ $date->format('Y-m-d') }}"><button class="btn btn-light">Xem</button>
        </form>
    </div>
    @if ($class)
        @if ($canCreateAttendance && $hasMissingAttendance)
            <form action="{{ route('teacher.attendance.store') }}" method="POST">@csrf<input type="hidden" name="class_id"
                    value="{{ $class->id }}"><input type="hidden" name="attended_on"
                    value="{{ $date->format('Y-m-d') }}">
        @endif
        <div class="card table-card">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Học sinh</th>
                            <th>Trạng thái</th>
                            <th>Lý do/Ghi chú</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($students as $enrollment)
                            @php($record = $records[$enrollment->student_id] ?? null)
                            <tr>
                                <td><strong>{{ $enrollment->student?->full_name }}</strong><small
                                        class="d-block text-secondary">{{ $enrollment->student?->student_code }}</small>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        @if (!$record && $canCreateAttendance)
                                            <select name="statuses[{{ $enrollment->student_id }}]" class="form-select">
                                                <option value="present">Có mặt</option>
                                                <option value="absent">Vắng</option>
                                                <option value="late">Đi muộn</option>
                                                <option value="excused">Nghỉ có phép</option>
                                            </select>
                                        @else
                                            <span>{{ ['present' => 'Có mặt', 'absent' => 'Vắng', 'late' => 'Đi muộn', 'excused' => 'Nghỉ có phép'][$record?->status] ?? 'Chưa điểm danh' }}</span>
                                        @endif
                                        @if ($record && $canEditAttendance)
                                            <button type="button" class="btn btn-sm btn-light" data-bs-toggle="modal"
                                                data-bs-target="#attendanceEditModal"
                                                data-attendance-id="{{ $record->id }}"
                                                data-attendance-date="{{ $record->attended_on?->format('Y-m-d') }}"
                                                data-attendance-status="{{ $record->status }}"
                                                data-attendance-reason="{{ $record->reason }}" title="Sửa điểm danh"><i
                                                    data-lucide="pencil"></i><span class="visually-hidden">Sửa điểm
                                                    danh</span></button>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    @if (!$record && $canCreateAttendance)
                                        <input name="reasons[{{ $enrollment->student_id }}]" class="form-control">
                                    @else
                                        {{ $record?->reason ?: '-' }}
                                    @endif
                                </td>
                        </tr>@empty<tr>
                                <td colspan="3" class="empty-state">Lớp chưa có học sinh.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($canCreateAttendance && $hasMissingAttendance)
                <div class="p-3 border-top text-end"><button class="btn btn-primary"><i data-lucide="save"></i> Lưu điểm
                        danh</button></div>
            @endif
            @if ($students->hasPages())
                <div class="p-3 border-top">{{ $students->links('pagination::bootstrap-5') }}</div>
            @endif
        </div>
        @if ($canCreateAttendance && $hasMissingAttendance)
            </form>
        @endif

        @if ($canEditAttendance)
            <div class="modal fade" id="attendanceEditModal" tabindex="-1" aria-labelledby="attendanceEditTitle"
                aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h2 class="modal-title fs-5" id="attendanceEditTitle">Sửa điểm danh</h2>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                        </div>
                        <form id="attendanceEditForm" method="POST">
                            @csrf
                            @method('PUT')
                            <div class="modal-body">
                                <div class="mb-3"><label class="form-label" for="attendanceEditDate">Ngày</label><input
                                        id="attendanceEditDate" name="attended_on" type="date" class="form-control"
                                        required></div>
                                <div class="mb-3"><label class="form-label" for="attendanceEditStatus">Trạng
                                        thái</label><select id="attendanceEditStatus" name="status" class="form-select"
                                        required>
                                        <option value="present">Có mặt</option>
                                        <option value="absent">Vắng</option>
                                        <option value="late">Đi muộn</option>
                                        <option value="excused">Nghỉ có phép</option>
                                    </select></div>
                                <div><label class="form-label" for="attendanceEditReason">Lý do/Ghi chú</label><input
                                        id="attendanceEditReason" name="reason" class="form-control" maxlength="255"></div>
                            </div>
                            <div class="modal-footer"><button type="button" class="btn btn-light"
                                    data-bs-dismiss="modal">Hủy</button><button type="submit" class="btn btn-primary"><i
                                        data-lucide="save"></i> Cập nhật điểm danh</button></div>
                        </form>
                    </div>
                </div>
            </div>
        @endif
    @else
        <div class="card">
            <div class="empty-state">Tài khoản chưa có lớp để điểm danh.</div>
        </div>
    @endif
    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const loadedDate = @json($date->format('Y-m-d'));
                const wasViewingToday = @json($date->isSameDay(today()));
                const attendanceUrl = @json(route('teacher.attendance'));
                const selectedClassId = @json($class?->id);

                const dateInSchoolTimezone = () => {
                    const parts = new Intl.DateTimeFormat('en-US', {
                        timeZone: 'Asia/Ho_Chi_Minh',
                        year: 'numeric',
                        month: '2-digit',
                        day: '2-digit'
                    }).formatToParts(new Date());
                    const values = Object.fromEntries(parts.map((part) => [part.type, part.value]));

                    return `${values.year}-${values.month}-${values.day}`;
                };

                const moveToNewAttendanceDay = () => {
                    if (!wasViewingToday) return;
                    const currentDate = dateInSchoolTimezone();
                    if (currentDate === loadedDate) return;

                    const url = new URL(attendanceUrl, window.location.origin);
                    if (selectedClassId) url.searchParams.set('class_id', selectedClassId);
                    url.searchParams.set('date', currentDate);
                    window.location.replace(url.toString());
                };

                moveToNewAttendanceDay();
                window.setInterval(moveToNewAttendanceDay, 60000);
                document.addEventListener('visibilitychange', () => {
                    if (!document.hidden) moveToNewAttendanceDay();
                });

                const form = document.getElementById('attendanceEditForm');
                const date = document.getElementById('attendanceEditDate');
                const status = document.getElementById('attendanceEditStatus');
                const reason = document.getElementById('attendanceEditReason');
                if (!form || !date || !status || !reason) return;
                const updateUrl = @json(route('teacher.attendance.update', ['attendance' => '__ATTENDANCE__']));
                document.querySelectorAll('[data-attendance-id]').forEach((button) => {
                    button.addEventListener('click', () => {
                        form.action = updateUrl.replace('__ATTENDANCE__', button.dataset.attendanceId);
                        date.value = button.dataset.attendanceDate || '';
                        status.value = button.dataset.attendanceStatus || 'present';
                        reason.value = button.dataset.attendanceReason || '';
                    });
                });
            });
        </script>
    @endpush
@endsection

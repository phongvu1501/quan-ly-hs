@extends('admin.layouts.app')

@section('title', 'Giáo viên chủ nhiệm')

@section('content')
    @php($weekdayNames = ['', 'Thứ 2', 'Thứ 3', 'Thứ 4', 'Thứ 5', 'Thứ 6', 'Thứ 7', 'Chủ nhật'])
    @php($section = request('section'))
    <div class="page-heading page-heading-inline">
        <div class="d-flex align-items-center gap-3">
            @if ($teacher->avatar_path)
                <img src="{{ asset('storage/' . $teacher->avatar_path) }}" alt="Ảnh {{ $teacher->full_name }}"
                    class="rounded-circle" style="width:64px;height:64px;object-fit:cover">
            @endif
            <div>
                <p class="eyebrow">Giáo viên chủ nhiệm</p>
                <h1>{{ $teacher->full_name }}</h1>
                <p class="text-secondary mb-0">{{ $year?->name ?: 'Chưa thiết lập năm học' }} · Quản lý sĩ số, chuyên cần và
                    tình hình lớp.</p>
            </div>
        </div>
        <div class="d-flex gap-2"><a href="{{ route('profile.show') }}" class="btn btn-light"><i
                    data-lucide="user-round-cog"></i> Hồ sơ</a><a class="btn btn-primary"
                href="{{ route('teacher.attendance') }}"><i data-lucide="clipboard-check"></i> Điểm danh lớp</a></div>
    </div>

    @if (!$section)
        <div class="overview-grid">
            <div class="overview-item"><span class="overview-icon"><i data-lucide="users-round"></i></span>
                <h2>Lớp chủ nhiệm</h2>
                <p class="fs-3 fw-bold text-dark mb-0">{{ $classes->count() }}</p>
            </div>
            <div class="overview-item"><span class="overview-icon"><i data-lucide="graduation-cap"></i></span>
                <h2>Học sinh đang phụ trách</h2>
                <p class="fs-3 fw-bold text-dark mb-0">{{ $studentCount }}</p>
            </div>
            <a class="overview-item text-decoration-none" href="{{ route('teacher.attendance') }}"><span
                    class="overview-icon"><i data-lucide="calendar-check"></i></span>
                <h2>Có mặt hôm nay</h2>
                <p class="fs-3 fw-bold text-dark mb-0">{{ ($todayAttendance['present'] ?? collect())->count() }}</p>
                <p>{{ ($todayAttendance['absent'] ?? collect())->count() }} vắng ·
                    {{ ($todayAttendance['late'] ?? collect())->count() }} đi muộn</p>
            </a>
            <a class="overview-item text-decoration-none" href="{{ route('teacher.scores') }}"><span
                    class="overview-icon"><i data-lucide="messages-square"></i></span>
                <h2>Điểm và nhận xét</h2>
                <p>Xem sổ điểm của các môn đã phân công.</p>
            </a>
        </div>
    @endif

    @if (!$section || $section === 'classes')
        <div id="classes">
            @forelse($classes as $class)
                <div class="card table-card mb-4">
                    <div class="card-body border-bottom d-flex justify-content-between align-items-center">
                        <div>
                            <h2 class="panel-title mb-1">{{ $class->name }}</h2><small
                                class="text-secondary">{{ $class->grade?->name }} · {{ $class->room ?: 'Chưa xếp phòng' }}
                                · {{ $class->enrollments_count }} học sinh</small>
                        </div><a href="{{ route('teacher.attendance', ['class_id' => $class->id]) }}"
                            class="btn btn-sm btn-light"><i data-lucide="clipboard-check"></i> Điểm danh</a>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>STT</th>
                                    <th>Mã học sinh</th>
                                    <th>Họ và tên</th>
                                    <th>Trạng thái</th>
                                    <th>Liên hệ</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($class->enrollments as $enrollment)
                                    <tr>
                                        <td>{{ $enrollment->roll_number ?: $loop->iteration }}</td>
                                        <td><code>{{ $enrollment->student?->student_code }}</code></td>
                                        <td><strong>{{ $enrollment->student?->full_name }}</strong></td>
                                        <td>{{ ['studying' => 'Đang đi học', 'sick_leave' => 'Nghỉ ốm dài ngày', 'reserved' => 'Bảo lưu', 'dropped' => 'Bỏ học'][$enrollment->student?->status] ?? $enrollment->student?->status }}
                                        </td>
                                        <td>{{ $enrollment->student?->phone ?: '-' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="empty-state">Lớp chưa có học sinh.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            @empty
                <div class="card">
                    <div class="empty-state">Tài khoản chưa được phân công chủ nhiệm lớp nào.</div>
                </div>
            @endforelse
        </div>
    @endif

    <div class="row g-4 mb-4">
        @if (!$section || $section === 'schedule')
            <div class="col-xl-7">
                <div id="schedule" class="card table-card h-100">
                    <div class="card-body border-bottom">
                        <h2 class="panel-title mb-0">Thời khóa biểu các lớp</h2>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Thứ</th>
                                    <th>Tiết</th>
                                    <th>Lớp</th>
                                    <th>Môn học</th>
                                    <th>Giáo viên</th>
                                    <th>Phòng</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($schedule as $item)
                                    <tr>
                                        <td>{{ $weekdayNames[$item->weekday] ?? $item->weekday }}</td>
                                        <td>{{ $item->period }}</td>
                                        <td>{{ $item->class?->name }}</td>
                                        <td><strong>{{ $item->subject?->name }}</strong></td>
                                        <td>{{ $item->teacher?->full_name ?: '-' }}</td>
                                        <td>{{ $item->room ?: '-' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="empty-state">Chưa có thời khóa biểu.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endif
        @if (!$section || $section === 'announcements')
            <div class="col-xl-5">
                <div id="announcements" class="card table-card h-100">
                    <div class="card-body border-bottom">
                        <h2 class="panel-title mb-0">Thông báo lớp và nhà trường</h2>
                    </div>
                    <div class="list-group list-group-flush">
                        @include('teacher.partials.invigilation-notifications')
                        @forelse($announcements as $announcement)
                            <div class="list-group-item px-4 py-3">
                                <div class="d-flex justify-content-between gap-3">
                                    <strong>{{ $announcement->title }}</strong><small
                                        class="text-secondary text-nowrap">{{ $announcement->published_at?->format('d/m/Y') }}</small>
                                </div>
                                <p class="small text-secondary mb-0 mt-1">
                                    {{ \Illuminate\Support\Str::limit($announcement->content, 140) }}</p>
                            </div>
                        @empty
                            <div class="empty-state">Chưa có thông báo mới.</div>
                        @endforelse
                    </div>
                </div>
            </div>
        @endif
    </div>

    @if (!$section || $section === 'exams')
        <div id="exams" class="card table-card">
            <div class="card-body border-bottom">
                <h2 class="panel-title mb-0">Lịch thi sắp tới của lớp</h2>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Kỳ thi</th>
                            <th>Môn học</th>
                            <th>Lớp</th>
                            <th>Ngày thi</th>
                            <th>Phòng</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($upcomingExams as $exam)
                            <tr>
                                <td><strong>{{ $exam->name }}</strong></td>
                                <td>{{ $exam->subject?->name }}</td>
                                <td>{{ $exam->class?->name ?: 'Toàn khối' }}</td>
                                <td>{{ $exam->exam_date?->format('d/m/Y') }}</td>
                                <td>{{ $exam->room ?: '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="empty-state">Chưa có lịch thi sắp tới.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif
@endsection

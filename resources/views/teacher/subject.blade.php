@extends('admin.layouts.app')

@section('title', 'Giáo viên bộ môn')

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
                <p class="eyebrow">Giáo viên bộ môn</p>
                <h1>{{ $teacher->full_name }}</h1>
                <p class="text-secondary mb-0">{{ $year?->name ?: 'Chưa thiết lập năm học' }} ·
                    {{ $semester?->name ?: 'Chưa có học kỳ' }}</p>
            </div>
        </div>
        <div class="d-flex gap-2"><a href="{{ route('profile.show') }}" class="btn btn-light"><i
                    data-lucide="user-round-cog"></i> Hồ sơ</a><a href="{{ route('teacher.scores') }}"
                class="btn btn-primary"><i data-lucide="square-pen"></i> Mở sổ điểm</a></div>
    </div>

    @if (!$section)
        <div class="overview-grid">
            <div class="overview-item"><span class="overview-icon"><i data-lucide="network"></i></span>
                <h2>Phân công</h2>
                <p class="fs-3 fw-bold text-dark mb-0">{{ $assignments->count() }}</p>
            </div>
            <div class="overview-item"><span class="overview-icon"><i data-lucide="graduation-cap"></i></span>
                <h2>Học sinh phụ trách</h2>
                <p class="fs-3 fw-bold text-dark mb-0">{{ $studentCount }}</p>
            </div>
            <a class="overview-item text-decoration-none" href="{{ route('teacher.scores') }}"><span
                    class="overview-icon"><i data-lucide="sigma"></i></span>
                <h2>Điểm đã nhập</h2>
                <p class="fs-3 fw-bold text-dark mb-0">{{ $assignments->sum('scores_count') }}</p>
            </a>
            <a class="overview-item text-decoration-none" href="{{ route('teacher.attendance') }}"><span
                    class="overview-icon"><i data-lucide="clipboard-check"></i></span>
                <h2>Điểm danh</h2>
                <p>Điểm danh nhanh theo lớp đang giảng dạy.</p>
            </a>
        </div>
    @endif

    @if (!$section || $section === 'assignments')
        <div id="assignments" class="card table-card mb-4">
            <div class="card-body border-bottom">
                <h2 class="panel-title mb-0">Các lớp và môn được phân công</h2>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Lớp</th>
                            <th>Môn học</th>
                            <th>Học kỳ</th>
                            <th>Số điểm đã nhập</th>
                            <th class="text-end">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($assignments as $assignment)
                            <tr>
                                <td><strong>{{ $assignment->class?->name }}</strong><small
                                        class="d-block text-secondary">{{ $assignment->class?->grade?->name }} ·
                                        {{ $assignment->class?->room ?: 'Chưa xếp phòng' }}</small></td>
                                <td>{{ $assignment->subject?->name }}</td>
                                <td>{{ $assignment->semester?->name ?: 'Cả năm' }}</td>
                                <td>{{ $assignment->scores_count }}</td>
                                <td class="text-end"><a class="btn btn-sm btn-light"
                                        href="{{ route('teacher.scores', ['assignment_id' => $assignment->id]) }}"><i
                                            data-lucide="square-pen"></i> Nhập điểm</a> <a class="btn btn-sm btn-light"
                                        href="{{ route('teacher.attendance', ['class_id' => $assignment->class_id]) }}"><i
                                            data-lucide="clipboard-check"></i> Điểm danh</a></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="empty-state">Chưa có phân công bộ môn.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <div class="row g-4">
        @if (!$section || $section === 'schedule')
            <div class="col-xl-7">
                <div id="schedule" class="card table-card h-100">
                    <div class="card-body border-bottom">
                        <h2 class="panel-title mb-0">Thời khóa biểu giảng dạy</h2>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Thứ</th>
                                    <th>Tiết</th>
                                    <th>Lớp</th>
                                    <th>Môn học</th>
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
                                        <td>{{ $item->room ?: '-' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="empty-state">Chưa có thời khóa biểu.</td>
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
                        <h2 class="panel-title mb-0">Thông báo dành cho giáo viên</h2>
                    </div>
                    <div class="list-group list-group-flush">
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
        <div id="exams" class="card table-card mt-4">
            <div class="card-body border-bottom">
                <h2 class="panel-title mb-0">Lịch thi sắp tới</h2>
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

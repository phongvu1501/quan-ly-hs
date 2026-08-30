@extends('admin.layouts.app')

@section('title', 'Cổng giáo viên')

@section('content')
    @php($weekdayNames = ['', 'Thứ 2', 'Thứ 3', 'Thứ 4', 'Thứ 5', 'Thứ 6', 'Thứ 7', 'Chủ nhật'])
    <div class="page-heading page-heading-inline">
        <div class="d-flex align-items-center gap-3">
            @if ($teacher->avatar_path)
                <img src="{{ asset('storage/' . $teacher->avatar_path) }}" alt="Ảnh {{ $teacher->full_name }}"
                    class="rounded-circle" style="width:64px;height:64px;object-fit:cover">
            @endif
            <div>
                <p class="eyebrow">Cổng giáo viên</p>
                <h1>Xin chào, {{ $teacher->full_name }}</h1>
                <p class="text-secondary mb-0">{{ $year?->name ?: 'Chưa thiết lập năm học' }} ·
                    {{ $semester?->name ?: 'Chưa có học kỳ' }}</p>
            </div>
        </div>
        <a href="{{ route('profile.show') }}" class="btn btn-light"><i data-lucide="user-round-cog"></i> Hồ sơ cá nhân</a>
    </div>

    <div class="overview-grid">
        @if (auth()->user()->hasPermission('homeroom.dashboard'))
        <a class="overview-item text-decoration-none" href="{{ route('teacher.homeroom.dashboard') }}">@else<div
                    class="overview-item">
        @endif
        <span class="overview-icon">
            <i data-lucide="school"></i></span>
        <h2>Lớp chủ nhiệm</h2>
        <p class="fs-3 fw-bold text-dark mb-0">{{ $homeroomClasses->count() }}</p>
        @if (auth()->user()->hasPermission('homeroom.dashboard'))
        </a>@else
    </div>
    @endif
    @if (auth()->user()->hasPermission('teacher.dashboard'))
    <a class="overview-item text-decoration-none" href="{{ route('teacher.subject.dashboard') }}">@else<div
                class="overview-item">
    @endif
    <span class="overview-icon"><i data-lucide="book-open-check"></i></span>
    <h2>Phân công bộ môn</h2>
    <p class="fs-3 fw-bold text-dark mb-0">{{ $assignments->count() }}</p>
    @if (auth()->user()->hasPermission('teacher.dashboard'))
    </a>@else</div>
    @endif
    <a class="overview-item text-decoration-none" href="{{ route('teacher.attendance') }}"><span class="overview-icon"><i
                data-lucide="clipboard-check"></i></span>
        <h2>Học sinh phụ trách</h2>
        <p class="fs-3 fw-bold text-dark mb-0">{{ $students }}</p>
    </a>
    <a class="overview-item text-decoration-none" href="{{ route('teacher.scores') }}"><span class="overview-icon"><i
                data-lucide="square-pen"></i></span>
        <h2>Số điểm đã nhập</h2>
        <p class="fs-3 fw-bold text-dark mb-0">{{ $assignments->sum('scores_count') }}</p>
    </a>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-xl-7">
            <div class="card table-card h-100">
                <div class="card-body border-bottom d-flex justify-content-between align-items-center">
                    <div>
                        <h2 class="panel-title mb-1">Phân công đang giảng dạy</h2><small class="text-secondary">Chỉ các lớp
                            và môn được phân công trong năm học hiện tại.</small>
                    </div><a class="btn btn-sm btn-light" href="{{ route('teacher.scores') }}">Mở sổ điểm</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Lớp</th>
                                <th>Môn học</th>
                                <th>Học kỳ</th>
                                <th class="text-end">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($assignments as $assignment)
                                <tr>
                                    <td><strong>{{ $assignment->class?->name }}</strong><small
                                            class="d-block text-secondary">{{ $assignment->class?->grade?->name }}</small>
                                    </td>
                                    <td>{{ $assignment->subject?->name }}</td>
                                    <td>{{ $assignment->semester?->name ?: 'Cả năm' }}</td>
                                    <td class="text-end"><a class="btn btn-sm btn-light"
                                            href="{{ route('teacher.scores', ['assignment_id' => $assignment->id]) }}"><i
                                                data-lucide="square-pen"></i> Nhập điểm</a></td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="empty-state">Chưa có phân công giảng dạy.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-xl-5">
            <div class="card table-card h-100">
                <div class="card-body border-bottom d-flex justify-content-between align-items-center">
                    <div>
                        <h2 class="panel-title mb-1">Thời khóa biểu của tôi</h2><small
                            class="text-secondary">{{ $schedule->count() }} tiết trong học kỳ</small>
                    </div><i data-lucide="calendar-days" class="text-secondary"></i>
                </div>
                <div class="list-group list-group-flush">
                    @forelse($schedule->take(7) as $item)
                        <div class="list-group-item px-4 py-3 d-flex justify-content-between gap-3">
                            <div><strong>{{ $item->subject?->name }}</strong><small
                                    class="d-block text-secondary">{{ $weekdayNames[$item->weekday] ?? 'Thứ ' . $item->weekday }}
                                    · Tiết {{ $item->period }} · {{ $item->class?->name }}</small></div><span
                                class="text-secondary small text-nowrap">{{ $item->room ?: 'Chưa xếp phòng' }}</span>
                        </div>
                    @empty
                        <div class="empty-state">Chưa có thời khóa biểu.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-xl-7">
            <div class="card table-card h-100">
                <div class="card-body border-bottom">
                    <h2 class="panel-title mb-0">Thông báo dành cho giáo viên</h2>
                </div>
                <div class="list-group list-group-flush">
                    @include('teacher.partials.invigilation-notifications')
                    @forelse($announcements as $announcement)
                        <div class="list-group-item px-4 py-3">
                            <div class="d-flex justify-content-between gap-3">
                                <strong>{{ $announcement->title }}</strong><small
                                    class="text-secondary text-nowrap">{{ $announcement->published_at?->format('d/m/Y') }}</small>
                            </div>
                            <p class="text-secondary small mb-0 mt-1">
                                {{ \Illuminate\Support\Str::limit($announcement->content, 180) }}</p>
                        </div>
                    @empty
                        <div class="empty-state">Chưa có thông báo mới.</div>
                    @endforelse
                </div>
            </div>
        </div>
        <div class="col-xl-5">
            <div class="card table-card h-100">
                <div class="card-body border-bottom">
                    <h2 class="panel-title mb-0">Lịch thi sắp tới</h2>
                </div>
                <div class="list-group list-group-flush">
                    @forelse($upcomingExams as $exam)
                        <div class="list-group-item px-4 py-3"><strong>{{ $exam->name }}</strong><small
                                class="d-block text-secondary">{{ $exam->subject?->name }} ·
                                {{ $exam->exam_date?->format('d/m/Y') }}{{ $exam->class?->name ? ' · ' . $exam->class->name : '' }}</small>
                        </div>
                    @empty
                        <div class="empty-state">Chưa có lịch thi sắp tới.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
@endsection

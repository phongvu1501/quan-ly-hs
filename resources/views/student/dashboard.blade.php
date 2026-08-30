@extends('admin.layouts.app')

@section('title', 'Cổng học sinh')

@section('content')
    @php($weekdayNames = ['', 'Thứ 2', 'Thứ 3', 'Thứ 4', 'Thứ 5', 'Thứ 6', 'Thứ 7', 'Chủ nhật'])
    @php($section = request('section'))
    @php($guardianByRelationship = $student->guardians->keyBy('pivot.relationship'))
    <div class="page-heading page-heading-inline">
        <div class="d-flex align-items-center gap-3">
            @if ($student->avatar_path)
                <img src="{{ asset('storage/' . $student->avatar_path) }}" alt="Ảnh {{ $student->full_name }}"
                    class="rounded-circle" style="width:64px;height:64px;object-fit:cover">
            @endif
            <div>
                <p class="eyebrow">Cổng học sinh</p>
                <h1>{{ $student->full_name }}</h1>
                <p class="text-secondary mb-0">{{ $student->student_code }} ·
                    {{ $enrollment?->class?->name ?: 'Chưa xếp lớp' }} · {{ $year?->name ?: 'Chưa thiết lập năm học' }}</p>
            </div>
        </div>
        <div class="d-flex gap-2"><a href="{{ route('profile.show') }}" class="btn btn-light"><i
                    data-lucide="user-round-cog"></i> Hồ sơ</a>@if(auth()->user()->hasPermission('fees.view'))<a href="{{ route('student.payments') }}"
                class="btn btn-light"><i data-lucide="receipt-text"></i> Học phí</a>@endif</div>
    </div>

    @if (!$section || $section === 'class')
        <div class="card table-card mb-4">
            <div class="card-body border-bottom"><h2 class="panel-title mb-0">Thông tin phụ huynh</h2></div>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead><tr><th>Quan hệ</th><th>Họ tên</th><th>Liên hệ</th><th>Nghề nghiệp</th><th>Tôn giáo</th><th>Nơi làm việc</th></tr></thead>
                    <tbody>
                        @foreach (['father' => 'Bố', 'mother' => 'Mẹ'] as $relationship => $label)
                            @php($guardian = $guardianByRelationship->get($relationship))
                            <tr><td><strong>{{ $label }}</strong></td><td>{{ $guardian?->full_name ?: '-' }}</td>
                                <td>{{ $guardian?->phone ?: '-' }}@if($guardian?->email)<small class="d-block text-secondary">{{ $guardian->email }}</small>@endif</td>
                                <td>{{ $guardian?->occupation ?: '-' }}</td><td>{{ $guardian?->religion ?: '-' }}</td><td>{{ $guardian?->workplace ?: '-' }}</td></tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    @if (!$section || in_array($section, ['class', 'scores', 'attendance'], true))
        <div class="overview-grid">
            @if (!$section || $section === 'class')
                <div id="class" class="overview-item"><span class="overview-icon"><i data-lucide="school"></i></span>
                    <h2>Lớp học</h2>
                    <p class="fs-3 fw-bold text-dark mb-0">{{ $enrollment?->class?->name ?: '-' }}</p>
                    <p>{{ $enrollment?->class?->room ? 'Phòng ' . $enrollment->class->room : 'Chưa có phòng học' }}</p>
                </div>
            @endif
            @if (!$section || $section === 'scores')
                <div id="scores" class="overview-item"><span class="overview-icon"><i
                            data-lucide="book-open-check"></i></span>
                    <h2>Điểm trung bình tạm tính</h2>
                    <p class="fs-3 fw-bold text-dark mb-0">
                        {{ $scores->count() ? number_format((float) $scores->avg('score'), 2) : '-' }}</p>
                    <p>{{ $scores->count() }} điểm trong năm học</p>
                </div>
            @endif
            @if (!$section || $section === 'attendance')
                <div class="overview-item"><span class="overview-icon"><i data-lucide="circle-check-big"></i></span>
                    <h2>Chuyên cần</h2>
                    <p class="fs-3 fw-bold text-dark mb-0">{{ ($attendance['present'] ?? collect())->count() }}</p>
                    <p>{{ ($attendance['absent'] ?? collect())->count() }} vắng ·
                        {{ ($attendance['late'] ?? collect())->count() }} đi muộn</p>
                </div>
            @endif
            @if (!$section && auth()->user()->hasPermission('student-fees.view'))
                <a class="overview-item text-decoration-none" href="{{ route('student.payments') }}"><span
                        class="overview-icon"><i data-lucide="wallet-cards"></i></span>
                    <h2>Học phí cần đóng</h2>
                    <p class="fs-3 fw-bold text-dark mb-0">
                        {{ $feeAssessments->isNotEmpty()
                            ? $feeAssessments->where('remaining_amount', '>', 0)->count()
                            : $fees->filter(fn($fee) => $fee->payments->first()?->status !== 'paid')->count() }}</p>
                    <p>Xem chi tiết các khoản thu</p>
                </a>
            @endif
        </div>
    @endif

    @if (!$section || in_array($section, ['scores', 'exams'], true))
    <div class="row g-4 mb-4">
        @if (!$section || $section === 'scores')
            <div class="col-xl-7">
                <div id="scores-table" class="card table-card h-100">
                    <div class="card-body border-bottom">
                        <h2 class="panel-title mb-0">Kết quả học tập · {{ $semester?->name ?: 'Năm học' }}</h2>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Môn học</th>
                                    <th>Điểm trong năm</th>
                                    <th>Trung bình tạm tính</th>
                                    <th class="text-end">Chi tiết</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($scoreGroups as $subjectScores)
                                    @php($scoreModalId = 'student-score-detail-' . $subjectScores->first()?->subject_id)
                                    <tr>
                                        <td><strong>{{ $subjectScores->first()?->subject?->name }}</strong></td>
                                        <td>
                                            @foreach ($subjectScores as $score)
                                                <span class="badge text-bg-light me-1"
                                                    title="{{ $score->score_type }} · {{ $score->assessed_on?->format('d/m/Y') }}">{{ number_format((float) $score->score, 1) }}</span>
                                            @endforeach
                                        </td>
                                        <td><strong>{{ number_format((float) $subjectScores->avg('score'), 2) }}</strong></td>
                                        <td class="text-end"><button type="button" class="btn btn-sm btn-light" data-bs-toggle="modal" data-bs-target="#{{ $scoreModalId }}" title="Xem chi tiết điểm"><i data-lucide="list-checks"></i><span class="visually-hidden">Xem chi tiết điểm</span></button></td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="empty-state">Chưa có điểm trong năm học này.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endif
        @if (!$section || $section === 'exams')
            <div id="exams" class="col-xl-5">
                <div class="card table-card h-100">
                    <div class="card-body border-bottom">
                        <h2 class="panel-title mb-0">Lịch thi sắp tới</h2>
                    </div>
                    <div class="list-group list-group-flush">
                        @forelse($exams as $exam)
                            <div class="list-group-item px-4 py-3"><strong>{{ $exam->name }}</strong><small
                                    class="d-block text-secondary">{{ $exam->subject?->name }} ·
                                    {{ $exam->exam_date?->format('d/m/Y') }} · {{ $exam->starts_at ?: 'Chưa chốt giờ' }} ·
                                    {{ $exam->room ?: 'Chưa chốt phòng' }}</small></div>
                        @empty
                            <div class="empty-state">Chưa có lịch thi.</div>
                        @endforelse
                    </div>
                </div>
            </div>
        @endif
    </div>

    @foreach ($scoreGroups as $subjectScores)
        @php($scoreModalId = 'student-score-detail-' . $subjectScores->first()?->subject_id)
        <div class="modal fade" id="{{ $scoreModalId }}" tabindex="-1" aria-labelledby="{{ $scoreModalId }}-title" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable"><div class="modal-content">
                <div class="modal-header"><div><h2 class="modal-title fs-5" id="{{ $scoreModalId }}-title">Chi tiết điểm · {{ $subjectScores->first()?->subject?->name }}</h2><small class="text-secondary">{{ $year?->name }} · {{ $subjectScores->count() }} điểm</small></div><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button></div>
                <div class="modal-body"><div class="table-responsive"><table class="table align-middle mb-0"><thead><tr><th>Học kỳ</th><th>Loại điểm</th><th>Điểm</th><th>Ngày</th><th>Ghi chú</th></tr></thead><tbody>
                    @foreach($subjectScores as $score)<tr><td>{{ $score->semester?->name ?: '-' }}</td><td>{{ ['oral' => 'Miệng', 'fifteen_minute' => '15 phút', 'one_period' => '1 tiết', 'midterm' => 'Giữa kỳ', 'final' => 'Cuối kỳ'][$score->score_type] ?? $score->score_type }}</td><td><strong>{{ number_format((float) $score->score, 2) }}</strong></td><td>{{ $score->assessed_on?->format('d/m/Y') ?: '-' }}</td><td>{{ $score->note ?: '-' }}</td></tr>@endforeach
                </tbody></table></div></div>
                <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Đóng</button></div>
            </div></div>
        </div>
    @endforeach
    @endif

    @if (!$section || $section === 'schedule')
        <div id="schedule" class="card table-card mb-4">
            <div class="card-body border-bottom">
                <h2 class="panel-title mb-0">Thời khóa biểu tuần · {{ $semester?->name ?: '' }}</h2>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Thứ</th>
                            <th>Tiết</th>
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
                                <td><strong>{{ $item->subject?->name }}</strong></td>
                                <td>{{ $item->teacher?->full_name ?: '-' }}</td>
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
    @endif

    @if (!$section || $section === 'announcements')
    <div class="row g-4 mb-4">
        @if (!$section || $section === 'announcements')
            <div id="announcements" class="col-xl-7">
                <div class="card table-card h-100">
                    <div class="card-body border-bottom">
                        <h2 class="panel-title mb-0">Thông báo nhà trường</h2>
                    </div>
                    <div class="list-group list-group-flush">
                        @forelse($announcements as $announcement)
                            <div class="list-group-item px-4 py-3">
                                <div class="d-flex justify-content-between gap-3">
                                    <strong>{{ $announcement->title }}</strong><small
                                        class="text-secondary text-nowrap">{{ $announcement->published_at?->format('d/m/Y') }}</small>
                                </div>
                                <p class="text-secondary small mb-0 mt-1">
                                    {{ \Illuminate\Support\Str::limit($announcement->content, 170) }}</p>
                            </div>
                        @empty
                            <div class="empty-state">Chưa có thông báo.</div>
                        @endforelse
                    </div>
                </div>
            </div>
        @endif
        @if (!$section && auth()->user()->hasPermission('fees.view'))
            <div class="col-xl-5">
                <div id="fees" class="card table-card h-100">
                    <div class="card-body border-bottom d-flex justify-content-between align-items-center">
                        <h2 class="panel-title mb-0">Khoản cần đóng</h2><a href="{{ route('student.payments') }}"
                            class="btn btn-sm btn-light">Chi tiết</a>
                    </div>
                    <div class="list-group list-group-flush">
                        @forelse($fees->take(6) as $fee)
                            @php($feePayment = $fee->payments->first())
                            <div class="list-group-item px-4 py-3"><strong>{{ $fee->title }}</strong>
                                <div class="d-flex justify-content-between mt-1"><small class="text-secondary">Hạn
                                        {{ $fee->due_on?->format('d/m/Y') ?: 'chưa chốt' }}</small><strong>{{ number_format((float) $fee->amount, 0, ',', '.') }}
                                        đ</strong></div><small
                                    class="{{ $feePayment?->status === 'paid' ? 'text-success' : 'text-warning' }}">{{ $feePayment?->status === 'paid' ? 'Đã đóng' : 'Chưa đóng' }}</small>
                            </div>
                        @empty
                            <div class="empty-state">Không có khoản cần đóng.</div>
                        @endforelse
                    </div>
                </div>
            </div>
        @endif
    </div>
    @endif

    @if (!$section || $section === 'attendance')
    <div class="row g-4">
        @if (!$section || $section === 'attendance')
            <div id="attendance" class="col-xl-7">
                <div class="card table-card h-100">
                    <div class="card-body border-bottom">
                        <h2 class="panel-title mb-0">Lịch sử chuyên cần</h2>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Ngày</th>
                                    <th>Lớp</th>
                                    <th>Môn</th>
                                    <th>Trạng thái</th>
                                    <th>Ghi chú</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($attendanceRecords as $record)
                                    <tr>
                                        <td>{{ $record->attended_on?->format('d/m/Y') }}</td>
                                        <td>{{ $record->class?->name ?: '-' }}</td>
                                        <td>{{ $record->subject?->name ?: 'Đầu buổi' }}</td>
                                        <td><span
                                                class="status-dot {{ in_array($record->status, ['present', 'excused'], true) ? 'status-on' : 'status-off' }}">{{ ['present' => 'Có mặt', 'absent' => 'Vắng', 'late' => 'Đi muộn', 'excused' => 'Nghỉ có phép'][$record->status] ?? $record->status }}</span>
                                        </td>
                                        <td>{{ $record->reason ?: '-' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="empty-state">Chưa có dữ liệu chuyên cần.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endif
        @if (!$section)
            <div class="col-xl-5">
                <div class="card table-card h-100">
                    <div class="card-body border-bottom">
                        <h2 class="panel-title mb-0">Nhận xét giáo viên</h2>
                    </div>
                    <div class="list-group list-group-flush">
                        @forelse($comments as $comment)
                            <div class="list-group-item px-4 py-3">
                                <strong>{{ $comment->subject?->name ?: 'Nhận xét chung' }}</strong>
                                <p class="mb-1 mt-1">{{ $comment->content }}</p><small
                                    class="text-secondary">{{ $comment->teacher?->full_name ?: 'Giáo viên' }} ·
                                    {{ $comment->created_at?->format('d/m/Y') }}</small>
                            </div>
                        @empty
                            <div class="empty-state">Chưa có nhận xét.</div>
                        @endforelse
                    </div>
                </div>
            </div>
        @endif
    </div>
    @endif
@endsection

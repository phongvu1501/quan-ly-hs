@extends('admin.layouts.app')

@section('title', 'Hồ sơ học sinh')

@php
    $studentStatusTone = match ($student->status) {
        'studying' => 'success',
        'sick_leave', 'reserved' => 'warning',
        'graduated' => 'info',
        default => 'danger',
    };
@endphp

@section('content')
    <x-admin.page-header eyebrow="Hồ sơ học sinh" :title="$student->full_name"
        :description="$student->student_code . ' · ' . ($currentEnrollment?->class?->name ?: 'Chưa xếp lớp')">
        <x-slot:actions>
            <a href="{{ route('admin.school.students.index') }}" class="btn btn-light">
                <i data-lucide="arrow-left"></i><span>Danh sách</span>
            </a>
            @if (auth()->user()->hasPermission('students.edit'))
                <a href="{{ route('admin.school.students.edit', $student) }}" class="btn btn-primary"
                    data-modal-title="Sửa học sinh">
                    <i data-lucide="pencil"></i><span>Sửa hồ sơ</span>
                </a>
            @endif
        </x-slot:actions>
    </x-admin.page-header>

    <div class="row g-3 mb-3">
        <div class="col-xl-8">
            <section class="card h-100" aria-labelledby="student-profile-title">
                <div class="card-body">
                    <div class="profile-summary mb-3">
                        <span class="profile-avatar" aria-hidden="true">
                            @if ($student->avatar_path)
                                <img src="{{ asset('storage/' . $student->avatar_path) }}" alt="">
                            @else
                                {{ mb_strtoupper(mb_substr($student->full_name, 0, 1)) }}
                            @endif
                        </span>
                        <div>
                            <h2 id="student-profile-title" class="panel-title mb-1">Thông tin cá nhân</h2>
                            <x-admin.status-badge :tone="$studentStatusTone">{{ $student->statusLabel() }}</x-admin.status-badge>
                        </div>
                    </div>

                    <dl class="detail-list">
                        <div><dt>Mã học sinh</dt><dd><code>{{ $student->student_code }}</code></dd></div>
                        <div><dt>Ngày sinh</dt><dd>{{ $student->date_of_birth?->format('d/m/Y') ?: 'Chưa cập nhật' }}</dd></div>
                        <div><dt>Giới tính</dt><dd>{{ ['male' => 'Nam', 'female' => 'Nữ', 'other' => 'Khác'][$student->gender] ?? 'Chưa cập nhật' }}</dd></div>
                        <div><dt>Năm tuyển sinh</dt><dd>{{ $student->admission_year ?: 'Chưa cập nhật' }}</dd></div>
                        <div><dt>Email</dt><dd>{{ $student->email ?: 'Chưa cập nhật' }}</dd></div>
                        <div><dt>Điện thoại</dt><dd>{{ $student->phone ?: 'Chưa cập nhật' }}</dd></div>
                        <div><dt>Nơi sinh</dt><dd>{{ $student->birth_place ?: 'Chưa cập nhật' }}</dd></div>
                        <div><dt>Địa chỉ</dt><dd>{{ $student->address ?: 'Chưa cập nhật' }}</dd></div>
                    </dl>
                </div>
            </section>
        </div>

        <div class="col-xl-4">
            <section class="card h-100" aria-labelledby="current-class-title">
                <div class="card-body">
                    <h2 id="current-class-title" class="panel-title">Lớp hiện tại</h2>
                    @if ($currentEnrollment)
                        <strong class="d-block fs-5 mb-1">{{ $currentEnrollment->class?->name ?: 'Chưa xác định' }}</strong>
                        <p class="text-secondary mb-3">
                            {{ $currentEnrollment->academicYear?->name }} · {{ $currentEnrollment->class?->grade?->name }}
                        </p>
                        <x-admin.status-badge :tone="$currentEnrollment->status === 'active' ? 'success' : 'neutral'">
                            {{ ['active' => 'Đang học', 'inactive' => 'Đã rời lớp', 'completed' => 'Đã hoàn thành', 'withdrawn' => 'Đã thôi học'][$currentEnrollment->status] ?? $currentEnrollment->status }}
                        </x-admin.status-badge>
                    @else
                        <x-admin.empty-state icon="school" title="Chưa xếp lớp" />
                    @endif

                    <hr>
                    <div class="d-grid gap-2">
                        <a href="{{ route('admin.school.students.transcript', $student) }}" class="btn btn-light justify-content-start"
                            data-modal-readonly="true" data-modal-title="Học bạ học sinh">
                            <i data-lucide="book-open-check"></i><span>Học bạ và chuyên cần</span>
                        </a>
                        @if (auth()->user()->hasPermission('fees.view'))
                            <a href="{{ route('admin.finance.fees.students.show', $student) }}" class="btn btn-light justify-content-start"
                                data-native-navigation="true">
                                <i data-lucide="receipt-text"></i><span>Học phí và thanh toán</span>
                            </a>
                        @endif
                    </div>
                </div>
            </section>
        </div>
    </div>

    <section class="card table-card mb-3" aria-labelledby="enrollment-history-title">
        <div class="card-body border-bottom d-flex align-items-center justify-content-between gap-3">
            <div>
                <h2 id="enrollment-history-title" class="panel-title mb-1">Lịch sử lớp học</h2>
                <span class="text-secondary small">{{ number_format($student->enrollments->count()) }} lần xếp lớp</span>
            </div>
            <a href="{{ route('admin.school.students.transcript', $student) }}" class="btn btn-sm btn-light"
                data-modal-readonly="true" data-modal-title="Học bạ học sinh">
                <i data-lucide="book-open-check"></i><span>Học bạ</span>
            </a>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead><tr><th>Năm học</th><th>Lớp</th><th>Trạng thái</th><th>Ngày vào</th><th>Ngày rời</th></tr></thead>
                <tbody>
                    @forelse ($student->enrollments as $enrollment)
                        @php($enrollmentActive = $enrollment->status === 'active')
                        <tr>
                            <td><strong>{{ $enrollment->academicYear?->name ?: '-' }}</strong></td>
                            <td>{{ $enrollment->class?->name ?: '-' }}</td>
                            <td>
                                <x-admin.status-badge :tone="$enrollmentActive ? 'success' : 'neutral'">
                                    {{ ['active' => 'Đang học', 'inactive' => 'Đã rời lớp', 'completed' => 'Đã hoàn thành', 'withdrawn' => 'Đã thôi học'][$enrollment->status] ?? $enrollment->status }}
                                </x-admin.status-badge>
                            </td>
                            <td>{{ $enrollment->enrolled_on?->format('d/m/Y') ?: '-' }}</td>
                            <td>{{ $enrollment->left_on?->format('d/m/Y') ?: '-' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="empty-state"><x-admin.empty-state icon="school" title="Chưa có lịch sử lớp học" /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <div class="row g-3">
        <div class="col-xl-8">
            <section class="card table-card h-100" aria-labelledby="recent-score-title">
                <div class="card-body border-bottom">
                    <h2 id="recent-score-title" class="panel-title mb-0">Điểm gần đây</h2>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead><tr><th>Môn</th><th>Học kỳ</th><th>Loại điểm</th><th>Điểm</th></tr></thead>
                        <tbody>
                            @forelse ($recentScores as $score)
                                <tr>
                                    <td><strong>{{ $score->subject?->name ?: '-' }}</strong></td>
                                    <td>{{ $score->semester?->name ?: '-' }}</td>
                                    <td>{{ $score->score_type }}</td>
                                    <td><strong>{{ $score->score }}</strong></td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="empty-state"><x-admin.empty-state icon="clipboard-list" title="Chưa có điểm" /></td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
        <div class="col-xl-4">
            <section class="card h-100" aria-labelledby="student-metrics-title">
                <div class="card-body">
                    <h2 id="student-metrics-title" class="panel-title">Tổng hợp hồ sơ</h2>
                    <div class="metric-strip">
                        <div class="metric-strip-item"><span>Điểm danh</span><strong>{{ number_format($student->attendance_count) }}</strong></div>
                        <div class="metric-strip-item"><span>Kết quả thi</span><strong>{{ number_format($student->exam_results_count) }}</strong></div>
                        <div class="metric-strip-item"><span>Khoản phải thu</span><strong>{{ number_format($student->fee_assessments_count) }}</strong></div>
                    </div>
                </div>
            </section>
        </div>
    </div>
@endsection

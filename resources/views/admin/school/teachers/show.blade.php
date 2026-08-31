@extends('admin.layouts.app')

@section('title', 'Hồ sơ giáo viên')

@php
    $teacherStatusTone = match ($teacher->status) {
        'active' => 'success',
        'on_leave' => 'warning',
        default => 'neutral',
    };
@endphp

@section('content')
    <x-admin.page-header eyebrow="Hồ sơ nhân sự" :title="$teacher->full_name"
        :description="$teacher->teacher_code . ' · ' . ($teacher->specialization ?: 'Chưa cập nhật chuyên môn')">
        <x-slot:actions>
            <a href="{{ route('admin.school.teachers.index') }}" class="btn btn-light">
                <i data-lucide="arrow-left"></i><span>Danh sách</span>
            </a>
            @if (auth()->user()->hasPermission('teachers.edit'))
                <a href="{{ route('admin.school.teachers.edit', $teacher) }}" class="btn btn-primary"
                    data-modal-title="Sửa giáo viên">
                    <i data-lucide="pencil"></i><span>Sửa hồ sơ</span>
                </a>
            @endif
        </x-slot:actions>
    </x-admin.page-header>

    <div class="row g-3 mb-3">
        <div class="col-xl-8">
            <section class="card h-100" aria-labelledby="teacher-profile-title">
                <div class="card-body">
                    <div class="profile-summary mb-3">
                        <span class="profile-avatar" aria-hidden="true">
                            @if ($teacher->avatar_path)
                                <img src="{{ asset('storage/' . $teacher->avatar_path) }}" alt="">
                            @else
                                {{ mb_strtoupper(mb_substr($teacher->full_name, 0, 1)) }}
                            @endif
                        </span>
                        <div>
                            <h2 id="teacher-profile-title" class="panel-title mb-1">Thông tin công tác</h2>
                            <x-admin.status-badge :tone="$teacherStatusTone">{{ $teacher->statusLabel() }}</x-admin.status-badge>
                        </div>
                    </div>

                    <dl class="detail-list">
                        <div><dt>Mã giáo viên</dt><dd><code>{{ $teacher->teacher_code }}</code></dd></div>
                        <div><dt>Ngày vào trường</dt><dd>{{ $teacher->hire_date?->format('d/m/Y') ?: 'Chưa cập nhật' }}</dd></div>
                        <div><dt>Chuyên môn</dt><dd>{{ $teacher->specialization ?: 'Chưa cập nhật' }}</dd></div>
                        <div><dt>Ngày sinh</dt><dd>{{ $teacher->date_of_birth?->format('d/m/Y') ?: 'Chưa cập nhật' }}</dd></div>
                        <div><dt>Giới tính</dt><dd>{{ ['male' => 'Nam', 'female' => 'Nữ', 'other' => 'Khác'][$teacher->gender] ?? 'Chưa cập nhật' }}</dd></div>
                        <div><dt>Email</dt><dd>{{ $teacher->email ?: 'Chưa cập nhật' }}</dd></div>
                        <div><dt>Điện thoại</dt><dd>{{ $teacher->phone ?: 'Chưa cập nhật' }}</dd></div>
                        <div><dt>Địa chỉ</dt><dd>{{ $teacher->address ?: 'Chưa cập nhật' }}</dd></div>
                    </dl>
                </div>
            </section>
        </div>

        <div class="col-xl-4">
            <section class="card h-100" aria-labelledby="teacher-subject-title">
                <div class="card-body">
                    <h2 id="teacher-subject-title" class="panel-title">Năng lực môn học</h2>
                    <div class="d-flex flex-wrap gap-2 mb-3">
                        @forelse ($teacher->subjects as $subject)
                            <span class="badge text-bg-light">{{ $subject->name }}</span>
                        @empty
                            <span class="text-secondary small">Chưa khai báo môn giảng dạy.</span>
                        @endforelse
                    </div>

                    <div class="metric-strip mb-3">
                        <div class="metric-strip-item"><span>Môn học</span><strong>{{ number_format($teacher->subjects->count()) }}</strong></div>
                        <div class="metric-strip-item"><span>Lớp chủ nhiệm</span><strong>{{ number_format($teacher->homeroomAssignments->count()) }}</strong></div>
                        <div class="metric-strip-item"><span>Phân công gần</span><strong>{{ number_format($recentAssignments->count()) }}</strong></div>
                    </div>

                    <a href="{{ route('admin.school.teachers.history', $teacher) }}" class="btn btn-light w-100"
                        data-modal-url="{{ route('admin.school.teachers.history', $teacher) }}"
                        data-modal-readonly="true" data-modal-title="Lịch sử công tác">
                        <i data-lucide="history"></i><span>Lịch sử công tác đầy đủ</span>
                    </a>
                </div>
            </section>
        </div>
    </div>

    <section class="card table-card mb-3" aria-labelledby="homeroom-history-title">
        <div class="card-body border-bottom">
            <h2 id="homeroom-history-title" class="panel-title mb-0">Lịch sử chủ nhiệm</h2>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead><tr><th>Năm học</th><th>Lớp</th><th>Bắt đầu</th><th>Kết thúc</th><th>Trạng thái</th></tr></thead>
                <tbody>
                    @forelse ($teacher->homeroomAssignments as $record)
                        <tr>
                            <td><strong>{{ $record->academicYear?->name ?: '-' }}</strong></td>
                            <td>{{ $record->class?->name ?: '-' }}</td>
                            <td>{{ $record->started_on?->format('d/m/Y') ?: 'Không xác định' }}</td>
                            <td>{{ $record->ended_on?->format('d/m/Y') ?: '-' }}</td>
                            <td>
                                <x-admin.status-badge :tone="$record->status === 'active' ? 'success' : 'neutral'">
                                    {{ $record->status === 'active' ? 'Đang chủ nhiệm' : 'Đã kết thúc' }}
                                </x-admin.status-badge>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="empty-state"><x-admin.empty-state icon="school" title="Chưa có lịch sử chủ nhiệm" /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section class="card table-card" aria-labelledby="recent-assignment-title">
        <div class="card-body border-bottom">
            <h2 id="recent-assignment-title" class="panel-title mb-0">Phân công gần đây</h2>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead><tr><th>Năm học</th><th>Lớp</th><th>Môn</th><th>Học kỳ</th><th>Trạng thái</th></tr></thead>
                <tbody>
                    @forelse ($recentAssignments as $assignment)
                        <tr>
                            <td><strong>{{ $assignment->academicYear?->name ?: '-' }}</strong></td>
                            <td>{{ $assignment->class?->name ?: '-' }}</td>
                            <td>{{ $assignment->subject?->name ?: '-' }}</td>
                            <td>{{ $assignment->semester?->name ?: 'Cả năm' }}</td>
                            <td>
                                <x-admin.status-badge :tone="$assignment->status === 'active' ? 'success' : 'neutral'">
                                    {{ $assignment->status === 'active' ? 'Đang dạy' : 'Đã kết thúc' }}
                                </x-admin.status-badge>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="empty-state"><x-admin.empty-state icon="book-open" title="Chưa có phân công" /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection

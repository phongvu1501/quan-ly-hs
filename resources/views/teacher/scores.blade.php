@extends('admin.layouts.app')

@section('title', 'Sổ điểm giáo viên')

@php
    $canCreateScores = auth()->user()->hasPermission('scores.create');
    $canEditScores = auth()->user()->hasPermission('scores.edit');
@endphp

@section('content')
    <div class="page-heading page-heading-inline">
        <div>
            <p class="eyebrow">Giáo viên bộ môn</p>
            <h1>Sổ điểm</h1>
            <p class="text-secondary mb-0">Nhập điểm theo đúng lớp và môn đã được phân công.</p>
        </div>
        <form method="GET" class="d-flex gap-2"><label class="visually-hidden" for="assignment_id">Chọn phân
                công</label><select id="assignment_id" name="assignment_id" class="form-select" onchange="this.form.submit()">
                <option value="">Chọn lớp và môn</option>
                @foreach ($assignments as $item)
                    <option value="{{ $item->id }}" @selected($assignment?->id === $item->id)>{{ $item->class?->name }} ·
                        {{ $item->subject?->name }} · {{ $item->semester?->name ?: 'Cả năm' }}</option>
                @endforeach
            </select>
        </form>
    </div>

    @if ($assignment)
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="card h-100">
                    <div class="card-body"><small class="text-secondary d-block">Điểm TB chung của lớp</small><strong
                            class="fs-3">{{ $classAverage !== null ? number_format((float) $classAverage, 2) : '-' }}</strong><small
                            class="d-block text-secondary">Tính theo các môn đã có điểm</small></div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-100">
                    <div class="card-body"><small class="text-secondary d-block">Học sinh có điểm cao nhất</small><strong
                            class="d-block">{{ $topStudent?->full_name ?: '-' }}</strong><small
                            class="text-secondary">{{ $topStudent && isset($academicSummaries[$topStudent->id]['average']) ? number_format((float) $academicSummaries[$topStudent->id]['average'], 2) : '-' }}
                            điểm</small></div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-100">
                    <div class="card-body"><small class="text-secondary d-block">Cách tính</small><strong class="d-block">GK
                            hệ số 2 · CK hệ số 3</strong><small class="text-secondary">Điểm thường xuyên hệ số 1</small>
                    </div>
                </div>
            </div>
        </div>
        <div class="card table-card">
            <div class="card-body border-bottom d-flex justify-content-between align-items-center gap-3">
                <div><strong>{{ $assignment->class?->name }} · {{ $assignment->subject?->name }}</strong><small
                        class="d-block text-secondary">Theo dõi điểm theo lớp và môn được phân công.</small></div>
                @if (auth()->user()->hasPermission('attendance.view'))
                    <a class="btn btn-sm btn-light"
                        href="{{ route('teacher.attendance', ['class_id' => $assignment->class_id]) }}"><i
                            data-lucide="clipboard-check"></i> Điểm danh lớp</a>
                @endif
            </div>
            @if ($canCreateScores)
                <form action="{{ route('teacher.scores.store') }}" method="POST" id="scores-form">
                    @csrf
                    <input type="hidden" name="assignment_id" value="{{ $assignment->id }}">
            @endif
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Học sinh</th>
                            <th>Điểm</th>
                            <th>Loại điểm</th>
                            <th>Điểm</th>
                            <th>TB môn / học lực</th>
                            <th>Ngày</th>
                            <th class="text-end">Sửa</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($students as $enrollment)
                            @php($recentScores = ($scoreMap[$enrollment->student_id] ?? collect())->take(4))
                            @php($latestScore = $recentScores->first())
                            @php($studentId = $enrollment->student_id)
                            @php($scoreInputId = 'score-input-' . $studentId)
                            @php($summary = $academicSummaries[$studentId] ?? null)
                            @php($subjectAverage = $subjectAverages[$studentId] ?? null)
                            <tr>
                                <td><strong>{{ $enrollment->student?->full_name }}</strong><small
                                        class="d-block text-secondary">{{ $enrollment->student?->student_code }}</small>
                                </td>
                                <td>
                                    @if ($recentScores->isNotEmpty())
                                        <span class="badge text-bg-light">{{ $recentScores->count() }} điểm</span>
                                        <small class="d-block text-secondary">TB
                                            {{ number_format((float) $recentScores->avg('score'), 2) }}</small>
                                    @else
                                        <span class="text-secondary">Chưa có</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($canCreateScores)
                                        <select name="scores[{{ $studentId }}][score_type]"
                                            class="form-select form-select-sm">
                                            <option value="oral">Miệng</option>
                                            <option value="fifteen_minute">15 phút</option>
                                            <option value="one_period">1 tiết</option>
                                            <option value="midterm">Giữa kỳ</option>
                                            <option value="final">Cuối kỳ</option>
                                        </select>
                                    @else
                                        <span class="text-secondary">{{ $latestScore?->score_type ?: '-' }}</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($canCreateScores)
                                        <input id="{{ $scoreInputId }}" type="number"
                                            name="scores[{{ $studentId }}][score]" class="form-control form-control-sm"
                                            min="0" max="10" step="0.01"
                                            value="{{ old('scores.' . $studentId . '.score', '') }}">
                                    @else
                                        {{ $latestScore?->score ?? '-' }}
                                    @endif
                                </td>
                                <td><strong>{{ $subjectAverage !== null ? number_format((float) $subjectAverage, 2) : '-' }}</strong><small
                                        class="d-block text-secondary">{{ $summary['rating'] ?? 'Chưa đủ dữ liệu' }}
                                        @if ($summary && $summary['reward'])
                                            <span class="text-success">· Khen thưởng</span>
                                        @endif
                                    </small>
                                </td>
                                <td>
                                    @if ($canCreateScores)
                                        <input type="date" name="scores[{{ $studentId }}][assessed_on]"
                                            class="form-control form-control-sm"
                                            value="{{ old('scores.' . $studentId . '.assessed_on', now()->format('Y-m-d')) }}">
                                    @else
                                        {{ $latestScore?->assessed_on?->format('d/m/Y') ?: '-' }}
                                    @endif
                                </td>
                                <td class="text-end">
                                    @if ($latestScore)
                                        <button type="button" class="btn btn-sm btn-light" data-bs-toggle="modal"
                                            data-bs-target="#scoreEditModal-{{ $studentId }}"
                                            title="Xem chi tiết tất cả điểm"><i data-lucide="list-checks"></i><span
                                                class="visually-hidden">Xem chi tiết tất cả điểm</span></button>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="empty-state">Lớp chưa có học sinh.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($canCreateScores)
                <div class="p-3 border-top d-flex justify-content-end"><button class="btn btn-primary" type="submit"><i
                            data-lucide="save"></i> Lưu toàn bộ điểm</button></div>
                </form>
            @endif
            @if ($students->hasPages())
                <div class="p-3 border-top">{{ $students->links('pagination::bootstrap-5') }}</div>
            @endif
        </div>

        @foreach ($students as $enrollment)
            @php($studentScores = $scoreMap[$enrollment->student_id] ?? collect())
            @if ($studentScores->isNotEmpty())
                <div class="modal fade" id="scoreEditModal-{{ $enrollment->student_id }}" tabindex="-1"
                    aria-labelledby="scoreEditTitle-{{ $enrollment->student_id }}" aria-hidden="true">
                    <div class="modal-dialog modal-lg modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h2 class="modal-title fs-5" id="scoreEditTitle-{{ $enrollment->student_id }}">Chi tiết các
                                    hệ điểm · {{ $enrollment->student?->full_name }}</h2>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"
                                    aria-label="Đóng"></button>
                            </div>
                            <div class="modal-body">
                                <div class="table-responsive">
                                    <table class="table align-middle mb-0">
                                        <thead>
                                            <tr>
                                                <th>Hệ điểm</th>
                                                <th>Điểm</th>
                                                <th>Ngày</th>
                                                <th>Ghi chú</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($studentScores as $scoreIndex => $score)
                                                <tr>
                                                    @php($scoreFormId = 'score-update-' . $score->id)
                                                    <td>
                                                        @if ($canEditScores)
                                                            <form id="{{ $scoreFormId }}"
                                                                action="{{ route('teacher.scores.update', $score) }}"
                                                                method="POST">@csrf @method('PUT')</form>
                                                            <select name="score_type" form="{{ $scoreFormId }}"
                                                                class="form-select form-select-sm" required>
                                                                <option value="oral" @selected($score->score_type === 'oral')>Miệng
                                                                </option>
                                                                <option value="fifteen_minute"
                                                                    @selected($score->score_type === 'fifteen_minute')>15 phút</option>
                                                                <option value="one_period" @selected($score->score_type === 'one_period')>1
                                                                    tiết</option>
                                                                <option value="midterm" @selected($score->score_type === 'midterm')>Giữa
                                                                    kỳ</option>
                                                                <option value="final" @selected($score->score_type === 'final')>Cuối
                                                                    kỳ</option>
                                                            </select>
                                                        @else
                                                            {{ $score->score_type }}
                                                        @endif
                                                    </td>
                                                    <td>
                                                        @if ($canEditScores)
                                                            <input type="number" name="score"
                                                                form="{{ $scoreFormId }}" value="{{ $score->score }}"
                                                                class="form-control form-control-sm" min="0"
                                                                max="10" step="0.01"
                                                                required>@else{{ $score->score }}
                                                        @endif
                                                    </td>
                                                    <td>
                                                        @if ($canEditScores)
                                                            <input type="date" name="assessed_on"
                                                                form="{{ $scoreFormId }}"
                                                                value="{{ $score->assessed_on?->format('Y-m-d') }}"
                                                                class="form-control form-control-sm">@else{{ $score->assessed_on?->format('d/m/Y') ?: '-' }}
                                                        @endif
                                                    </td>
                                                    <td>
                                                        @if ($canEditScores)
                                                            <div class="d-flex gap-2"><input name="note"
                                                                    form="{{ $scoreFormId }}"
                                                                    value="{{ $score->note }}"
                                                                    class="form-control form-control-sm"><button
                                                                    type="submit" form="{{ $scoreFormId }}"
                                                                    class="btn btn-sm btn-primary" title="Lưu điểm"><i
                                                                        data-lucide="save"></i></button></div>
                                                        @else
                                                            {{ $score->note ?: '-' }}
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <div class="modal-footer"><button type="button" class="btn btn-light"
                                    data-bs-dismiss="modal">Đóng</button></div>
                        </div>
                    </div>
                </div>
            @endif
        @endforeach
    @else
        <div class="card">
            <div class="empty-state">Tài khoản chưa có phân công bộ môn đang hoạt động.</div>
        </div>
    @endif
@endsection

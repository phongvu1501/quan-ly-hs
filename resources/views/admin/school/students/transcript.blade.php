@extends('admin.layouts.app')

@section('title', 'Học bạ học sinh')

@section('content')
    <div class="page-heading page-heading-inline">
        <div>
            <p class="eyebrow">Hồ sơ học tập</p>
            <h1>Học bạ · {{ $student->full_name }}</h1>
            <p class="text-secondary mb-0">{{ $student->student_code }} · Lưu kết quả theo từng năm học.</p>
        </div>
        <a href="{{ route('admin.school.students.index') }}" class="btn btn-light"><i data-lucide="arrow-left"></i> Danh sách</a>
    </div>

    <div class="card table-card mb-4">
        <div class="card-body border-bottom"><strong>Lịch sử lớp học</strong><span class="text-secondary ms-2">{{ $enrollments->count() }} năm đã ghi nhận</span></div>
        <div class="table-responsive"><table class="table table-hover align-middle mb-0"><thead><tr><th>Năm học</th><th>Lớp</th><th>Trạng thái</th><th>Ngày vào</th><th>Ngày rời</th></tr></thead><tbody>
            @forelse($enrollments as $enrollment)
                <tr><td><strong>{{ $enrollment->academicYear?->name ?: '-' }}</strong></td><td>{{ $enrollment->class?->name ?: '-' }}<small class="d-block text-secondary">{{ $enrollment->class?->grade?->name }}</small></td><td>{{ ['active' => 'Đang đi học', 'inactive' => 'Đã rời lớp', 'completed' => 'Hoàn thành', 'withdrawn' => 'Đã thôi học'][$enrollment->status] ?? $enrollment->status }}</td><td>{{ $enrollment->enrolled_on?->format('d/m/Y') ?: '-' }}</td><td>{{ $enrollment->left_on?->format('d/m/Y') ?: '-' }}</td></tr>
            @empty
                <tr><td colspan="5" class="empty-state">Chưa có lịch sử đăng ký lớp.</td></tr>
            @endforelse
        </tbody></table></div>
    </div>

    @forelse($years as $year)
        @php
            $enrollment = $enrollments->firstWhere('academic_year_id', $year->id);
            $yearResults = $subjectResults->where('academic_year_id', $year->id);
            $yearScores = $scores->where('academic_year_id', $year->id);
            $yearConduct = $conductEvaluations->where('academic_year_id', $year->id);
            $yearExams = $examResults->filter(fn ($result) => $result->exam?->academic_year_id == $year->id);
            $attendance = ($attendanceSummary[$year->id] ?? collect())->keyBy('status');
        @endphp
        <section class="card table-card mb-4">
            <div class="card-body border-bottom d-flex justify-content-between align-items-center gap-3"><div><h2 class="panel-title mb-1">Năm học {{ $year->name }}</h2><span class="text-secondary">{{ $enrollment?->class?->name ?: 'Chưa có lớp' }}</span></div><span class="badge text-bg-light">{{ $yearResults->count() }} kết quả môn</span></div>
            <div class="card-body">
                <div class="row g-4">
                    <div class="col-xl-7">
                        <h3 class="section-label">Kết quả học tập</h3>
                        @if ($yearResults->isNotEmpty())
                            <div class="table-responsive"><table class="table table-sm align-middle"><thead><tr><th>Học kỳ</th><th>Môn</th><th>Điểm TB</th><th>Xếp loại</th><th>Nhận xét</th></tr></thead><tbody>
                                @foreach($yearResults as $result)<tr><td>{{ $result->semester?->name ?: '-' }}</td><td>{{ $result->subject?->name ?: '-' }}</td><td><strong>{{ $result->average_score !== null ? number_format((float) $result->average_score, 2) : '-' }}</strong></td><td>{{ $result->rating ?: '-' }}</td><td>{{ $result->teacher_comment ?: '-' }}</td></tr>@endforeach
                            </tbody></table></div>
                        @elseif($yearScores->isNotEmpty())
                            @php($scoreGroups = $yearScores->groupBy('subject_id'))
                            <div class="table-responsive"><table class="table table-sm align-middle"><thead><tr><th>Môn</th><th>Học kỳ</th><th>Điểm</th><th>Ngày gần nhất</th><th class="text-end">Chi tiết</th></tr></thead><tbody>
                                @foreach($scoreGroups as $subjectScores)
                                    @php($scoreModalId = 'transcript-score-detail-' . $year->id . '-' . $subjectScores->first()?->subject_id)
                                    <tr><td><strong>{{ $subjectScores->first()?->subject?->name ?: '-' }}</strong></td><td>{{ $subjectScores->pluck('semester.name')->filter()->unique()->join(', ') ?: '-' }}</td><td><span class="badge text-bg-light">{{ $subjectScores->count() }} điểm</span><strong class="d-block">TB {{ number_format((float) $subjectScores->avg('score'), 2) }}</strong></td><td>{{ $subjectScores->sortByDesc(fn($score) => $score->assessed_on?->timestamp ?: 0)->first()?->assessed_on?->format('d/m/Y') ?: '-' }}</td><td class="text-end"><button type="button" class="btn btn-sm btn-light" data-bs-toggle="modal" data-bs-target="#{{ $scoreModalId }}" title="Xem chi tiết điểm"><i data-lucide="list-checks"></i><span class="visually-hidden">Xem chi tiết điểm</span></button></td></tr>
                                @endforeach
                            </tbody></table></div>
                            @foreach($scoreGroups as $subjectScores)
                                @php($scoreModalId = 'transcript-score-detail-' . $year->id . '-' . $subjectScores->first()?->subject_id)
                                <div class="modal fade" id="{{ $scoreModalId }}" tabindex="-1" aria-labelledby="{{ $scoreModalId }}-title" aria-hidden="true"><div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable"><div class="modal-content">
                                    <div class="modal-header"><div><h2 class="modal-title fs-5" id="{{ $scoreModalId }}-title">Chi tiết điểm · {{ $subjectScores->first()?->subject?->name }}</h2><small class="text-secondary">Năm học {{ $year->name }} · {{ $subjectScores->count() }} điểm</small></div><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button></div>
                                    <div class="modal-body"><div class="table-responsive"><table class="table align-middle mb-0"><thead><tr><th>Học kỳ</th><th>Loại điểm</th><th>Điểm</th><th>Ngày</th><th>Ghi chú</th></tr></thead><tbody>@foreach($subjectScores as $score)<tr><td>{{ $score->semester?->name ?: '-' }}</td><td>{{ ['oral' => 'Miệng', 'fifteen_minute' => '15 phút', 'one_period' => '1 tiết', 'midterm' => 'Giữa kỳ', 'final' => 'Cuối kỳ'][$score->score_type] ?? $score->score_type }}</td><td><strong>{{ number_format((float) $score->score, 2) }}</strong></td><td>{{ $score->assessed_on?->format('d/m/Y') ?: '-' }}</td><td>{{ $score->note ?: '-' }}</td></tr>@endforeach</tbody></table></div></div>
                                    <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Đóng</button></div>
                                </div></div></div>
                            @endforeach
                        @else
                            <p class="text-secondary mb-0">Chưa có điểm hoặc kết quả môn học.</p>
                        @endif
                    </div>
                    <div class="col-xl-5">
                        <h3 class="section-label">Hạnh kiểm và chuyên cần</h3>
                        @forelse($yearConduct as $conduct)<div class="border-bottom pb-2 mb-2"><strong>{{ $conduct->semester?->name ?: 'Học kỳ' }} · {{ $conduct->rating ?: 'Chưa xếp loại' }}</strong><p class="mb-0 text-secondary">{{ $conduct->comment ?: 'Không có nhận xét.' }}</p></div>@empty<p class="text-secondary">Chưa có đánh giá hạnh kiểm.</p>@endforelse
                        <div class="d-flex flex-wrap gap-2 mt-3"><span class="badge text-bg-light">Có mặt: {{ $attendance['present']->total ?? 0 }}</span><span class="badge text-bg-light">Vắng: {{ $attendance['absent']->total ?? 0 }}</span><span class="badge text-bg-light">Đi muộn: {{ $attendance['late']->total ?? 0 }}</span><span class="badge text-bg-light">Có phép: {{ $attendance['excused']->total ?? 0 }}</span></div>
                        <h3 class="section-label mt-4">Kết quả kỳ thi</h3>
                        @forelse($yearExams as $result)<div class="d-flex justify-content-between border-bottom py-2"><span>{{ $result->exam?->subject?->name ?: $result->exam?->name }}</span><strong>{{ $result->score !== null ? number_format((float) $result->score, 2) : ($result->status ?: '-') }}</strong></div>@empty<p class="text-secondary">Chưa có kết quả kỳ thi.</p>@endforelse
                    </div>
                </div>
            </div>
        </section>
    @empty
        <div class="card"><div class="empty-state">Chưa có dữ liệu học bạ theo năm học.</div></div>
    @endforelse
@endsection

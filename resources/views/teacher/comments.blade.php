@extends('admin.layouts.app')

@section('title', 'Nhận xét học sinh')

@section('content')
    <div class="page-heading page-heading-inline">
        <div>
            <p class="eyebrow">Theo dõi học tập</p>
            <h1>Nhận xét học sinh</h1>
            <p class="text-secondary mb-0">Ghi nhận xét theo từng học sinh để các em xem lại trong cổng học sinh.</p>
        </div>
        <a href="{{ route('teacher.subject.dashboard') }}" class="btn btn-light">
            <i data-lucide="arrow-left"></i> Về trang giáo viên
        </a>
    </div>

    <div class="card table-card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label" for="comment-class">Lớp</label>
                    <select id="comment-class" name="class_id" class="form-select" onchange="this.form.submit()">
                        @forelse ($classes as $item)
                            <option value="{{ $item->id }}" @selected($class?->id === $item->id)>
                                {{ $item->name }}{{ $item->grade?->name ? ' · ' . $item->grade->name : '' }}
                            </option>
                        @empty
                            <option value="">Chưa có lớp phụ trách</option>
                        @endforelse
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="comment-semester">Học kỳ</label>
                    <select id="comment-semester" name="semester_id" class="form-select" onchange="this.form.submit()">
                        @forelse ($semesterOptions as $item)
                            <option value="{{ $item->id }}" @selected($selectedSemester?->id === $item->id)>{{ $item->name }}</option>
                        @empty
                            <option value="">Chưa có học kỳ</option>
                        @endforelse
                    </select>
                </div>
                <div class="col-md-5">
                    <label class="form-label" for="comment-subject">Môn nhận xét</label>
                    <select id="comment-subject" name="subject_id" class="form-select" onchange="this.form.submit()">
                        <option value="">Nhận xét chung</option>
                        @foreach ($subjects as $item)
                            <option value="{{ $item->id }}" @selected($subjectId === $item->id)>{{ $item->name }}</option>
                        @endforeach
                    </select>
                </div>
            </form>
        </div>
    </div>

    @if ($class && $selectedSemester)
        <div class="card table-card">
            <div class="card-body border-bottom d-flex justify-content-between align-items-center gap-3">
                <div>
                    <h2 class="panel-title mb-1">{{ $class->name }} · {{ $selectedSemester->name }}</h2>
                    <small class="text-secondary">
                        {{ $subjectId ? $subjects->firstWhere('id', $subjectId)?->name : 'Nhận xét chung của giáo viên' }}
                    </small>
                </div>
                <span class="badge text-bg-light">{{ $students->count() }} học sinh</span>
            </div>
            <form action="{{ route('teacher.comments.store') }}" method="POST" id="comments-form">
                @csrf
                <input type="hidden" name="class_id" value="{{ $class->id }}">
                <input type="hidden" name="subject_id" value="{{ $subjectId }}">
                <input type="hidden" name="semester_id" value="{{ $selectedSemester->id }}">
                <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th style="min-width: 190px">Học sinh</th>
                            <th style="min-width: 360px">Nhận xét gần nhất</th>
                            <th style="min-width: 360px">Nhập nhận xét mới</th>
                            <th class="text-end">Sửa</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($students as $enrollment)
                            @php($student = $enrollment->student)
                            @php($comment = $commentMap[$enrollment->student_id] ?? null)
                            <tr>
                                <td>
                                    <strong>{{ $student?->full_name }}</strong>
                                    <small class="d-block text-secondary">{{ $student?->student_code }}</small>
                                </td>
                                <td>
                                    @if ($comment)
                                        <p class="mb-1">{{ $comment->content }}</p>
                                        <small class="text-secondary">
                                            {{ $comment->teacher?->full_name ?: 'Giáo viên' }} · {{ $comment->created_at?->format('d/m/Y H:i') }}
                                        </small>
                                    @else
                                        <span class="text-secondary">Chưa có nhận xét</span>
                                    @endif
                                </td>
                                <td>
                                    <input type="hidden" name="comments[{{ $enrollment->student_id }}][comment_id]" value="{{ $comment?->id }}">
                                    <textarea name="comments[{{ $enrollment->student_id }}][content]" class="form-control" rows="2" maxlength="3000"
                                        placeholder="Nhập nhận xét cho {{ $student?->full_name }}">{{ old('comments.' . $enrollment->student_id . '.content', $comment?->content) }}</textarea>
                                </td>
                                <td class="text-end">
                                    @if ($comment)
                                        <button type="button" class="btn btn-sm btn-light" data-bs-toggle="modal"
                                            data-bs-target="#commentEditModal" data-comment-id="{{ $comment->id }}"
                                            data-comment-content="{{ $comment->content }}" title="Sửa nhận xét">
                                            <i data-lucide="pencil"></i><span class="visually-hidden">Sửa nhận xét</span>
                                        </button>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="empty-state">Lớp chưa có học sinh đang học.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
                </div>
                <div class="p-3 border-top d-flex justify-content-end"><button class="btn btn-primary" type="submit"><i
                            data-lucide="save"></i> Lưu toàn bộ nhận xét</button></div>
            </form>
        </div>

        <div class="modal fade" id="commentEditModal" tabindex="-1" aria-labelledby="commentEditTitle" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h2 class="modal-title fs-5" id="commentEditTitle">Sửa nhận xét</h2>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                    </div>
                    <form id="commentEditForm" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="modal-body">
                            <label class="form-label" for="commentEditContent">Nội dung nhận xét</label>
                            <textarea id="commentEditContent" name="content" class="form-control" rows="6" maxlength="3000" required></textarea>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Hủy</button>
                            <button type="submit" class="btn btn-primary"><i data-lucide="save"></i> Cập nhật nhận xét</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @else
        <div class="card">
            <div class="empty-state">Chưa có lớp hoặc học kỳ để nhập nhận xét.</div>
        </div>
    @endif
    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const form = document.getElementById('commentEditForm');
                const content = document.getElementById('commentEditContent');
                if (!form || !content) return;
                const updateUrl = @json(route('teacher.comments.update', ['comment' => '__COMMENT__']));
                document.querySelectorAll('[data-comment-id]').forEach((button) => {
                    button.addEventListener('click', () => {
                        form.action = updateUrl.replace('__COMMENT__', button.dataset.commentId);
                        content.value = button.dataset.commentContent || '';
                    });
                });
            });
        </script>
    @endpush
@endsection

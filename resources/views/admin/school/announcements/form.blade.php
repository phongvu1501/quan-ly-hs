@extends('admin.layouts.app')
@php($isEditing = $announcement !== null)
@section('title', $isEditing ? 'Sửa thông báo' : 'Tạo thông báo')
@section('content')<div class="page-heading">
        <div>
            <p class="eyebrow">Truyền thông nhà trường</p>
            <h1>{{ $isEditing ? 'Sửa thông báo' : 'Tạo thông báo' }}</h1>
        </div>
    </div>
    <form action="{{ $isEditing ? route('admin.school.announcements.update', $announcement) : route('admin.school.announcements.store') }}" method="POST">@csrf @if ($isEditing)
        @method('PUT')
    @endif<div class="card form-card">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-12"><label class="form-label">Tiêu đề</label><input name="title" class="form-control"
                            value="{{ old('title', $announcement?->title) }}" required></div>
                    <div class="col-md-4"><label class="form-label">Loại</label><select name="type" class="form-select">
                            <option value="general" @selected(old('type', $announcement?->type ?: 'general') === 'general')>Chung</option>
                            <option value="tuition" @selected(old('type', $announcement?->type) === 'tuition')>Học phí</option>
                            <option value="exam" @selected(old('type', $announcement?->type) === 'exam')>Lịch thi</option>
                            <option value="schedule" @selected(old('type', $announcement?->type) === 'schedule')>Lịch học</option>
                            <option value="urgent" @selected(old('type', $announcement?->type) === 'urgent')>Khẩn</option>
                        </select></div>
                    <div class="col-md-4"><label class="form-label">Đối tượng</label><select name="audience"
                            class="form-select" id="announcement-audience">
                            <option value="all" @selected(old('audience', $announcement?->audience ?: 'all') === 'all')>Toàn trường</option>
                            <option value="students" @selected(old('audience', $announcement?->audience) === 'students')>Học sinh</option>
                            <option value="teachers" @selected(old('audience', $announcement?->audience) === 'teachers')>Giáo viên</option>
                        </select></div>
                    <div class="col-md-8"><label class="form-label">Lớp nhận thông báo</label><select name="class_id"
                            class="form-select" id="announcement-class">
                            <option value="">Tất cả lớp thuộc đối tượng đã chọn</option>
                            @foreach($classes as $class)
                                <option value="{{ $class->id }}" @selected(old('class_id', $announcement?->class_id) == $class->id)>{{ $class->name }} · {{ $class->grade?->name }} · {{ $class->academicYear?->name }}</option>
                            @endforeach
                        </select></div>
                    <div class="col-md-4"><label class="form-label">Phát hành lúc</label><input type="datetime-local"
                            name="published_at" class="form-control" value="{{ old('published_at', $announcement?->published_at?->format('Y-m-d\\TH:i')) }}"></div>
                    <div class="col-md-4"><label class="form-label">Hết hạn lúc</label><input type="datetime-local"
                            name="expires_at" class="form-control" value="{{ old('expires_at', $announcement?->expires_at?->format('Y-m-d\\TH:i')) }}"></div>
                    <div class="col-12"><label class="form-label">Nội dung</label>
                        <textarea name="content" rows="7" class="form-control" required>{{ old('content', $announcement?->content) }}</textarea>
                    </div>
                    <div class="col-12"><label class="form-check"><input type="checkbox" name="is_published" value="1"
                                class="form-check-input" @checked(old('is_published', $announcement?->is_published ?? true))><span class="form-check-label">Phát hành ngay</span></label></div>
                </div>
                <div class="form-actions"><button class="btn btn-primary"><i data-lucide="send"></i> {{ $isEditing ? 'Cập nhật thông báo' : 'Lưu thông báo' }}</button><a href="{{ route('admin.school.announcements.index') }}" class="btn btn-light">Hủy</a>
                </div>
            </div>
        </div>
</form>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const audience = document.getElementById('announcement-audience');
            const classSelect = document.getElementById('announcement-class');
            const refresh = () => {
                classSelect.disabled = audience.value === 'all';
                if (classSelect.disabled) classSelect.value = '';
            };
            audience.addEventListener('change', refresh);
            refresh();
        });
    </script>
@endsection

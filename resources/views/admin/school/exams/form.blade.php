@extends('admin.layouts.app')
@php($isEditing = $exam !== null)
@section('title', $isEditing ? 'Sửa lịch thi' : 'Thêm lịch thi')
@section('content')<div class="page-heading">
        <div>
            <p class="eyebrow">Kiểm tra và thi</p>
            <h1>{{ $isEditing ? 'Sửa lịch thi' : 'Thêm lịch thi' }}</h1>
        </div>
    </div>
    <form action="{{ $isEditing ? route('admin.school.exams.update', $exam) : route('admin.school.exams.store') }}" method="POST">@csrf @if ($isEditing)
        @method('PUT')
    @endif<div class="card form-card">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6"><label class="form-label">Năm học</label><select name="academic_year_id"
                            class="form-select" required>
                            @foreach ($years as $year)
                                <option value="{{ $year->id }}" @selected(old('academic_year_id', $exam?->academic_year_id) == $year->id)>{{ $year->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6"><label class="form-label">Học kỳ</label><select name="semester_id"
                            class="form-select" required>
                            @foreach ($semesters as $semester)
                                <option value="{{ $semester->id }}" @selected(old('semester_id', $exam?->semester_id) == $semester->id)>{{ $semester->academicYear?->name }} ·
                                    {{ $semester->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6"><label class="form-label">Tên kỳ thi</label><input name="name"
                            class="form-control" value="{{ old('name', $exam?->name) }}" placeholder="Kiểm tra giữa kỳ I" required></div>
                    <div class="col-md-6"><label class="form-label">Loại</label><select name="exam_type"
                            class="form-select">
                            <option value="midterm" @selected(old('exam_type', $exam?->exam_type ?: 'midterm') === 'midterm')>Giữa kỳ</option>
                            <option value="final" @selected(old('exam_type', $exam?->exam_type) === 'final')>Cuối kỳ</option>
                            <option value="regular" @selected(old('exam_type', $exam?->exam_type) === 'regular')>Kiểm tra thường xuyên</option>
                            <option value="graduation" @selected(old('exam_type', $exam?->exam_type) === 'graduation')>Thi tốt nghiệp</option>
                        </select></div>
                    <div class="col-md-6"><label class="form-label">Môn học</label><select name="subject_id"
                            class="form-select" required>
                            @foreach ($subjects as $subject)
                                <option value="{{ $subject->id }}" @selected(old('subject_id', $exam?->subject_id) == $subject->id)>{{ $subject->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6"><label class="form-label">Lớp</label><select name="class_id" class="form-select">
                            <option value="">Toàn khối</option>
                            @foreach ($classes as $class)
                                <option value="{{ $class->id }}" @selected(old('class_id', $exam?->class_id) == $class->id)>{{ $class->name }} · {{ $class->academicYear?->name }}
                                </option>
                            @endforeach
                        </select></div>
                    <div class="col-md-4"><label class="form-label">Ngày thi</label><input type="date" name="exam_date"
                            class="form-control" value="{{ old('exam_date', $exam?->exam_date?->format('Y-m-d')) }}" required></div>
                    <div class="col-md-4"><label class="form-label">Bắt đầu</label><input type="time" name="starts_at"
                            class="form-control" value="{{ substr((string) old('starts_at', $exam?->starts_at), 0, 5) }}"></div>
                    <div class="col-md-4"><label class="form-label">Kết thúc</label><input type="time" name="ends_at"
                            class="form-control" value="{{ substr((string) old('ends_at', $exam?->ends_at), 0, 5) }}"></div>
                    <div class="col-md-6"><label class="form-label">Phòng</label><input name="room" class="form-control" value="{{ old('room', $exam?->room) }}">
                    </div>
                    <div class="col-12"><label class="form-label">Ghi chú</label>
                        <textarea name="notes" rows="3" class="form-control">{{ old('notes', $exam?->notes) }}</textarea>
                    </div>
                </div>
                <div class="form-actions"><button class="btn btn-primary"><i data-lucide="save"></i> {{ $isEditing ? 'Cập nhật lịch thi' : 'Lưu lịch thi' }}</button><a
                        href="{{ route('admin.school.exams.index') }}" class="btn btn-light">Hủy</a></div>
            </div>
        </div>
</form>@endsection

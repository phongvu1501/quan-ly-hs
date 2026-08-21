@extends('admin.layouts.app')
@php($isEditing = $examInvigilation !== null)
@section('title', $isEditing ? 'Sửa phân công coi thi' : 'Thêm phân công coi thi')
@section('content')
    <div class="page-heading"><div><p class="eyebrow">Kiểm tra và thi</p><h1>{{ $isEditing ? 'Sửa phân công coi thi' : 'Thêm phân công coi thi' }}</h1></div></div>
    <form action="{{ $isEditing ? route('admin.school.exam-invigilations.update', $examInvigilation) : route('admin.school.exam-invigilations.store') }}" method="POST">@csrf @if($isEditing) @method('PUT') @endif
        <div class="card form-card"><div class="card-body"><div class="row g-3">
            <div class="col-12"><label class="form-label">Kỳ thi</label><select name="exam_id" class="form-select" required><option value="">Chọn kỳ thi</option>@foreach($exams as $exam)<option value="{{ $exam->id }}" @selected(old('exam_id', $examInvigilation?->exam_id ?: $selectedExamId) == $exam->id)>{{ $exam->exam_date?->format('d/m/Y') }} · {{ $exam->name }} · {{ $exam->subject?->name }} · {{ $exam->class?->name ?: 'Toàn khối' }}</option>@endforeach</select></div>
            <div class="col-md-7"><label class="form-label">Giáo viên</label><select name="teacher_id" class="form-select" required><option value="">Chọn giáo viên đang công tác</option>@foreach($teachers as $teacher)<option value="{{ $teacher->id }}" @selected(old('teacher_id', $examInvigilation?->teacher_id) == $teacher->id)>{{ $teacher->full_name }} · {{ $teacher->teacher_code }}</option>@endforeach</select></div>
            <div class="col-md-5"><label class="form-label">Vai trò</label><select name="role" class="form-select" required>@foreach($roles as $value => $label)<option value="{{ $value }}" @selected(old('role', $examInvigilation?->role ?: 'invigilator') === $value)>{{ $label }}</option>@endforeach</select></div>
            <div class="col-12"><label class="form-label">Ghi chú</label><textarea name="notes" rows="3" class="form-control">{{ old('notes', $examInvigilation?->notes) }}</textarea></div>
        </div><div class="form-actions"><button class="btn btn-primary"><i data-lucide="save"></i> {{ $isEditing ? 'Cập nhật' : 'Lưu phân công' }}</button><a href="{{ route('admin.school.exam-invigilations.index') }}" class="btn btn-light">Hủy</a></div></div></div>
    </form>
@endsection

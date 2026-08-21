@extends('admin.layouts.app')

@php($isEditing = filled($assignment))
@section('title', $isEditing ? 'Sửa phân công' : 'Thêm phân công')

@section('content')
    <div class="page-heading">
        <div><p class="eyebrow">Giáo viên bộ môn</p><h1>{{ $isEditing ? 'Sửa phân công' : 'Thêm phân công' }}</h1></div>
    </div>
    <div class="card form-card"><div class="card-body">
        <form method="POST" action="{{ $isEditing ? route('admin.school.assignments.update', $assignment) : route('admin.school.assignments.store') }}">
            @csrf
            @if($isEditing) @method('PUT') @endif
            <div class="row g-3">
                <div class="col-md-6"><label class="form-label">Năm học</label><select name="academic_year_id" class="form-select" required>@foreach($years as $year)<option value="{{ $year->id }}" @selected(old('academic_year_id', $assignment?->academic_year_id) == $year->id)>{{ $year->name }}</option>@endforeach</select></div>
                <div class="col-md-6"><label class="form-label">Học kỳ</label><select name="semester_id" class="form-select"><option value="">Cả năm</option>@foreach($semesters as $semester)<option value="{{ $semester->id }}" data-year="{{ $semester->academic_year_id }}" @selected(old('semester_id', $assignment?->semester_id) == $semester->id)>{{ $semester->academicYear?->name }} · {{ $semester->name }}</option>@endforeach</select></div>
                <div class="col-md-6"><label class="form-label">Lớp</label><select name="class_id" class="form-select" required>@foreach($classes as $class)<option value="{{ $class->id }}" data-year="{{ $class->academic_year_id }}" @selected(old('class_id', $assignment?->class_id) == $class->id)>{{ $class->name }} · {{ $class->grade?->name }} · {{ $class->academicYear?->name }}</option>@endforeach</select></div>
                <div class="col-md-6"><label class="form-label">Môn học</label><select name="subject_id" class="form-select" required>@foreach($subjects as $subject)<option value="{{ $subject->id }}" @selected(old('subject_id', $assignment?->subject_id) == $subject->id)>{{ $subject->name }} · {{ $subject->code }}</option>@endforeach</select></div>
                <div class="col-md-6"><label class="form-label">Giáo viên</label><select name="teacher_id" class="form-select" required>@foreach($teachers as $teacher)<option value="{{ $teacher->id }}" @selected(old('teacher_id', $assignment?->teacher_id) == $teacher->id)>{{ $teacher->full_name }} · {{ $teacher->teacher_code }}</option>@endforeach</select></div>
            </div>
            <div class="d-flex justify-content-end gap-2 mt-4"><a href="{{ route('admin.school.assignments.index') }}" class="btn btn-light">Hủy</a><button class="btn btn-primary"><i data-lucide="save"></i> Lưu</button></div>
        </form>
    </div></div>
@endsection

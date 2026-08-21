@extends('admin.layouts.app')
@php($isEditing = isset($schoolClass))
@section('title', $isEditing ? 'Sửa lớp học' : 'Thêm lớp học')
@section('content')<div class="page-heading">
        <div>
            <p class="eyebrow">Tổ chức lớp</p>
            <h1>{{ $isEditing ? 'Sửa lớp học' : 'Thêm lớp học' }}</h1>
        </div>
    </div>
    <form
        action="{{ $isEditing ? route('admin.school.classes.update', $schoolClass) : route('admin.school.classes.store') }}"
        method="POST">@csrf @if ($isEditing)
            @method('PUT')
        @endif
        <div class="card form-card">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6"><label class="form-label" for="academic_year_id">Năm học có sẵn</label><select
                            name="academic_year_id" id="academic_year_id" class="form-select">
                            <option value="">Chọn năm học</option>
                            @foreach ($years as $year)
                                <option value="{{ $year->id }}" @selected(old('academic_year_id', $schoolClass?->academic_year_id) == $year->id)>{{ $year->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6"><label class="form-label" for="academic_year_name">Hoặc nhập năm học
                            mới</label><input name="academic_year_name" id="academic_year_name" class="form-control"
                            value="{{ old('academic_year_name') }}" placeholder="2027-2028">
                        <div class="form-text">Định dạng bắt buộc: YYYY-YYYY. Hệ thống sẽ tự tạo 2 học kỳ.</div>
                    </div>
                    <div class="col-md-6"><label class="form-label">Khối lớp</label><select name="grade_id"
                            class="form-select" required>
                            @foreach ($grades as $grade)
                                <option value="{{ $grade->id }}" @selected(old('grade_id', $schoolClass?->grade_id) == $grade->id)>{{ $grade->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4"><label class="form-label">Mã lớp</label><input name="code" class="form-control"
                            value="{{ old('code', $schoolClass?->code) }}" placeholder="10A1" required></div>
                    <div class="col-md-8"><label class="form-label">Tên lớp</label><input name="name"
                            class="form-control" value="{{ old('name', $schoolClass?->name) }}"
                            placeholder="10A1 - Khoa học tự nhiên" required></div>
                    <div class="col-md-6"><label class="form-label">Giáo viên chủ nhiệm</label><select
                            name="homeroom_teacher_id" class="form-select">
                            <option value="">Chưa phân công</option>
                            @foreach ($teachers as $teacher)
                                <option value="{{ $teacher->id }}" @selected(old('homeroom_teacher_id', $schoolClass?->homeroom_teacher_id) == $teacher->id)>{{ $teacher->full_name }}
                                    · {{ $teacher->teacher_code }}</option>
                            @endforeach
                        </select></div>
                    <div class="col-md-3"><label class="form-label">Phòng</label><input name="room" class="form-control"
                            value="{{ old('room', $schoolClass?->room) }}"></div>
                    <div class="col-md-3"><label class="form-label">Sĩ số tối đa</label><input type="number"
                            name="capacity" class="form-control"
                            value="{{ old('capacity', $schoolClass?->capacity ?: 45) }}" min="1" max="100"
                            required></div>
                    <div class="col-12"><label class="form-label">Ghi chú</label>
                        <textarea name="notes" rows="3" class="form-control">{{ old('notes', $schoolClass?->notes) }}</textarea>
                    </div>
                </div>
                <div class="form-actions"><button class="btn btn-primary"><i data-lucide="save"></i> Lưu lớp học</button><a
                        href="{{ route('admin.school.classes.index') }}" class="btn btn-light">Hủy</a></div>
            </div>
        </div>
    </form>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const select = document.getElementById('academic_year_id');
        const input = document.getElementById('academic_year_name');
        input.addEventListener('input', () => {
            if (input.value.trim()) select.value = '';
        });
        select.addEventListener('change', () => {
            if (select.value) input.value = '';
        });
    });
</script>@endsection

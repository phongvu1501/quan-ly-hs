@extends('admin.layouts.app')
@php($isEditing = $fee !== null)
@section('title', $isEditing ? 'Sửa thông báo học phí' : 'Tạo thông báo học phí')
@section('content')<div class="page-heading">
        <div>
            <p class="eyebrow">Tài chính học sinh</p>
            <h1>{{ $isEditing ? 'Sửa thông báo học phí' : 'Tạo thông báo học phí' }}</h1>
        </div>
    </div>
    <form action="{{ $isEditing ? route('admin.school.fees.update', $fee) : route('admin.school.fees.store') }}" method="POST">@csrf @if ($isEditing)
        @method('PUT')
    @endif<div class="card form-card">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6"><label class="form-label">Năm học</label><select name="academic_year_id"
                            class="form-select" required>
                            @foreach ($years as $year)
                                <option value="{{ $year->id }}" @selected(old('academic_year_id', $fee?->academic_year_id) == $year->id)>{{ $year->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6"><label class="form-label">Học kỳ</label><select name="semester_id"
                            class="form-select">
                            <option value="">Cả năm</option>
                            @foreach ($semesters as $semester)
                                <option value="{{ $semester->id }}" @selected(old('semester_id', $fee?->semester_id) == $semester->id)>{{ $semester->academicYear?->name }} ·
                                    {{ $semester->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-8"><label class="form-label">Tên khoản thu</label><input name="title"
                            class="form-control" value="{{ old('title', $fee?->title) }}" placeholder="Học phí học kỳ I" required></div>
                    <div class="col-md-4"><label class="form-label">Số tiền</label><input type="number" name="amount"
                            class="form-control" value="{{ old('amount', $fee?->amount) }}" min="0" step="1000" required></div>
                    <div class="col-md-6"><label class="form-label">Hạn đóng</label><input type="date" name="due_on"
                            class="form-control" value="{{ old('due_on', $fee?->due_on?->format('Y-m-d')) }}"></div>
                    <div class="col-md-6"><label class="form-label">Lớp áp dụng</label><select name="class_id"
                            class="form-select">
                            <option value="">Toàn trường</option>
                            @foreach ($classes as $class)
                                <option value="{{ $class->id }}" @selected(old('class_id', $fee?->class_id) == $class->id)>{{ $class->name }} · {{ $class->academicYear?->name }}
                                </option>
                            @endforeach
                        </select></div>
                    <div class="col-12"><label class="form-label">Nội dung hướng dẫn</label>
                        <textarea name="description" rows="4" class="form-control">{{ old('description', $fee?->description) }}</textarea>
                    </div>
                    <div class="col-12"><label class="form-check"><input type="checkbox" name="is_active" value="1"
                                class="form-check-input" @checked(old('is_active', $fee?->is_active ?? true))><span class="form-check-label">Đang thu</span></label>
                    </div>
                </div>
                <div class="form-actions"><button class="btn btn-primary"><i data-lucide="save"></i> {{ $isEditing ? 'Cập nhật khoản thu' : 'Lưu khoản thu' }}</button><a href="{{ route('admin.school.fees.index') }}" class="btn btn-light">Hủy</a></div>
            </div>
        </div>
</form>@endsection

@extends('admin.layouts.app')

@section('title', 'Hồ sơ giáo viên')

@section('content')
<div class="page-heading page-heading-inline">
    <div><p class="eyebrow">Hồ sơ nhân sự</p><h1>{{ $teacher->full_name }}</h1><p class="text-secondary mb-0">{{ $teacher->teacher_code }} · {{ $teacher->statusLabel() }}</p></div>
    <div class="d-flex gap-2"><a href="{{ route('admin.school.teachers.index') }}" class="btn btn-light"><i data-lucide="arrow-left"></i> Danh sách</a>@if(auth()->user()->hasPermission('teachers.edit'))<a href="{{ route('admin.school.teachers.edit',$teacher) }}" class="btn btn-primary"><i data-lucide="pencil"></i> Sửa hồ sơ</a>@endif</div>
</div>

<div class="row g-4 mb-4">
    <div class="col-xl-7"><section class="card"><div class="card-body"><h2 class="panel-title">Thông tin công tác</h2><dl class="row mb-0">
        <dt class="col-sm-4">Mã giáo viên</dt><dd class="col-sm-8"><code>{{ $teacher->teacher_code }}</code></dd>
        <dt class="col-sm-4">Trạng thái</dt><dd class="col-sm-8">{{ $teacher->statusLabel() }}</dd>
        <dt class="col-sm-4">Ngày vào trường</dt><dd class="col-sm-8">{{ $teacher->hire_date?->format('d/m/Y') ?: '-' }}</dd>
        <dt class="col-sm-4">Chuyên môn</dt><dd class="col-sm-8">{{ $teacher->specialization ?: '-' }}</dd>
        <dt class="col-sm-4">Ngày sinh</dt><dd class="col-sm-8">{{ $teacher->date_of_birth?->format('d/m/Y') ?: '-' }}</dd>
        <dt class="col-sm-4">Email</dt><dd class="col-sm-8">{{ $teacher->email ?: '-' }}</dd>
        <dt class="col-sm-4">Điện thoại</dt><dd class="col-sm-8">{{ $teacher->phone ?: '-' }}</dd>
        <dt class="col-sm-4">Địa chỉ</dt><dd class="col-sm-8">{{ $teacher->address ?: '-' }}</dd>
    </dl></div></section></div>
    <div class="col-xl-5"><section class="card"><div class="card-body"><h2 class="panel-title">Năng lực môn học</h2><div class="d-flex flex-wrap gap-2">@forelse($teacher->subjects as $subject)<span class="badge text-bg-light">{{ $subject->name }} · {{ $subject->pivot->academic_year_id }}</span>@empty<span class="text-secondary">Chưa khai báo môn.</span>@endforelse</div><hr><a href="{{ route('admin.school.teachers.history',$teacher) }}" class="btn btn-light w-100"><i data-lucide="history"></i> Lịch sử công tác đầy đủ</a></div></section></div>
</div>

<section class="card table-card mb-4"><div class="card-body border-bottom"><h2 class="panel-title mb-0">Lịch sử chủ nhiệm</h2></div><div class="table-responsive"><table class="table mb-0"><thead><tr><th>Năm học</th><th>Lớp</th><th>Bắt đầu</th><th>Kết thúc</th><th>Trạng thái</th></tr></thead><tbody>@forelse($teacher->homeroomAssignments as $record)<tr><td>{{ $record->academicYear?->name }}</td><td>{{ $record->class?->name }}</td><td>{{ $record->started_on?->format('d/m/Y') ?: 'Không xác định' }}</td><td>{{ $record->ended_on?->format('d/m/Y') ?: '-' }}</td><td>{{ $record->status==='active'?'Đang chủ nhiệm':'Đã kết thúc' }}</td></tr>@empty<tr><td colspan="5" class="empty-state">Chưa có lịch sử chủ nhiệm.</td></tr>@endforelse</tbody></table></div></section>

<section class="card table-card"><div class="card-body border-bottom"><h2 class="panel-title mb-0">Phân công gần đây</h2></div><div class="table-responsive"><table class="table mb-0"><thead><tr><th>Năm học</th><th>Lớp</th><th>Môn</th><th>Học kỳ</th><th>Trạng thái</th></tr></thead><tbody>@forelse($recentAssignments as $assignment)<tr><td>{{ $assignment->academicYear?->name }}</td><td>{{ $assignment->class?->name }}</td><td>{{ $assignment->subject?->name }}</td><td>{{ $assignment->semester?->name ?: 'Cả năm' }}</td><td>{{ $assignment->status==='active'?'Đang dạy':'Đã kết thúc' }}</td></tr>@empty<tr><td colspan="5" class="empty-state">Chưa có phân công.</td></tr>@endforelse</tbody></table></div></section>
@endsection

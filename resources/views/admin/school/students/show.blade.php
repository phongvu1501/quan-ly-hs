@extends('admin.layouts.app')

@section('title', 'Hồ sơ học sinh')

@section('content')
<div class="page-heading page-heading-inline">
    <div><p class="eyebrow">Hồ sơ học sinh</p><h1>{{ $student->full_name }}</h1><p class="text-secondary mb-0">{{ $student->student_code }} · {{ $student->statusLabel() }}</p></div>
    <div class="d-flex gap-2"><a href="{{ route('admin.school.students.index') }}" class="btn btn-light"><i data-lucide="arrow-left"></i> Danh sách</a>@if(auth()->user()->hasPermission('students.edit'))<a href="{{ route('admin.school.students.edit',$student) }}" class="btn btn-primary"><i data-lucide="pencil"></i> Sửa hồ sơ</a>@endif</div>
</div>

<div class="row g-4 mb-4">
    <div class="col-xl-7"><section class="card"><div class="card-body"><h2 class="panel-title">Thông tin cá nhân</h2><dl class="row mb-0">
        <dt class="col-sm-4">Mã học sinh</dt><dd class="col-sm-8"><code>{{ $student->student_code }}</code></dd>
        <dt class="col-sm-4">Trạng thái</dt><dd class="col-sm-8">{{ $student->statusLabel() }}</dd>
        <dt class="col-sm-4">Ngày sinh</dt><dd class="col-sm-8">{{ $student->date_of_birth?->format('d/m/Y') ?: '-' }}</dd>
        <dt class="col-sm-4">Giới tính</dt><dd class="col-sm-8">{{ ['male'=>'Nam','female'=>'Nữ','other'=>'Khác'][$student->gender] ?? '-' }}</dd>
        <dt class="col-sm-4">Năm tuyển sinh</dt><dd class="col-sm-8">{{ $student->admission_year ?: '-' }}</dd>
        <dt class="col-sm-4">Email</dt><dd class="col-sm-8">{{ $student->email ?: '-' }}</dd>
        <dt class="col-sm-4">Điện thoại</dt><dd class="col-sm-8">{{ $student->phone ?: '-' }}</dd>
        <dt class="col-sm-4">Nơi sinh</dt><dd class="col-sm-8">{{ $student->birth_place ?: '-' }}</dd>
        <dt class="col-sm-4">Địa chỉ</dt><dd class="col-sm-8">{{ $student->address ?: '-' }}</dd>
    </dl></div></section></div>
    <div class="col-xl-5"><section class="card"><div class="card-body"><h2 class="panel-title">Lớp hiện tại / gần nhất</h2>@if($currentEnrollment)<strong class="fs-5">{{ $currentEnrollment->class?->name }}</strong><p class="text-secondary">{{ $currentEnrollment->academicYear?->name }} · {{ $currentEnrollment->class?->grade?->name }}</p><p class="mb-0">{{ ['active'=>'Đang học','inactive'=>'Đã rời lớp','completed'=>'Đã hoàn thành','withdrawn'=>'Đã thôi học'][$currentEnrollment->status] ?? $currentEnrollment->status }}</p>@else<p class="text-secondary mb-0">Chưa có lịch sử xếp lớp.</p>@endif</div></section></div>
</div>

<section class="card table-card mb-4"><div class="card-body border-bottom d-flex justify-content-between"><h2 class="panel-title mb-0">Lịch sử lớp</h2><a href="{{ route('admin.school.students.transcript',$student) }}" class="btn btn-sm btn-light"><i data-lucide="book-open-check"></i> Học bạ</a></div><div class="table-responsive"><table class="table mb-0"><thead><tr><th>Năm học</th><th>Lớp</th><th>Trạng thái</th><th>Ngày vào</th><th>Ngày rời</th></tr></thead><tbody>@forelse($student->enrollments as $enrollment)<tr><td>{{ $enrollment->academicYear?->name }}</td><td>{{ $enrollment->class?->name }}</td><td>{{ ['active'=>'Đang học','inactive'=>'Đã rời lớp','completed'=>'Đã hoàn thành','withdrawn'=>'Đã thôi học'][$enrollment->status] ?? $enrollment->status }}</td><td>{{ $enrollment->enrolled_on?->format('d/m/Y') ?: '-' }}</td><td>{{ $enrollment->left_on?->format('d/m/Y') ?: '-' }}</td></tr>@empty<tr><td colspan="5" class="empty-state">Chưa có enrollment.</td></tr>@endforelse</tbody></table></div></section>

<div class="row g-4">
    <div class="col-xl-7"><section class="card table-card"><div class="card-body border-bottom"><h2 class="panel-title mb-0">Điểm gần đây</h2></div><div class="table-responsive"><table class="table mb-0"><thead><tr><th>Môn</th><th>Học kỳ</th><th>Loại</th><th>Điểm</th></tr></thead><tbody>@forelse($recentScores as $score)<tr><td>{{ $score->subject?->name }}</td><td>{{ $score->semester?->name }}</td><td>{{ $score->score_type }}</td><td><strong>{{ $score->score }}</strong></td></tr>@empty<tr><td colspan="4" class="empty-state">Chưa có điểm.</td></tr>@endforelse</tbody></table></div></section></div>
    <div class="col-xl-5"><section class="card"><div class="card-body"><h2 class="panel-title">Học vụ và tài chính</h2><div class="d-grid gap-2"><a href="{{ route('admin.school.students.transcript',$student) }}" class="btn btn-light text-start"><i data-lucide="book-open-check"></i> Điểm, chuyên cần, kỳ thi và hạnh kiểm</a>@if(auth()->user()->hasPermission('fees.view'))<a href="{{ route('admin.school.students.payments',$student) }}" class="btn btn-light text-start"><i data-lucide="receipt-text"></i> Học phí và thanh toán</a>@endif</div><hr><p class="mb-1">Điểm danh: <strong>{{ $student->attendance_count }}</strong></p><p class="mb-1">Kết quả kỳ thi: <strong>{{ $student->exam_results_count }}</strong></p><p class="mb-0">Khoản phải thu: <strong>{{ $student->fee_assessments_count }}</strong></p></div></section></div>
</div>
@endsection

@extends('admin.layouts.app')

@section('title', 'Tổng quan quản lý THPT')

@section('content')
<div class="page-heading page-heading-inline">
    <div>
        <p class="eyebrow">Quản lý nhà trường</p>
        <h1>Tổng quan quản lý THPT</h1>
        <p class="text-secondary mb-0">{{ $year?->name ?: 'Chưa thiết lập năm học' }} · Theo dõi dữ liệu vận hành trong ngày.</p>
    </div>
    <a href="{{ route('admin.school.students.create') }}" class="btn btn-primary" data-modal-title="Thêm học sinh"><i data-lucide="user-plus"></i> Thêm học sinh</a>
</div>

<div class="overview-grid">
    <a class="overview-item text-decoration-none" href="{{ route('admin.school.students.index') }}"><span class="overview-icon"><i data-lucide="graduation-cap"></i></span><h2>Học sinh đang học</h2><p class="fs-3 fw-bold text-dark mb-0">{{ number_format($students) }}</p></a>
    <a class="overview-item text-decoration-none" href="{{ route('admin.school.teachers.index') }}"><span class="overview-icon"><i data-lucide="contact-round"></i></span><h2>Giáo viên đang công tác</h2><p class="fs-3 fw-bold text-dark mb-0">{{ number_format($teachers) }}</p></a>
    <a class="overview-item text-decoration-none" href="{{ route('admin.school.classes.index') }}"><span class="overview-icon"><i data-lucide="users-round"></i></span><h2>Lớp học hiện tại</h2><p class="fs-3 fw-bold text-dark mb-0">{{ number_format($classes) }}</p></a>
    <a class="overview-item text-decoration-none" href="{{ route('admin.school.subjects.index') }}"><span class="overview-icon"><i data-lucide="book-open"></i></span><h2>Môn học</h2><p class="fs-3 fw-bold text-dark mb-0">{{ number_format($subjects) }}</p></a>
</div>

<div class="row g-4">
    <div class="col-xl-7">
        <div class="card table-card">
            <div class="card-body border-bottom"><h2 class="panel-title mb-0">Thông báo mới nhất</h2></div>
            <div class="list-group list-group-flush">
                @forelse($recentAnnouncements as $announcement)
                    <div class="list-group-item px-4 py-3"><strong>{{ $announcement->title }}</strong><small class="d-block text-secondary mt-1">{{ $announcement->published_at?->format('d/m/Y H:i') }} · {{ $announcement->audience }}</small></div>
                @empty
                    <div class="empty-state">Chưa có thông báo được phát hành.</div>
                @endforelse
            </div>
        </div>
    </div>
    <div class="col-xl-5">
        <div class="card table-card">
            <div class="card-body border-bottom"><h2 class="panel-title mb-0">Lịch thi sắp tới</h2></div>
            <div class="list-group list-group-flush">
                @forelse($upcomingExams as $exam)
                    <div class="list-group-item px-4 py-3"><strong>{{ $exam->name }}</strong><small class="d-block text-secondary mt-1">{{ $exam->exam_date?->format('d/m/Y') }} · {{ $exam->subject?->name }} · {{ $exam->class?->name ?: 'Toàn khối' }}</small></div>
                @empty
                    <div class="empty-state">Chưa có lịch thi sắp tới.</div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection

@extends('admin.layouts.app')

@section('title', 'Học sinh lớp '.$schoolClass->name)

@section('content')
    <div class="page-heading page-heading-inline">
        <div>
            <p class="eyebrow">Danh sách lớp</p>
            <h1>{{ $schoolClass->name }}</h1>
            <p class="text-secondary mb-0">{{ $schoolClass->grade?->name }} · {{ $schoolClass->academicYear?->name }} · {{ $schoolClass->homeroomTeacher?->full_name ?: 'Chưa phân công chủ nhiệm' }}</p>
        </div>
        <a href="{{ route('admin.school.classes.index', ['grade_id' => $schoolClass->grade_id, 'academic_year_id' => $schoolClass->academic_year_id]) }}" class="btn btn-light"><i data-lucide="arrow-left"></i> Danh sách lớp</a>
    </div>

    <div class="admin-search-wrap mb-4">
        <form method="GET" action="{{ route('admin.school.classes.students', $schoolClass) }}" class="admin-search">
            <label class="visually-hidden" for="class-students-search">Tìm học sinh</label>
            <i data-lucide="search" aria-hidden="true"></i>
            <input id="class-students-search" type="search" name="q" value="{{ request('q') }}" placeholder="Tìm theo mã, họ tên, email hoặc số điện thoại" autocomplete="off">
            @if(request()->filled('q'))<a class="admin-search-clear" href="{{ route('admin.school.classes.students', $schoolClass) }}" aria-label="Xóa tìm kiếm" title="Xóa tìm kiếm"><i data-lucide="x"></i></a>@endif
            <button type="submit" class="btn btn-sm btn-primary"><i data-lucide="search"></i><span>Tìm kiếm</span></button>
        </form>
    </div>

    <div class="card table-card">
        <div class="table-responsive"><table class="table table-hover align-middle mb-0">
            <thead><tr><th>STT</th><th>Mã học sinh</th><th>Họ và tên</th><th>Liên hệ</th><th>Ngày vào lớp</th><th>Trạng thái</th><th class="text-end">Thao tác</th></tr></thead>
            <tbody>
                @forelse($enrollments as $enrollment)
                    @php($student = $enrollment->student)
                    <tr>
                        <td>{{ $enrollment->roll_number ?: $enrollment->getKey() }}</td>
                        <td><code>{{ $student?->student_code }}</code></td>
                        <td><strong>{{ $student?->full_name }}</strong></td>
                        <td>{{ $student?->email ?: ($student?->phone ?: '-') }}</td>
                        <td>{{ $enrollment->enrolled_on?->format('d/m/Y') ?: '-' }}</td>
                        <td><span class="status-dot {{ $student?->status === 'studying' ? 'status-on' : 'status-off' }}">{{ $student?->status === 'studying' ? 'Đang đi học' : ($student?->status ?: '-') }}</span></td>
                        <td class="text-end"><a href="{{ route('admin.school.students.transcript', $student) }}" data-modal-readonly="true" class="btn btn-sm btn-light" title="Xem học bạ"><i data-lucide="book-open-check"></i><span class="visually-hidden">Xem học bạ</span></a></td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="empty-state">Lớp chưa có học sinh phù hợp.</td></tr>
                @endforelse
            </tbody>
        </table></div>
        @if($enrollments->hasPages())<div class="p-3 border-top">{{ $enrollments->links('pagination::bootstrap-5') }}</div>@endif
    </div>
@endsection

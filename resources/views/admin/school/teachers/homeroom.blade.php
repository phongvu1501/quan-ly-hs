@extends('admin.layouts.app')

@section('title', 'Giáo viên chủ nhiệm')

@section('content')
    <x-admin.page-header eyebrow="Phân công giáo viên" title="Giáo viên chủ nhiệm"
        description="Danh sách giáo viên và lớp chủ nhiệm theo năm học." />

    <nav class="view-switcher" aria-label="Chế độ xem giáo viên">
        <a href="{{ route('admin.school.teachers.index') }}"><i data-lucide="users"></i><span>Tất cả</span></a>
        <a href="{{ route('admin.school.teachers.homeroom') }}" class="active" aria-current="page"><i data-lucide="school"></i><span>Chủ nhiệm</span></a>
        <a href="{{ route('admin.school.teachers.subject') }}"><i data-lucide="book-open"></i><span>Bộ môn</span></a>
    </nav>

    <section class="card filter-card" aria-labelledby="homeroom-filter-title">
        <div class="card-body">
            <div class="filter-heading">
                <div><strong id="homeroom-filter-title">Tìm kiếm và bộ lọc</strong><small>{{ number_format($teachers->total()) }} giáo viên phù hợp</small></div>
                @if (request()->hasAny(['q', 'academic_year_id']))
                    <a class="btn btn-sm btn-light" href="{{ route('admin.school.teachers.homeroom') }}" aria-label="Đặt lại bộ lọc" title="Đặt lại bộ lọc"><i data-lucide="rotate-ccw"></i><span>Đặt lại</span></a>
                @endif
            </div>
            <form method="GET" action="{{ route('admin.school.teachers.homeroom') }}" class="row g-3 align-items-end">
                <div class="col-md-7 col-xl-5">
                    <label class="form-label" for="homeroom-search">Tìm giáo viên hoặc lớp</label>
                    <div class="filter-search"><i data-lucide="search" aria-hidden="true"></i><input id="homeroom-search" type="search" name="q" value="{{ request('q') }}" class="form-control" placeholder="Mã, họ tên hoặc lớp" autocomplete="off"></div>
                </div>
                <div class="col-md-3 col-xl-3">
                    <label class="form-label" for="homeroom-year">Năm học</label>
                    <select id="homeroom-year" name="academic_year_id" class="form-select">
                        <option value="">Tất cả năm học</option>
                        @foreach ($years as $year)
                            <option value="{{ $year->id }}" @selected(request('academic_year_id') == $year->id)>{{ $year->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2"><button class="btn btn-primary w-100" type="submit"><i data-lucide="search"></i><span>Lọc</span></button></div>
            </form>
        </div>
    </section>

    <section class="card table-card" aria-label="Danh sách giáo viên chủ nhiệm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead><tr><th>Giáo viên</th><th>Lớp chủ nhiệm</th><th>Năm học</th><th>Trạng thái</th><th class="text-end">Chi tiết</th></tr></thead>
                <tbody>
                    @forelse ($teachers as $teacher)
                        @foreach ($teacher->homeroomClasses as $class)
                            <tr>
                                <td><a class="entity-name" href="{{ route('admin.school.teachers.show', $teacher) }}">{{ $teacher->full_name }}</a><span class="entity-meta">{{ $teacher->teacher_code }}</span></td>
                                <td><strong>{{ $class->name }}</strong><span class="entity-meta">{{ $class->grade?->name }} · {{ $class->room ?: 'Chưa có phòng' }}</span></td>
                                <td>{{ $class->academicYear?->name }}</td>
                                <td><x-admin.status-badge :tone="$teacher->status === 'active' ? 'success' : ($teacher->status === 'on_leave' ? 'warning' : 'neutral')">{{ $teacher->statusLabel() }}</x-admin.status-badge></td>
                                <td class="text-end"><a href="{{ route('admin.school.teachers.history', $teacher) }}" data-modal-url="{{ route('admin.school.teachers.history', $teacher) }}" data-modal-readonly="true" data-modal-title="Chi tiết giáo viên chủ nhiệm" class="btn btn-sm btn-light" title="Xem chi tiết" aria-label="Xem chi tiết {{ $teacher->full_name }}"><i data-lucide="eye"></i></a></td>
                            </tr>
                        @endforeach
                    @empty
                        <tr><td colspan="5" class="empty-state"><x-admin.empty-state icon="user-search" title="Không tìm thấy giáo viên chủ nhiệm" /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <x-admin.pagination :paginator="$teachers" />
    </section>
@endsection

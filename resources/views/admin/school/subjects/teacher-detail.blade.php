@extends('admin.layouts.app')

@section('title', 'Chi tiết giáo viên giảng dạy')

@section('content')
    <div class="page-heading">
        <div>
            <p class="eyebrow">{{ $subject->name }}</p>
            <h1>{{ $teacher->full_name }}</h1>
            <p class="text-secondary mb-0">{{ $teacher->teacher_code }} · {{ ['active' => 'Đang công tác', 'on_leave' => 'Tạm nghỉ', 'inactive' => 'Ngừng công tác'][$teacher->status] ?? $teacher->status }}</p>
        </div>
    </div>

    <div class="card table-card mb-4">
        <div class="card-body border-bottom"><h2 class="panel-title mb-0">Phân công môn {{ $subject->name }}</h2></div>
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead><tr><th>Năm học</th><th>Học kỳ</th><th>Lớp giảng dạy</th><th>Trạng thái</th></tr></thead>
                <tbody>@foreach ($assignments as $assignment)<tr><td>{{ $assignment->academicYear?->name }}</td><td>{{ $assignment->semester?->name ?: 'Cả năm' }}</td><td>{{ $assignment->class?->name }}<small class="d-block text-secondary">{{ $assignment->class?->grade?->name }}</small></td><td>{{ $assignment->status === 'active' ? 'Đang phân công' : $assignment->status }}</td></tr>@endforeach</tbody>
            </table>
        </div>
    </div>

    <div class="card table-card">
        <div class="card-body border-bottom"><h2 class="panel-title mb-1">Lớp chủ nhiệm</h2><p class="text-secondary small mb-0">Dấu tích cho biết giáo viên đang là chủ nhiệm của lớp tương ứng.</p></div>
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead><tr><th>Năm học</th><th>Lớp</th><th>Khối</th><th class="text-center">Chủ nhiệm</th></tr></thead>
                <tbody>
                    @forelse ($classes as $class)
                        @php($isHomeroom = $class->homeroom_teacher_id === $teacher->id)
                        <tr><td>{{ $class->academicYear?->name }}</td><td><strong>{{ $class->name }}</strong></td><td>{{ $class->grade?->name }}</td><td class="text-center">@if ($isHomeroom)<span class="text-success" title="Giáo viên chủ nhiệm"><i data-lucide="circle-check-big"></i><span class="visually-hidden">Có</span></span>@else<span class="text-secondary">-</span>@endif</td></tr>
                    @empty
                        <tr><td colspan="4" class="empty-state">Không có lớp trong năm học của phân công này.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection

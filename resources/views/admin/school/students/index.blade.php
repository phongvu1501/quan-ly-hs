@extends('admin.layouts.app')

@section('title', 'Học sinh')

@section('content')
    <div class="page-heading page-heading-inline">
        <div>
            <p class="eyebrow">Hồ sơ học sinh</p>
            <h1>Học sinh</h1>
            <p class="text-secondary mb-0">Lưu hồ sơ xuyên suốt ba năm học và trạng thái hiện tại.</p>
        </div>
        @if (auth()->user()->hasPermission('students.create'))
            <a href="{{ route('admin.school.students.create') }}" class="btn btn-primary" data-modal-title="Thêm học sinh"><i
                    data-lucide="user-plus"></i> Thêm học sinh</a>
        @endif
    </div>
    <div class="card filter-card mb-4">
        <div class="card-body">
            <div class="d-flex align-items-center justify-content-between gap-3 mb-3">
                <div><strong>Bộ lọc học sinh</strong><small class="d-block text-secondary">Lọc theo năm học, lớp và trạng thái hồ sơ.</small></div>
                <a class="btn btn-sm btn-light" href="{{ route('admin.school.students.index') }}" title="Đặt lại bộ lọc"><i data-lucide="rotate-ccw"></i><span class="visually-hidden">Đặt lại</span></a>
            </div>
            <form method="GET" class="row g-3">
                @if (request('q')) <input type="hidden" name="q" value="{{ request('q') }}"> @endif
                <div class="col-md-4 col-xl-3"><label class="form-label" for="student-year">Năm học</label><select id="student-year" name="academic_year_id" class="form-select"><option value="">Tất cả năm học</option>@foreach($years as $year)<option value="{{ $year->id }}" @selected(request('academic_year_id') == $year->id)>{{ $year->name }}</option>@endforeach</select></div>
                <div class="col-md-4 col-xl-3"><label class="form-label" for="student-class">Lớp</label><select id="student-class" name="class_id" class="form-select"><option value="">Tất cả lớp</option>@foreach($classes as $class)<option value="{{ $class->id }}" data-year="{{ $class->academic_year_id }}" @selected(request('class_id') == $class->id)>{{ $class->name }} · {{ $class->academicYear?->name }}</option>@endforeach</select></div>
                <div class="col-md-4 col-xl-3"><label class="form-label" for="student-status">Trạng thái</label><select id="student-status" name="status" class="form-select"><option value="">Tất cả trạng thái</option>@foreach($studentStatuses as $value => $label)<option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>@endforeach</select></div>
                <div class="col-md-4 col-xl-2 d-flex align-items-end"><button class="btn btn-primary w-100" type="submit"><i data-lucide="filter"></i> Lọc</button></div>
            </form>
        </div>
    </div>
    <div class="card table-card"><div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Mã học sinh</th>
                        <th>Họ và tên</th>
                        <th>Lớp hiện tại</th>
                        <th>Ngày sinh</th>
                        <th>Trạng thái</th>
                        <th class="text-end">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($students as $student)
                        @php
                            $enrollment = $student->enrollments->first();
                        @endphp
                        <tr>
                            <td><code>{{ $student->student_code }}</code></td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    @if ($student->avatar_path)
                                        <img src="{{ asset('storage/' . $student->avatar_path) }}" alt=""
                                            class="rounded-circle" style="width:36px;height:36px;object-fit:cover">
                                    @endif
                                    <strong>
                                        {{ $student->full_name }}</strong>
                                </div><small
                                    class="d-block text-secondary">{{ $student->email ?: $student->phone ?: 'Chưa cập nhật liên hệ' }}</small>
                            </td>
                            <td>{{ $enrollment?->class?->name ?: '-' }}<small
                                    class="d-block text-secondary">{{ $enrollment?->class?->grade?->name }}</small></td>
                            <td>{{ $student->date_of_birth?->format('d/m/Y') ?: '-' }}</td>
                            <td><span
                                    class="status-dot {{ $student->status === 'studying' ? 'status-on' : 'status-off' }}">{{ ['studying' => 'Đang đi học', 'sick_leave' => 'Nghỉ ốm dài ngày', 'reserved' => 'Bảo lưu', 'dropped' => 'Bỏ học', 'graduated' => 'Đã tốt nghiệp'][$student->status] ?? $student->status }}</span>
                            </td>
                            <td class="text-end"><a href="{{ route('admin.school.students.payments', $student) }}" data-modal-readonly="true"
                                     class="btn btn-sm btn-light" title="Lịch sử đóng tiền"><i
                                        data-lucide="receipt-text"></i></a>
                                <a href="{{ route('admin.school.students.transcript', $student) }}"
                                    data-modal-readonly="true" class="btn btn-sm btn-light" title="Xem học bạ"><i data-lucide="book-open-check"></i></a>
                                @if (auth()->user()->hasPermission('students.edit'))
                                    <a href="{{ route('admin.school.students.edit', $student) }}"
                                        class="btn btn-sm btn-light" data-modal-title="Sửa học sinh" title="Sửa"><i
                                            data-lucide="pencil"></i></a>
                                    @endif @if (auth()->user()->hasPermission('students.delete'))
                                        <form action="{{ route('admin.school.students.destroy', $student) }}"
                                            method="POST" class="d-inline" data-modal-title="Xóa học sinh"
                                            data-confirm="Hồ sơ có lịch sử học tập sẽ không được xóa. Bạn có chắc muốn tiếp tục?">
                                            @csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger"
                                                title="Xóa"><i data-lucide="trash-2"></i></button></form>
                                    @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="empty-state">Chưa có học sinh.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($students->hasPages())
            <div class="p-3 border-top">{{ $students->links('pagination::bootstrap-5') }}</div>
        @endif
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const year = document.getElementById('student-year');
            const classSelect = document.getElementById('student-class');
            if (!year || !classSelect) return;
            const refresh = () => {
                [...classSelect.options].forEach((option, index) => {
                    if (index === 0) return;
                    option.hidden = Boolean(year.value) && option.dataset.year !== year.value;
                });
                if (classSelect.selectedOptions[0]?.hidden) classSelect.value = '';
            };
            year.addEventListener('change', refresh);
            refresh();
        });
    </script>
@endpush

@extends('admin.layouts.app')

@section('title', 'Học sinh')

@section('content')
    <x-admin.page-header eyebrow="Quản lý hồ sơ" title="Học sinh"
        description="Theo dõi hồ sơ, lớp hiện tại và trạng thái học tập của học sinh.">
        @if (auth()->user()->hasPermission('students.create'))
            <x-slot:actions>
                <a href="{{ route('admin.school.students.create') }}" class="btn btn-primary"
                    data-modal-title="Thêm học sinh">
                    <i data-lucide="user-plus"></i><span>Thêm học sinh</span>
                </a>
            </x-slot:actions>
        @endif
    </x-admin.page-header>

    <section class="card filter-card" aria-labelledby="student-filter-title">
        <div class="card-body">
            <div class="filter-heading">
                <div>
                    <strong id="student-filter-title">Tìm kiếm và bộ lọc</strong>
                    <small>{{ number_format($students->total()) }} hồ sơ phù hợp</small>
                </div>
                @if (request()->hasAny(['q', 'admission_year', 'academic_year_id', 'class_id', 'status']))
                    <a class="btn btn-sm btn-light" href="{{ route('admin.school.students.index') }}"
                        title="Đặt lại bộ lọc" aria-label="Đặt lại bộ lọc">
                        <i data-lucide="rotate-ccw"></i><span>Đặt lại</span>
                    </a>
                @endif
            </div>

            <form method="GET" action="{{ route('admin.school.students.index') }}" class="row g-3">
                <div class="col-12 col-xl-4">
                    <label class="form-label" for="student-search">Tìm học sinh</label>
                    <div class="filter-search">
                        <i data-lucide="search" aria-hidden="true"></i>
                        <input id="student-search" type="search" name="q" class="form-control"
                            value="{{ request('q') }}" placeholder="Mã, họ tên, email hoặc điện thoại"
                            autocomplete="off">
                    </div>
                </div>
                <div class="col-6 col-md-4 col-xl-2">
                    <label class="form-label" for="student-admission-year">Năm tuyển sinh</label>
                    <select id="student-admission-year" name="admission_year" class="form-select">
                        <option value="">Tất cả</option>
                        @foreach ($admissionYears as $value)
                            <option value="{{ $value }}" @selected((string) request('admission_year') === (string) $value)>
                                {{ $value }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-4 col-xl-2">
                    <label class="form-label" for="student-year">Năm học</label>
                    <select id="student-year" name="academic_year_id" class="form-select">
                        <option value="">Tất cả</option>
                        @foreach ($years as $year)
                            <option value="{{ $year->id }}" @selected(request('academic_year_id') == $year->id)>
                                {{ $year->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-4 col-xl-2">
                    <label class="form-label" for="student-class">Lớp</label>
                    <select id="student-class" name="class_id" class="form-select">
                        <option value="">Tất cả</option>
                        @foreach ($classes as $class)
                            <option value="{{ $class->id }}" data-year="{{ $class->academic_year_id }}"
                                @selected(request('class_id') == $class->id)>
                                {{ $class->name }} · {{ $class->academicYear?->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-4 col-xl-2">
                    <label class="form-label" for="student-status">Trạng thái</label>
                    <select id="student-status" name="status" class="form-select">
                        <option value="">Tất cả</option>
                        @foreach ($studentStatuses as $value => $label)
                            <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 d-flex justify-content-end">
                    <button class="btn btn-primary" type="submit">
                        <i data-lucide="search"></i><span>Lọc</span>
                    </button>
                </div>
            </form>
        </div>
    </section>

    <section class="card table-card" aria-label="Danh sách học sinh">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th class="d-none d-xl-table-cell" style="width: 64px">STT</th>
                        <th>Mã học sinh</th>
                        <th>Học sinh</th>
                        <th>Lớp hiện tại</th>
                        <th class="d-none d-lg-table-cell">Ngày sinh</th>
                        <th>Trạng thái</th>
                        <th class="text-end" style="width: 72px">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($students as $student)
                        @php
                            $enrollment = $student->enrollments->first();
                            $statusTone = match ($student->status) {
                                'studying' => 'success',
                                'sick_leave', 'reserved' => 'warning',
                                'graduated' => 'info',
                                default => 'danger',
                            };
                        @endphp
                        <tr>
                            <td class="d-none d-xl-table-cell text-secondary">
                                {{ number_format($students->firstItem() + $loop->index) }}
                            </td>
                            <td><code>{{ $student->student_code }}</code></td>
                            <td>
                                <div class="entity-cell">
                                    <span class="entity-avatar" aria-hidden="true">
                                        @if ($student->avatar_path)
                                            <img src="{{ asset('storage/' . $student->avatar_path) }}" alt="">
                                        @else
                                            {{ mb_strtoupper(mb_substr($student->full_name, 0, 1)) }}
                                        @endif
                                    </span>
                                    <span class="min-w-0">
                                        <a href="{{ route('admin.school.students.show', $student) }}" class="entity-name">
                                            {{ $student->full_name }}
                                        </a>
                                        <span class="entity-meta">
                                            {{ $student->email ?: $student->phone ?: 'Chưa cập nhật liên hệ' }}
                                        </span>
                                    </span>
                                </div>
                            </td>
                            <td>
                                <strong>{{ $enrollment?->class?->name ?: 'Chưa xếp lớp' }}</strong>
                                @if ($enrollment?->class?->grade?->name)
                                    <span class="entity-meta">{{ $enrollment->class->grade->name }}</span>
                                @endif
                            </td>
                            <td class="d-none d-lg-table-cell">
                                {{ $student->date_of_birth?->format('d/m/Y') ?: '-' }}
                            </td>
                            <td><x-admin.status-badge :tone="$statusTone">{{ $student->statusLabel() }}</x-admin.status-badge></td>
                            <td class="text-end">
                                <div class="table-actions">
                                    <div class="dropdown action-menu">
                                        <button class="btn btn-sm btn-light dropdown-toggle" type="button"
                                            data-bs-toggle="dropdown" data-bs-boundary="viewport" aria-expanded="false"
                                            aria-label="Thao tác với {{ $student->full_name }}" title="Thao tác">
                                            <i data-lucide="ellipsis"></i>
                                        </button>
                                        <div class="dropdown-menu dropdown-menu-end">
                                            <a class="dropdown-item" href="{{ route('admin.school.students.show', $student) }}">
                                                <i data-lucide="eye"></i><span>Xem hồ sơ</span>
                                            </a>
                                            <a class="dropdown-item" href="{{ route('admin.school.students.transcript', $student) }}"
                                                data-modal-readonly="true" data-modal-title="Học bạ học sinh">
                                                <i data-lucide="book-open-check"></i><span>Xem học bạ</span>
                                            </a>
                                            @if (auth()->user()->hasPermission('fees.view'))
                                                <a class="dropdown-item" href="{{ route('admin.finance.fees.students.show', $student) }}"
                                                    data-native-navigation="true">
                                                    <i data-lucide="receipt-text"></i><span>Học phí và thanh toán</span>
                                                </a>
                                            @endif
                                            @if (auth()->user()->hasPermission('students.edit'))
                                                <a class="dropdown-item" href="{{ route('admin.school.students.edit', $student) }}"
                                                    data-modal-title="Sửa học sinh">
                                                    <i data-lucide="pencil"></i><span>Sửa hồ sơ</span>
                                                </a>
                                            @endif
                                            @if (auth()->user()->hasPermission('students.delete'))
                                                <div class="dropdown-divider"></div>
                                                @if ($student->can_delete)
                                                    <form action="{{ route('admin.school.students.destroy', $student) }}" method="POST"
                                                        data-modal-title="Xóa học sinh"
                                                        data-confirm="Hồ sơ có lịch sử học tập sẽ không được xóa. Bạn có chắc muốn tiếp tục?">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button class="dropdown-item text-danger" type="submit">
                                                            <i data-lucide="trash-2"></i><span>Xóa học sinh</span>
                                                        </button>
                                                    </form>
                                                @else
                                                    <button class="dropdown-item" type="button" disabled
                                                        title="Hồ sơ đã có dữ liệu liên quan">
                                                        <i data-lucide="shield-ban"></i><span>Không thể xóa</span>
                                                    </button>
                                                @endif
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="empty-state">
                                <x-admin.empty-state icon="user-search" title="Không tìm thấy học sinh"
                                    description="Không có hồ sơ phù hợp với điều kiện hiện tại.">
                                    @if (auth()->user()->hasPermission('students.create'))
                                        <x-slot:actions>
                                            <a href="{{ route('admin.school.students.create') }}" class="btn btn-sm btn-primary"
                                                data-modal-title="Thêm học sinh">
                                                <i data-lucide="user-plus"></i><span>Thêm học sinh</span>
                                            </a>
                                        </x-slot:actions>
                                    @endif
                                </x-admin.empty-state>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <x-admin.pagination :paginator="$students" />
    </section>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const year = document.getElementById('student-year');
            const classSelect = document.getElementById('student-class');
            if (!year || !classSelect) return;

            const refreshClasses = () => {
                Array.from(classSelect.options).forEach((option, index) => {
                    if (index === 0) return;
                    option.hidden = Boolean(year.value) && option.dataset.year !== year.value;
                });
                if (classSelect.selectedOptions[0]?.hidden) classSelect.value = '';
            };

            year.addEventListener('change', refreshClasses);
            refreshClasses();
        });
    </script>
@endpush

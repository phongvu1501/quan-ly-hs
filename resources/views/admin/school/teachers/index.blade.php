@extends('admin.layouts.app')

@section('title', 'Giáo viên')

@section('content')
    <x-admin.page-header eyebrow="Quản lý nhân sự" title="Giáo viên"
        description="Theo dõi hồ sơ, chuyên môn và phân công giảng dạy của giáo viên.">
        @if (auth()->user()->hasPermission('teachers.create'))
            <x-slot:actions>
                <a href="{{ route('admin.school.teachers.create') }}" class="btn btn-primary"
                    data-modal-title="Thêm giáo viên">
                    <i data-lucide="user-plus"></i><span>Thêm giáo viên</span>
                </a>
            </x-slot:actions>
        @endif
    </x-admin.page-header>

    <nav class="view-switcher" aria-label="Chế độ xem giáo viên">
        <a href="{{ route('admin.school.teachers.index') }}" class="active" aria-current="page">
            <i data-lucide="users"></i><span>Tất cả</span>
        </a>
        <a href="{{ route('admin.school.teachers.homeroom') }}">
            <i data-lucide="school"></i><span>Chủ nhiệm</span>
        </a>
        <a href="{{ route('admin.school.teachers.subject') }}">
            <i data-lucide="book-open"></i><span>Bộ môn</span>
        </a>
    </nav>

    <section class="card filter-card" aria-labelledby="teacher-filter-title">
        <div class="card-body">
            <div class="filter-heading">
                <div>
                    <strong id="teacher-filter-title">Tìm kiếm và bộ lọc</strong>
                    <small>{{ number_format($teachers->total()) }} hồ sơ phù hợp</small>
                </div>
                @if (request()->hasAny(['q', 'status', 'type', 'academic_year_id', 'class_id']))
                    <a class="btn btn-sm btn-light" href="{{ route('admin.school.teachers.index') }}"
                        title="Đặt lại bộ lọc" aria-label="Đặt lại bộ lọc">
                        <i data-lucide="rotate-ccw"></i><span>Đặt lại</span>
                    </a>
                @endif
            </div>

            <form method="GET" action="{{ route('admin.school.teachers.index') }}" class="row g-3">
                <div class="col-12 col-xl-4">
                    <label class="form-label" for="teacher-search">Tìm giáo viên</label>
                    <div class="filter-search">
                        <i data-lucide="search" aria-hidden="true"></i>
                        <input id="teacher-search" type="search" name="q" class="form-control"
                            value="{{ request('q') }}" placeholder="Mã, họ tên, email hoặc chuyên môn"
                            autocomplete="off">
                    </div>
                </div>
                <div class="col-6 col-md-4 col-xl-2">
                    <label class="form-label" for="teacher-status">Trạng thái</label>
                    <select id="teacher-status" name="status" class="form-select">
                        <option value="">Tất cả</option>
                        @foreach ($teacherStatuses as $value => $label)
                            <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-4 col-xl-2">
                    <label class="form-label" for="teacher-type">Loại phân công</label>
                    <select id="teacher-type" name="type" class="form-select">
                        <option value="">Tất cả</option>
                        <option value="homeroom" @selected(request('type') === 'homeroom')>Chủ nhiệm</option>
                        <option value="subject" @selected(request('type') === 'subject')>Bộ môn</option>
                    </select>
                </div>
                <div class="col-6 col-md-4 col-xl-2">
                    <label class="form-label" for="teacher-year">Năm học</label>
                    <select id="teacher-year" name="academic_year_id" class="form-select">
                        <option value="">Tất cả</option>
                        @foreach ($years as $year)
                            <option value="{{ $year->id }}" @selected(request('academic_year_id') == $year->id)>{{ $year->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-4 col-xl-2">
                    <label class="form-label" for="teacher-class">Lớp</label>
                    <select id="teacher-class" name="class_id" class="form-select">
                        <option value="">Tất cả</option>
                        @foreach ($classes as $class)
                            <option value="{{ $class->id }}" data-year="{{ $class->academic_year_id }}"
                                @selected(request('class_id') == $class->id)>
                                {{ $class->name }} · {{ $class->academicYear?->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 d-flex justify-content-end">
                    <button class="btn btn-primary" type="submit">
                        <i data-lucide="search"></i><span>Áp dụng</span>
                    </button>
                </div>
            </form>
        </div>
    </section>

    <section class="card table-card" aria-label="Danh sách giáo viên">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th class="d-none d-xl-table-cell" style="width: 64px">STT</th>
                        <th>Mã giáo viên</th>
                        <th>Giáo viên</th>
                        <th>Chuyên môn</th>
                        <th class="d-none d-lg-table-cell">Phân công</th>
                        <th>Trạng thái</th>
                        <th class="text-end" style="width: 72px">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($teachers as $teacher)
                        @php
                            $statusTone = match ($teacher->status) {
                                'active' => 'success',
                                'on_leave' => 'warning',
                                default => 'neutral',
                            };
                        @endphp
                        <tr>
                            <td class="d-none d-xl-table-cell text-secondary">
                                {{ number_format($teachers->firstItem() + $loop->index) }}
                            </td>
                            <td><code>{{ $teacher->teacher_code }}</code></td>
                            <td>
                                <div class="entity-cell">
                                    <span class="entity-avatar" aria-hidden="true">
                                        @if ($teacher->avatar_path)
                                            <img src="{{ asset('storage/' . $teacher->avatar_path) }}" alt="">
                                        @else
                                            {{ mb_strtoupper(mb_substr($teacher->full_name, 0, 1)) }}
                                        @endif
                                    </span>
                                    <span class="min-w-0">
                                        <a href="{{ route('admin.school.teachers.show', $teacher) }}" class="entity-name">
                                            {{ $teacher->full_name }}
                                        </a>
                                        <span class="entity-meta">{{ $teacher->email ?: $teacher->phone ?: 'Chưa cập nhật liên hệ' }}</span>
                                    </span>
                                </div>
                            </td>
                            <td>{{ $teacher->specialization ?: 'Chưa cập nhật' }}</td>
                            <td class="d-none d-lg-table-cell">
                                <strong>{{ number_format($teacher->assignments_count) }}</strong> bộ môn
                                <span class="entity-meta">{{ number_format($teacher->homeroom_classes_count) }} lớp chủ nhiệm</span>
                            </td>
                            <td><x-admin.status-badge :tone="$statusTone">{{ $teacher->statusLabel() }}</x-admin.status-badge></td>
                            <td class="text-end">
                                <div class="table-actions">
                                    <div class="dropdown action-menu">
                                        <button class="btn btn-sm btn-light dropdown-toggle" type="button"
                                            data-bs-toggle="dropdown" data-bs-boundary="viewport" aria-expanded="false"
                                            aria-label="Thao tác với {{ $teacher->full_name }}" title="Thao tác">
                                            <i data-lucide="ellipsis"></i>
                                        </button>
                                        <div class="dropdown-menu dropdown-menu-end">
                                            <a class="dropdown-item" href="{{ route('admin.school.teachers.show', $teacher) }}">
                                                <i data-lucide="eye"></i><span>Xem hồ sơ</span>
                                            </a>
                                            <a class="dropdown-item" href="{{ route('admin.school.teachers.history', $teacher) }}"
                                                data-modal-url="{{ route('admin.school.teachers.history', $teacher) }}"
                                                data-modal-readonly="true" data-modal-title="Lịch sử công tác">
                                                <i data-lucide="history"></i><span>Lịch sử công tác</span>
                                            </a>
                                            @if (auth()->user()->hasPermission('teachers.edit'))
                                                <a class="dropdown-item" href="{{ route('admin.school.teachers.edit', $teacher) }}"
                                                    data-modal-title="Sửa giáo viên">
                                                    <i data-lucide="pencil"></i><span>Sửa hồ sơ</span>
                                                </a>
                                            @endif
                                            @if (auth()->user()->hasPermission('teachers.delete'))
                                                <div class="dropdown-divider"></div>
                                                @if ($teacher->can_delete)
                                                    <form action="{{ route('admin.school.teachers.destroy', $teacher) }}" method="POST"
                                                        data-modal-title="Xóa giáo viên"
                                                        data-confirm="Giáo viên đang có phân công sẽ không thể xóa. Tiếp tục?">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button class="dropdown-item text-danger" type="submit">
                                                            <i data-lucide="trash-2"></i><span>Xóa giáo viên</span>
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
                                <x-admin.empty-state icon="user-search" title="Không tìm thấy giáo viên"
                                    description="Không có hồ sơ phù hợp với điều kiện hiện tại.">
                                    @if (auth()->user()->hasPermission('teachers.create'))
                                        <x-slot:actions>
                                            <a href="{{ route('admin.school.teachers.create') }}" class="btn btn-sm btn-primary"
                                                data-modal-title="Thêm giáo viên">
                                                <i data-lucide="user-plus"></i><span>Thêm giáo viên</span>
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
        <x-admin.pagination :paginator="$teachers" />
    </section>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const year = document.getElementById('teacher-year');
            const classSelect = document.getElementById('teacher-class');
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

@extends('admin.layouts.app')

@php($isEditing = isset($teacher))
@php($selectedSubjects = old('subjects', $teacher?->subjects?->pluck('id')->all() ?? []))
@php($selectedClasses = old('homeroom_class_ids', $teacher?->homeroomClasses?->pluck('id')->all() ?? []))
@php($selectedYear = old('academic_year_id', $teacher?->homeroomClasses?->first()?->academic_year_id ?: $years->first()?->id))

@section('title', $isEditing ? 'Sửa giáo viên' : 'Thêm giáo viên')

@section('content')
    <x-admin.page-header eyebrow="Hồ sơ nhân sự" :title="$isEditing ? 'Cập nhật giáo viên' : 'Thêm giáo viên mới'"
        description="Thông tin hồ sơ, chuyên môn và phân công chủ nhiệm.">
        <x-slot:actions>
            <a href="{{ route('admin.school.teachers.index') }}" class="btn btn-light">
                <i data-lucide="arrow-left"></i><span>Danh sách</span>
            </a>
        </x-slot:actions>
    </x-admin.page-header>

    <form class="form-shell" action="{{ $isEditing ? route('admin.school.teachers.update', $teacher) : route('admin.school.teachers.store') }}"
        method="POST" enctype="multipart/form-data" novalidate>
        @csrf
        @if ($isEditing)
            @method('PUT')
        @endif

        <div class="card form-card">
            <div class="card-body">
                <x-admin.form-section title="Thông tin cơ bản"
                    description="Thông tin định danh và trạng thái nhân sự.">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label" for="teacher-avatar">Ảnh giáo viên</label>
                            <div class="profile-summary">
                                <span class="profile-avatar" aria-hidden="true">
                                    @if ($teacher?->avatar_path)
                                        <img src="{{ asset('storage/' . $teacher->avatar_path) }}" alt="">
                                    @else
                                        <i data-lucide="user-round"></i>
                                    @endif
                                </span>
                                <div class="flex-grow-1">
                                    <input id="teacher-avatar" type="file" name="avatar"
                                        class="form-control @error('avatar') is-invalid @enderror"
                                        accept="image/jpeg,image/png,image/webp" @error('avatar') aria-invalid="true" @enderror>
                                    @error('avatar')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @else
                                        <div class="form-text">JPG, PNG hoặc WebP, tối đa 2MB.</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="teacher-code">Mã giáo viên</label>
                            <input id="teacher-code" class="form-control" value="{{ $teacher?->teacher_code }}"
                                placeholder="Tự động sau khi lưu" readonly>
                            <div class="form-text">Định dạng GV + 6 chữ số.</div>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label" for="teacher-full-name">Họ và tên <span class="required-mark">*</span></label>
                            <input id="teacher-full-name" name="full_name"
                                class="form-control @error('full_name') is-invalid @enderror"
                                value="{{ old('full_name', $teacher?->full_name) }}" required autocomplete="name"
                                @error('full_name') aria-invalid="true" @enderror>
                            @error('full_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="teacher-date-of-birth">Ngày sinh</label>
                            <input id="teacher-date-of-birth" type="date" name="date_of_birth"
                                class="form-control @error('date_of_birth') is-invalid @enderror"
                                value="{{ old('date_of_birth', $teacher?->date_of_birth?->format('Y-m-d')) }}"
                                @error('date_of_birth') aria-invalid="true" @enderror>
                            @error('date_of_birth')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="teacher-gender">Giới tính</label>
                            <select id="teacher-gender" name="gender"
                                class="form-select @error('gender') is-invalid @enderror"
                                @error('gender') aria-invalid="true" @enderror>
                                <option value="">Chưa chọn</option>
                                <option value="male" @selected(old('gender', $teacher?->gender) === 'male')>Nam</option>
                                <option value="female" @selected(old('gender', $teacher?->gender) === 'female')>Nữ</option>
                                <option value="other" @selected(old('gender', $teacher?->gender) === 'other')>Khác</option>
                            </select>
                            @error('gender')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="teacher-status">Trạng thái <span class="required-mark">*</span></label>
                            <select id="teacher-status" name="status"
                                class="form-select @error('status') is-invalid @enderror" required
                                @error('status') aria-invalid="true" @enderror>
                                @foreach (\App\Models\Teacher::STATUS_LABELS as $value => $label)
                                    <option value="{{ $value }}" @selected(old('status', $teacher?->status ?: 'active') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </x-admin.form-section>

                <x-admin.form-section title="Liên hệ và công tác"
                    description="Tài khoản đăng nhập và thông tin chuyên môn.">
                    <div class="row g-3">
                        <div class="col-md-7">
                            <label class="form-label" for="teacher-email">
                                Email / tài khoản đăng nhập @unless ($isEditing)<span class="required-mark">*</span>@endunless
                            </label>
                            <input id="teacher-email" type="email" name="email"
                                class="form-control @error('email') is-invalid @enderror"
                                value="{{ old('email', $teacher?->email) }}" {{ $isEditing ? '' : 'required' }}
                                autocomplete="email" @error('email') aria-invalid="true" @enderror>
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @else
                                <div class="form-text">
                                    {{ $isEditing ? 'Email hồ sơ giáo viên.' : 'Tài khoản mới sử dụng mật khẩu mặc định theo cấu hình hiện tại.' }}
                                </div>
                            @enderror
                        </div>
                        <div class="col-md-5">
                            <label class="form-label" for="teacher-phone">Điện thoại</label>
                            <input id="teacher-phone" type="tel" inputmode="tel" name="phone"
                                class="form-control @error('phone') is-invalid @enderror"
                                value="{{ old('phone', $teacher?->phone) }}" maxlength="30" autocomplete="tel"
                                @error('phone') aria-invalid="true" @enderror>
                            @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-7">
                            <label class="form-label" for="teacher-specialization">Chuyên môn</label>
                            <input id="teacher-specialization" name="specialization"
                                class="form-control @error('specialization') is-invalid @enderror"
                                value="{{ old('specialization', $teacher?->specialization) }}"
                                placeholder="Ví dụ: Toán, Vật lý"
                                @error('specialization') aria-invalid="true" @enderror>
                            @error('specialization')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-5">
                            <label class="form-label" for="teacher-hire-date">Ngày vào trường</label>
                            <input id="teacher-hire-date" type="date" name="hire_date"
                                class="form-control @error('hire_date') is-invalid @enderror"
                                value="{{ old('hire_date', $teacher?->hire_date?->format('Y-m-d')) }}"
                                @error('hire_date') aria-invalid="true" @enderror>
                            @error('hire_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="teacher-address">Địa chỉ</label>
                            <input id="teacher-address" name="address"
                                class="form-control @error('address') is-invalid @enderror"
                                value="{{ old('address', $teacher?->address) }}" autocomplete="street-address"
                                @error('address') aria-invalid="true" @enderror>
                            @error('address')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </x-admin.form-section>

                <x-admin.form-section title="Môn giảng dạy"
                    description="Năng lực môn học từ danh mục đang hoạt động.">
                    <div class="row g-2">
                        @forelse ($subjects as $subject)
                            <div class="col-md-6 col-lg-4">
                                <label class="check-tile">
                                    <input type="checkbox" name="subjects[]" value="{{ $subject->id }}"
                                        @checked(in_array($subject->id, $selectedSubjects))>
                                    <span>
                                        <strong>{{ $subject->name }}</strong>
                                        <small>{{ $subject->code }}</small>
                                    </span>
                                </label>
                            </div>
                        @empty
                            <x-admin.empty-state icon="book-open" title="Chưa có môn học" />
                        @endforelse
                    </div>
                    @error('subjects')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    @error('subjects.*')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </x-admin.form-section>

                <x-admin.form-section title="Phân công chủ nhiệm"
                    description="Mỗi giáo viên được chọn tối đa một lớp chủ nhiệm.">
                    <div class="form-check mb-3">
                        <input id="is-homeroom-teacher" type="checkbox" name="is_homeroom_teacher" value="1"
                            class="form-check-input @error('is_homeroom_teacher') is-invalid @enderror"
                            @checked(count($selectedClasses) > 0)>
                        <label class="form-check-label" for="is-homeroom-teacher">Giáo viên chủ nhiệm</label>
                        @error('is_homeroom_teacher')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div id="homeroom-fields">
                        <label class="form-label" for="teacher-academic-year">Năm học</label>
                        <select id="teacher-academic-year" name="academic_year_id"
                            class="form-select mb-3 @error('academic_year_id') is-invalid @enderror">
                            <option value="">Chọn năm học</option>
                            @foreach ($years as $year)
                                <option value="{{ $year->id }}" @selected($selectedYear == $year->id)>{{ $year->name }}</option>
                            @endforeach
                        </select>
                        @error('academic_year_id')<div class="invalid-feedback">{{ $message }}</div>@enderror

                        <div class="row g-2">
                            @forelse ($classes as $class)
                                <div class="col-md-6 homeroom-class-option" data-year="{{ $class->academic_year_id }}">
                                    <label class="check-tile">
                                        <input type="radio" name="homeroom_class_ids[]" value="{{ $class->id }}"
                                            @checked(in_array($class->id, $selectedClasses))>
                                        <span>
                                            <strong>{{ $class->name }}</strong>
                                            <small>{{ $class->grade?->name }} · {{ $class->academicYear?->name }} · {{ $class->room ?: 'Chưa có phòng' }}</small>
                                        </span>
                                    </label>
                                </div>
                            @empty
                                <x-admin.empty-state icon="school" title="Chưa có lớp học" />
                            @endforelse
                        </div>
                        @error('homeroom_class_ids')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        @error('homeroom_class_ids.*')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                </x-admin.form-section>

                <x-admin.form-section title="Ghi chú" description="Thông tin nội bộ bổ sung cho hồ sơ.">
                    <label class="form-label visually-hidden" for="teacher-notes">Ghi chú</label>
                    <textarea id="teacher-notes" name="notes" rows="4"
                        class="form-control @error('notes') is-invalid @enderror"
                        @error('notes') aria-invalid="true" @enderror>{{ old('notes', $teacher?->notes) }}</textarea>
                    @error('notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </x-admin.form-section>

                <div class="form-actions">
                    <a href="{{ route('admin.school.teachers.index') }}" class="btn btn-light">Hủy</a>
                    <button class="btn btn-primary" type="submit">
                        <i data-lucide="save"></i><span>Lưu giáo viên</span>
                    </button>
                </div>
            </div>
        </div>
    </form>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const homeroomToggle = document.getElementById('is-homeroom-teacher');
            const homeroomFields = document.getElementById('homeroom-fields');
            const academicYear = document.getElementById('teacher-academic-year');
            const classOptions = Array.from(document.querySelectorAll('.homeroom-class-option'));

            const syncHomeroom = () => {
                const enabled = Boolean(homeroomToggle?.checked);
                homeroomFields?.classList.toggle('d-none', !enabled);
                homeroomFields?.querySelectorAll('select, input').forEach((control) => {
                    control.disabled = !enabled;
                });
            };

            const syncClasses = () => {
                const yearId = academicYear?.value || '';
                classOptions.forEach((option) => {
                    option.hidden = Boolean(yearId) && option.dataset.year !== yearId;
                    const input = option.querySelector('input');
                    if (option.hidden && input?.checked) input.checked = false;
                });
            };

            homeroomToggle?.addEventListener('change', syncHomeroom);
            academicYear?.addEventListener('change', syncClasses);
            syncHomeroom();
            syncClasses();
        });
    </script>
@endpush

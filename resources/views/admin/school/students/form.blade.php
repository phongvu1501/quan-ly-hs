@extends('admin.layouts.app')

@php($isEditing = isset($student))
@php($enrollment = $student?->enrollments?->first())
@php($guardianByRelationship = $student?->guardians?->keyBy('pivot.relationship') ?? collect())

@section('title', $isEditing ? 'Sửa học sinh' : 'Thêm học sinh')

@section('content')
    <x-admin.page-header eyebrow="Hồ sơ học sinh" :title="$isEditing ? 'Cập nhật học sinh' : 'Thêm học sinh mới'"
        description="Thông tin hồ sơ, xếp lớp và liên hệ phụ huynh.">
        <x-slot:actions>
            <a href="{{ route('admin.school.students.index') }}" class="btn btn-light">
                <i data-lucide="arrow-left"></i><span>Danh sách</span>
            </a>
        </x-slot:actions>
    </x-admin.page-header>

    <form class="form-shell" action="{{ $isEditing ? route('admin.school.students.update', $student) : route('admin.school.students.store') }}"
        method="POST" enctype="multipart/form-data" novalidate>
        @csrf
        @if ($isEditing)
            @method('PUT')
        @endif

        <div class="card form-card">
            <div class="card-body">
                <x-admin.form-section title="Thông tin cơ bản"
                    description="Thông tin định danh và trạng thái hồ sơ.">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label" for="student-avatar">Ảnh học sinh</label>
                            <div class="profile-summary">
                                <span class="profile-avatar" aria-hidden="true">
                                    @if ($student?->avatar_path)
                                        <img src="{{ asset('storage/' . $student->avatar_path) }}" alt="">
                                    @else
                                        <i data-lucide="user-round"></i>
                                    @endif
                                </span>
                                <div class="flex-grow-1">
                                    <input id="student-avatar" type="file" name="avatar"
                                        class="form-control @error('avatar') is-invalid @enderror" accept="image/jpeg,image/png,image/webp"
                                        @error('avatar') aria-invalid="true" @enderror>
                                    @error('avatar')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @else
                                        <div class="form-text">JPG, PNG hoặc WebP, tối đa 2MB.</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label" for="student-code">Mã học sinh</label>
                            <input id="student-code" class="form-control" value="{{ $student?->student_code }}"
                                placeholder="Tự động sau khi lưu" readonly>
                            <div class="form-text">Định dạng HS + 6 chữ số.</div>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label" for="student-full-name">Họ và tên <span class="required-mark">*</span></label>
                            <input id="student-full-name" name="full_name"
                                class="form-control @error('full_name') is-invalid @enderror"
                                value="{{ old('full_name', $student?->full_name) }}" required autocomplete="name"
                                @error('full_name') aria-invalid="true" aria-describedby="student-full-name-error" @enderror>
                            @error('full_name')
                                <div id="student-full-name-error" class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="student-date-of-birth">Ngày sinh</label>
                            <input id="student-date-of-birth" type="date" name="date_of_birth"
                                class="form-control @error('date_of_birth') is-invalid @enderror"
                                value="{{ old('date_of_birth', $student?->date_of_birth?->format('Y-m-d')) }}"
                                @error('date_of_birth') aria-invalid="true" @enderror>
                            @error('date_of_birth')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="student-gender">Giới tính</label>
                            <select id="student-gender" name="gender"
                                class="form-select @error('gender') is-invalid @enderror"
                                @error('gender') aria-invalid="true" @enderror>
                                <option value="">Chưa chọn</option>
                                @foreach (['male' => 'Nam', 'female' => 'Nữ', 'other' => 'Khác'] as $value => $label)
                                    <option value="{{ $value }}" @selected(old('gender', $student?->gender) === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('gender')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="student-status">Trạng thái <span class="required-mark">*</span></label>
                            <select id="student-status" name="status"
                                class="form-select @error('status') is-invalid @enderror" required
                                @error('status') aria-invalid="true" @enderror>
                                @foreach (\App\Models\Student::STATUS_LABELS as $value => $label)
                                    <option value="{{ $value }}" @selected(old('status', $student?->status ?: 'studying') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </x-admin.form-section>

                <x-admin.form-section title="Liên hệ và tuyển sinh"
                    description="Tài khoản đăng nhập, địa chỉ và năm tuyển sinh.">
                    <div class="row g-3">
                        <div class="col-md-7">
                            <label class="form-label" for="student-email">
                                Email / tài khoản đăng nhập @unless ($isEditing)<span class="required-mark">*</span>@endunless
                            </label>
                            <input id="student-email" type="email" name="email"
                                class="form-control @error('email') is-invalid @enderror"
                                value="{{ old('email', $student?->email) }}" {{ $isEditing ? '' : 'required' }}
                                autocomplete="email" @error('email') aria-invalid="true" @enderror>
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @else
                                <div class="form-text">
                                    {{ $isEditing ? 'Email hồ sơ học sinh.' : 'Tài khoản mới sử dụng mật khẩu mặc định theo cấu hình hiện tại.' }}
                                </div>
                            @enderror
                        </div>
                        <div class="col-md-5">
                            <label class="form-label" for="student-phone">Điện thoại</label>
                            <input id="student-phone" type="tel" inputmode="tel" name="phone"
                                class="form-control @error('phone') is-invalid @enderror"
                                value="{{ old('phone', $student?->phone) }}" maxlength="30" autocomplete="tel"
                                @error('phone') aria-invalid="true" @enderror>
                            @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="student-birth-place">Nơi sinh</label>
                            <input id="student-birth-place" name="birth_place"
                                class="form-control @error('birth_place') is-invalid @enderror"
                                value="{{ old('birth_place', $student?->birth_place) }}"
                                @error('birth_place') aria-invalid="true" @enderror>
                            @error('birth_place')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="student-admission-year">Năm tuyển sinh</label>
                            <input id="student-admission-year" name="admission_year"
                                class="form-control @error('admission_year') is-invalid @enderror"
                                value="{{ old('admission_year', $student?->admission_year) }}" placeholder="2026-2027"
                                inputmode="numeric" @error('admission_year') aria-invalid="true" @enderror>
                            @error('admission_year')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="student-address">Địa chỉ</label>
                            <input id="student-address" name="address"
                                class="form-control @error('address') is-invalid @enderror"
                                value="{{ old('address', $student?->address) }}" autocomplete="street-address"
                                @error('address') aria-invalid="true" @enderror>
                            @error('address')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </x-admin.form-section>

                <x-admin.form-section title="Xếp lớp"
                    description="Lớp hiện tại theo năm học đang áp dụng.">
                    <div class="row g-3">
                        <div class="col-md-5">
                            <label class="form-label" for="student-academic-year">Năm học</label>
                            <select id="student-academic-year" name="academic_year_id"
                                class="form-select @error('academic_year_id') is-invalid @enderror"
                                @error('academic_year_id') aria-invalid="true" @enderror>
                                <option value="">Chưa xếp lớp</option>
                                @foreach ($years as $year)
                                    <option value="{{ $year->id }}" @selected(old('academic_year_id', $enrollment?->academic_year_id) == $year->id)>
                                        {{ $year->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('academic_year_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-7">
                            <label class="form-label" for="student-class">Lớp hiện tại</label>
                            <select id="student-class" name="class_id"
                                class="form-select @error('class_id') is-invalid @enderror"
                                @error('class_id') aria-invalid="true" @enderror>
                                <option value="">Chưa xếp lớp</option>
                                @foreach ($classes as $class)
                                    <option value="{{ $class->id }}" data-year="{{ $class->academic_year_id }}"
                                        @selected(old('class_id', $enrollment?->class_id) == $class->id)>
                                        {{ $class->name }} · {{ $class->grade?->name }} · {{ $class->academicYear?->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('class_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </x-admin.form-section>

                <x-admin.form-section title="Thông tin phụ huynh"
                    description="Thông tin liên hệ của bố và mẹ học sinh.">
                    <div class="row g-4">
                        @foreach (['father' => 'Thông tin bố', 'mother' => 'Thông tin mẹ'] as $relationship => $label)
                            @php($guardian = $guardianByRelationship->get($relationship))
                            <div class="col-xl-6">
                                <div class="permission-section h-100 mt-0">
                                    <h3 class="panel-title mb-3">{{ $label }}</h3>
                                    <div class="row g-3">
                                        <div class="col-12">
                                            <label class="form-label" for="guardian-{{ $relationship }}-name">Họ và tên</label>
                                            <input id="guardian-{{ $relationship }}-name"
                                                name="guardians[{{ $relationship }}][full_name]"
                                                class="form-control @error("guardians.$relationship.full_name") is-invalid @enderror"
                                                value="{{ old("guardians.$relationship.full_name", $guardian?->full_name) }}">
                                            @error("guardians.$relationship.full_name")<div class="invalid-feedback">{{ $message }}</div>@enderror
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label" for="guardian-{{ $relationship }}-birth">Ngày sinh</label>
                                            <input id="guardian-{{ $relationship }}-birth" type="date"
                                                name="guardians[{{ $relationship }}][date_of_birth]"
                                                class="form-control @error("guardians.$relationship.date_of_birth") is-invalid @enderror"
                                                value="{{ old("guardians.$relationship.date_of_birth", $guardian?->date_of_birth?->format('Y-m-d')) }}">
                                            @error("guardians.$relationship.date_of_birth")<div class="invalid-feedback">{{ $message }}</div>@enderror
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label" for="guardian-{{ $relationship }}-phone">Điện thoại</label>
                                            <input id="guardian-{{ $relationship }}-phone" type="tel" inputmode="tel"
                                                name="guardians[{{ $relationship }}][phone]" maxlength="30"
                                                class="form-control @error("guardians.$relationship.phone") is-invalid @enderror"
                                                value="{{ old("guardians.$relationship.phone", $guardian?->phone) }}">
                                            @error("guardians.$relationship.phone")<div class="invalid-feedback">{{ $message }}</div>@enderror
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label" for="guardian-{{ $relationship }}-occupation">Nghề nghiệp</label>
                                            <input id="guardian-{{ $relationship }}-occupation"
                                                name="guardians[{{ $relationship }}][occupation]"
                                                class="form-control @error("guardians.$relationship.occupation") is-invalid @enderror"
                                                value="{{ old("guardians.$relationship.occupation", $guardian?->occupation) }}">
                                            @error("guardians.$relationship.occupation")<div class="invalid-feedback">{{ $message }}</div>@enderror
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label" for="guardian-{{ $relationship }}-religion">Tôn giáo</label>
                                            <input id="guardian-{{ $relationship }}-religion"
                                                name="guardians[{{ $relationship }}][religion]"
                                                class="form-control @error("guardians.$relationship.religion") is-invalid @enderror"
                                                value="{{ old("guardians.$relationship.religion", $guardian?->religion) }}">
                                            @error("guardians.$relationship.religion")<div class="invalid-feedback">{{ $message }}</div>@enderror
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label" for="guardian-{{ $relationship }}-workplace">Nơi làm việc</label>
                                            <input id="guardian-{{ $relationship }}-workplace"
                                                name="guardians[{{ $relationship }}][workplace]"
                                                class="form-control @error("guardians.$relationship.workplace") is-invalid @enderror"
                                                value="{{ old("guardians.$relationship.workplace", $guardian?->workplace) }}">
                                            @error("guardians.$relationship.workplace")<div class="invalid-feedback">{{ $message }}</div>@enderror
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label" for="guardian-{{ $relationship }}-email">Email</label>
                                            <input id="guardian-{{ $relationship }}-email" type="email"
                                                name="guardians[{{ $relationship }}][email]"
                                                class="form-control @error("guardians.$relationship.email") is-invalid @enderror"
                                                value="{{ old("guardians.$relationship.email", $guardian?->email) }}">
                                            @error("guardians.$relationship.email")<div class="invalid-feedback">{{ $message }}</div>@enderror
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label" for="guardian-{{ $relationship }}-address">Địa chỉ</label>
                                            <input id="guardian-{{ $relationship }}-address"
                                                name="guardians[{{ $relationship }}][address]"
                                                class="form-control @error("guardians.$relationship.address") is-invalid @enderror"
                                                value="{{ old("guardians.$relationship.address", $guardian?->address) }}">
                                            @error("guardians.$relationship.address")<div class="invalid-feedback">{{ $message }}</div>@enderror
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </x-admin.form-section>

                <x-admin.form-section title="Ghi chú" description="Thông tin nội bộ bổ sung cho hồ sơ.">
                    <label class="form-label visually-hidden" for="student-notes">Ghi chú</label>
                    <textarea id="student-notes" name="notes" rows="4"
                        class="form-control @error('notes') is-invalid @enderror"
                        @error('notes') aria-invalid="true" @enderror>{{ old('notes', $student?->notes) }}</textarea>
                    @error('notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </x-admin.form-section>

                <div class="form-actions">
                    <a href="{{ route('admin.school.students.index') }}" class="btn btn-light">Hủy</a>
                    <button class="btn btn-primary" type="submit">
                        <i data-lucide="save"></i><span>Lưu học sinh</span>
                    </button>
                </div>
            </div>
        </div>
    </form>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const status = document.getElementById('student-status');
            const year = document.getElementById('student-academic-year');
            const classSelect = document.getElementById('student-class');

            const syncEnrollment = () => {
                const canEnroll = status?.value === 'studying';
                [year, classSelect].forEach((select) => {
                    if (!select) return;
                    select.disabled = !canEnroll;
                    if (!canEnroll) select.value = '';
                });
            };

            const syncClasses = () => {
                if (!year || !classSelect) return;
                Array.from(classSelect.options).forEach((option, index) => {
                    if (index === 0) return;
                    option.hidden = Boolean(year.value) && option.dataset.year !== year.value;
                });
                if (classSelect.selectedOptions[0]?.hidden) classSelect.value = '';
            };

            status?.addEventListener('change', syncEnrollment);
            year?.addEventListener('change', syncClasses);
            syncEnrollment();
            syncClasses();
        });
    </script>
@endpush

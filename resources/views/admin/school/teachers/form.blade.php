@extends('admin.layouts.app')

@php($isEditing = isset($teacher))
@php($selectedSubjects = old('subjects', $teacher?->subjects?->pluck('id')->all() ?? []))
@php($selectedClasses = old('homeroom_class_ids', $teacher?->homeroomClasses?->pluck('id')->all() ?? []))
@php($selectedYear = old('academic_year_id', $teacher?->homeroomClasses?->first()?->academic_year_id ?: $years->first()?->id))

@section('title', $isEditing ? 'Sửa giáo viên' : 'Thêm giáo viên')

@section('content')
    <div class="page-heading">
        <div>
            <p class="eyebrow">Nhân sự giảng dạy</p>
            <h1>{{ $isEditing ? 'Sửa giáo viên' : 'Thêm giáo viên' }}</h1>
            <p class="text-secondary mb-0">Môn giảng dạy lấy từ danh mục môn học. Lớp chủ nhiệm lấy từ danh sách lớp theo năm
                học.</p>
        </div>
    </div>
    <form action="{{ $isEditing ? route('admin.school.teachers.update', $teacher) : route('admin.school.teachers.store') }}"
        method="POST" enctype="multipart/form-data">
        @csrf @if ($isEditing)
            @method('PUT')
        @endif
        <div class="card form-card">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-12"><label class="form-label" for="avatar">Ảnh giáo viên</label>
                        @if ($teacher?->avatar_path)
                            <div class="mb-2"><img src="{{ asset('storage/' . $teacher->avatar_path) }}"
                                    alt="Ảnh {{ $teacher->full_name }}" class="rounded-circle"
                                    style="width:72px;height:72px;object-fit:cover"></div>
                        @endif
                        <input id="avatar" type="file" name="avatar" class="form-control" accept="image/*">
                        <div class="form-text">JPG, PNG hoặc WebP, tối đa 2MB.</div>
                    </div>
                    <div class="col-md-4"><label class="form-label">Mã giáo viên</label><input
                            class="form-control" value="{{ $teacher?->teacher_code }}"
                            placeholder="Tự động tạo sau khi lưu" readonly>
                        <div class="form-text">Định dạng tự động: GV + 6 chữ số.</div>
                    </div>
                    <div class="col-md-8"><label class="form-label">Họ và tên</label><input name="full_name"
                            class="form-control" value="{{ old('full_name', $teacher?->full_name) }}" required></div>
                    <div class="col-md-4"><label class="form-label">Ngày sinh</label><input type="date"
                            name="date_of_birth" class="form-control"
                            value="{{ old('date_of_birth', $teacher?->date_of_birth?->format('Y-m-d')) }}"></div>
                    <div class="col-md-4"><label class="form-label">Giới tính</label><select name="gender"
                            class="form-select">
                            <option value="">Chưa chọn</option>
                            <option value="male" @selected(old('gender', $teacher?->gender) === 'male')>Nam</option>
                            <option value="female" @selected(old('gender', $teacher?->gender) === 'female')>Nữ</option>
                            <option value="other" @selected(old('gender', $teacher?->gender) === 'other')>Khác</option>
                        </select></div>
                    <div class="col-md-4"><label class="form-label">Trạng thái</label><select name="status"
                            class="form-select" required>
                            @foreach (['active' => 'Đang công tác', 'on_leave' => 'Tạm nghỉ', 'inactive' => 'Ngừng công tác'] as $value => $label)
                                <option value="{{ $value }}" @selected(old('status', $teacher?->status ?: 'active') === $value)>{{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6"><label class="form-label">Email / tài khoản đăng nhập
                            {{ $isEditing ? '' : '*' }}</label><input type="email" name="email" class="form-control"
                            value="{{ old('email', $teacher?->email) }}" {{ $isEditing ? '' : 'required' }}>
                        <div class="form-text">
                            {{ $isEditing ? 'Email hồ sơ giáo viên.' : 'Hệ thống sẽ tạo tài khoản với email này, mật khẩu mặc định 123456 và bắt buộc đổi khi đăng nhập lần đầu.' }}
                        </div>
                    </div>
                    <div class="col-md-6"><label class="form-label">Điện thoại</label><input name="phone"
                            class="form-control" value="{{ old('phone', $teacher?->phone) }}" type="number" maxlength="10"></div>
                    <div class="col-md-6"><label class="form-label">Chuyên môn</label><input name="specialization"
                            class="form-control" value="{{ old('specialization', $teacher?->specialization) }}"
                            placeholder="Toán, Vật lý..."></div>
                    <div class="col-md-6"><label class="form-label">Ngày vào trường</label><input type="date"
                            name="hire_date" class="form-control"
                            value="{{ old('hire_date', $teacher?->hire_date?->format('Y-m-d')) }}"></div>
                    <div class="col-12"><label class="form-label">Địa chỉ</label><input name="address" class="form-control"
                            value="{{ old('address', $teacher?->address) }}"></div>
                </div>

                <div class="permission-section">
                    <div class="section-label">Môn giảng dạy</div>
                    <p class="text-secondary small">Chọn trực tiếp từ bảng môn học. Phân công môn vào từng lớp được thực
                        hiện ở mục “Phân công giáo viên”.</p>
                    <div class="row g-2">
                        @forelse($subjects as $subject)
                            <div class="col-lg-4 col-md-6"><label class="check-tile"><input type="checkbox"
                                        name="subjects[]" value="{{ $subject->id }}"
                                        @checked(in_array($subject->id, $selectedSubjects))><span><strong>{{ $subject->name }}</strong><small>{{ $subject->code }}</small></span></label>
                        </div>@empty<div class="text-secondary small">Chưa có môn học trong danh mục.</div>
                        @endforelse
                    </div>
                </div>

                <div class="permission-section">
                    <div class="section-label">Giáo viên chủ nhiệm</div>
                    <div class="form-check mb-3"><input type="checkbox" name="is_homeroom_teacher" value="1"
                            class="form-check-input" id="is_homeroom_teacher" @checked(count($selectedClasses) > 0)><label
                            class="form-check-label" for="is_homeroom_teacher">Giáo viên này là giáo viên chủ nhiệm</label>
                    </div><label class="form-label">Lớp chủ nhiệm theo năm học</label><select name="academic_year_id"
                        class="form-select mb-3">
                        <option value="">Chọn năm học</option>
                        @foreach ($years as $year)
                            <option value="{{ $year->id }}" @selected($selectedYear == $year->id)>{{ $year->name }}
                            </option>
                        @endforeach
                    </select>
                    <div class="row g-2">
                        @forelse($classes as $class)
                            <div class="col-lg-6"><label class="check-tile"><input type="checkbox"
                                        name="homeroom_class_ids[]" value="{{ $class->id }}"
                                        @checked(in_array($class->id, $selectedClasses))><span><strong>{{ $class->name }}</strong><small>{{ $class->grade?->name }}
                                            · {{ $class->academicYear?->name }} ·
                                        {{ $class->room ?: 'Chưa có phòng' }}</small></span></label></div>@empty<div
                                class="text-secondary small">Chưa có lớp học. Hãy tạo lớp trước.</div>
                        @endforelse
                    </div>
                </div>

                <div class="mb-3"><label class="form-label">Ghi chú</label>
                    <textarea name="notes" rows="3" class="form-control">{{ old('notes', $teacher?->notes) }}</textarea>
                </div>
                <div class="form-actions"><button class="btn btn-primary"><i data-lucide="save"></i> Lưu giáo
                        viên</button><a href="{{ route('admin.school.teachers.index') }}" class="btn btn-light">Hủy</a>
                </div>
            </div>
        </div>
    </form>
@endsection

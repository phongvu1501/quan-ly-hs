@extends('admin.layouts.app')

@php($isEditing = isset($student))
@php($enrollment = $student?->enrollments?->first())
@php($guardianByRelationship = $student?->guardians?->keyBy('pivot.relationship') ?? collect())

@section('title', $isEditing ? 'Sửa học sinh' : 'Thêm học sinh')

@section('content')
    <div class="page-heading">
        <div>
            <p class="eyebrow">Hồ sơ học sinh</p>
            <h1>{{ $isEditing ? 'Sửa học sinh' : 'Thêm học sinh' }}</h1>
        </div>
    </div>
    <form action="{{ $isEditing ? route('admin.school.students.update', $student) : route('admin.school.students.store') }}"
        method="POST" enctype="multipart/form-data">
        @csrf @if ($isEditing)
            @method('PUT')
        @endif
        <div class="card form-card">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-12"><label class="form-label" for="avatar">Ảnh học sinh</label>
                        @if ($student?->avatar_path)
                            <div class="mb-2"><img src="{{ asset('storage/' . $student->avatar_path) }}"
                                    alt="Ảnh {{ $student->full_name }}" class="rounded-circle"
                                    style="width:72px;height:72px;object-fit:cover"></div>
                        @endif
                        <input id="avatar" type="file" name="avatar" class="form-control" accept="image/*">
                        <div class="form-text">JPG, PNG hoặc WebP, tối đa 2MB. Để trống nếu không đổi ảnh.</div>
                    </div>
                    <div class="col-md-4"><label class="form-label">Mã học sinh</label><input class="form-control"
                            value="{{ $student?->student_code }}" placeholder="Tự động tạo sau khi lưu" readonly>
                        <div class="form-text">Định dạng tự động: HS + 6 chữ số.</div>
                    </div>
                    <div class="col-md-8"><label class="form-label">Họ và tên</label><input name="full_name"
                            class="form-control" value="{{ old('full_name', $student?->full_name) }}" required></div>
                    <div class="col-md-4"><label class="form-label">Ngày sinh</label><input type="date"
                            name="date_of_birth" class="form-control"
                            value="{{ old('date_of_birth', $student?->date_of_birth?->format('Y-m-d')) }}"></div>
                    <div class="col-md-4"><label class="form-label">Giới tính</label><select name="gender"
                            class="form-select">
                            <option value="">Chưa chọn</option>
                            @foreach (['male' => 'Nam', 'female' => 'Nữ', 'other' => 'Khác'] as $value => $label)
                                <option value="{{ $value }}" @selected(old('gender', $student?->gender) === $value)>{{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4"><label class="form-label">Trạng thái</label><select name="status"
                            class="form-select" required>
                            @foreach (['studying' => 'Đang đi học', 'sick_leave' => 'Nghỉ ốm dài ngày', 'reserved' => 'Bảo lưu', 'dropped' => 'Bỏ học', 'graduated' => 'Đã tốt nghiệp'] as $value => $label)
                                <option value="{{ $value }}" @selected(old('status', $student?->status ?: 'studying') === $value)>{{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6"><label class="form-label">Email / tài khoản đăng nhập
                            {{ $isEditing ? '' : '*' }}</label><input type="email" name="email" class="form-control"
                            value="{{ old('email', $student?->email) }}" {{ $isEditing ? '' : 'required' }}>
                        <div class="form-text">
                            {{ $isEditing ? 'Email hồ sơ học sinh.' : 'Hệ thống sẽ tạo tài khoản với email này, mật khẩu mặc định 123456 và bắt buộc đổi khi đăng nhập lần đầu.' }}
                        </div>
                    </div>
                    <div class="col-md-6"><label class="form-label" type="number">Điện thoại</label><input name="phone"
                            class="form-control" value="{{ old('phone', $student?->phone) }}" type="number" maxlength="10"></div>
                    <div class="col-md-6"><label class="form-label">Nơi sinh</label><input name="birth_place"
                            class="form-control" value="{{ old('birth_place', $student?->birth_place) }}"></div>
                    <div class="col-md-6"><label class="form-label">Năm nhập học</label><input name="admission_year"
                            class="form-control" value="{{ old('admission_year', $student?->admission_year) }}"
                            placeholder="2026-2027"></div>
                    <div class="col-12"><label class="form-label">Địa chỉ</label><input name="address" class="form-control"
                            value="{{ old('address', $student?->address) }}"></div>
                    <div class="col-md-6"><label class="form-label">Năm học xếp lớp</label><select name="academic_year_id"
                            id="student-academic-year" class="form-select">
                            <option value="">Chưa xếp lớp</option>
                            @foreach ($years as $year)
                                <option value="{{ $year->id }}" @selected(old('academic_year_id', $enrollment?->academic_year_id) == $year->id)>{{ $year->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6"><label class="form-label">Lớp hiện tại</label><select name="class_id"
                            id="student-class" class="form-select">
                            <option value="">Chưa xếp lớp</option>
                            @foreach ($classes as $class)
                                <option value="{{ $class->id }}" @selected(old('class_id', $enrollment?->class_id) == $class->id)>{{ $class->name }} ·
                                    {{ $class->grade?->name }} · {{ $class->academicYear?->name }}</option>
                            @endforeach
                        </select></div>
                    <div class="col-12">
                        <div class="permission-section mb-0">
                            <div class="section-label">Thông tin phụ huynh</div>
                            <div class="row g-4">
                                @foreach (['father' => 'Thông tin bố', 'mother' => 'Thông tin mẹ'] as $relationship => $label)
                                    @php($guardian = $guardianByRelationship->get($relationship))
                                    <div class="col-xl-6">
                                        <h2 class="panel-title mb-3">{{ $label }}</h2>
                                        <div class="row g-3">
                                            <div class="col-12"><label class="form-label">Họ và tên</label><input
                                                    name="guardians[{{ $relationship }}][full_name]" class="form-control"
                                                    value="{{ old("guardians.$relationship.full_name", $guardian?->full_name) }}">
                                            </div>
                                            <div class="col-md-6"><label class="form-label">Ngày sinh</label><input
                                                    type="date" name="guardians[{{ $relationship }}][date_of_birth]"
                                                    class="form-control"
                                                    value="{{ old("guardians.$relationship.date_of_birth", $guardian?->date_of_birth?->format('Y-m-d')) }}">
                                            </div>
                                            <div class="col-md-6"><label class="form-label">Số điện
                                                    thoại</label><input name="guardians[{{ $relationship }}][phone]"
                                                    class="form-control" type="number" maxlength="10"
                                                    value="{{ old("guardians.$relationship.phone", $guardian?->phone) }}">
                                            </div>
                                            <div class="col-md-6"><label class="form-label">Nghề nghiệp</label><input
                                                    name="guardians[{{ $relationship }}][occupation]"
                                                    class="form-control"
                                                    value="{{ old("guardians.$relationship.occupation", $guardian?->occupation) }}">
                                            </div>
                                            <div class="col-md-6"><label class="form-label">Tôn giáo</label><input
                                                    name="guardians[{{ $relationship }}][religion]" class="form-control"
                                                    value="{{ old("guardians.$relationship.religion", $guardian?->religion) }}">
                                            </div>
                                            <div class="col-12"><label class="form-label">Nơi làm việc</label><input
                                                    name="guardians[{{ $relationship }}][workplace]" class="form-control"
                                                    value="{{ old("guardians.$relationship.workplace", $guardian?->workplace) }}">
                                            </div>
                                            <div class="col-12"><label class="form-label">Email</label><input
                                                    type="email" name="guardians[{{ $relationship }}][email]"
                                                    class="form-control"
                                                    value="{{ old("guardians.$relationship.email", $guardian?->email) }}">
                                            </div>
                                            <div class="col-12"><label class="form-label">Địa chỉ</label><input
                                                    name="guardians[{{ $relationship }}][address]" class="form-control"
                                                    value="{{ old("guardians.$relationship.address", $guardian?->address) }}">
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    <div class="col-12"><label class="form-label">Ghi chú</label>
                        <textarea name="notes" rows="3" class="form-control">{{ old('notes', $student?->notes) }}</textarea>
                    </div>
                </div>
                <div class="form-actions"><button class="btn btn-primary"><i data-lucide="save"></i> Lưu học
                        sinh</button><a href="{{ route('admin.school.students.index') }}" class="btn btn-light">Hủy</a>
                </div>
            </div>
        </div>
    </form>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const status = document.querySelector('select[name="status"]');
            const year = document.getElementById('student-academic-year');
            const schoolClass = document.getElementById('student-class');
            const refresh = () => {
                const canEnroll = status?.value === 'studying';
                [year, schoolClass].forEach((select) => {
                    if (!select) return;
                    select.disabled = !canEnroll;
                    if (!canEnroll) select.value = '';
                });
            };
            status?.addEventListener('change', refresh);
            refresh();
        });
    </script>
@endsection

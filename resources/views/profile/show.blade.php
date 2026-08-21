@extends('admin.layouts.app')

@section('title', 'Hồ sơ cá nhân')

@section('content')
    @php($teacher = $user->teacherProfile)
    @php($student = $user->studentProfile)
    @php($currentEnrollment = $student?->enrollments?->sortByDesc('academic_year_id')->first())
    <div class="page-heading">
        <div>
            <p class="eyebrow">Tài khoản cá nhân</p>
            <h1>Hồ sơ của tôi</h1>
            <p class="text-secondary mb-0">Quản lý thông tin đăng nhập, ảnh đại diện và xem hồ sơ được nhà trường liên kết.
            </p>
        </div>
    </div>
    @if ($user->must_change_password)
        <div class="alert alert-warning"><strong>Yêu cầu đổi mật khẩu:</strong> tài khoản đang dùng mật khẩu mặc định
            `123456`. Hãy đặt mật khẩu mới để tiếp tục sử dụng.</div>
    @endif

    @if (($user->hasRole('teacher') || $user->hasRole('homeroom_teacher') || $user->hasRole('subject_teacher')) && !$teacher)
        <div class="alert alert-warning">Tài khoản có vai trò giáo viên nhưng chưa được liên kết với hồ sơ giáo viên. Quản
            trị viên cần mở “Tài khoản”, sửa tài khoản này và chọn hồ sơ giáo viên tương ứng.</div>
    @endif
    @if ($user->hasRole('student') && !$student)
        <div class="alert alert-warning">Tài khoản có vai trò học sinh nhưng chưa được liên kết với hồ sơ học sinh. Quản trị
            viên cần mở “Tài khoản”, sửa tài khoản này và chọn hồ sơ học sinh tương ứng.</div>
    @endif

    <div class="row g-4">
        <div class="col-xl-5">
            <form action="{{ route('profile.update') }}" method="POST" enctype="multipart/form-data" class="card form-card">
                @csrf @method('PUT')
                <div class="card-body">
                    <div class="d-flex align-items-center gap-3 mb-4">
                        @if ($user->avatar_path)
                            <img src="{{ asset('storage/' . $user->avatar_path) }}" alt="Ảnh đại diện" class="rounded-circle"
                            style="width:84px;height:84px;object-fit:cover">@else<div
                                class="rounded-circle d-flex align-items-center justify-content-center bg-light border"
                                style="width:84px;height:84px;font-size:28px;font-weight:700">
                                {{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}</div>
                        @endif
                        <div><strong class="d-block fs-5">{{ $user->name }}</strong><small
                                class="text-secondary">{{ $user->roles->pluck('name')->join(', ') ?: 'Chưa có vai trò' }}</small>
                        </div>
                    </div>
                    <div class="mb-3"><label class="form-label" for="avatar">Ảnh đại diện</label><input id="avatar"
                            type="file" name="avatar" class="form-control" accept="image/*">
                        <div class="form-text">JPG, PNG hoặc WebP, tối đa 2MB.</div>
                    </div>
                    <div class="mb-3"><label class="form-label" for="name">Họ và tên hiển thị</label><input
                            id="name" name="name" class="form-control" value="{{ old('name', $user->name) }}"
                            required></div>
                    <div class="mb-3"><label class="form-label" for="email">Email đăng nhập</label><input
                            id="email" type="email" name="email" class="form-control"
                            value="{{ old('email', $user->email) }}" required></div>
                    <div class="form-check mb-3"><input id="change_password" type="checkbox" name="change_password"
                            value="1" class="form-check-input" @checked(old('change_password', $user->must_change_password))><label
                            class="form-check-label" for="change_password">Đổi mật
                            khẩu{{ $user->must_change_password ? ' (bắt buộc)' : '' }}</label></div>
                    <div class="row g-3">
                        <div class="col-md-6"><label class="form-label" for="password">Mật khẩu mới</label><input
                                id="password" type="password" name="password" class="form-control"
                                autocomplete="new-password" @disabled(!$user->must_change_password && !old('change_password'))></div>
                        <div class="col-md-6"><label class="form-label" for="password_confirmation">Xác nhận</label><input
                                id="password_confirmation" type="password" name="password_confirmation" class="form-control"
                                autocomplete="new-password" @disabled(!$user->must_change_password && !old('change_password'))></div>
                    </div>
                    <div class="form-actions"><button class="btn btn-primary"><i data-lucide="save"></i> Lưu hồ sơ</button>
                    </div>
                </div>
            </form>
        </div>
        <div class="col-xl-7">
            @if ($teacher)
                <div class="card table-card mb-4">
                    <div class="card-body">
                        <div class="section-label">Hồ sơ giáo viên</div>
                        <div class="row g-3">
                            <div class="col-md-6"><small class="text-secondary d-block">Mã giáo
                                    viên</small><strong>{{ $teacher->teacher_code }}</strong></div>
                            <div class="col-md-6"><small class="text-secondary d-block">Trạng
                                    thái</small><strong>{{ ['active' => 'Đang công tác', 'on_leave' => 'Tạm nghỉ', 'inactive' => 'Ngừng công tác'][$teacher->status] ?? $teacher->status }}</strong>
                            </div>
                            <div class="col-md-6"><small class="text-secondary d-block">Điện
                                    thoại</small><strong>{{ $teacher->phone ?: 'Chưa cập nhật' }}</strong></div>
                            <div class="col-md-6"><small class="text-secondary d-block">Chuyên
                                    môn</small><strong>{{ $teacher->specialization ?: 'Chưa cập nhật' }}</strong></div>
                            <div class="col-12"><small class="text-secondary d-block">Môn giảng
                                    dạy</small><strong>{{ $teacher->subjects->pluck('name')->unique()->join(', ') ?: 'Chưa phân môn' }}</strong>
                            </div>
                            <div class="col-12"><small class="text-secondary d-block">Lớp chủ
                                    nhiệm</small><strong>{{ $teacher->homeroomClasses->pluck('name')->join(', ') ?: 'Không có lớp chủ nhiệm' }}</strong>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
            @if ($student)
                <div class="card table-card">
                    <div class="card-body">
                        <div class="section-label">Hồ sơ học sinh</div>
                        <div class="row g-3">
                            <div class="col-md-6"><small class="text-secondary d-block">Mã học
                                    sinh</small><strong>{{ $student->student_code }}</strong></div>
                            <div class="col-md-6"><small class="text-secondary d-block">Trạng
                                    thái</small><strong>{{ ['studying' => 'Đang đi học', 'sick_leave' => 'Nghỉ ốm dài ngày', 'reserved' => 'Bảo lưu', 'dropped' => 'Bỏ học', 'graduated' => 'Đã tốt nghiệp'][$student->status] ?? $student->status }}</strong>
                            </div>
                            <div class="col-md-6"><small class="text-secondary d-block">Lớp hiện
                                    tại</small><strong>{{ $currentEnrollment?->class?->name ?: 'Chưa xếp lớp' }}</strong>
                            </div>
                            <div class="col-md-6"><small class="text-secondary d-block">Khối
                                    lớp</small><strong>{{ $currentEnrollment?->class?->grade?->name ?: 'Chưa cập nhật' }}</strong>
                            </div>
                            <div class="col-12"><small class="text-secondary d-block">Địa
                                    chỉ</small><strong>{{ $student->address ?: 'Chưa cập nhật' }}</strong></div>
                        </div><a href="{{ route('student.payments') }}" class="btn btn-light mt-4"><i
                                data-lucide="receipt-text"></i> Xem lịch sử đóng tiền</a>
                    </div>
                </div>
            @endif
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const toggle = document.getElementById('change_password');
            const fields = [document.getElementById('password'), document.getElementById('password_confirmation')];
            const refresh = () => fields.forEach(field => field.disabled = !toggle.checked);
            toggle.addEventListener('change', refresh);
            refresh();
        });
    </script>
@endsection

@extends('admin.layouts.app')

@section('title', 'Sửa tài khoản')

@section('content')
    @php($userRoles = $user->roles->pluck('id')->all())
    <div class="page-heading">
        <div>
            <p class="eyebrow">RBAC / User</p>
            <h1>Sửa tài khoản</h1>
            <p class="text-secondary mb-0">Bỏ chọn role sẽ thu hồi toàn bộ quyền và menu đi kèm.</p>
        </div>
    </div>
    <form action="{{ route('admin.users.update', $user) }}" method="POST" enctype="multipart/form-data">
        @csrf @method('PUT')
        <div class="card form-card">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-12"><label class="form-label" for="avatar">Ảnh đại diện</label>
                        @if ($user->avatar_path)
                            <div class="mb-2"><img src="{{ asset('storage/' . $user->avatar_path) }}"
                                    alt="Ảnh đại diện {{ $user->name }}" class="rounded-circle"
                                    style="width:64px;height:64px;object-fit:cover"></div>
                        @endif
                        <input id="avatar" type="file" name="avatar" class="form-control" accept="image/*">
                        <div class="form-text">Để trống nếu không thay ảnh.</div>
                    </div>
                    <div class="col-md-6"><label class="form-label" for="name">Họ tên</label><input id="name"
                            name="name" class="form-control" value="{{ old('name', $user->name) }}" required></div>
                    <div class="col-md-6"><label class="form-label" for="email">Email</label><input id="email"
                            type="email" name="email" class="form-control" value="{{ old('email', $user->email) }}"
                            required></div>
                    <div class="col-12">
                        <div class="form-check"><input id="change_password" type="checkbox" name="change_password"
                                value="1" class="form-check-input" @checked(old('change_password'))><label
                                class="form-check-label" for="change_password">Đổi mật khẩu cho tài khoản này</label></div>
                        <div class="form-text">Không tích ô này nếu chỉ sửa thông tin hoặc vai trò.</div>
                    </div>
                    <div class="col-md-6"><label class="form-label" for="password">Mật khẩu mới</label><input id="password"
                            type="password" name="password" class="form-control" autocomplete="new-password" disabled></div>
                    <div class="col-md-6"><label class="form-label" for="password_confirmation">Xác nhận mật
                            khẩu</label><input id="password_confirmation" type="password" name="password_confirmation"
                            class="form-control" autocomplete="new-password" disabled></div>
                </div>
                <div class="permission-section">
                    <div class="section-label">Vai trò</div>
                    <div class="row g-2">
                        @foreach ($roles as $role)
                            <div class="col-lg-4 col-md-6"><label class="check-tile"><input type="checkbox" name="roles[]"
                                        value="{{ $role->id }}"
                                        @checked(in_array($role->id, old('roles', $userRoles)))><span><strong>{{ $role->name }}</strong><small>{{ $role->slug }}</small></span></label>
                            </div>
                        @endforeach
                    </div>
                </div>
                <div class="permission-section">
                    <div class="section-label">Liên kết hồ sơ nghiệp vụ</div>
                    <p class="text-secondary small">Tài khoản giáo viên hoặc học sinh phải được liên kết hồ sơ để vào đúng
                        cổng và xem đúng dữ liệu.</p>
                    <div class="row g-3">
                        <div class="col-md-6"><label class="form-label" for="teacher_id">Hồ sơ giáo viên</label><select
                                id="teacher_id" name="teacher_id" class="form-select">
                                <option value="">Không liên kết giáo viên</option>
                                @foreach ($teachers as $teacher)
                                    <option value="{{ $teacher->id }}" @selected(old('teacher_id', $user->teacherProfile?->id) == $teacher->id)>
                                        {{ $teacher->full_name }} · {{ $teacher->teacher_code }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6"><label class="form-label" for="student_id">Hồ sơ học sinh</label><select
                                id="student_id" name="student_id" class="form-select">
                                <option value="">Không liên kết học sinh</option>
                                @foreach ($students as $student)
                                    <option value="{{ $student->id }}" @selected(old('student_id', $user->studentProfile?->id) == $student->id)>
                                        {{ $student->full_name }} · {{ $student->student_code }}</option>
                                @endforeach
                            </select></div>
                    </div>
                </div>
                <div class="form-actions"><button class="btn btn-primary" type="submit"><i data-lucide="save"></i> Lưu thay
                        đổi</button><a href="{{ route('admin.users.index') }}" class="btn btn-light">Huỷ</a></div>
            </div>
        </div>
    </form>
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

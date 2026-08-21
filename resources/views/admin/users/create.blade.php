@extends('admin.layouts.app')

@section('title', 'Thêm tài khoản')

@section('content')
    <div class="page-heading">
        <div>
            <p class="eyebrow">RBAC / User</p>
            <h1>Thêm tài khoản</h1>
        </div>
    </div>
    <form action="{{ route('admin.users.store') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <div class="card form-card">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-12"><label class="form-label" for="avatar">Ảnh đại diện</label><input id="avatar"
                            type="file" name="avatar" class="form-control" accept="image/*">
                        <div class="form-text">JPG, PNG hoặc WebP, tối đa 2MB.</div>
                    </div>
                    <div class="col-md-6"><label class="form-label" for="name">Họ tên</label><input id="name"
                            name="name" class="form-control" value="{{ old('name') }}" required></div>
                    <div class="col-md-6"><label class="form-label" for="email">Email</label><input id="email"
                            type="email" name="email" class="form-control" value="{{ old('email') }}" required></div>
                    <div class="col-md-6"><label class="form-label" for="password">Mật khẩu</label><input id="password"
                            type="password" name="password" class="form-control" required></div>
                    <div class="col-md-6"><label class="form-label" for="password_confirmation">Xác nhận mật
                            khẩu</label><input id="password_confirmation" type="password" name="password_confirmation"
                            class="form-control" required></div>
                </div>
                <div class="permission-section">
                    <div class="section-label">Vai trò</div>
                    <div class="row g-2">
                        @foreach ($roles as $role)
                            <div class="col-lg-4 col-md-6"><label class="check-tile"><input type="checkbox" name="roles[]"
                                        value="{{ $role->id }}"
                                        @checked(in_array($role->id, old('roles', [])))><span><strong>{{ $role->name }}</strong><small>{{ $role->slug }}</small></span></label>
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
                                    <option value="{{ $teacher->id }}" @selected(old('teacher_id') == $teacher->id)>
                                        {{ $teacher->full_name }} · {{ $teacher->teacher_code }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6"><label class="form-label" for="student_id">Hồ sơ học sinh</label><select
                                id="student_id" name="student_id" class="form-select">
                                <option value="">Không liên kết học sinh</option>
                                @foreach ($students as $student)
                                    <option value="{{ $student->id }}" @selected(old('student_id') == $student->id)>
                                        {{ $student->full_name }} · {{ $student->student_code }}</option>
                                @endforeach
                            </select></div>
                    </div>
                </div>
                <div class="form-actions"><button class="btn btn-primary" type="submit"><i data-lucide="save"></i> Lưu tài
                        khoản</button><a href="{{ route('admin.users.index') }}" class="btn btn-light">Huỷ</a></div>
            </div>
        </div>
    </form>
@endsection

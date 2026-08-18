             @extends('admin.layouts.app')

@section('title', 'Sửa Role')

@section('content')

<h2 class="mb-4">
    Sửa Role
</h2>

<form
    action="{{ route('admin.roles.update', $role) }}"
    method="POST">

    @csrf
    @method('PUT')

    <div class="card">

        <div class="card-body">

            <div class="mb-3">

                <label class="form-label">
                    Tên Role
                </label>

                <input
                    type="text"
                    name="name"
                    class="form-control"
                    value="{{ old('name', $role->name) }}"
                    required>

            </div>

            <div class="mb-3">

                <label class="form-label">
                    Slug
                </label>

                <input
                    type="text"
                    name="slug"
                    class="form-control"
                    value="{{ old('slug', $role->slug) }}"
                    required>

            </div>

            <div class="mb-3">

                <label class="form-label">
                    Mô tả
                </label>

                <textarea
                    name="description"
                    class="form-control"
                    rows="3">{{ old('description', $role->description) }}</textarea>

            </div>

            <hr>

            <h5>
                Permissions
            </h5>

            @php
                $rolePermissions = $role->permissions
                    ->pluck('id')
                    ->toArray();
            @endphp

            <div class="row mt-3">

                @foreach($permissions as $permission)

                    <div class="col-md-4 mb-2">

                        <div class="form-check">

                            <input
                                type="checkbox"
                                name="permissions[]"
                                value="{{ $permission->id }}"
                                class="form-check-input"
                                id="permission{{ $permission->id }}"

                                @checked(
                                    in_array(
                                        $permission->id,
                                        $rolePermissions
                                    )
                                )
                            >

                            <label
                                class="form-check-label"
                                for="permission{{ $permission->id }}">

                                {{ $permission->name }}

                                <small class="text-muted">
                                    ({{ $permission->slug }})
                                </small>

                            </label>

                        </div>

                    </div>

                @endforeach

            </div>

            <div class="mt-4">

                <button class="btn btn-primary">
                    Cập nhật
                </button>

                <a
                    href="{{ route('admin.roles.index') }}"
                    class="btn btn-secondary">
                    Quay lại
                </a>

            </div>

        </div>

    </div>

</form>

@endsection
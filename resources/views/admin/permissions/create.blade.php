@extends('admin.layouts.app')

@section('title', 'Thêm Permission')

@section('content')

<h2 class="mb-4">
    Thêm Permission
</h2>

<form
    action="{{ route('admin.permissions.store') }}"
    method="POST">

    @csrf

    <div class="card">

        <div class="card-body">

            <div class="mb-3">

                <label class="form-label">
                    Tên Permission
                </label>

                <input
                    type="text"
                    name="name"
                    class="form-control"
                    value="{{ old('name') }}"
                    placeholder="Xem sản phẩm"
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
                    value="{{ old('slug') }}"
                    placeholder="products.view"
                    required>

            </div>

            <div class="mb-3">

                <label class="form-label">
                    Mô tả
                </label>

                <textarea
                    name="description"
                    class="form-control"
                    rows="3">{{ old('description') }}</textarea>

            </div>

            <button class="btn btn-primary">
                Lưu
            </button>

            <a
                href="{{ route('admin.permissions.index') }}"
                class="btn btn-secondary">
                Quay lại
            </a>

        </div>

    </div>

</form>

@endsection
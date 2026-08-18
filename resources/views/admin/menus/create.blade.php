@extends('admin.layouts.app')

@section('title', 'Thêm Menu')

@section('content')

<h2 class="mb-4">
    Thêm Menu
</h2>

<form
    action="{{ route('admin.menus.store') }}"
    method="POST">

    @csrf

    <div class="card">

        <div class="card-body">

            <div class="mb-3">

                <label class="form-label">
                    Tên Menu
                </label>

                <input
                    type="text"
                    name="name"
                    class="form-control"
                    placeholder="Sản phẩm"
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
                    placeholder="products"
                    required>

            </div>

            <div class="mb-3">

                <label class="form-label">
                    Route
                </label>

                <input
                    type="text"
                    name="route"
                    class="form-control"
                    placeholder="products.index">

            </div>

            <div class="mb-3">

                <label class="form-label">
                    Icon
                </label>

                <input
                    type="text"
                    name="icon"
                    class="form-control"
                    placeholder="bi bi-box">

            </div>

            <div class="mb-3">

                <label class="form-label">
                    Menu cha
                </label>

                <select
                    name="parent_id"
                    class="form-select">

                    <option value="">
                        -- Menu gốc --
                    </option>

                    @foreach($parents as $parent)

                        <option value="{{ $parent->id }}">

                            {{ $parent->name }}

                        </option>

                    @endforeach

                </select>

            </div>

            <div class="mb-3">

                <label class="form-label">
                    Thứ tự
                </label>

                <input
                    type="number"
                    name="sort_order"
                    class="form-control"
                    value="0">

            </div>

            <div class="form-check mb-3">

                <input
                    type="checkbox"
                    name="is_active"
                    value="1"
                    class="form-check-input"
                    id="is_active"
                    checked>

                <label
                    class="form-check-label"
                    for="is_active">

                    Hiển thị menu

                </label>

            </div>

            <button class="btn btn-primary">
                Lưu
            </button>

            <a
                href="{{ route('admin.menus.index') }}"
                class="btn btn-secondary">
                Quay lại
            </a>

        </div>

    </div>

</form>

@endsection
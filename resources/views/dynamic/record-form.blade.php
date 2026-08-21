@extends('admin.layouts.app')

@php($isEditing = isset($record))

@section('title', $isEditing ? 'Sửa bản ghi' : 'Thêm bản ghi')

@section('content')
    <div class="page-heading">
        <div>
            <p class="eyebrow">{{ $menu->name }}</p>
            <h1>{{ $isEditing ? 'Sửa bản ghi' : 'Thêm bản ghi' }}</h1>
        </div>
    </div>
    <form
        action="{{ $isEditing
            ? route('dynamic.menus.records.update', ['menu' => $menu->slug, 'record' => $record])
            : route('dynamic.menus.records.store', ['menu' => $menu->slug]) }}"
        method="POST">
        @csrf
        @if ($isEditing)
            @method('PUT')
        @endif
        <div class="card form-card">
            <div class="card-body">
                <div class="mb-3"><label class="form-label" for="title">Tiêu đề</label><input id="title" name="title"
                        class="form-control" value="{{ old('title', $record?->title) }}" required></div>
                <div class="mb-3"><label class="form-label" for="code">Mã</label><input id="code" name="code"
                        class="form-control" value="{{ old('code', $record?->code) }}"></div>
                <div class="mb-3"><label class="form-label" for="description">Mô tả</label>
                    <textarea id="description" name="description" rows="5" class="form-control">{{ old('description', $record?->description) }}</textarea>
                </div>
                <label class="form-check mb-3"><input type="checkbox" name="is_active" value="1"
                        class="form-check-input" @checked(old('is_active', $record?->is_active ?? true))><span class="form-check-label">Đang hiển
                        thị</span></label>
                <div class="form-actions"><button class="btn btn-primary" type="submit"><i data-lucide="save"></i>
                        {{ $isEditing ? 'Lưu thay đổi' : 'Lưu bản ghi' }}</button><a
                        href="{{ route('dynamic.menus.show', ['menu' => $menu->slug]) }}" class="btn btn-light">Huỷ</a>
                </div>
            </div>
        </div>
    </form>
@endsection

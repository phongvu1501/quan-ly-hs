@extends('admin.layouts.app')
@section('title', 'Danh mục khoản thu')
@section('content')
    <div class="page-heading page-heading-inline">
        <div>
            <p class="eyebrow">Cấu hình học phí</p>
            <h1>Danh mục khoản thu</h1>
        </div>
        @if (auth()->user()->hasPermission('fee-categories.create'))
            <a href="{{ route('admin.finance.fee-categories.create') }}" class="btn btn-primary"><i data-lucide="plus"></i>
                Thêm danh mục</a>
        @endif
    </div>
    @include('admin.finance.partials.nav')
    <div class="card table-card">
        <div class="table-responsive">
            <table class="table mb-0">
                <thead>
                    <tr>
                        <th>Mã</th>
                        <th>Tên</th>
                        <th>Loại</th>
                        <th>Mức thu</th>
                        <th>Assessment</th>
                        <th>Trạng thái</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($categories as $c)
                        <tr>
                            <td><code>{{ $c->code }}</code></td>
                            <td><strong>{{ $c->name }}</strong></td>
                            <td>{{ $c->type }}</td>
                            <td>{{ $c->rates_count }}</td>
                            <td>{{ $c->assessments_count }}</td>
                            <td>{{ $c->is_active ? 'Đang dùng' : 'Ngừng' }}</td>
                            <td class="text-end">
                                @if (auth()->user()->hasPermission('fee-categories.edit'))
                                    <a href="{{ route('admin.finance.fee-categories.edit', $c) }}"
                                        class="btn btn-sm btn-light"><i data-lucide="pencil"></i></a>
                                @endif
                            </td>
                    </tr>@empty<tr>
                            <td colspan="7" class="empty-state">Chưa có danh mục.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($categories->hasPages())
            <div class="p-3 border-top">{{ $categories->links('pagination::bootstrap-5') }}</div>
        @endif
    </div>
@endsection

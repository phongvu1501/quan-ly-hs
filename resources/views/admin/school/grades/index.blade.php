@extends('admin.layouts.app')
@section('title', 'Khối lớp')
@section('content')
    <div class="page-heading page-heading-inline">
        <div>
            <p class="eyebrow">Cấu trúc đào tạo</p>
            <h1>Khối lớp</h1>
            <p class="text-secondary mb-0">Khối 10, 11, 12 được dùng để phân loại lớp.</p>
        </div>
        @if (auth()->user()->hasPermission('grades.create'))
            <a href="{{ route('admin.school.grades.create') }}" class="btn btn-primary" data-modal-title="Thêm khối lớp"><i
                    data-lucide="plus"></i> Thêm khối lớp</a>
        @endif
    </div>
    <div class="card filter-card mb-4"><div class="card-body">
        <div class="d-flex align-items-center justify-content-between gap-3 mb-3"><div><strong>Bộ lọc khối lớp</strong><small class="d-block text-secondary">Chọn khối để xem nhanh danh sách.</small></div><a class="btn btn-sm btn-light" href="{{ route('admin.school.grades.index') }}" title="Đặt lại bộ lọc"><i data-lucide="rotate-ccw"></i><span class="visually-hidden">Đặt lại</span></a></div>
        <form method="GET" class="row g-3">
            @if (request('q')) <input type="hidden" name="q" value="{{ request('q') }}"> @endif
            <div class="col-md-4 col-xl-3"><label class="form-label" for="grade-level">Khối</label><select id="grade-level" name="level" class="form-select"><option value="">Tất cả khối</option>@foreach($levels as $level)<option value="{{ $level }}" @selected(request('level') == $level)>Khối {{ $level }}</option>@endforeach</select></div>
            <div class="col-md-4 col-xl-2 d-flex align-items-end"><button class="btn btn-primary w-100" type="submit"><i data-lucide="filter"></i> Lọc</button></div>
        </form>
    </div></div>
    <div class="card table-card">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Cấp khối</th>
                        <th>Tên khối</th>
                        <th>Số lớp</th>
                        <th>Mô tả</th>
                        <th class="text-end">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($grades as $grade)
                        <tr>
                            <td><strong>Khối {{ $grade->level }}</strong></td>
                            <td>{{ $grade->name }}</td>
                            <td>{{ $grade->classes_count }}</td>
                            <td class="text-secondary">{{ $grade->description ?: '-' }}</td>
                            <td class="text-end">
                                @if (auth()->user()->hasPermission('grades.edit'))
                                    <a href="{{ route('admin.school.grades.edit', $grade) }}"
                                        class="btn btn-sm btn-light" data-modal-title="Sửa khối lớp" title="Sửa"><i
                                            data-lucide="pencil"></i></a>
                                @endif
                                @if (auth()->user()->hasPermission('grades.delete'))
                                    <form action="{{ route('admin.school.grades.destroy', $grade) }}" method="POST"
                                        class="d-inline" data-modal-title="Xóa khối lớp"
                                        data-confirm="Khối có lớp đang sử dụng sẽ không thể xóa.">@csrf @method('DELETE')<button
                                            class="btn btn-sm btn-outline-danger" title="Xóa"><i
                                                data-lucide="trash-2"></i></button></form>
                                @endif
                            </td>
                    </tr>@empty<tr>
                            <td colspan="5" class="empty-state">Chưa có khối lớp.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($grades->hasPages())
            <div class="p-3 border-top">{{ $grades->links('pagination::bootstrap-5') }}</div>
        @endif
    </div>
@endsection

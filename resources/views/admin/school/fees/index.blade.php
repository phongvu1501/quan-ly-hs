@extends('admin.layouts.app')
@section('title', 'Thông báo học phí')
@section('content')<div class="page-heading page-heading-inline">
        <div>
            <p class="eyebrow">Tài chính học sinh</p>
            <h1>Thông báo học phí</h1>
            <p class="text-secondary mb-0">Khoản thu legacy; không tính trong báo cáo finance assessment mới.</p>
        </div>
        @if (auth()->user()->hasPermission('fees.generate'))
            <a href="{{ route('admin.finance.fees.generate.create') }}" class="btn btn-primary"><i data-lucide="plus"></i> Phát sinh học phí mới</a>
        @endif
    </div>
    <div class="card filter-card mb-4"><div class="card-body">
        <div class="d-flex align-items-center justify-content-between gap-3 mb-3"><div><strong>Bộ lọc khoản thu</strong><small class="d-block text-secondary">Lọc theo năm học, học kỳ, lớp và trạng thái thu.</small></div><a class="btn btn-sm btn-light" href="{{ route('admin.school.fees.index') }}" title="Đặt lại bộ lọc"><i data-lucide="rotate-ccw"></i><span class="visually-hidden">Đặt lại</span></a></div>
        <form method="GET" class="row g-3">
            @if (request('q')) <input type="hidden" name="q" value="{{ request('q') }}"> @endif
            <div class="col-md-4 col-xl-3"><label class="form-label" for="fee-year">Năm học</label><select id="fee-year" name="academic_year_id" class="form-select"><option value="">Tất cả năm học</option>@foreach($years as $year)<option value="{{ $year->id }}" @selected(request('academic_year_id') == $year->id)>{{ $year->name }}</option>@endforeach</select></div>
            <div class="col-md-4 col-xl-3"><label class="form-label" for="fee-semester">Học kỳ</label><select id="fee-semester" name="semester_id" class="form-select"><option value="">Cả năm hoặc tất cả học kỳ</option>@foreach($semesters as $semester)<option value="{{ $semester->id }}" data-year="{{ $semester->academic_year_id }}" @selected(request('semester_id') == $semester->id)>{{ $semester->academicYear?->name }} · {{ $semester->name }}</option>@endforeach</select></div>
            <div class="col-md-4 col-xl-3"><label class="form-label" for="fee-class">Lớp</label><select id="fee-class" name="class_id" class="form-select"><option value="">Toàn trường hoặc tất cả lớp</option>@foreach($classes as $class)<option value="{{ $class->id }}" data-year="{{ $class->academic_year_id }}" @selected(request('class_id') == $class->id)>{{ $class->name }} · {{ $class->academicYear?->name }}</option>@endforeach</select></div>
            <div class="col-md-4 col-xl-3"><label class="form-label" for="fee-active">Trạng thái</label><select id="fee-active" name="active" class="form-select"><option value="">Tất cả trạng thái</option><option value="active" @selected(request('active') === 'active')>Đang thu</option><option value="locked" @selected(request('active') === 'locked')>Đã khóa</option></select></div>
            <div class="col-md-4 col-xl-2 d-flex align-items-end"><button class="btn btn-primary w-100" type="submit"><i data-lucide="filter"></i> Lọc</button></div>
        </form>
    </div></div>
    <div class="card table-card">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Khoản thu legacy</th>
                        <th>Số tiền</th>
                        <th>Hạn đóng</th>
                        <th>Đã ghi nhận</th>
                        <th>Trạng thái</th>
                        <th class="text-end">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($fees as $fee)
                        <tr>
                            <td><strong>{{ $fee->title }}</strong><small
                                    class="d-block text-secondary">{{ \Illuminate\Support\Str::limit($fee->description, 80) }}</small>
                                @if($fee->isLegacy())<span class="badge text-bg-warning mt-1">Legacy / Data warning</span>@endif
                            </td>
                            <td>{{ number_format((float) $fee->amount, 0, ',', '.') }} đ</td>
                            <td>{{ $fee->due_on?->format('d/m/Y') ?: '-' }}</td>
                            <td>{{ $fee->payments_count }}</td>
                            <td><span
                                    class="status-dot {{ $fee->is_active ? 'status-on' : 'status-off' }}">{{ $fee->is_active ? 'Đang thu' : 'Đã khóa' }}</span>
                            </td>
                            <td class="text-end">
                                @if (! $fee->isLegacy() && auth()->user()->hasPermission('fees.edit'))
                                    <a href="{{ route('admin.school.fees.edit', $fee) }}"
                                        class="btn btn-sm btn-light" data-modal-title="Sửa khoản thu" title="Sửa"><i
                                            data-lucide="pencil"></i></a>
                                @endif
                                @if (! $fee->isLegacy() && auth()->user()->hasPermission('fees.delete'))
                                    <form action="{{ route('admin.school.fees.destroy', $fee) }}" method="POST"
                                        data-modal-title="Xóa khoản thu" data-confirm="Xóa thông báo học phí này?">@csrf
                                        @method('DELETE')<button class="btn btn-sm btn-outline-danger" title="Xóa"><i
                                                data-lucide="trash-2"></i></button></form>
                                @endif
                            </td>
                    </tr>@empty<tr>
                            <td colspan="6" class="empty-state">Chưa có khoản thu.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($fees->hasPages())
            <div class="p-3 border-top">{{ $fees->links('pagination::bootstrap-5') }}</div>
        @endif
</div>@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const year = document.getElementById('fee-year');
            const semester = document.getElementById('fee-semester');
            const classSelect = document.getElementById('fee-class');
            if (!year || !semester || !classSelect) return;
            const refresh = (select) => { [...select.options].forEach((option, index) => { if (index) option.hidden = Boolean(year.value) && option.dataset.year !== year.value; }); if (select.selectedOptions[0]?.hidden) select.value = ''; };
            const apply = () => { refresh(semester); refresh(classSelect); };
            year.addEventListener('change', apply); apply();
        });
    </script>
@endpush

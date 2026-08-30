@extends('admin.layouts.app')
@section('title', 'Tạo bảng lương tháng')
@section('content')<div class="page-heading">
        <p class="eyebrow">Lương giáo viên</p>
        <h1>Tạo bảng lương tháng</h1>
    </div>
    <form method="POST" action="{{ route('admin.finance.payrolls.generate') }}" class="card form-card">@csrf<div
            class="card-body">
            <div class="row g-3">
                <div class="col-md-3"><label class="form-label">Năm</label><input type="number" name="payroll_year"
                        value="{{ now()->year }}" class="form-control"></div>
                <div class="col-md-3"><label class="form-label">Tháng</label><input type="number" name="payroll_month"
                        min="1" max="12" value="{{ now()->month }}" class="form-control"></div>
                <div class="col-md-6"><label class="form-label">Năm học</label><select name="academic_year_id"
                        class="form-select">
                        <option value="">Không chọn</option>
                        @foreach ($academicYears as $y)
                            <option value="{{ $y->id }}">{{ $y->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12"><label class="form-label">Giáo viên</label><select name="teacher_ids[]" multiple
                        size="12" class="form-select">
                        @foreach ($teachers as $t)
                            <option value="{{ $t->id }}">{{ $t->teacher_code }} · {{ $t->full_name }} ·
                                {{ $t->specialization }}</option>
                        @endforeach
                    </select><small class="text-secondary">Không chọn để tạo toàn bộ giáo viên.</small></div>
            </div>
            <div class="form-actions"><button class="btn btn-primary"><i data-lucide="play"></i> Tạo bảng lương</button></div>
        </div>
</form>@endsection

@extends('admin.layouts.app')
@php($editing = $payrollInput !== null)
@section('title', $editing ? 'Sửa payroll input' : 'Thêm payroll input')
@section('content')<div class="page-heading">
        <p class="eyebrow">Payroll input</p>
        <h1>{{ $editing ? 'Sửa payroll input' : 'Thêm payroll input' }}</h1>
    </div>
    <form method="POST"
        action="{{ $editing ? route('admin.finance.payroll-inputs.update', $payrollInput) : route('admin.finance.payroll-inputs.store') }}"
        class="card form-card">@csrf @if ($editing)
            @method('PUT')
        @endif
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6"><label class="form-label">Giáo viên</label><select name="teacher_id" class="form-select">
                        @foreach ($teachers as $t)
                            <option value="{{ $t->id }}" @selected(old('teacher_id', $payrollInput?->teacher_id) == $t->id)>{{ $t->teacher_code }} ·
                                {{ $t->full_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6"><label class="form-label">Component</label><select name="salary_component_id"
                        class="form-select">
                        @foreach ($components as $c)
                            <option value="{{ $c->id }}" @selected(old('salary_component_id', $payrollInput?->salary_component_id) == $c->id)>{{ $c->type }} ·
                                {{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3"><label class="form-label">Năm</label><input type="number" name="input_year"
                        value="{{ old('input_year', $payrollInput?->input_year ?? now()->year) }}" class="form-control"></div>
                <div class="col-md-3"><label class="form-label">Tháng</label><input type="number" min="1"
                        max="12" name="input_month"
                        value="{{ old('input_month', $payrollInput?->input_month ?? now()->month) }}" class="form-control">
                </div>
                <div class="col-md-3"><label class="form-label">Loại input</label><select name="input_type"
                        class="form-select">
                        @foreach (['working_days', 'unpaid_leave', 'overtime', 'bonus', 'manual_adjustment', 'other_input'] as $v)
                            <option @selected(old('input_type', $payrollInput?->input_type) === $v)>{{ $v }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3"><label class="form-label">Trạng thái</label><select name="status"
                        class="form-select">
                        <option value="draft">draft</option>
                        <option value="approved" @selected(old('status', $payrollInput?->status) === 'approved')>approved</option>
                    </select></div>
                <div class="col-md-4"><label class="form-label">Quantity</label><input name="quantity"
                        value="{{ old('quantity', $payrollInput?->quantity) }}" class="form-control"></div>
                <div class="col-md-4"><label class="form-label">Input amount</label><input name="input_amount"
                        value="{{ old('input_amount', $payrollInput?->input_amount) }}" class="form-control"><small
                        class="text-secondary">Chỉ bonus/manual/other; các loại khác server tính từ quantity.</small></div>
                <div class="col-md-4"><label class="form-label">Năm học</label><select name="academic_year_id"
                        class="form-select">
                        <option value="">Không chọn</option>
                        @foreach ($years as $y)
                            <option value="{{ $y->id }}" @selected(old('academic_year_id', $payrollInput?->academic_year_id) == $y->id)>{{ $y->name }}</option>
                        @endforeach
                    </select></div>
                <div class="col-12"><label class="form-label">Ghi chú</label>
                    <textarea name="note" class="form-control">{{ old('note', $payrollInput?->note) }}</textarea>
                </div>
            </div>
            <div class="form-actions"><button class="btn btn-primary"><i data-lucide="save"></i> Lưu dữ liệu</button></div>
        </div>
</form>@endsection

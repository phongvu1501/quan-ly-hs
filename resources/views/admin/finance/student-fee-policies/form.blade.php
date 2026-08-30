@extends('admin.layouts.app')
@php($editing = $studentFeePolicy !== null)
@section('title', $editing ? 'Sửa diện miễn giảm' : 'Gán diện miễn giảm')
@section('content')<div class="page-heading">
        <p class="eyebrow">Tài chính học sinh</p>
        <h1>{{ $editing ? 'Sửa diện miễn giảm' : 'Gán diện miễn giảm' }}</h1>
    </div>
    <form method="POST"
        action="{{ $editing ? route('admin.finance.student-fee-policies.update', $studentFeePolicy) : route('admin.finance.student-fee-policies.store') }}"
        class="card form-card">@csrf @if ($editing)
            @method('PUT')
        @endif
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6"><label class="form-label">Học sinh</label><select name="student_id" class="form-select">
                        @foreach ($students as $s)
                            <option value="{{ $s->id }}" @selected(old('student_id', $studentFeePolicy?->student_id) == $s->id)>{{ $s->student_code }} ·
                                {{ $s->full_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6"><label class="form-label">Chính sách</label><select name="fee_policy_id"
                        class="form-select">
                        @foreach ($policies as $p)
                            <option value="{{ $p->id }}" @selected(old('fee_policy_id', $studentFeePolicy?->fee_policy_id) == $p->id)>{{ $p->name }} ·
                                {{ $p->academicYear?->name }}</option>
                        @endforeach
                    </select></div>
                <div class="col-md-3"><label class="form-label">Từ ngày</label><input type="date" name="effective_from"
                        value="{{ old('effective_from', $studentFeePolicy?->effective_from?->toDateString()) }}"
                        class="form-control" required></div>
                <div class="col-md-3"><label class="form-label">Đến ngày</label><input type="date" name="effective_to"
                        value="{{ old('effective_to', $studentFeePolicy?->effective_to?->toDateString()) }}"
                        class="form-control"></div>
                <div class="col-md-3"><label class="form-label">Trạng thái</label><select name="status"
                        class="form-select">
                        <option value="active">Đang áp dụng</option>
                        <option value="inactive" @selected(old('status', $studentFeePolicy?->status) === 'inactive')>Ngừng áp dụng</option>
                    </select></div>
                <div class="col-md-3"><label class="form-label">Mã minh chứng</label><input name="evidence_reference"
                        value="{{ old('evidence_reference', $studentFeePolicy?->evidence_reference) }}"
                        class="form-control"></div>
                <div class="col-12"><label class="form-label">Ghi chú</label>
                    <textarea name="note" class="form-control">{{ old('note', $studentFeePolicy?->note) }}</textarea>
                </div>
            </div>
            <div class="form-actions"><button class="btn btn-primary"><i data-lucide="save"></i> Lưu</button></div>
        </div>
</form>@endsection

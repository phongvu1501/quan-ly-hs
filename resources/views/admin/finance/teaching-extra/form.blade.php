@extends('admin.layouts.app')
@section('title', 'Ghi nhận dạy thêm')
@section('content')<div class="page-heading">
        <p class="eyebrow">Payroll input</p>
        <h1>Ghi nhận dạy thêm</h1>
    </div>
    <form method="POST" action="{{ route('admin.finance.teaching-extra.store') }}" class="card form-card">@csrf<div
            class="card-body">
            <div class="row g-3">
                <div class="col-md-6"><label class="form-label">Giáo viên</label><select name="teacher_id" class="form-select">
                        @foreach ($teachers as $t)
                            <option value="{{ $t->id }}">{{ $t->teacher_code }} · {{ $t->full_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3"><label class="form-label">Năm học</label><select name="academic_year_id"
                        class="form-select">
                        @foreach ($years as $y)
                            <option value="{{ $y->id }}">{{ $y->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3"><label class="form-label">Học kỳ</label><select name="semester_id"
                        class="form-select">
                        @foreach ($semesters as $s)
                            <option value="{{ $s->id }}">{{ $s->academicYear?->name }} · {{ $s->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6"><label class="form-label">Môn</label><select name="subject_id" class="form-select">
                        @foreach ($subjects as $s)
                            <option value="{{ $s->id }}">{{ $s->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6"><label class="form-label">Lớp</label><select name="class_id" class="form-select">
                        @foreach ($classes as $c)
                            <option value="{{ $c->id }}">{{ $c->name }} · {{ $c->academicYear?->name }}
                            </option>
                        @endforeach
                    </select></div>
                <div class="col-md-4"><label class="form-label">Năm</label><input type="number" name="teaching_year"
                        value="{{ now()->year }}" class="form-control"></div>
                <div class="col-md-4"><label class="form-label">Tháng</label><input type="number" min="1"
                        max="12" name="teaching_month" value="{{ now()->month }}" class="form-control"></div>
                <div class="col-md-4"><label class="form-label">Số tiết</label><input name="number_of_periods"
                        class="form-control" required><small class="text-secondary">Đơn giá và thành tiền do server
                        tính.</small></div>
                <div class="col-12"><label class="form-label">Ghi chú</label>
                    <textarea name="note" class="form-control"></textarea>
                </div>
            </div>
            <div class="form-actions"><button class="btn btn-primary"><i data-lucide="save"></i> Lưu</button></div>
        </div>
</form>@endsection

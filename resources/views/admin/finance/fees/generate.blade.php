@extends('admin.layouts.app')
@section('title', 'Phát sinh học phí')
@section('content')
    <div class="page-heading">
        <p class="eyebrow">Tài chính học sinh</p>
        <h1>Phát sinh học phí</h1>
    </div>
    <form method="POST" action="{{ route('admin.finance.fees.generate') }}" class="card form-card">@csrf<div class="card-body">
            <div class="row g-3">
                <div class="col-md-4"><label class="form-label">Năm học</label><select name="academic_year_id"
                        class="form-select" required>
                        @foreach ($years as $year)
                            <option value="{{ $year->id }}">{{ $year->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4"><label class="form-label">Học kỳ</label><select name="semester_id"
                        class="form-select">
                        <option value="">Không áp dụng</option>
                        @foreach ($semesters as $s)
                            <option value="{{ $s->id }}">{{ $s->academicYear?->name }} · {{ $s->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4"><label class="form-label">Mức thu</label><select name="fee_rate_id" id="fee-rate"
                        class="form-select" required>
                        <option value="">Chọn mức thu</option>
                        @foreach ($rates as $rate)
                            <option value="{{ $rate->id }}" data-category="{{ $rate->fee_category_id }}"
                                data-period="{{ $rate->period_type }}">{{ $rate->name }} ·
                                {{ number_format((int) $rate->amount, 0, ',', '.') }} ₫</option>
                        @endforeach
                    </select>
                    <input type="hidden" name="fee_category_id" id="fee-category">
                </div>
                <div class="col-md-6"><label class="form-label">Tên đợt thu</label><input name="title"
                        class="form-control" required></div>
                <div class="col-md-2"><label class="form-label">Loại kỳ</label><select name="period_type" id="period-type"
                        class="form-select" required>
                        @foreach (['monthly' => 'Theo tháng', 'semester' => 'Học kỳ', 'annual' => 'Năm học', 'one_time' => 'Một lần'] as $v => $l)
                            <option value="{{ $v }}">{{ $l }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2"><label class="form-label">Khóa kỳ</label><input name="period_key" class="form-control"
                        placeholder="2026-09" required></div>
                <div class="col-md-2"><label class="form-label">Lần phát sinh</label><input type="number"
                        name="charge_sequence" value="1" min="1" class="form-control" required></div>
                <div class="col-md-3"><label class="form-label">Ngày tính</label><input type="date"
                        name="assessment_date" value="{{ today()->toDateString() }}" class="form-control" required></div>
                <div class="col-md-3"><label class="form-label">Hạn đóng</label><input type="date" name="due_date"
                        class="form-control"></div>
                <div class="col-md-3"><label class="form-label">Phạm vi</label><select name="scope_type" class="form-select"
                        required>
                        <option value="all">Toàn trường</option>
                        <option value="class">Theo lớp</option>
                        <option value="selected">Học sinh được chọn</option>
                    </select></div>
                <div class="col-md-3"><label class="form-label">Lớp</label><select name="class_id" class="form-select">
                        <option value="">Không chọn</option>
                        @foreach ($classes as $class)
                            <option value="{{ $class->id }}">{{ $class->name }} · {{ $class->academicYear?->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12"><label class="form-label">Học sinh chọn riêng</label><select name="student_ids[]"
                        multiple size="6" class="form-select">
                        @foreach ($students as $student)
                            <option value="{{ $student->id }}">{{ $student->student_code }} · {{ $student->full_name }}
                            </option>
                        @endforeach
                    </select></div>
                <div class="col-12"><label class="form-label">Ghi chú</label>
                    <textarea name="note" class="form-control" rows="3"></textarea>
                </div>
            </div>
            <div class="form-actions"><button class="btn btn-primary"><i data-lucide="play"></i> Lưu học phí</button><a
                    href="{{ route('admin.finance.fees.index') }}" class="btn btn-light">Hủy</a></div>
        </div>
    </form>
@endsection
@push('scripts')
    <script>
        document.getElementById('fee-rate')?.addEventListener('change', e => {
            const o = e.target.selectedOptions[0];
            document.getElementById('fee-category').value = o?.dataset.category || '';
            document.getElementById('period-type').value = o?.dataset.period || 'monthly';
        });
    </script>
@endpush

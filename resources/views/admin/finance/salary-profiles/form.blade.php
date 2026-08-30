@extends('admin.layouts.app')
@php($editing = $salaryProfile !== null)
@php($configRows = old('configs', $salaryProfile?->configs?->map(fn($c) => $c->only(['salary_component_id', 'subject_id', 'fixed_amount', 'percentage_value', 'rate_per_unit', 'default_quantity', 'calculation_base', 'is_active', 'note']))->all() ?? [[]]))
@section('title', $editing ? 'Sửa hồ sơ lương' : 'Tạo hồ sơ lương')
@section('content')<div class="page-heading">
        <p class="eyebrow">Cấu hình lương</p>
        <h1>{{ $editing ? 'Sửa hồ sơ lương' : 'Tạo hồ sơ lương' }}</h1>
    </div>
    <form method="POST"
        action="{{ $editing ? route('admin.finance.salary-profiles.update', $salaryProfile) : route('admin.finance.salary-profiles.store') }}"
        class="card form-card">@csrf @if ($editing)
            @method('PUT')
        @endif
        <div class="card-body">
            <div class="row g-3 mb-4">
                <div class="col-md-4"><label class="form-label">Giáo viên</label><select name="teacher_id" class="form-select">
                        @foreach ($teachers as $t)
                            <option value="{{ $t->id }}" @selected(old('teacher_id', $salaryProfile?->teacher_id) == $t->id)>{{ $t->teacher_code }} ·
                                {{ $t->full_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4"><label class="form-label">Mã profile</label><input name="profile_code"
                        value="{{ old('profile_code', $salaryProfile?->profile_code) }}" class="form-control" required></div>
                <div class="col-md-2"><label class="form-label">Từ ngày</label><input type="date" name="effective_from"
                        value="{{ old('effective_from', $salaryProfile?->effective_from?->toDateString()) }}"
                        class="form-control" required></div>
                <div class="col-md-2"><label class="form-label">Đến ngày</label><input type="date" name="effective_to"
                        value="{{ old('effective_to', $salaryProfile?->effective_to?->toDateString()) }}"
                        class="form-control"></div>
                <div class="col-md-3"><label class="form-label">Người phụ thuộc</label><input type="number" min="0"
                        name="dependent_count" value="{{ old('dependent_count', $salaryProfile?->dependent_count ?? 0) }}"
                        class="form-control"></div>
                <div class="col-md-3"><label class="form-label">Trạng thái</label><select name="status"
                        class="form-select">
                        <option value="draft">draft</option>
                        <option value="active" @selected(old('status', $salaryProfile?->status) === 'active')>active</option>
                        <option value="inactive" @selected(old('status', $salaryProfile?->status) === 'inactive')>inactive</option>
                    </select></div>
                <div class="col-md-3"><label class="form-check mt-4"><input type="checkbox" name="tax_enabled"
                            value="1" class="form-check-input" @checked(old('tax_enabled', $salaryProfile?->tax_enabled))> Tính thuế</label></div>
                <div class="col-md-3"><label class="form-check mt-4"><input type="checkbox" name="insurance_enabled"
                            value="1" class="form-check-input" @checked(old('insurance_enabled', $salaryProfile?->insurance_enabled))> Tính bảo hiểm</label>
                </div>
            </div>
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h2 class="panel-title mb-0">Components</h2><button type="button" id="add-config"
                    class="btn btn-sm btn-light"><i data-lucide="plus"></i> Thêm dòng</button>
            </div>
            <div id="config-rows">
                @foreach ($configRows as $i => $row)
                    <div class="row g-2 mb-2 config-row">
                        <div class="col-md-3"><select name="configs[{{ $i }}][salary_component_id]"
                                class="form-select">
                                @foreach ($components as $c)
                                    <option value="{{ $c->id }}" @selected(($row['salary_component_id'] ?? null) == $c->id)>{{ $c->code }} ·
                                        {{ $c->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2"><select name="configs[{{ $i }}][subject_id]" class="form-select">
                                <option value="">Global</option>
                                @foreach ($subjects as $s)
                                    <option value="{{ $s->id }}" @selected(($row['subject_id'] ?? null) == $s->id)>{{ $s->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col"><input name="configs[{{ $i }}][fixed_amount]"
                                value="{{ $row['fixed_amount'] ?? '' }}" class="form-control" placeholder="Fixed"></div>
                        <div class="col"><input name="configs[{{ $i }}][percentage_value]"
                                value="{{ $row['percentage_value'] ?? '' }}" class="form-control" placeholder="%"></div>
                        <div class="col"><input name="configs[{{ $i }}][rate_per_unit]"
                                value="{{ $row['rate_per_unit'] ?? '' }}" class="form-control" placeholder="Rate"></div>
                        <div class="col"><input name="configs[{{ $i }}][default_quantity]"
                                value="{{ $row['default_quantity'] ?? '' }}" class="form-control" placeholder="Qty"><input
                                type="hidden" name="configs[{{ $i }}][is_active]" value="1"></div>
                        <div class="col-auto"><button type="button" class="btn btn-light remove-row"><i
                                    data-lucide="x"></i></button></div>
                    </div>
                @endforeach
            </div>
            <div class="form-actions"><button class="btn btn-primary"><i data-lucide="save"></i> Lưu hồ sơ</button></div>
        </div>
    </form>
    <template id="config-template">
        <div class="row g-2 mb-2 config-row">
            <div class="col-md-3"><select data-name="salary_component_id" class="form-select">
                    @foreach ($components as $c)
                        <option value="{{ $c->id }}">{{ $c->code }} · {{ $c->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2"><select data-name="subject_id" class="form-select">
                    <option value="">Global</option>
                    @foreach ($subjects as $s)
                        <option value="{{ $s->id }}">{{ $s->name }}</option>
                    @endforeach
                </select>
            </div>
            @foreach (['fixed_amount' => 'Fixed', 'percentage_value' => '%', 'rate_per_unit' => 'Rate', 'default_quantity' => 'Qty'] as $n => $p)
                <div class="col"><input data-name="{{ $n }}" class="form-control"
                        placeholder="{{ $p }}"></div>
            @endforeach
            <div class="col-auto"><button type="button" class="btn btn-light remove-row"><i
                        data-lucide="x"></i></button></div>
        </div>
    </template>
@endsection
@push('scripts')
    <script>
        (() => {
            let i = {{ count($configRows) }};
            const box = document.getElementById('config-rows');
            document.getElementById('add-config')?.addEventListener('click', () => {
                const row = document.getElementById('config-template').content.firstElementChild.cloneNode(
                true);
                row.querySelectorAll('[data-name]').forEach(el => el.name =
                `configs[${i}][${el.dataset.name}]`);
                const hidden = document.createElement('input');
                hidden.type = 'hidden';
                hidden.name = `configs[${i}][is_active]`;
                hidden.value = '1';
                row.append(hidden);
                box.append(row);
                i++;
                lucide.createIcons();
            });
            document.addEventListener('click', e => {
                if (e.target.closest('.remove-row')) e.target.closest('.config-row').remove();
            });
        })();
    </script>
@endpush

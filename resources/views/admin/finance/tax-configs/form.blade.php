@extends('admin.layouts.app')
@php($editing = $taxConfig !== null)
@php($bracketRows = old('brackets', $taxConfig?->brackets?->map(fn($b) => $b->only(['sequence', 'lower_bound', 'upper_bound', 'rate_percentage', 'quick_deduction']))->all() ?? [['sequence' => 1]]))
@section('title', $editing ? 'Sửa cấu hình thuế' : 'Thêm cấu hình thuế')
@section('content')<div class="page-heading">
        <p class="eyebrow">Thuế</p>
        <h1>{{ $editing ? 'Sửa cấu hình thuế' : 'Thêm cấu hình thuế' }}</h1>
    </div>
    <form method="POST"
        action="{{ $editing ? route('admin.finance.tax-configs.update', $taxConfig) : route('admin.finance.tax-configs.store') }}"
        class="card form-card">@csrf @if ($editing)
            @method('PUT')
        @endif
        <div class="card-body">
            <div class="row g-3 mb-4">
                <div class="col-md-3"><label class="form-label">Mã</label><input name="code"
                        value="{{ old('code', $taxConfig?->code) }}" class="form-control" required></div>
                <div class="col-md-5"><label class="form-label">Tên</label><input name="name"
                        value="{{ old('name', $taxConfig?->name) }}" class="form-control" required></div>
                <div class="col-md-2"><label class="form-label">Năm thuế</label><input type="number" name="tax_year"
                        value="{{ old('tax_year', $taxConfig?->tax_year ?? now()->year) }}" class="form-control"></div>
                <div class="col-md-2"><label class="form-check mt-4"><input type="checkbox" name="is_active" value="1"
                            class="form-check-input" @checked(old('is_active', $taxConfig?->is_active ?? true))> Active</label></div>
                <div class="col-md-3"><label class="form-label">Giảm trừ cá nhân</label><input name="personal_deduction"
                        value="{{ old('personal_deduction', $taxConfig?->personal_deduction ?? 0) }}" class="form-control">
                </div>
                <div class="col-md-3"><label class="form-label">Giảm trừ phụ thuộc</label><input name="dependent_deduction"
                        value="{{ old('dependent_deduction', $taxConfig?->dependent_deduction ?? 0) }}" class="form-control">
                </div>
                <div class="col-md-3"><label class="form-label">Từ ngày</label><input type="date" name="effective_from"
                        value="{{ old('effective_from', $taxConfig?->effective_from?->toDateString()) }}"
                        class="form-control"></div>
                <div class="col-md-3"><label class="form-label">Đến ngày</label><input type="date" name="effective_to"
                        value="{{ old('effective_to', $taxConfig?->effective_to?->toDateString()) }}" class="form-control">
                </div>
            </div>
            <div class="d-flex justify-content-between mb-2">
                <h2 class="panel-title">Bậc thuế</h2><button type="button" id="add-bracket" class="btn btn-sm btn-light"><i
                        data-lucide="plus"></i> Thêm bậc</button>
            </div>
            <div id="brackets">
                @foreach ($bracketRows as $i => $b)
                    <div class="row g-2 mb-2 bracket-row">
                        <div class="col"><input name="brackets[{{ $i }}][sequence]"
                                value="{{ $b['sequence'] ?? $i + 1 }}" class="form-control" placeholder="Thứ tự"></div>
                        <div class="col"><input name="brackets[{{ $i }}][lower_bound]"
                                value="{{ $b['lower_bound'] ?? 0 }}" class="form-control" placeholder="Từ"></div>
                        <div class="col"><input name="brackets[{{ $i }}][upper_bound]"
                                value="{{ $b['upper_bound'] ?? '' }}" class="form-control" placeholder="Đến"></div>
                        <div class="col"><input name="brackets[{{ $i }}][rate_percentage]"
                                value="{{ $b['rate_percentage'] ?? 0 }}" class="form-control" placeholder="%"></div>
                        <div class="col"><input name="brackets[{{ $i }}][quick_deduction]"
                                value="{{ $b['quick_deduction'] ?? 0 }}" class="form-control" placeholder="Giảm nhanh"></div>
                        <div class="col-auto"><button type="button" class="btn btn-light remove-bracket"><i
                                    data-lucide="x"></i></button></div>
                    </div>
                @endforeach
            </div>
            <div class="form-actions"><button class="btn btn-primary"><i data-lucide="save"></i> Lưu</button></div>
        </div>
    </form><template id="bracket-template">
        <div class="row g-2 mb-2 bracket-row">
            @foreach (['sequence' => 'Thứ tự', 'lower_bound' => 'Từ', 'upper_bound' => 'Đến', 'rate_percentage' => '%', 'quick_deduction' => 'Giảm nhanh'] as $n => $p)
                <div class="col"><input data-name="{{ $n }}" class="form-control"
                        placeholder="{{ $p }}"></div>
            @endforeach
            <div class="col-auto"><button type="button" class="btn btn-light remove-bracket"><i
                        data-lucide="x"></i></button></div>
        </div>
</template>@endsection
@push('scripts')
    <script>
        (() => {
            let i = {{ count($bracketRows) }};
            document.getElementById('add-bracket')?.addEventListener('click', () => {
                const r = document.getElementById('bracket-template').content.firstElementChild.cloneNode(true);
                r.querySelectorAll('[data-name]').forEach(e => e.name = `brackets[${i}][${e.dataset.name}]`);
                document.getElementById('brackets').append(r);
                i++;
                lucide.createIcons();
            });
            document.addEventListener('click', e => {
                if (e.target.closest('.remove-bracket')) e.target.closest('.bracket-row').remove();
            });
        })();
    </script>
@endpush

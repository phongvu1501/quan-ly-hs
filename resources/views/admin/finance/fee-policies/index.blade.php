@extends('admin.layouts.app')
@section('title', 'Chính sách miễn giảm')
@section('content')<div class="page-heading page-heading-inline">
        <div>
            <p class="eyebrow">Cấu hình học phí</p>
            <h1>Chính sách miễn giảm</h1>
        </div>
        @if (auth()->user()->hasPermission('fee-policies.create'))
            <a href="{{ route('admin.finance.fee-policies.create') }}" class="btn btn-primary"><i data-lucide="plus"></i> Thêm
                chính sách</a>
        @endif
    </div>
    @include('admin.finance.partials.nav')<div class="card table-card">
        <div class="table-responsive">
            <table class="table mb-0">
                <thead>
                    <tr>
                        <th>Chính sách</th>
                        <th>Năm / khoản thu</th>
                        <th>Mức giảm</th>
                        <th>Quy tắc</th>
                        <th>Hiệu lực</th>
                        <th>Học sinh</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($policies as $p)
                        <tr>
                            <td><strong>{{ $p->name }}</strong><small
                                    class="d-block"><code>{{ $p->code }}</code></small></td>
                            <td>{{ $p->academicYear?->name }}<small
                                    class="d-block text-secondary">{{ $p->category?->name ?: 'Tất cả khoản thu' }}</small></td>
                            <td>{{ \App\Support\FinanceLabels::reductionType($p->reduction_type) }} ·
                                {{ $p->reduction_value }}</td>
                            <td>{{ \App\Support\FinanceLabels::applicationMode($p->application_mode) }} · ưu tiên
                                {{ $p->priority }}</td>
                            <td>{{ $p->effective_from?->format('d/m/Y') }} -
                                {{ $p->effective_to?->format('d/m/Y') ?: '...' }}</td>
                            <td>{{ $p->student_assignments_count }}</td>
                            <td>
                                @if (auth()->user()->hasPermission('fee-policies.edit'))
                                    <a href="{{ route('admin.finance.fee-policies.edit', $p) }}"
                                        class="btn btn-sm btn-light"><i data-lucide="pencil"></i></a>
                                @endif
                            </td>
                    </tr>@empty<tr>
                            <td colspan="7" class="empty-state">Chưa có chính sách.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($policies->hasPages())
            <div class="p-3 border-top">{{ $policies->links('pagination::bootstrap-5') }}</div>
        @endif
</div>@endsection

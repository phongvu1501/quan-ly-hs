@extends('admin.layouts.app')
@section('title', 'Mức thu')
@section('content')<div class="page-heading page-heading-inline">
        <div>
            <p class="eyebrow">Cấu hình học phí</p>
            <h1>Mức thu theo thời gian</h1>
        </div>
        @if (auth()->user()->hasPermission('fee-rates.create'))
            <a href="{{ route('admin.finance.fee-rates.create') }}" class="btn btn-primary"><i data-lucide="plus"></i> Thêm mức
                thu</a>
        @endif
    </div>
    @include('admin.finance.partials.nav')<div class="card table-card">
        <div class="table-responsive">
            <table class="table mb-0">
                <thead>
                    <tr>
                        <th>Mã / tên</th>
                        <th>Khoản thu</th>
                        <th>Năm học</th>
                        <th>Chu kỳ</th>
                        <th>Số tiền</th>
                        <th>Hiệu lực</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rates as $r)
                        <tr>
                            <td><strong>{{ $r->name }}</strong><small
                                    class="d-block"><code>{{ $r->code }}</code></small></td>
                            <td>{{ $r->category?->name }}</td>
                            <td>{{ $r->academicYear?->name }}</td>
                            <td>{{ $r->period_type }}</td>
                            <td>{{ number_format((int) $r->amount, 0, ',', '.') }} ₫</td>
                            <td>{{ $r->effective_from?->format('d/m/Y') }} - {{ $r->effective_to?->format('d/m/Y') ?: '...' }}
                            </td>
                            <td>
                                @if (auth()->user()->hasPermission('fee-rates.edit'))
                                    <a href="{{ route('admin.finance.fee-rates.edit', $r) }}" class="btn btn-sm btn-light"><i
                                            data-lucide="pencil"></i></a>
                                @endif
                            </td>
                    </tr>@empty<tr>
                            <td colspan="7" class="empty-state">Chưa có mức thu.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($rates->hasPages())
            <div class="p-3 border-top">{{ $rates->links('pagination::bootstrap-5') }}</div>
        @endif
</div>@endsection

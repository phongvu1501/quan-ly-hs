@extends('admin.layouts.app')
@section('title', 'Diện miễn giảm học sinh')
@section('content')<div class="page-heading page-heading-inline">
        <div>
            <p class="eyebrow">Tài chính học sinh</p>
            <h1>Diện miễn giảm học sinh</h1>
        </div>
        @if (auth()->user()->hasPermission('student-fee-policies.create'))
            <a href="{{ route('admin.finance.student-fee-policies.create') }}" class="btn btn-primary"><i
                    data-lucide="plus"></i> Gán chính sách</a>
        @endif
    </div>
    @include('admin.finance.partials.nav')<div class="card table-card">
        <div class="table-responsive">
            <table class="table mb-0">
                <thead>
                    <tr>
                        <th>Học sinh</th>
                        <th>Chính sách</th>
                        <th>Hiệu lực</th>
                        <th>Minh chứng</th>
                        <th>Trạng thái</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($assignments as $a)
                        <tr>
                            <td><strong>{{ $a->student?->full_name }}</strong><small
                                    class="d-block">{{ $a->student?->student_code }}</small></td>
                            <td>{{ $a->policy?->name }}</td>
                            <td>{{ $a->effective_from?->format('d/m/Y') }} - {{ $a->effective_to?->format('d/m/Y') ?: '...' }}
                            </td>
                            <td>{{ $a->evidence_reference ?: '-' }}</td>
                            <td>{{ $a->status === 'active' ? 'Đang áp dụng' : 'Ngừng áp dụng' }}</td>
                            <td>
                                @if (auth()->user()->hasPermission('student-fee-policies.edit'))
                                    <a href="{{ route('admin.finance.student-fee-policies.edit', $a) }}"
                                        class="btn btn-sm btn-light"><i data-lucide="pencil"></i></a>
                                @endif
                            </td>
                    </tr>@empty<tr>
                            <td colspan="6" class="empty-state">Chưa gán chính sách.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($assignments->hasPages())
            <div class="p-3 border-top">{{ $assignments->links('pagination::bootstrap-5') }}</div>
        @endif
</div>@endsection

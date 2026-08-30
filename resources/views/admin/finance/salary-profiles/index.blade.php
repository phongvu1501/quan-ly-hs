@extends('admin.layouts.app')
@section('title', 'Cấu hình lương giáo viên')
@section('content')<div class="page-heading page-heading-inline">
        <div>
            <p class="eyebrow">Cấu hình lương</p>
            <h1>Hồ sơ lương theo hiệu lực</h1>
        </div>
        @if (auth()->user()->hasPermission('salary-configs.create'))
            <a href="{{ route('admin.finance.salary-profiles.create') }}" class="btn btn-primary"><i data-lucide="plus"></i>
                Tạo hồ sơ</a>
        @endif
    </div>
    @include('admin.finance.partials.nav')<div class="card table-card">
        <div class="table-responsive">
            <table class="table mb-0">
                <thead>
                    <tr>
                        <th>Giáo viên</th>
                        <th>Mã profile</th>
                        <th>Hiệu lực</th>
                        <th>Components</th>
                        <th>Tax / insurance</th>
                        <th>Trạng thái</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($profiles as $p)
                        <tr>
                            <td><strong>{{ $p->teacher?->full_name }}</strong><small
                                    class="d-block">{{ $p->teacher?->teacher_code }}</small></td>
                            <td><code>{{ $p->profile_code }}</code></td>
                            <td>{{ $p->effective_from?->format('d/m/Y') }} - {{ $p->effective_to?->format('d/m/Y') ?: '...' }}
                            </td>
                            <td>{{ $p->configs_count }}</td>
                            <td>{{ $p->tax_enabled ? 'Có' : 'Không' }} / {{ $p->insurance_enabled ? 'Có' : 'Không' }}</td>
                            <td>{{ $p->status }}</td>
                            <td>
                                @if (auth()->user()->hasPermission('salary-configs.edit'))
                                    <a href="{{ route('admin.finance.salary-profiles.edit', $p) }}"
                                        class="btn btn-sm btn-light"><i data-lucide="pencil"></i></a>
                                @endif
                            </td>
                    </tr>@empty<tr>
                            <td colspan="7" class="empty-state">Chưa có hồ sơ lương.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($profiles->hasPages())
            <div class="p-3 border-top">{{ $profiles->links('pagination::bootstrap-5') }}</div>
        @endif
</div>@endsection

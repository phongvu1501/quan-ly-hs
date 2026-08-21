@extends('admin.layouts.app')
@section('title', 'Thông báo')
@section('content')<div class="page-heading page-heading-inline">
        <div>
            <p class="eyebrow">Truyền thông nhà trường</p>
            <h1>Thông báo</h1>
            <p class="text-secondary mb-0">Thông báo học tập, lịch học tuần tới, lịch thi và các khoản cần đóng.</p>
        </div><a href="{{ route('admin.school.announcements.create') }}" class="btn btn-primary"
            data-modal-title="Tạo thông báo"><i data-lucide="plus"></i> Tạo thông báo</a>
    </div>
    <div class="card filter-card mb-4"><div class="card-body">
        <div class="d-flex align-items-center justify-content-between gap-3 mb-3"><div><strong>Bộ lọc thông báo</strong><small class="d-block text-secondary">Lọc theo đối tượng, loại và trạng thái phát hành.</small></div><a class="btn btn-sm btn-light" href="{{ route('admin.school.announcements.index') }}" title="Đặt lại bộ lọc"><i data-lucide="rotate-ccw"></i><span class="visually-hidden">Đặt lại</span></a></div>
        <form method="GET" class="row g-3">
            @if (request('q')) <input type="hidden" name="q" value="{{ request('q') }}"> @endif
            <div class="col-md-4 col-xl-3"><label class="form-label" for="announcement-audience">Đối tượng</label><select id="announcement-audience" name="audience" class="form-select"><option value="">Tất cả đối tượng</option>@foreach($audiences as $value => $label)<option value="{{ $value }}" @selected(request('audience') === $value)>{{ $label }}</option>@endforeach</select></div>
            <div class="col-md-4 col-xl-3"><label class="form-label" for="announcement-type">Loại thông báo</label><select id="announcement-type" name="type" class="form-select"><option value="">Tất cả loại</option>@foreach($announcementTypes as $type)<option value="{{ $type }}" @selected(request('type') === $type)>{{ $type }}</option>@endforeach</select></div>
            <div class="col-md-4 col-xl-3"><label class="form-label" for="announcement-published">Trạng thái</label><select id="announcement-published" name="published" class="form-select"><option value="">Tất cả trạng thái</option><option value="published" @selected(request('published') === 'published')>Đã phát hành</option><option value="draft" @selected(request('published') === 'draft')>Bản nháp</option></select></div>
            <div class="col-md-4 col-xl-2 d-flex align-items-end"><button class="btn btn-primary w-100" type="submit"><i data-lucide="filter"></i> Lọc</button></div>
        </form>
    </div></div>
    <div class="card table-card">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Tiêu đề</th>
                        <th>Đối tượng</th>
                        <th>Loại</th>
                        <th>Phát hành</th>
                        <th>Trạng thái</th>
                        <th class="text-end">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($announcements as $announcement)
                        <tr>
                            <td><strong>{{ $announcement->title }}</strong><small
                                    class="d-block text-secondary">{{ \Illuminate\Support\Str::limit($announcement->content, 100) }}</small>
                            </td>
                            <td>{{ ['all' => 'Toàn trường', 'students' => 'Học sinh', 'teachers' => 'Giáo viên'][$announcement->audience] ?? $announcement->audience }}
                            </td>
                            <td>{{ $announcement->type }}</td>
                            <td>{{ $announcement->published_at?->format('d/m/Y H:i') ?: '-' }}</td>
                            <td><span
                                    class="status-dot {{ $announcement->is_published ? 'status-on' : 'status-off' }}">{{ $announcement->is_published ? 'Đã phát hành' : 'Bản nháp' }}</span>
                            </td>
                            <td class="text-end">
                                <a href="{{ route('admin.school.announcements.edit', $announcement) }}"
                                    class="btn btn-sm btn-light" data-modal-title="Sửa thông báo" title="Sửa"><i
                                        data-lucide="pencil"></i></a>
                                <form action="{{ route('admin.school.announcements.destroy', $announcement) }}"
                                    method="POST" data-modal-title="Xóa thông báo" data-confirm="Xóa thông báo này?">@csrf
                                    @method('DELETE')<button class="btn btn-sm btn-outline-danger" title="Xóa"><i
                                            data-lucide="trash-2"></i></button></form>
                            </td>
                    </tr>@empty<tr>
                            <td colspan="6" class="empty-state">Chưa có thông báo.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($announcements->hasPages())
            <div class="p-3 border-top">{{ $announcements->links('pagination::bootstrap-5') }}</div>
        @endif
</div>@endsection

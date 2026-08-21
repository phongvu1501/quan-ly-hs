@if ($invigilationNotifications->isNotEmpty())
    <div class="border-bottom bg-light-subtle px-4 py-3">
        <div class="d-flex align-items-center justify-content-between gap-3">
            <strong><i data-lucide="shield-check" class="me-1"></i>Lịch coi thi của tôi</strong>
            @if ($invigilationNotifications->whereNull('read_at')->isNotEmpty())
                <span class="badge text-bg-primary">Mới</span>
            @endif
        </div>
    </div>
    @foreach ($invigilationNotifications as $notification)
        <div class="list-group-item px-4 py-3 {{ $notification->read_at ? '' : 'bg-primary-subtle' }}">
            <div class="d-flex justify-content-between gap-3">
                <strong>{{ $notification->data['title'] ?? 'Thông báo lịch coi thi' }}</strong>
                <small class="text-secondary text-nowrap">{{ $notification->created_at?->format('d/m/Y H:i') }}</small>
            </div>
            <p class="small mb-1 mt-1">{{ $notification->data['message'] ?? '' }}</p>
            @if (!empty($notification->data['details']))
                <small class="text-secondary">{{ $notification->data['details'] }}</small>
            @endif
        </div>
    @endforeach
@endif

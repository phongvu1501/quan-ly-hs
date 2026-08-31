@props([
    'icon' => 'inbox',
    'title' => 'Không có dữ liệu',
    'description' => null,
])

<div {{ $attributes->class(['empty-state-content']) }}>
    <span class="empty-state-icon" aria-hidden="true"><i data-lucide="{{ $icon }}"></i></span>
    <p class="empty-state-title">{{ $title }}</p>
    @if ($description)
        <p class="empty-state-description">{{ $description }}</p>
    @endif
    @isset($actions)
        <div class="empty-state-actions">{{ $actions }}</div>
    @endisset
</div>

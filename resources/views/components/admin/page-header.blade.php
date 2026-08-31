@props([
    'eyebrow' => null,
    'title',
    'description' => null,
])

<div {{ $attributes->class(['page-heading', 'page-heading-inline' => isset($actions)]) }}>
    <div>
        @if ($eyebrow)
            <p class="eyebrow">{{ $eyebrow }}</p>
        @endif
        <h1>{{ $title }}</h1>
        @if ($description)
            <p class="page-description">{{ $description }}</p>
        @endif
    </div>

    @isset($actions)
        <div class="page-heading-actions">{{ $actions }}</div>
    @endisset
</div>

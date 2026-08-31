@props(['title', 'description' => null])

<section {{ $attributes->class(['form-section']) }}>
    <div class="form-section-header">
        <div class="form-section-heading">
            <h2>{{ $title }}</h2>
            @if ($description)
                <p>{{ $description }}</p>
            @endif
        </div>
        <div class="form-section-content">{{ $slot }}</div>
    </div>
</section>

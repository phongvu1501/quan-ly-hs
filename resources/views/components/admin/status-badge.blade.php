@props(['tone' => 'neutral'])

<span {{ $attributes->class(['status-badge', 'status-' . $tone]) }}>{{ $slot }}</span>

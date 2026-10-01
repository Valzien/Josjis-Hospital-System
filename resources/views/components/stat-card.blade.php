@props([
    'label',
    'value',
    'icon' => 'bi-graph-up',
    'color' => 'primary',
    'meta' => null,
    'href' => null,
])

@php
    $tag = $href ? 'a' : 'div';
@endphp

<{{ $tag }} @if ($href) href="{{ $href }}" @endif
    {{ $attributes->class(['jhs-stat-card', 'text-decoration-none' => (bool) $href]) }}>
    <span class="jhs-stat-icon jhs-icon-soft-{{ $color }}"><i class="bi {{ $icon }}"></i></span>
    <div class="flex-grow-1 min-w-0">
        <div class="jhs-stat-label">{{ $label }}</div>
        <div class="jhs-stat-value">{{ $value }}</div>
        @if ($meta)
            <div class="jhs-stat-meta">{{ $meta }}</div>
        @endif
    </div>
</{{ $tag }}>

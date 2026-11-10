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
    $valStr = strip_tags((string) $value);
    $isLong = mb_strlen($valStr) > 10;
@endphp

<{{ $tag }} @if ($href) href="{{ $href }}" @endif
    {{ $attributes->class(['jhs-stat-card', 'text-decoration-none' => (bool) $href]) }}>
    <span class="jhs-stat-icon jhs-icon-soft-{{ $color }}"><i class="bi {{ $icon }}"></i></span>
    <div class="flex-grow-1 min-w-0">
        <div class="jhs-stat-label text-truncate">{{ $label }}</div>
        <div class="jhs-stat-value text-truncate {{ $isLong ? 'fs-5' : '' }}" title="{{ $valStr }}">{{ $value }}</div>
        @if ($meta)
            <div class="jhs-stat-meta text-truncate">{{ $meta }}</div>
        @endif
    </div>
</{{ $tag }}>

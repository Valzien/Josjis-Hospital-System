@props([
    'text',
    'icon' => 'bi-check-circle-fill',
    'color' => 'success',
])

<span {{ $attributes->class(['jhs-badge', 'jhs-badge-'.$color]) }}>
    <i class="bi {{ $icon }}"></i>{{ $text }}
</span>

@props([
    'route' => null,
    'icon' => 'bi-plus-lg',
    'label' => 'Tambah',
    'variant' => 'primary',
    'size' => '',
    'type' => 'submit',
    'name' => null,
    'value' => null,
])

<button type="{{ $type }}"
        @if ($route) onclick="window.location.href='{{ $route }}'" @endif
        @if ($name) name="{{ $name }}" value="{{ $value ?? 1 }}" @endif
        {{ $attributes->class(['btn', 'btn-'.$variant, $size ? 'btn-'.$size : null]) }}>
    <i class="bi {{ $icon }} me-1"></i>{{ $label }}
</button>

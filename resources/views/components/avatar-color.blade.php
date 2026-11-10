@php
    $palette = [
        1 => ['#2563eb', '#ffffff'],
        2 => ['#0d9488', '#ffffff'],
        3 => ['#4f46e5', '#ffffff'],
        4 => ['#0284c7', '#ffffff'],
        5 => ['#059669', '#ffffff'],
        6 => ['#6366f1', '#ffffff'],
    ];
    $pair = $palette[((int) crc32((string) $name)) % count($palette) + 1];
@endphp

<span {{ $attributes->class(['jhs-avatar']) }} style="background: {{ $pair[0] }}; color: {{ $pair[1] }}">
    {{ collect(preg_split('/\s+/', trim((string) $name)))->filter()->take(2)->map(fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)))->implode('') ?: '?' }}
</span>

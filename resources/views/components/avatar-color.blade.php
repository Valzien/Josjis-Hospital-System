@php
    $palette = [
        1 => ['#0d6efd', '#0d9488'],
        2 => ['#0d9488', '#22c55e'],
        3 => ['#f59e0b', '#ef4444'],
        4 => ['#8b5cf6', '#0d6efd'],
        5 => ['#ec4899', '#f59e0b'],
        6 => ['#06b6d4', '#0d9488'],
    ];
    $pair = $palette[((int) crc32((string) $name)) % count($palette) + 1];
@endphp

<span {{ $attributes->class(['jhs-avatar']) }} style="background:linear-gradient(135deg, {{ $pair[0] }}, {{ $pair[1] }})">
    {{ collect(preg_split('/\s+/', trim((string) $name)))->filter()->take(2)->map(fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)))->implode('') ?: '?' }}
</span>

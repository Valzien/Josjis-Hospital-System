@props(['name' => null, 'size' => '', 'color' => null])

@php
    $initials = collect(preg_split('/\s+/', trim((string) $name)))
        ->filter()
        ->take(2)
        ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))
        ->implode('');

    $sizeClass = $size ? 'jhs-avatar-'.$size : '';
    $style = $color ? "background:linear-gradient(135deg, {$color}, #0d9488);" : null;
@endphp

<span {{ $attributes->class(['jhs-avatar', $sizeClass]) }} @if ($style) style="{{ $style }}" @endif>
    {{ $initials !== '' ? $initials : '?' }}
</span>

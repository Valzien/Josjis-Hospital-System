@props(['status', 'label' => null, 'icon' => null, 'class' => ''])

@php
    $enum = $status instanceof \BackedEnum ? $status : null;

    if ($enum === null && $status) {
        foreach ([\App\Enums\QueueStatus::class, \App\Enums\PrescriptionStatus::class] as $candidate) {
            $found = $candidate::tryFrom((string) $status);
            if ($found !== null) {
                $enum = $found;
                break;
            }
        }
    }

    $badge = $enum?->badge() ?? 'secondary';
    $text = $label ?? $enum?->label() ?? (is_scalar($status) ? (string) $status : '—');
    $glyph = $icon ?? $enum?->icon() ?? 'bi-dot';
@endphp

<span {{ $attributes->class(['jhs-badge', 'jhs-badge-'.$badge, $class]) }}>
    <i class="bi {{ $glyph }}"></i>{{ $text }}
</span>
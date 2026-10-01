@props([
    'title' => null,
    'subtitle' => null,
    'icon' => null,
    'bodyClass' => '',
    'cardClass' => '',
    'headerless' => false,
])

<div {{ $attributes->class(['jhs-card', $cardClass]) }}>
    @if (! $headerless && ($title || $subtitle || $icon || isset($actions)))
        <div class="jhs-card-header">
            <div>
                @if ($icon)
                    <i class="bi {{ $icon }} text-primary me-1"></i>
                @endif
                <h3 class="jhs-card-title d-inline">{{ $title }}</h3>
                @if ($subtitle)
                    <p class="jhs-card-subtitle">{{ $subtitle }}</p>
                @endif
            </div>
            @if (isset($actions))
                <div class="d-flex align-items-center gap-2 flex-wrap">{{ $actions }}</div>
            @endif
        </div>
    @endif

    <div class="jhs-card-body {{ $bodyClass }}">
        {{ $slot }}
        @if (isset($footer))
            <div class="jhs-card-footer mt-3 mb-0">{{ $footer }}</div>
        @endif
    </div>
</div>

@props([
    'title',
    'description' => null,
    'icon' => null,
])

<div {{ $attributes->class(['jhs-page-header', 'jhs-no-print']) }}>
    <div class="min-w-0">
        <div class="d-flex align-items-center gap-2 mb-1">
            @if ($icon)
                <span class="text-primary fs-4 lh-1"><i class="bi {{ $icon }}"></i></span>
            @endif
            <h1 class="jhs-page-title mb-0">{{ $title }}</h1>
        </div>
        @if ($description)
            <p class="jhs-page-description mb-0">{{ $description }}</p>
        @endif
    </div>
    @isset($actions)
        <div class="jhs-page-actions d-flex align-items-center gap-2 flex-wrap">{{ $actions }}</div>
    @endisset
</div>

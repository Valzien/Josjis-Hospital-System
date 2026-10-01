@props([
    'title',
    'description' => null,
    'icon' => null,
])

<div {{ $attributes->class(['jhs-page-header', 'jhs-no-print']) }}>
    <div>
        @if ($icon)
            <div class="d-flex align-items-center gap-2 mb-1">
                <span class="jhs-stat-icon jhs-icon-soft-primary" style="width:40px;height:40px;font-size:1.1rem;border-radius:12px">
                    <i class="bi {{ $icon }}"></i>
                </span>
            </div>
        @endif
        <h1 class="jhs-page-title">{{ $title }}</h1>
        @if ($description)
            <p class="jhs-page-description">{{ $description }}</p>
        @endif
    </div>
    @isset($actions)
        <div class="jhs-page-actions">{{ $actions }}</div>
    @endisset
</div>

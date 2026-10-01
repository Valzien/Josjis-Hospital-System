@props([
    'icon' => 'bi-inbox',
    'title' => 'Belum ada data',
    'text' => null,
])

<div {{ $attributes->class(['jhs-empty-state']) }}>
    <div class="jhs-empty-icon"><i class="bi {{ $icon }}"></i></div>
    <div class="jhs-empty-title">{{ $title }}</div>
    @if ($text)
        <p class="jhs-empty-text">{{ $text }}</p>
    @endif
    @isset($action)
        <div>{{ $action }}</div>
    @endisset
</div>

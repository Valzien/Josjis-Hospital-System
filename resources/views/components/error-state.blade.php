@props([
    'title' => 'Terjadi kesalahan',
    'text' => 'Silakan coba lagi beberapa saat lagi.',
])

<div {{ $attributes->class(['jhs-error-state']) }}>
    <div class="jhs-empty-icon"><i class="bi bi-exclamation-triangle"></i></div>
    <div class="jhs-empty-title">{{ $title }}</div>
    <p class="jhs-empty-text">{{ $text }}</p>
    <button class="btn btn-sm btn-light border" onclick="window.location.reload()">
        <i class="bi bi-arrow-clockwise me-1"></i>Muat Ulang
    </button>
</div>

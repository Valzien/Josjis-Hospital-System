@props(['current', 'total', 'color' => null])

<div class="d-flex align-items-center gap-2">
    <div class="jhs-progress flex-grow-1" style="min-width:80px">
        <div class="jhs-progress-bar {{ $color }}"
             style="width: {{ $total > 0 ? round(($current / $total) * 100, 1) : 0 }}%"></div>
    </div>
    <span class="fs-8 text-muted-2 fw-600 text-nowrap-2">{{ $current }}/{{ $total }}</span>
</div>

@props([
    'label' => null,
    'items' => [],
    'active' => null,
])

<div class="d-flex align-items-center gap-2 flex-wrap">
    @foreach ($items as $item)
        @php $isActive = $active === $item['value']; @endphp
        <button type="button"
                class="btn btn-sm {{ $isActive ? 'btn-primary' : 'btn-light border' }}"
                data-filter-chip="{{ $item['value'] }}"
                onclick="window.jhsApplyFilter(this)">
            {{ $item['label'] }}
            @if (isset($item['count']))
                <span class="badge text-bg-{{ $isActive ? 'light text-primary' : 'secondary' }} ms-1">{{ $item['count'] }}</span>
            @endif
        </button>
    @endforeach
</div>

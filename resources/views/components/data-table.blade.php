@props([
    'columns' => [],
    'rows' => null,
    'emptyTitle' => 'Belum ada data',
    'emptyText' => null,
    'emptyIcon' => 'bi-inbox',
    'tableClass' => '',
])

@php
    $paginator = $rows instanceof \Illuminate\Contracts\Pagination\Paginator
        || $rows instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator;
    $isEmpty = $rows instanceof \Illuminate\Support\Collection ? $rows->isEmpty() : ($rows === null || (is_countable($rows) && count($rows) === 0));
@endphp

<div {{ $attributes->class(['jhs-card']) }}>
    @if ($columns)
        <div class="jhs-table-wrap">
            <table class="table table-hover jhs-table-clickable align-middle {{ $tableClass }}">
                <thead>
                    <tr>
                        @foreach ($columns as $column)
                            <th class="{{ $column['class'] ?? '' }}">{{ $column['label'] }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @if (trim($slot))
                        {{ $slot }}
                    @else
                        @forelse ($rows instanceof \Illuminate\Support\Enumerable ? $rows : ($rows ?? []) as $row)
                            <tr @if (data_get($row, 'href')) onclick="window.location.href='{{ data_get($row, 'href') }}'" @endif>
                                @foreach ($columns as $column)
                                    <td class="{{ $column['cellClass'] ?? '' }}">{!! data_get($row, $column['key']) ?? '-' !!}</td>
                                @endforeach
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ count($columns) }}">
                                    <x-empty-state :icon="$emptyIcon" :title="$emptyTitle" :text="$emptyText" />
                                </td>
                            </tr>
                        @endforelse
                    @endif
                </tbody>
            </table>
        </div>
    @else
        <div class="jhs-card-body">{{ $slot }}</div>
    @endif

    @if ($paginator && $rows->hasPages())
        <div class="jhs-card-footer d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div class="text-muted-2 fs-7">
                Menampilkan {{ $rows->firstItem() }}&ndash;{{ $rows->lastItem() }} dari {{ $rows->total() }} data
            </div>
            {{ $rows->onEachSide(1)->links('pagination::bootstrap-5') }}
        </div>
    @endif
</div>
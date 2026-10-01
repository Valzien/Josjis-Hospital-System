@extends('layouts.app')

@section('title', 'Antrean Saya')

@section('content')
    <x-page-header title="Antrean Saya" description="Riwayat antrean yang pernah Anda ambil beserta statusnya." icon="bi-list-ol">
        <x-slot:actions>
            @unless ($activeQueue)
                <a href="{{ route('patient.queue.create') }}" class="btn btn-primary btn-sm">
                    <i class="bi bi-plus-lg me-1"></i>Ambil Antrean
                </a>
            @endunless
        </x-slot:actions>
    </x-page-header>

    @if ($activeQueue)
        <div class="alert alert-success d-flex flex-wrap align-items-center justify-content-between gap-2">
            <div>
                <i class="bi bi-check-circle me-1"></i>
                Anda memiliki antrean aktif bernomor <strong>{{ $activeQueue->queue_number }}</strong>
                pada {{ $activeQueue->doctor?->name }}
                ({{ $activeQueue->queue_date?->translatedFormat('d F Y') }}).
            </div>
            <a href="{{ route('patient.queue.show', $activeQueue) }}" class="btn btn-sm btn-success">Lihat Detail</a>
        </div>
    @endif

    <x-card class="mb-3">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-lg-4">
                <label class="form-label small mb-1" for="status">Status</label>
                <select name="status" id="status" class="form-select form-select-sm">
                    <option value="">Semua Status</option>
                    @foreach ($statuses as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-lg-8 d-flex gap-2">
                <button class="btn btn-primary btn-sm" type="submit"><i class="bi bi-funnel me-1"></i>Terapkan</button>
                @if ($filters['status'] ?? null)
                    <a href="{{ route('patient.queue.index') }}" class="btn btn-light border btn-sm">Reset</a>
                @endif
            </div>
        </form>
    </x-card>

    <x-data-table :rows="$queues" :columns="[
        ['label' => 'No. Antrean', 'key' => 'queue_number'],
        ['label' => 'Tanggal', 'key' => 'queue_date'],
        ['label' => 'Dokter', 'key' => 'doctor'],
        ['label' => 'Status', 'key' => 'status'],
    ]" empty-title="Belum ada antrean" empty-icon="bi-list-ol"
       empty-text="Ambil antrean pertama Anda untuk konsultasi.">
        @forelse ($queues as $queue)
            <tr onclick="window.location.href='{{ route('patient.queue.show', $queue) }}'">
                <td><span class="jhs-queue-number">{{ $queue->queue_number }}</span></td>
                <td class="small text-nowrap text-muted-2">{{ $queue->queue_date?->translatedFormat('d M Y') }}</td>
                <td>
                    <div class="small fw-semibold text-truncate">{{ $queue->doctor?->name ?? '-' }}</div>
                    <div class="fs-7 text-muted-2">{{ $queue->doctor?->specialization ?? '-' }}</div>
                </td>
                <td><x-status-badge :status="$queue->status" /></td>
            </tr>
        @empty
            <tr>
                <td colspan="4">
                    <x-empty-state icon="bi-list-ol" title="Belum ada antrean"
                                  text="Ambil antrean pertama Anda untuk konsultasi." />
                </td>
            </tr>
        @endforelse
    </x-data-table>
@endsection
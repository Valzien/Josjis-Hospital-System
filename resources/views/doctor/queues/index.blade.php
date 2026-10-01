@extends('layouts.app')

@section('title', 'Antrean Saya')

@section('content')
    <x-page-header title="Antrean Saya"
                   description="Antrean tanggal {{ \Illuminate\Support\Carbon::parse($date)->translatedFormat('l, d F Y') }}."
                   icon="bi-list-ol">
        <x-slot:actions>
            <form method="GET" class="d-flex gap-2 flex-wrap">
                <input type="date" name="date" value="{{ $date }}" class="form-control form-control-sm" aria-label="Tanggal">
                <select name="status" class="form-select form-select-sm" aria-label="Status">
                    <option value="">Semua Status</option>
                    @foreach ($statuses as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <input type="search" name="q" value="{{ $filters['q'] ?? '' }}" class="form-control form-control-sm"
                       placeholder="Nama pasien" aria-label="Cari">
                <button class="btn btn-light border btn-sm" type="submit">Terapkan</button>
            </form>
        </x-slot:actions>
    </x-page-header>

    <div class="row g-3 mb-3">
        <div class="col-6 col-xl-3">
            <x-stat-card label="Menunggu" :value="$summary['waiting'] ?? 0" icon="bi-hourglass-split" color="warning" />
        </div>
        <div class="col-6 col-xl-3">
            <x-stat-card label="Dipanggil" :value="$summary['called'] ?? 0" icon="bi-megaphone" color="info" />
        </div>
        <div class="col-6 col-xl-3">
            <x-stat-card label="Diperiksa" :value="$summary['in_examination'] ?? 0" icon="bi-clipboard2-pulse" color="primary" />
        </div>
        <div class="col-6 col-xl-3">
            <x-stat-card label="Selesai" :value="$summary['completed'] ?? 0" icon="bi-check2-circle" color="success" />
        </div>
    </div>

    <x-data-table :rows="$queues" :columns="[
        ['label' => 'Nomor', 'key' => 'queue_number'],
        ['label' => 'Pasien', 'key' => 'patient'],
        ['label' => 'Keluhan', 'key' => 'complaint'],
        ['label' => 'Status', 'key' => 'status'],
    ]" empty-title="Tidak ada antrean" empty-icon="bi-list-ol">
        @forelse ($queues as $queue)
            <tr onclick="window.location.href='{{ route('doctor.queues.show', $queue) }}'">
                <td><span class="jhs-queue-number">{{ $queue->queue_number }}</span></td>
                <td>
                    <div class="small fw-semibold text-truncate">{{ $queue->patient->name }}</div>
                    <div class="fs-7 text-muted-2">{{ $queue->patient->medical_record_number }}</div>
                </td>
                <td class="small text-muted-2">{{ \Illuminate\Support\Str::limit($queue->complaint_note ?? '-', 40) }}</td>
                <td><x-status-badge :status="$queue->status" /></td>
            </tr>
        @empty
            <tr>
                <td colspan="4"><x-empty-state icon="bi-list-ol" title="Tidak ada antrean" /></td>
            </tr>
        @endforelse
    </x-data-table>
@endsection
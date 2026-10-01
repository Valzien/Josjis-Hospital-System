@extends('layouts.app')

@section('title', 'Daftar Antrean')

@section('content')
    <x-page-header title="Daftar Antrean"
                   description="Antrean tanggal {{ \Illuminate\Support\Carbon::parse($date)->translatedFormat('l, d F Y') }}."
                   icon="bi-list-ol">
        <x-slot:actions>
            <form method="GET" class="d-flex gap-2 flex-wrap">
                <input type="date" name="date" value="{{ $date }}" class="form-control form-control-sm" aria-label="Tanggal">
                <select name="doctor_id" class="form-select form-select-sm" aria-label="Dokter">
                    <option value="">Semua Dokter</option>
                    @foreach ($doctors as $doctor)
                        <option value="{{ $doctor->id }}" @selected((string) ($filters['doctor_id'] ?? '') === (string) $doctor->id)>{{ $doctor->name }}</option>
                    @endforeach
                </select>
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
            <a href="{{ route('reception.queues.create') }}" class="btn btn-primary btn-sm">
                <i class="bi bi-plus-lg me-1"></i>Buat Antrean
            </a>
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
        ['label' => 'Dokter', 'key' => 'doctor'],
        ['label' => 'Status', 'key' => 'status'],
        ['label' => 'Dibuat Oleh', 'key' => 'createdBy'],
    ]" empty-title="Belum ada antrean" empty-icon="bi-list-ol">
        @forelse ($queues as $queue)
            <tr onclick="window.location.href='{{ route('reception.queues.show', $queue) }}'">
                <td><span class="jhs-queue-number">{{ $queue->queue_number }}</span></td>
                <td>
                    <div class="small fw-semibold text-truncate">{{ $queue->patient->name }}</div>
                    <div class="fs-7 text-muted-2">{{ $queue->patient->medical_record_number }}</div>
                </td>
                <td class="small">{{ $queue->doctor->name }}</td>
                <td><x-status-badge :status="$queue->status" /></td>
                <td class="small text-muted-2">{{ $queue->createdBy?->name ?? $queue->patient->name }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="5"><x-empty-state icon="bi-list-ol" title="Belum ada antrean" /></td>
            </tr>
        @endforelse
    </x-data-table>
@endsection
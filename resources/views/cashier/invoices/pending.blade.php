@extends('layouts.app')

@section('title', 'Kunjungan Siap Ditagih')

@section('content')
    <x-page-header title="Kunjungan Siap Ditagih"
                   description="Kunjungan dengan rekam medis yang belum memiliki invoice."
                   icon="bi-clipboard2-check">
        <x-slot:actions>
            <a href="{{ route('cashier.invoices.index') }}" class="btn btn-light border btn-sm">
                <i class="bi bi-receipt me-1"></i>Semua Tagihan
            </a>
        </x-slot:actions>
    </x-page-header>

    <x-card class="mb-3">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-5 col-lg-4">
                <label class="form-label small mb-1" for="q">Cari Pasien</label>
                <input type="search" name="q" id="q" value="{{ $filters['q'] ?? '' }}" class="form-control form-control-sm"
                       placeholder="Nama pasien atau No. RM" aria-label="Cari" data-search-submit>
            </div>
            <div class="col-md-3 col-lg-2">
                <label class="form-label small mb-1" for="date">Tanggal Kunjungan</label>
                <input type="date" name="date" id="date" value="{{ $filters['date'] ?? '' }}" class="form-control form-control-sm" aria-label="Tanggal">
            </div>
            <div class="col-md-3 col-lg-3 d-flex gap-2">
                <button class="btn btn-primary btn-sm flex-grow-1" type="submit"><i class="bi bi-funnel me-1"></i>Terapkan</button>
                <a href="{{ route('cashier.invoices.pending') }}" class="btn btn-light border btn-sm">Reset</a>
            </div>
        </form>
    </x-card>

    <x-data-table :rows="$queues" :columns="[
        ['label' => 'Antrean', 'key' => 'queue'],
        ['label' => 'Pasien', 'key' => 'patient'],
        ['label' => 'Dokter', 'key' => 'doctor'],
        ['label' => 'Resep', 'key' => 'prescription'],
        ['label' => 'Aksi', 'class' => 'text-end'],
    ]" empty-title="Tidak ada kunjungan menunggu tagihan" empty-icon="bi-check2-circle"
       empty-text="Semua kunjungan dengan rekam medis sudah memiliki tagihan.">
        @forelse ($queues as $queue)
            <tr>
                <td>
                    <div class="small fw-semibold">{{ $queue->queue_number }}</div>
                    <div class="fs-7 text-muted-2 text-nowrap">{{ $queue->queue_date?->translatedFormat('d M Y') }}</div>
                </td>
                <td>
                    <div class="small fw-semibold text-truncate">{{ $queue->patient?->name ?? '-' }}</div>
                    <div class="fs-7 text-muted-2">{{ $queue->patient?->medical_record_number ?? '-' }}</div>
                </td>
                <td class="small text-truncate">{{ $queue->doctor?->name ?? '-' }}</td>
                <td>
                    @if ($queue->medicalRecord?->prescription)
                        <x-status-badge :status="$queue->medicalRecord->prescription->status" />
                    @else
                        <span class="fs-7 text-muted-2">Tanpa resep</span>
                    @endif
                </td>
                <td class="text-end">
                    <a href="{{ route('cashier.invoices.create', ['queue_id' => $queue->id]) }}" class="btn btn-sm btn-primary">
                        <i class="bi bi-plus-lg me-1"></i>Buat Tagihan
                    </a>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="5">
                    <x-empty-state icon="bi-check2-circle" title="Tidak ada kunjungan menunggu tagihan"
                                  text="Semua kunjungan dengan rekam medis sudah memiliki tagihan." />
                </td>
            </tr>
        @endforelse
    </x-data-table>
@endsection

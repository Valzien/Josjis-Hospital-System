@extends('layouts.app')

@section('title', 'Daftar Antrean')

@section('content')
    <x-page-header title="Daftar Antrean"
                   description="Antrean tanggal {{ \Illuminate\Support\Carbon::parse($date)->translatedFormat('l, d F Y') }}."
                   icon="bi-list-ol">
        <x-slot:actions>
            <a href="{{ route('reception.queues.create') }}" class="btn btn-primary btn-sm">
                <i class="bi bi-plus-lg me-1"></i>Buat Antrean
            </a>
        </x-slot:actions>
    </x-page-header>

    @if (($staleCount ?? 0) > 0)
        <div class="alert alert-warning d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
            <div>
                <i class="bi bi-clock-history me-1"></i>
                Terdapat <strong>{{ $staleCount }}</strong> antrean dari hari kemarin/lampau yang belum diselesaikan atau dibatalkan.
            </div>
            <form method="POST" action="{{ route('reception.queues.close-stale') }}">
                @csrf
                <button type="submit" class="btn btn-sm btn-warning" data-confirm-title="Tutup Antrean Lampau"
                        data-confirm-message="Semua antrean sebelum hari ini yang masih berstatus menunggu/dipanggil akan ditandai dibatalkan (jam layanan berakhir). Lanjutkan?">
                    <i class="bi bi-check-all me-1"></i>Tutup Antrean Lampau
                </button>
            </form>
        </div>
    @endif

    <x-card class="mb-3">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-2 col-lg-2">
                <label class="form-label small mb-1" for="date">Tanggal</label>
                <input type="date" name="date" id="date" value="{{ $date }}" class="form-control form-control-sm" aria-label="Tanggal">
            </div>
            <div class="col-md-3 col-lg-3">
                <label class="form-label small mb-1" for="doctor_id">Dokter</label>
                <select name="doctor_id" id="doctor_id" class="form-select form-select-sm" aria-label="Dokter">
                    <option value="">Semua Dokter</option>
                    @foreach ($doctors as $doctor)
                        <option value="{{ $doctor->id }}" @selected((string) ($filters['doctor_id'] ?? '') === (string) $doctor->id)>{{ $doctor->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2 col-lg-2">
                <label class="form-label small mb-1" for="status">Status</label>
                <select name="status" id="status" class="form-select form-select-sm" aria-label="Status">
                    <option value="">Semua Status</option>
                    @foreach ($statuses as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3 col-lg-3">
                <label class="form-label small mb-1" for="q">Nama Pasien</label>
                <input type="search" name="q" id="q" value="{{ $filters['q'] ?? '' }}" class="form-control form-control-sm"
                       placeholder="Nama pasien" aria-label="Cari" data-search-submit>
            </div>
            <div class="col-md-2 col-lg-2 d-flex gap-2">
                <button class="btn btn-primary btn-sm flex-grow-1" type="submit"><i class="bi bi-funnel me-1"></i>Terapkan</button>
                <a href="{{ route('reception.queues.index') }}" class="btn btn-light border btn-sm">Reset</a>
            </div>
        </form>
    </x-card>

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
        ['label' => 'Aksi Cepat', 'class' => 'text-end'],
    ]" empty-title="Belum ada antrean" empty-icon="bi-list-ol">
        @forelse ($queues as $queue)
            <tr>
                <td>
                    <a href="{{ route('reception.queues.show', $queue) }}" class="text-decoration-none">
                        <span class="jhs-queue-number">{{ $queue->queue_number }}</span>
                    </a>
                </td>
                <td>
                    <div class="small fw-semibold text-truncate">{{ $queue->patient->name }}</div>
                    <div class="fs-7 text-muted-2">{{ $queue->patient->medical_record_number }}</div>
                </td>
                <td class="small">{{ $queue->doctor->name }}</td>
                <td><x-status-badge :status="$queue->status" /></td>
                <td class="small text-muted-2">{{ $queue->createdBy?->name ?? $queue->patient->name }}</td>
                <td class="text-end">
                    <div class="d-inline-flex gap-1" onclick="event.stopPropagation();">
                        @if ($queue->isWaiting())
                            <form method="POST" action="{{ route('reception.queues.call', $queue) }}" class="d-inline">
                                @csrf @method('PATCH')
                                <button type="submit" class="btn btn-sm btn-primary" title="Panggil Antrean">
                                    <i class="bi bi-megaphone"></i>
                                </button>
                            </form>
                        @elseif ($queue->status === \App\Enums\QueueStatus::Called)
                            <form method="POST" action="{{ route('reception.queues.recall', $queue) }}" class="d-inline">
                                @csrf @method('PATCH')
                                <button type="submit" class="btn btn-sm btn-warning text-dark" title="Panggil Ulang">
                                    <i class="bi bi-megaphone-fill"></i>
                                </button>
                            </form>
                        @endif
                        <a href="{{ route('reception.queues.ticket', $queue) }}" target="_blank"
                           class="btn btn-sm btn-light border" title="Cetak Karcis Antrean">
                            <i class="bi bi-printer"></i>
                        </a>
                        <a href="{{ route('reception.queues.show', $queue) }}"
                           class="btn btn-sm btn-light border" title="Lihat Detail">
                            <i class="bi bi-eye"></i>
                        </a>
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="6"><x-empty-state icon="bi-list-ol" title="Belum ada antrean" /></td>
            </tr>
        @endforelse
    </x-data-table>
@endsection

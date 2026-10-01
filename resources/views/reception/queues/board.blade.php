@extends('layouts.app')

@section('title', 'Papan Antrean')

@section('content')
    <x-page-header title="Papan Antrean" description="Pantau dan panggil antrean per dokter." icon="bi-broadcast">
        <x-slot:actions>
            <form method="GET" class="d-flex gap-2 flex-wrap">
                <input type="date" name="date" value="{{ $date }}" class="form-control form-control-sm" aria-label="Tanggal">
                <select name="doctor_id" class="form-select form-select-sm" aria-label="Dokter">
                    <option value="">Semua Dokter</option>
                    @foreach ($doctors as $doctor)
                        <option value="{{ $doctor->id }}" @selected((string) $selectedDoctor === (string) $doctor->id)>{{ $doctor->name }}</option>
                    @endforeach
                </select>
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

    <div class="row g-3">
        <div class="col-xl-4">
            <x-card title="Sedang Dilayani" icon="bi-person-check">
                <div class="vstack gap-2">
                    @forelse ($nowServing as $item)
                        <div class="d-flex align-items-center justify-content-between gap-2 border rounded-3 p-2">
                            <div class="min-w-0">
                                <div class="fw-bold">{{ $item->queue_number }}</div>
                                <div class="small text-truncate">{{ $item->patient->name }}</div>
                                <div class="fs-7 text-muted-2">{{ $item->doctor->name }}</div>
                            </div>
                            <x-status-badge :status="$item->status" />
                        </div>
                    @empty
                        <x-empty-state icon="bi-hourglass" title="Belum ada yang dipanggil"
                                       text="Gunakan tombol Panggil pada antrean berikutnya." />
                    @endforelse
                </div>
            </x-card>
        </div>

        <div class="col-xl-8">
            <x-card title="Daftar Antrean" icon="bi-list-ol">
                <div class="table-responsive">
                    <table class="table table-hover align-middle jhs-table-compact">
                        <thead>
                            <tr>
                                <th class="text-muted-2">#</th>
                                <th>Nomor</th>
                                <th>Pasien</th>
                                <th>Dokter</th>
                                <th>Status</th>
                                <th class="text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($queues as $item)
                                <tr>
                                    <td class="small text-muted-2">{{ $loop->iteration }}</td>
                                    <td class="fw-bold">{{ $item->queue_number }}</td>
                                    <td class="small">{{ $item->patient->name }}</td>
                                    <td class="small">{{ $item->doctor->name }}</td>
                                    <td><x-status-badge :status="$item->status" /></td>
                                    <td class="text-end">
                                        <a href="{{ route('reception.queues.show', $item) }}" class="btn btn-sm btn-light border">Detail</a>
                                        @if ($item->isWaiting())
                                            <form method="POST" action="{{ route('reception.queues.call', $item) }}" class="d-inline">
                                                @csrf @method('PATCH')
                                                <button class="btn btn-sm btn-primary" type="submit">Panggil</button>
                                            </form>
                                        @elseif ($item->status === \App\Enums\QueueStatus::Cancelled)
                                            <form method="POST" action="{{ route('reception.queues.restore', $item) }}" class="d-inline">
                                                @csrf @method('PATCH')
                                                <button class="btn btn-sm btn-light border" type="submit">Pulihkan</button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6">
                                        <x-empty-state icon="bi-inbox" title="Belum ada antrean"
                                                       text="Belum ada antrean pada tanggal ini." />
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-card>
        </div>
    </div>
@endsection
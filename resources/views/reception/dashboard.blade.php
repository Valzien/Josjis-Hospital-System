@extends('layouts.app')

@section('title', 'Dashboard Resepsionis')

@section('content')
    <x-page-header title="Dashboard Resepsionis"
                   description="Ringkasan pendaftaran dan antrean untuk {{ now()->translatedFormat('l, d F Y') }}."
                   icon="bi-clipboard2-pulse">
        <x-slot:actions>
            <a href="{{ route('reception.registration.create') }}" class="btn btn-primary btn-sm">
                <i class="bi bi-person-plus me-1"></i>Daftarkan Pasien
            </a>
            <a href="{{ route('reception.queues.create') }}" class="btn btn-light border btn-sm">
                <i class="bi bi-plus-lg me-1"></i>Buat Antrean
            </a>
        </x-slot:actions>
    </x-page-header>

    <div class="row g-3 mb-3">
        <div class="col-6 col-xl-3">
            <x-stat-card label="Total Antrean Hari Ini" :value="$totalToday" icon="bi-list-ol" color="primary"
                         :href="route('reception.queues.index')" />
        </div>
        <div class="col-6 col-xl-3">
            <x-stat-card label="Menunggu" :value="$summary['waiting'] ?? 0" icon="bi-hourglass-split" color="warning"
                         :href="route('reception.queues.board')" />
        </div>
        <div class="col-6 col-xl-3">
            <x-stat-card label="Selesai" :value="$completedToday" icon="bi-check2-circle" color="success"
                         :href="route('reception.queues.board')" />
        </div>
        <div class="col-6 col-xl-3">
            <x-stat-card label="Pasien Baru Hari Ini" :value="$newPatientsToday" icon="bi-person-plus" color="teal"
                         :meta="'Total '.$totalPatients.' pasien'" :href="route('reception.patients.index')" />
        </div>
    </div>

    <div class="row g-3">
        <div class="col-xl-4">
            <x-card title="Sedang Dilayani" icon="bi-person-check">
                <div class="vstack gap-2">
                    @forelse ($nowServing as $queue)
                        <div class="d-flex align-items-center justify-content-between gap-2 border rounded-3 p-2">
                            <div class="min-w-0">
                                <div class="fw-bold">{{ $queue->queue_number }}</div>
                                <div class="small text-truncate">{{ $queue->patient->name }}</div>
                                <div class="fs-7 text-muted-2">{{ $queue->doctor->name }}</div>
                            </div>
                            <x-status-badge :status="$queue->status" />
                        </div>
                    @empty
                        <x-empty-state icon="bi-hourglass" title="Belum ada yang dipanggil" />
                    @endforelse
                </div>
            </x-card>

            <x-card title="Antrean Menunggu" subtitle="10 antrean teratas" icon="bi-hourglass" class="mt-3">
                <x-slot:actions>
                    <a href="{{ route('reception.queues.board') }}" class="btn btn-sm btn-light border">Papan Antrean</a>
                </x-slot:actions>
                <div class="vstack gap-2">
                    @forelse ($waitingQueues as $queue)
                        <div class="d-flex align-items-center justify-content-between gap-2">
                            <div class="d-flex align-items-center gap-2 min-w-0">
<span class="jhs-queue-number">{{ $queue->queue_number }}</span>
                            <div class="min-w-0">
                                <div class="small fw-semibold text-truncate">{{ $queue->patient->name }}</div>
                                <div class="fs-7 text-muted-2">{{ $queue->doctor->name }}</div>
                            </div>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            @if ($position = $queue->position())
                                <span class="fs-7 text-muted-2 text-nowrap">Posisi {{ $position }}</span>
                            @endif
                        </div>
                        </div>
                    @empty
                        <x-empty-state icon="bi-check2-all" title="Tidak ada antrean menunggu" />
                    @endforelse
                </div>
            </x-card>
        </div>

        <div class="col-xl-8">
            <x-card title="Jadwal Dokter Hari Ini" icon="bi-calendar-week">
                <x-slot:actions>
                    <a href="{{ route('reception.schedules.index') }}" class="btn btn-sm btn-light border">Semua Jadwal</a>
                </x-slot:actions>
                <div class="table-responsive">
                    <table class="table table-hover align-middle jhs-table-compact">
                        <thead>
                            <tr>
                                <th>Dokter</th>
                                <th>Jam</th>
                                <th>Kuota</th>
                                <th>Sisa</th>
                                <th>Selesai</th>
                                <th class="text-end">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($todaySchedules as $row)
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <x-avatar :name="$row['schedule']->doctor->name" size="sm" />
                                            <div class="min-w-0">
                                                <div class="small fw-semibold text-truncate">{{ $row['schedule']->doctor->name }}</div>
                                                <div class="fs-7 text-muted-2">{{ $row['schedule']->doctor->specialization }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="small text-nowrap">{{ $row['schedule']->timeRange() }}</td>
                                    <td class="small">{{ $row['schedule']->quota }}</td>
                                    <td class="small">
                                        <x-progress :current="$row['schedule']->quota - $row['remaining']" :total="$row['schedule']->quota"
                                                    color="{{ $row['remaining'] > 0 ? 'bg-primary' : 'bg-danger' }}" />
                                    </td>
                                    <td class="small">{{ $row['done'] }}</td>
                                    <td class="text-end">
                                        <x-badge :text="$row['schedule']->isOpen() ? 'Buka' : 'Tutup'"
                                                 :icon="$row['schedule']->isOpen() ? 'bi-unlock' : 'bi-lock'"
                                                 :color="$row['schedule']->isOpen() ? 'success' : 'secondary'" />
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6">
                                        <x-empty-state icon="bi-calendar-x" title="Tidak ada jadwal hari ini"
                                                       text="Dokter belum memiliki jadwal aktif untuk hari ini." />
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
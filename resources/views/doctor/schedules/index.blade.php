@extends('layouts.app')

@section('title', 'Jadwal Praktik')

@section('content')
    <x-page-header title="Jadwal Praktik Saya"
                   description="Jadwal praktik, kuota antrean, dan ruang konsultasi yang assigned untuk Anda."
                   icon="bi-calendar-week">
        <x-slot:actions>
            <span class="badge bg-light border">
                <i class="bi bi-calendar-day me-1"></i>{{ now()->translatedFormat('l, d F Y') }}
            </span>
        </x-slot:actions>
    </x-page-header>

    <div class="row g-3 mb-3">
        <div class="col-6">
            <x-stat-card label="Antrean Hari Ini" :value="$todayStats['total']" icon="bi-list-ol" color="primary"
                         :href="route('doctor.queues.index')" />
        </div>
        <div class="col-6">
            <x-stat-card label="Selesai Hari Ini" :value="$todayStats['completed']" icon="bi-check2-circle" color="success"
                         :href="route('doctor.queues.index', ['status' => 'completed'])" />
        </div>
    </div>

    <x-card title="Jadwal Mingguan" subtitle="{{ $schedules->count() }} jadwal terdaftar" icon="bi-calendar-week">
        <div class="table-responsive">
            <table class="table table-hover align-middle jhs-table-compact">
                <thead>
                    <tr>
                        <th>Hari</th>
                        <th>Jam Praktik</th>
                        <th class="text-center">Kuota</th>
                        <th>Ruang</th>
                        <th class="text-end">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($schedules as $schedule)
                        <tr>
                            <td class="small fw-semibold">{{ $schedule->day?->label() ?? '-' }}</td>
                            <td class="small text-nowrap">{{ $schedule->timeRange() }}</td>
                            <td class="small text-center">{{ $schedule->quota }} pasien</td>
                            <td class="small">{{ $schedule->room ?? '-' }}</td>
                            <td class="text-end">
                                @if ($schedule->status?->value === 'active')
                                    <x-badge text="Aktif" icon="bi-check-circle-fill" color="success" />
                                @else
                                    <x-badge text="Nonaktif" icon="bi-pause-circle-fill" color="secondary" />
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5">
                                <x-empty-state icon="bi-calendar-x" title="Belum ada jadwal"
                                               text="Jadwal praktik ditetapkan oleh administrator." />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-card>
@endsection
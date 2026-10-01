@extends('layouts.app')

@section('title', 'Detail Dokter')

@section('content')
    <x-page-header :title="$doctor->name" :description="$doctor->specialization.' &middot; '.$doctor->doctor_code" icon="bi-heart-pulse">
        <x-slot:actions>
            <a href="{{ route('admin.doctors.edit', $doctor) }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-pencil me-1"></i>Ubah
            </a>
            <a href="{{ route('admin.schedules.create', ['doctor_id' => $doctor->id]) }}" class="btn btn-primary btn-sm">
                <i class="bi bi-calendar-plus me-1"></i>Tambah Jadwal
            </a>
        </x-slot:actions>
    </x-page-header>

    <div class="row g-3 mb-3">
        <div class="col-6 col-xl-3">
            <x-stat-card label="Total Pemeriksaan" :value="$doctor->medical_records_count" icon="bi-clipboard2-pulse" color="primary" />
        </div>
        <div class="col-6 col-xl-3">
            <x-stat-card label="Resep Dibuat" :value="$prescriptionCount" icon="bi-file-earmark-medical" color="success" />
        </div>
        <div class="col-6 col-xl-3">
            <x-stat-card label="Total Antrean" :value="$doctor->queues_count" icon="bi-list-ol" color="info" />
        </div>
        <div class="col-6 col-xl-3">
            <x-stat-card label="Jadwal Praktik" :value="$doctor->schedules->count()" icon="bi-calendar-week" color="warning" />
        </div>
    </div>

    <div class="row g-3">
        <div class="col-xl-4">
            <x-card title="Profil" icon="bi-person-vcard">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <x-avatar :name="$doctor->name" size="xl" />
                    <div>
                        <div class="fw-bold">{{ $doctor->name }}</div>
                        <div class="small text-muted-2">{{ $doctor->specialization }}</div>
                        <div class="mt-1">
                            <x-badge :text="$doctor->status->label()"
                                    :color="$doctor->isActive() ? 'success' : 'secondary'"
                                    :icon="$doctor->isActive() ? 'bi-check-circle-fill' : 'bi-slash-circle'" />
                        </div>
                    </div>
                </div>

                <dl class="row small mb-0">
                    <dt class="col-4 text-muted-2 fw-normal">Kode</dt>
                    <dd class="col-8">{{ $doctor->doctor_code }}</dd>
                    <dt class="col-4 text-muted-2 fw-normal">Telepon</dt>
                    <dd class="col-8">{{ $doctor->phone ?? '-' }}</dd>
                    <dt class="col-4 text-muted-2 fw-normal">Email</dt>
                    <dd class="col-8">{{ $doctor->email ?? '-' }}</dd>
                    <dt class="col-4 text-muted-2 fw-normal">Akun</dt>
                    <dd class="col-8">{{ $doctor->user?->email ?? 'Belum ditautkan' }}</dd>
                </dl>

                @if ($doctor->bio)
                    <p class="small text-muted-2 mt-3 mb-0">{{ $doctor->bio }}</p>
                @endif
            </x-card>
        </div>

        <div class="col-xl-8">
            <x-card title="Jadwal Praktik" icon="bi-calendar-week" class="mb-3">
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Hari</th>
                                <th>Waktu</th>
                                <th>Ruang</th>
                                <th>Kuota</th>
                                <th>Status</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($doctor->schedules as $schedule)
                                <tr>
                                    <td class="small fw-semibold">{{ $schedule->dayLabel() }}</td>
                                    <td class="small">{{ $schedule->timeRange() }}</td>
                                    <td class="small">{{ $schedule->room ?? '-' }}</td>
                                    <td class="small">{{ $schedule->quota }}</td>
                                    <td>
                                        <x-badge :text="$schedule->status->label()"
                                                :color="$schedule->isActive() ? 'success' : 'secondary'"
                                                :icon="$schedule->isActive() ? 'bi-check-circle-fill' : 'bi-slash-circle'" />
                                    </td>
                                    <td class="text-end">
                                        <a href="{{ route('admin.schedules.edit', $schedule) }}" class="btn btn-sm btn-light border">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6">
                                        <x-empty-state icon="bi-calendar-x" title="Belum ada jadwal"
                                                       text="Tambahkan jadwal agar pasien dapat mengambil antrean." />
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-card>

            <x-card title="Pemeriksaan Terakhir" icon="bi-clipboard2-pulse" class="mb-3">
                <ul class="list-unstyled mb-0">
                    @forelse ($records as $record)
                        <li class="d-flex justify-content-between align-items-center py-2 border-bottom">
                            <div class="d-flex align-items-center gap-2 min-w-0">
                                <x-avatar :name="$record->patient->name" size="sm" />
                                <div class="min-w-0">
                                    <div class="small fw-semibold text-truncate">{{ $record->patient->name }}</div>
                                    <div class="fs-7 text-muted-2 text-truncate">{{ $record->chief_complaint ?? '-' }}</div>
                                </div>
                            </div>
                            <div class="small text-muted-2 text-nowrap">{{ $record->examined_at?->translatedFormat('d M Y') }}</div>
                        </li>
                    @empty
                        <li><x-empty-state icon="bi-clipboard2-pulse" title="Belum ada pemeriksaan" /></li>
                    @endforelse
                </ul>
            </x-card>

            <x-card title="Antrean Terakhir" icon="bi-list-ol">
                <ul class="list-unstyled mb-0">
                    @forelse ($recentQueues as $queue)
                        <li class="d-flex justify-content-between align-items-center py-2 border-bottom">
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge text-bg-primary">{{ $queue->queue_number }}</span>
                                <span class="small text-truncate">{{ $queue->patient->name }}</span>
                            </div>
                            <x-status-badge :status="$queue->status" />
                        </li>
                    @empty
                        <li><x-empty-state icon="bi-list-ol" title="Belum ada antrean" /></li>
                    @endforelse
                </ul>
            </x-card>
        </div>
    </div>
@endsection
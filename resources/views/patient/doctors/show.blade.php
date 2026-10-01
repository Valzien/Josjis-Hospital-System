@extends('layouts.app')

@section('title', $doctor->name)

@section('content')
    <x-page-header :title="$doctor->name" :description="$doctor->specialization" icon="bi-heart-pulse">
        <x-slot:actions>
            <a href="{{ route('patient.doctors.index') }}" class="btn btn-light border btn-sm">
                <i class="bi bi-arrow-left me-1"></i>Daftar Dokter
            </a>
            <a href="{{ route('patient.queue.create', ['doctor_id' => $doctor->id]) }}" class="btn btn-primary btn-sm">
                <i class="bi bi-ticket-perforated me-1"></i>Ambil Antrean
            </a>
        </x-slot:actions>
    </x-page-header>

    <div class="row g-3">
        <div class="col-xl-4">
            <x-card class="text-center">
                <span class="jhs-avatar jhs-avatar-xl mx-auto mb-2">{{ $doctor->initials() }}</span>
                <div class="fw-semibold">{{ $doctor->name }}</div>
                <div class="fs-7 text-muted-2 mb-2">{{ $doctor->specialization }}</div>
                <x-badge text="Aktif" icon="bi-check-circle-fill" color="success" />

                @if ($doctor->bio)
                    <p class="small text-muted-2 mt-3 mb-0">{{ $doctor->bio }}</p>
                @endif

                <dl class="row mb-0 small mt-3 pt-3 border-top">
                    @if ($doctor->phone)
                        <dt class="col-5 text-muted-2 fw-normal">Telepon</dt>
                        <dd class="col-7">{{ $doctor->phone }}</dd>
                    @endif
                    @if ($doctor->email)
                        <dt class="col-5 text-muted-2 fw-normal">Email</dt>
                        <dd class="col-7 text-truncate">{{ $doctor->email }}</dd>
                    @endif
                    <dt class="col-5 text-muted-2 fw-normal">Jadwal Aktif</dt>
                    <dd class="col-7">{{ $doctor->schedules->count() }} per minggu</dd>
                </dl>
            </x-card>

            <x-card title="Statistik" icon="bi-graph-up" class="mt-3">
                <div class="row g-3">
                    <div class="col-6">
                        <div class="fs-7 text-muted-2">Antrean Menunggu</div>
                        <div class="fs-5 fw-bold">{{ $waitingToday }}</div>
                        <div class="fs-8 text-muted-2">hari ini</div>
                    </div>
                    <div class="col-6">
                        <div class="fs-7 text-muted-2">Riwayat Anda</div>
                        <div class="fs-5 fw-bold">{{ $myHistory }}</div>
                        <div class="fs-8 text-muted-2">kali diperiksa</div>
                    </div>
                </div>
            </x-card>
        </div>

        <div class="col-xl-8">
            <x-card title="Jadwal Praktik" icon="bi-calendar-week">
                @if ($doctor->schedules->isEmpty())
                    <x-empty-state icon="bi-calendar-x" title="Belum ada jadwal aktif" />
                @else
                    <div class="vstack gap-2">
                        @foreach ($doctor->schedules as $schedule)
                            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 border rounded-3 p-2">
                                <div class="min-w-0">
                                    <div class="small fw-semibold">{{ $schedule->dayLabel() }}</div>
                                    <div class="fs-7 text-muted-2">
                                        {{ $schedule->timeRange() }} &middot; Kuota {{ $schedule->quota }} antrean
                                        @if ($schedule->room) &middot; {{ $schedule->room }} @endif
                                    </div>
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    <x-badge :text="$schedule->isOpen() ? 'Buka' : 'Tutup'"
                                             :icon="$schedule->isOpen() ? 'bi-unlock' : 'bi-lock'"
                                             :color="$schedule->isOpen() ? 'success' : 'secondary'" />
                                    @if ($schedule->isToday())
                                        <a href="{{ route('patient.queue.create', ['doctor_id' => $doctor->id]) }}"
                                           class="btn btn-sm btn-primary">Ambil Antrean</a>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </x-card>

            <x-card title="Riwayat Anda dengan Dokter Ini" icon="bi-journal-medical" class="mt-3">
                @if ($myHistory > 0)
                    <a href="{{ route('patient.history.index') }}" class="btn btn-sm btn-light border">
                        <i class="bi bi-journal-text me-1"></i>Lihat {{ $myHistory }} Riwayat Pemeriksaan
                    </a>
                @else
                    <x-empty-state icon="bi-journal-medical" title="Belum ada riwayat"
                                  text="Anda belum pernah diperiksa oleh dokter ini." />
                @endif
            </x-card>
        </div>
    </div>
@endsection
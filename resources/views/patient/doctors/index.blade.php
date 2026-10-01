@extends('layouts.app')

@section('title', 'Dokter')

@section('content')
    <x-page-header title="Dokter" description="Lihat dokter yang tersedia beserta jadwal praktiknya." icon="bi-heart-pulse">
        <x-slot:actions>
            <a href="{{ route('patient.schedules.index') }}" class="btn btn-light border btn-sm">
                <i class="bi bi-calendar-week me-1"></i>Jadwal Mingguan
            </a>
        </x-slot:actions>
    </x-page-header>

    <x-card class="mb-3">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-lg-5">
                <label class="form-label small mb-1" for="q">Cari Dokter</label>
                <input type="search" name="q" id="q" value="{{ $filters['q'] ?? '' }}"
                       class="form-control form-control-sm" placeholder="Nama dokter atau spesialisasi" data-search-submit>
            </div>
            <div class="col-lg-4">
                <label class="form-label small mb-1" for="specialization">Spesialisasi</label>
                <select name="specialization" id="specialization" class="form-select form-select-sm">
                    <option value="">Semua Spesialisasi</option>
                    @foreach ($specializations as $specialization)
                        <option value="{{ $specialization }}" @selected(($filters['specialization'] ?? '') === $specialization)>{{ $specialization }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-lg-3 d-flex gap-2 align-items-center">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="available_today" value="1" id="available_today"
                           @checked($filters['available_today'] ?? false)>
                    <label class="form-check-label small" for="available_today">Bertugas hari ini</label>
                </div>
                <button class="btn btn-primary btn-sm ms-auto" type="submit"><i class="bi bi-funnel me-1"></i>Terapkan</button>
            </div>
        </form>
    </x-card>

    <div class="row g-3">
        @forelse ($doctors as $doctor)
            <div class="col-md-6 col-xl-4">
                <x-card class="h-100">
                    <div class="d-flex align-items-start gap-3">
                        <span class="jhs-avatar jhs-avatar-lg">{{ $doctor->initials() }}</span>
                        <div class="min-w-0 flex-grow-1">
                            <div class="fw-semibold text-truncate">{{ $doctor->name }}</div>
                            <div class="fs-7 text-muted-2">{{ $doctor->specialization }}</div>
                            @if ($doctor->isActive())
                                <x-badge text="Aktif" icon="bi-check-circle-fill" color="success" class="mt-1" />
                            @endif
                        </div>
                    </div>

                    @if ($doctor->bio)
                        <p class="small text-muted-2 mt-3 mb-2">{{ \Illuminate\Support\Str::limit($doctor->bio, 110) }}</p>
                    @endif

                    @if ($doctor->schedules->isNotEmpty())
                        <div class="fs-8 text-muted-2 mb-1">Jadwal minggu ini</div>
                        <div class="vstack gap-1 mb-3">
                            @foreach ($doctor->schedules->take(3) as $schedule)
                                <div class="d-flex justify-content-between align-items-center small">
                                    <span>{{ $schedule->dayLabel() }}</span>
                                    <span class="text-muted-2">{{ $schedule->timeRange() }}</span>
                                </div>
                            @endforeach
                            @if ($doctor->schedules->count() > 3)
                                <div class="small text-muted-2">+{{ $doctor->schedules->count() - 3 }} jadwal lainnya</div>
                            @endif
                        </div>
                    @else
                        <div class="small text-muted-2 mb-3">Belum ada jadwal aktif.</div>
                    @endif

                    <div class="d-grid gap-2 mt-auto">
                        <a href="{{ route('patient.doctors.show', $doctor) }}" class="btn btn-sm btn-light border">
                            <i class="bi bi-info-circle me-1"></i>Lihat Detail
                        </a>
                        <a href="{{ route('patient.queue.create', ['doctor_id' => $doctor->id]) }}"
                           class="btn btn-sm btn-primary">
                            <i class="bi bi-ticket-perforated me-1"></i>Ambil Antrean
                        </a>
                    </div>
                </x-card>
            </div>
        @empty
            <div class="col-12">
                <x-card>
                    <x-empty-state icon="bi-heart-pulse" title="Dokter tidak ditemukan"
                                  text="Coba ubah kata kunci atau filter spesialisasi." />
                </x-card>
            </div>
        @endforelse
    </div>

    @if ($doctors->hasPages())
        <div class="mt-3">{{ $doctors->links('pagination::bootstrap-5') }}</div>
    @endif
@endsection
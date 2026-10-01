@extends('layouts.app')

@section('title', 'Jadwal Dokter')

@section('content')
    <x-page-header title="Jadwal Dokter" description="Jadwal praktik seluruh dokter untuk membantu Anda memilih hari kunjungan." icon="bi-calendar-week">
        <x-slot:actions>
            <a href="{{ route('patient.doctors.index') }}" class="btn btn-light border btn-sm">
                <i class="bi bi-heart-pulse me-1"></i>Daftar Dokter
            </a>
            <a href="{{ route('patient.queue.create') }}" class="btn btn-primary btn-sm">
                <i class="bi bi-ticket-perforated me-1"></i>Ambil Antrean
            </a>
        </x-slot:actions>
    </x-page-header>

    <div class="row g-3">
        <div class="col-xl-4">
            <x-card title="Keterangan" icon="bi-info-circle">
                <div class="alert alert-light border small mb-3">
                    <i class="bi bi-lightbulb me-1"></i>
                    Antrean hanya dapat diambil pada hari yang sama dengan jadwal praktik dokter.
                </div>

                <div class="fs-7 text-muted-2 mb-2">Legenda Status</div>
                <div class="vstack gap-2 small">
                    <div class="d-flex align-items-center gap-2">
                        <x-badge text="Buka" icon="bi-unlock" color="success" />
                        <span class="text-muted-2">Jam layanan sedang berlangsung.</span>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <x-badge text="Tutup" icon="bi-lock" color="secondary" />
                        <span class="text-muted-2">Di luar jam layanan.</span>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <x-badge text="Sisa N" icon="bi-check-circle-fill" color="info" />
                        <span class="text-muted-2">Kuota antrean yang masih tersedia.</span>
                    </div>
                </div>

                <div class="fs-7 text-muted-2 mb-2 mt-3">Total Jadwal</div>
                <div class="fs-4 fw-bold">{{ $schedules->total() }}</div>
            </x-card>

            <x-card title="Filter" icon="bi-funnel" class="mt-3">
                <form method="GET">
                    <div class="mb-3">
                        <label class="form-label small mb-1" for="specialization">Spesialisasi</label>
                        <select name="specialization" id="specialization" class="form-select form-select-sm">
                            <option value="">Semua Spesialisasi</option>
                            @foreach ($specializations as $specialization)
                                <option value="{{ $specialization }}" @selected(($filters['specialization'] ?? '') === $specialization)>{{ $specialization }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small mb-1" for="doctor_id">Dokter</label>
                        <select name="doctor_id" id="doctor_id" class="form-select form-select-sm">
                            <option value="">Semua Dokter</option>
                            @foreach ($doctors as $doctor)
                                <option value="{{ $doctor->id }}" @selected((string) ($filters['doctor_id'] ?? '') === (string) $doctor->id)>{{ $doctor->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <button class="btn btn-primary btn-sm w-100" type="submit">Terapkan Filter</button>
                </form>
            </x-card>
        </div>

        <div class="col-xl-8">
            <x-card title="Jadwal Mingguan" icon="bi-calendar-week"
                    subtitle="Dokter aktif: {{ count($doctors) }} orang">
                @if ($schedules->isEmpty())
                    <x-empty-state icon="bi-calendar-x" title="Tidak ada jadwal"
                                  text="Tidak ada jadwal yang sesuai dengan filter Anda." />
                @else
                    @foreach ($grouped as $dayLabel => $daySchedules)
                        <div class="mb-3">
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <span class="fw-semibold small">{{ $dayLabel }}</span>
                                @if (\Illuminate\Support\Str::contains($dayLabel, now()->translatedFormat('l')))
                                    <x-badge text="Hari Ini" icon="bi-calendar-check" color="primary" />
                                @endif
                            </div>

                            <div class="vstack gap-2">
                                @foreach ($daySchedules as $schedule)
                                    @php $remaining = $schedule->remainingQuota(); @endphp
                                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 border rounded-3 p-2">
                                        <div class="d-flex align-items-center gap-2 min-w-0">
                                            <span class="jhs-avatar jhs-avatar-sm">{{ $schedule->doctor?->initials() ?? '?' }}</span>
                                            <div class="min-w-0">
                                                <div class="small fw-semibold text-truncate">{{ $schedule->doctor?->name ?? '-' }}</div>
                                                <div class="fs-8 text-muted-2">
                                                    {{ $schedule->timeRange() }}
                                                    @if ($schedule->room) &middot; {{ $schedule->room }} @endif
                                                </div>
                                            </div>
                                        </div>

                                        <div class="d-flex align-items-center gap-2">
                                            <x-badge :text="$schedule->isOpen() ? 'Buka' : 'Tutup'"
                                                     :icon="$schedule->isOpen() ? 'bi-unlock' : 'bi-lock'"
                                                     :color="$schedule->isOpen() ? 'success' : 'secondary'" />
                                            @if ($schedule->queues_count > 0)
                                                <x-badge :text="'Antrean '.$schedule->queues_count"
                                                         icon="bi-people" color="secondary" />
                                            @endif
                                            @if ($schedule->isToday() && $remaining > 0)
                                                <a href="{{ route('patient.queue.create', ['doctor_id' => $schedule->doctor_id]) }}"
                                                   class="btn btn-sm btn-primary">Ambil</a>
                                            @elseif ($remaining > 0)
                                                <x-badge :text="'Sisa '.$remaining" icon="bi-check-circle-fill" color="info" />
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                @endif

                @if ($schedules->hasPages())
                    <div class="mt-3">{{ $schedules->links('pagination::bootstrap-5') }}</div>
                @endif
            </x-card>
        </div>
    </div>
@endsection
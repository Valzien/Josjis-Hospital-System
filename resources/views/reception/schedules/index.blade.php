@extends('layouts.app')

@section('title', 'Jadwal Dokter')

@section('content')
    <x-page-header title="Jadwal Dokter"
                   description="Jadwal praktik, kuota antrean, dan ruang konsultasi dokter."
                   icon="bi-calendar-week">
        <x-slot:actions>
            <a href="{{ route('admin.schedules.index') }}" class="btn btn-light border btn-sm">
                <i class="bi bi-sliders me-1"></i>Kelola Jadwal
            </a>
        </x-slot:actions>
    </x-page-header>

    <x-card class="mb-3">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-lg-2">
                <label class="form-label small mb-1" for="date">Tanggal Acuan</label>
                <input type="date" name="date" value="{{ $date }}" class="form-control form-control-sm">
            </div>
            <div class="col-lg-3">
                <label class="form-label small mb-1" for="doctor_id">Dokter</label>
                <select name="doctor_id" id="doctor_id" class="form-select form-select-sm">
                    <option value="">Semua Dokter</option>
                    @foreach ($doctors as $doctor)
                        <option value="{{ $doctor->id }}" @selected((string) ($filters['doctor_id'] ?? '') === (string) $doctor->id)>{{ $doctor->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-lg-3">
                <label class="form-label small mb-1" for="day">Hari</label>
                <select name="day" id="day" class="form-select form-select-sm">
                    <option value="">Semua Hari</option>
                    @foreach ($days as $value => $label)
                        <option value="{{ $value }}" @selected((string) ($filters['day'] ?? '') === (string) $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-lg-4 d-flex gap-2">
                <button class="btn btn-primary btn-sm" type="submit"><i class="bi bi-funnel me-1"></i>Terapkan</button>
                <a href="{{ route('reception.schedules.index') }}" class="btn btn-light border btn-sm">Reset</a>
            </div>
        </form>
    </x-card>

    <x-card :title="$isToday ? 'Jadwal Hari Ini' : 'Jadwal Mingguan'"
            subtitle="\Illuminate\Support\Carbon::parse($date)->translatedFormat('l, d F Y')"
            icon="bi-calendar-week">
        <div class="table-responsive">
            <table class="table table-hover align-middle jhs-table-compact">
                <thead>
                    <tr>
                        <th>Dokter</th>
                        <th>Hari</th>
                        <th>Jam</th>
                        <th class="text-center">Kuota</th>
                        <th class="text-center">Terisi</th>
                        <th class="text-center">Sisa</th>
                        <th>Ruang</th>
                        <th class="text-end">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($schedules as $schedule)
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <x-avatar :name="$schedule->doctor->name" size="sm" />
                                    <div class="min-w-0">
                                        <div class="small fw-semibold text-truncate">{{ $schedule->doctor->name }}</div>
                                        <div class="fs-7 text-muted-2">{{ $schedule->doctor->specialization }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="small">{{ $schedule->day?->label() ?? '-' }}</td>
                            <td class="small text-nowrap">{{ $schedule->timeRange() }}</td>
                            <td class="small text-center">{{ $schedule->quota }}</td>
                            <td class="small text-center">{{ $schedule->queues_count }}</td>
                            <td class="small text-center fw-semibold">
                                {{ $schedule->remainingQuota() }}
                            </td>
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
                            <td colspan="8">
                                <x-empty-state icon="bi-calendar-x" title="Tidak ada jadwal"
                                               text="Tidak ada jadwal dokter yang cocok dengan filter." />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($schedules->hasPages())
            <div class="mt-3">{{ $schedules->links('pagination::bootstrap-5') }}</div>
        @endif
    </x-card>
@endsection
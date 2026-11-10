@extends('layouts.app')

@section('title', 'Janji Temu')

@section('content')
    <x-page-header title="Janji Temu"
                   description="Daftar booking pasien tanggal {{ \Illuminate\Support\Carbon::parse($date)->translatedFormat('l, d F Y') }}."
                   icon="bi-calendar-check">
        <x-slot:actions>
            <a href="{{ route('reception.queues.index', ['date' => $date]) }}" class="btn btn-light border btn-sm">
                <i class="bi bi-list-ol me-1"></i>Daftar Antrean
            </a>
        </x-slot:actions>
    </x-page-header>

    <div class="alert alert-info d-flex gap-2 mb-3">
        <i class="bi bi-info-circle mt-1"></i>
        <div class="small">
            Tekan <strong>Check-in</strong> saat pasien tiba untuk mengubah janji temu menjadi nomor antrean.
            Check-in hanya tersedia pada tanggal janji temu.
        </div>
    </div>

    <x-card class="mb-3">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-3 col-lg-2">
                <label class="form-label small mb-1" for="date">Tanggal</label>
                <input type="date" name="date" id="date" value="{{ $date }}" class="form-control form-control-sm" aria-label="Tanggal">
            </div>
            <div class="col-md-3 col-lg-2">
                <label class="form-label small mb-1" for="status">Status</label>
                <select name="status" id="status" class="form-select form-select-sm" aria-label="Status">
                    <option value="">Semua Status</option>
                    @foreach ($statuses as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4 col-lg-3">
                <label class="form-label small mb-1" for="q">Cari Pasien / Kode</label>
                <input type="search" name="q" id="q" value="{{ $filters['q'] ?? '' }}" class="form-control form-control-sm"
                       placeholder="Nama, No. RM, atau kode booking" aria-label="Cari" data-search-submit>
            </div>
            <div class="col-md-2 col-lg-2 d-flex gap-2">
                <button class="btn btn-primary btn-sm flex-grow-1" type="submit"><i class="bi bi-funnel me-1"></i>Terapkan</button>
                <a href="{{ route('reception.appointments.index') }}" class="btn btn-light border btn-sm">Reset</a>
            </div>
        </form>
    </x-card>

    <div class="row g-3 mb-3">
        <div class="col-6 col-xl-3">
            <x-stat-card label="Terjadwal" :value="$summary['BOOKED'] ?? 0" icon="bi-calendar-check" color="primary" />
        </div>
        <div class="col-6 col-xl-3">
            <x-stat-card label="Sudah Check-in" :value="$summary['CHECKED_IN'] ?? 0" icon="bi-person-check" color="info" />
        </div>
        <div class="col-6 col-xl-3">
            <x-stat-card label="Selesai" :value="$summary['COMPLETED'] ?? 0" icon="bi-check-circle" color="success" />
        </div>
        <div class="col-6 col-xl-3">
            <x-stat-card label="Tidak Hadir" :value="$summary['NO_SHOW'] ?? 0" icon="bi-person-dash" color="secondary" />
        </div>
    </div>

    <x-data-table :rows="$appointments" :columns="[
        ['label' => 'Jam', 'key' => 'time'],
        ['label' => 'Kode', 'key' => 'code'],
        ['label' => 'Pasien', 'key' => 'patient'],
        ['label' => 'Dokter', 'key' => 'doctor'],
        ['label' => 'Status', 'key' => 'status'],
        ['label' => 'Aksi', 'class' => 'text-end'],
    ]" empty-title="Belum ada janji temu" empty-icon="bi-calendar-x"
       empty-text="Pasien belum membuat booking pada tanggal ini.">
        @forelse ($appointments as $appointment)
            <tr>
                <td class="fw-semibold text-nowrap">{{ $appointment->slotLabel() }}</td>
                <td class="small text-nowrap">{{ $appointment->code }}</td>
                <td>
                    <div class="small fw-semibold text-truncate">{{ $appointment->patient?->name ?? '-' }}</div>
                    <div class="fs-7 text-muted-2">{{ $appointment->patient?->medical_record_number ?? '-' }}</div>
                </td>
                <td>
                    <div class="small text-truncate">{{ $appointment->doctor?->name ?? '-' }}</div>
                    <div class="fs-7 text-muted-2">{{ $appointment->doctorSchedule?->room ?? '-' }}</div>
                </td>
                <td>
                    <x-status-badge :status="$appointment->status" />
                    @if ($appointment->queue)
                        <div class="fs-7 text-muted-2 mt-1">
                            <i class="bi bi-ticket-perforated me-1"></i>{{ $appointment->queue->queue_number }}
                        </div>
                    @endif
                </td>
                <td class="text-end">
                    <div class="d-inline-flex gap-1">
                        @if ($appointment->canCheckIn())
                            <form method="POST" action="{{ route('reception.appointments.check-in', $appointment) }}" class="d-inline">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="btn btn-sm btn-success" title="Check-in & buat antrean"
                                        data-confirm-title="Check-in Pasien"
                                        data-confirm-message="Check-in janji temu {{ $appointment->code }} dan buatkan nomor antrean untuk {{ $appointment->patient?->name }}?">
                                    <i class="bi bi-person-check"></i>
                                </button>
                            </form>
                        @endif

                        @if ($appointment->status === \App\Enums\AppointmentStatus::Booked && $appointment->appointment_date?->lt(now()->startOfDay()))
                            <form method="POST" action="{{ route('reception.appointments.no-show', $appointment) }}" class="d-inline">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="btn btn-sm btn-light border" title="Tandai tidak hadir"
                                        data-confirm-title="Tandai Tidak Hadir"
                                        data-confirm-message="Tandai janji temu {{ $appointment->code }} sebagai tidak hadir?">
                                    <i class="bi bi-person-dash"></i>
                                </button>
                            </form>
                        @endif

                        @if ($appointment->status === \App\Enums\AppointmentStatus::Booked)
                            <form method="POST" action="{{ route('reception.appointments.cancel', $appointment) }}" class="d-inline">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Batalkan janji temu"
                                        data-confirm-title="Batalkan Janji Temu"
                                        data-confirm-message="Batalkan janji temu {{ $appointment->code }} atas nama {{ $appointment->patient?->name }}?">
                                    <i class="bi bi-x-circle"></i>
                                </button>
                            </form>
                        @endif

                        @if ($appointment->complaint_note)
                            <button type="button" class="btn btn-sm btn-light border" title="{{ $appointment->complaint_note }}"
                                    data-bs-toggle="tooltip">
                                <i class="bi bi-chat-left-text"></i>
                            </button>
                        @endif
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="6">
                    <x-empty-state icon="bi-calendar-x" title="Belum ada janji temu"
                                  text="Pasien belum membuat booking pada tanggal ini." />
                </td>
            </tr>
        @endforelse
    </x-data-table>
@endsection

@extends('layouts.app')

@section('title', 'Janji Temu Saya')

@section('content')
    <x-page-header title="Janji Temu Saya" description="Jadwalkan kunjungan Anda secara online, lalu check-in di resepsionis pada hari H." icon="bi-calendar-check">
        <x-slot:actions>
            <a href="{{ route('patient.appointments.create') }}" class="btn btn-primary btn-sm">
                <i class="bi bi-plus-lg me-1"></i>Buat Janji Temu
            </a>
            <a href="{{ route('patient.queue.create') }}" class="btn btn-light border btn-sm">
                <i class="bi bi-ticket-perforated me-1"></i>Antrean Hari Ini
            </a>
        </x-slot:actions>
    </x-page-header>

    <div class="alert alert-info d-flex gap-2 mb-3">
        <i class="bi bi-info-circle mt-1"></i>
        <div class="small">
            Janji temu hanya berupa pemesanan slot waktu. Pada tanggal kunjungan, datang ke resepsionis
            untuk <strong>check-in</strong> agar nomor antrean Anda dibuatkan.
        </div>
    </div>

    <h2 class="h6 fw-bold text-uppercase text-muted-2 mb-2">
        <i class="bi bi-calendar-event me-1"></i>Janji Temu Mendatang
    </h2>

    <div class="row g-3 mb-4">
        @forelse ($upcoming as $appointment)
            <div class="col-lg-6">
                <div class="jhs-card h-100">
                    <div class="jhs-card-body">
                        <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                            <div class="min-w-0">
                                <div class="fs-7 text-muted-2 mb-1">{{ $appointment->code }}</div>
                                <h3 class="h6 fw-bold mb-0 text-truncate">{{ $appointment->doctor?->name ?? 'Dokter' }}</h3>
                                <div class="fs-7 text-muted-2">{{ $appointment->doctor?->specialization ?? '-' }}</div>
                            </div>
                            <x-status-badge :status="$appointment->status" />
                        </div>

                        <div class="row g-2 small mb-3">
                            <div class="col-6">
                                <div class="text-muted-2 fs-7">Tanggal</div>
                                <div class="fw-semibold">{{ $appointment->appointment_date?->translatedFormat('l, d F Y') }}</div>
                            </div>
                            <div class="col-6">
                                <div class="text-muted-2 fs-7">Pukul</div>
                                <div class="fw-semibold">{{ $appointment->slotLabel() }} WIB</div>
                            </div>
                            @if ($appointment->doctorSchedule?->room)
                                <div class="col-6">
                                    <div class="text-muted-2 fs-7">Ruang</div>
                                    <div class="fw-semibold">{{ $appointment->doctorSchedule->room }}</div>
                                </div>
                            @endif
                            @if ($appointment->queue)
                                <div class="col-6">
                                    <div class="text-muted-2 fs-7">Nomor Antrean</div>
                                    <div class="fw-semibold">{{ $appointment->queue->queue_number }}</div>
                                </div>
                            @endif
                        </div>

                        @if ($appointment->complaint_note)
                            <p class="small text-muted-2 mb-3">
                                <i class="bi bi-chat-left-text me-1"></i>{{ $appointment->complaint_note }}
                            </p>
                        @endif

                        <div class="d-flex flex-wrap gap-2">
                            @if ($appointment->queue)
                                <a href="{{ route('patient.queue.show', $appointment->queue) }}" class="btn btn-sm btn-light border">
                                    <i class="bi bi-eye me-1"></i>Lihat Antrean
                                </a>
                            @endif
                            @if ($appointment->canCancel())
                                <form method="POST" action="{{ route('patient.appointments.cancel', $appointment) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="btn btn-sm btn-outline-danger"
                                            data-confirm-title="Batalkan Janji Temu"
                                            data-confirm-message="Janji temu {{ $appointment->code }} akan dibatalkan. Slot yang sudah dipesan tidak dapat digunakan lagi oleh Anda. Lanjutkan?">
                                        <i class="bi bi-x-circle me-1"></i>Batalkan
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <x-card>
                    <x-empty-state icon="bi-calendar-plus" title="Belum ada janji temu mendatang"
                                  text="Buat janji temu agar Anda tidak perlu menunggu lama di rumah sakit.">
                        <x-slot:action>
                            <a href="{{ route('patient.appointments.create') }}" class="btn btn-sm btn-primary mt-2">
                                <i class="bi bi-plus-lg me-1"></i>Buat Janji Temu
                            </a>
                        </x-slot:action>
                    </x-empty-state>
                </x-card>
            </div>
        @endforelse
    </div>

    <h2 class="h6 fw-bold text-uppercase text-muted-2 mb-2">
        <i class="bi bi-clock-history me-1"></i>Riwayat Janji Temu
    </h2>

    <x-data-table :rows="$history" :columns="[
        ['label' => 'Kode', 'key' => 'code'],
        ['label' => 'Tanggal', 'key' => 'date'],
        ['label' => 'Dokter', 'key' => 'doctor'],
        ['label' => 'Status', 'key' => 'status'],
    ]" empty-title="Belum ada riwayat janji temu" empty-icon="bi-calendar-x">
        @forelse ($history as $appointment)
            <tr>
                <td class="small fw-semibold text-nowrap">{{ $appointment->code }}</td>
                <td class="small text-nowrap text-muted-2">
                    {{ $appointment->appointment_date?->translatedFormat('d M Y') }}
                    <span class="d-block fs-7">{{ $appointment->slotLabel() }}</span>
                </td>
                <td>
                    <div class="small fw-semibold text-truncate">{{ $appointment->doctor?->name ?? '-' }}</div>
                    <div class="fs-7 text-muted-2">{{ $appointment->doctor?->specialization ?? '-' }}</div>
                </td>
                <td><x-status-badge :status="$appointment->status" /></td>
            </tr>
        @empty
            <tr>
                <td colspan="4"><x-empty-state icon="bi-calendar-x" title="Belum ada riwayat janji temu" /></td>
            </tr>
        @endforelse
    </x-data-table>
@endsection

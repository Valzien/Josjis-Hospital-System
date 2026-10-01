@extends('layouts.app')

@section('title', 'Antrean '.$queue->queue_number)

@section('content')
    <x-page-header title="Antrean {{ $queue->queue_number }}"
                   description="{{ $queue->patient->name }} &middot; {{ $queue->doctor->name }} &middot; {{ $queue->queue_date?->translatedFormat('l, d F Y') }}"
                   icon="bi-ticket-perforated">
        <x-slot:actions>
            <a href="{{ route('reception.queues.index') }}" class="btn btn-light border btn-sm">
                <i class="bi bi-arrow-left me-1"></i>Daftar Antrean
            </a>
            <a href="{{ route('reception.patients.show', $queue->patient) }}" class="btn btn-light border btn-sm">
                <i class="bi bi-person-vcard me-1"></i>Data Pasien
            </a>
        </x-slot:actions>
    </x-page-header>

    <div class="row g-3">
        <div class="col-xl-4">
            <x-card title="Nomor Antrean" icon="bi-ticket">
                <div class="text-center py-2">
                    <div class="jhs-queue-number jhs-queue-number-lg">{{ $queue->queue_number }}</div>
                    <div class="mt-2"><x-status-badge :status="$queue->status" /></div>

                    @if ($position)
                        <div class="small text-muted-2 mt-2">Posisi antrean: {{ $position }}</div>
                    @endif
                    @if ($queue->estimatedMinutes() !== null)
                        <div class="fs-7 text-muted-2">Estimasi giliran ± {{ $queue->estimatedMinutes() }} menit</div>
                    @endif
                </div>

                <div class="d-grid gap-2 mt-3">
                    @if ($queue->isWaiting())
                        <form method="POST" action="{{ route('reception.queues.call', $queue) }}">
                            @csrf @method('PATCH')
                            <button class="btn btn-primary w-100" type="submit"><i class="bi bi-megaphone me-1"></i>Panggil Pasien</button>
                        </form>
                    @elseif ($queue->isOpen())
                        <form method="POST" action="{{ route('reception.queues.call', $queue) }}">
                            @csrf @method('PATCH')
                            <button class="btn btn-primary w-100" type="submit"><i class="bi bi-clipboard2-pulse me-1"></i>Mulai Pemeriksaan</button>
                        </form>
                    @endif

                    @if ($queue->isOpen())
                        <form method="POST" action="{{ route('reception.queues.cancel', $queue) }}">
                            @csrf @method('PATCH')
                            <button class="btn btn-outline-danger w-100" type="submit" data-confirm-title="Batalkan Antrean"
                                    data-confirm-message="Antrean {{ $queue->queue_number }} akan dibatalkan.">
                                <i class="bi bi-x-circle me-1"></i>Batalkan Antrean
                            </button>
                        </form>
                    @elseif ($queue->status === \App\Enums\QueueStatus::Cancelled)
                        <form method="POST" action="{{ route('reception.queues.restore', $queue) }}">
                            @csrf @method('PATCH')
                            <button class="btn btn-light border w-100" type="submit"><i class="bi bi-arrow-counterclockwise me-1"></i>Pulihkan</button>
                        </form>
                    @endif
                </div>
            </x-card>

            <x-card title="Detail Antrean" icon="bi-info-circle" class="mt-3">
                <dl class="row mb-0 small">
                    <dt class="col-5 text-muted-2 fw-normal">Tanggal</dt>
                    <dd class="col-7">{{ $queue->queue_date?->translatedFormat('d M Y') }}</dd>

                    <dt class="col-5 text-muted-2 fw-normal">Jadwal</dt>
                    <dd class="col-7">{{ $queue->doctorSchedule?->timeRange() ?? '-' }}</dd>

                    <dt class="col-5 text-muted-2 fw-normal">Dibuat</dt>
                    <dd class="col-7">{{ $queue->created_at?->translatedFormat('d M Y H:i') }}</dd>

                    <dt class="col-5 text-muted-2 fw-normal">Dibuat Oleh</dt>
                    <dd class="col-7">{{ $queue->createdBy?->name ?? ($queue->patient->user?->name ?? 'Sistem') }}</dd>

                    <dt class="col-5 text-muted-2 fw-normal">Dipanggil</dt>
                    <dd class="col-7">{{ $queue->called_at?->translatedFormat('d M Y H:i') ?? '-' }}</dd>

                    <dt class="col-5 text-muted-2 fw-normal">Selesai</dt>
                    <dd class="col-7">{{ $queue->completed_at?->translatedFormat('d M Y H:i') ?? '-' }}</dd>

                    <dt class="col-5 text-muted-2 fw-normal">Keluhan</dt>
                    <dd class="col-7">{{ $queue->complaint_note ?? '-' }}</dd>
                </dl>
            </x-card>
        </div>

        <div class="col-xl-8">
            <x-card title="Data Pasien" icon="bi-person">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <x-avatar :name="$queue->patient->name" />
                    <div class="min-w-0">
                        <div class="fw-bold">{{ $queue->patient->name }}</div>
                        <div class="small text-muted-2">
                            {{ $queue->patient->medical_record_number }}
                            &middot; {{ $queue->patient->genderLabel() }}
                            @if ($queue->patient->age()) &middot; {{ $queue->patient->age() }} tahun @endif
                        </div>
                    </div>
                </div>

                @if ($queue->patient->allergies)
                    <div class="alert alert-danger py-2 small mb-0">
                        <i class="bi bi-exclamation-triangle me-1"></i>
                        <strong>Alergi:</strong> {{ $queue->patient->allergies }}
                    </div>
                @endif
            </x-card>

            @if ($queue->medicalRecord)
                <x-card title="Hasil Pemeriksaan" icon="bi-clipboard2-pulse" class="mt-3">
                    <dl class="row mb-0 small">
                        <dt class="col-4 text-muted-2 fw-normal">Keluhan</dt>
                        <dd class="col-8">{{ $queue->medicalRecord->complaint ?? $queue->complaint_note ?? '-' }}</dd>

                        <dt class="col-4 text-muted-2 fw-normal">Hasil Pemeriksaan</dt>
                        <dd class="col-8">{{ $queue->medicalRecord->examination_result ?? '-' }}</dd>

                        <dt class="col-4 text-muted-2 fw-normal">Diagnosis</dt>
                        <dd class="col-8">{{ $queue->medicalRecord->diagnosis ?? '-' }}</dd>

                        <dt class="col-4 text-muted-2 fw-normal">Tindakan</dt>
                        <dd class="col-8">{{ $queue->medicalRecord->treatment ?? '-' }}</dd>

                        <dt class="col-4 text-muted-2 fw-normal">Catatan</dt>
                        <dd class="col-8">{{ $queue->medicalRecord->notes ?? '-' }}</dd>

                        <dt class="col-4 text-muted-2 fw-normal">Waktu</dt>
                        <dd class="col-8">{{ $queue->medicalRecord->examined_at?->translatedFormat('d M Y H:i') ?? '-' }}</dd>
                    </dl>
                </x-card>
            @endif

            @if ($queue->medicalRecord?->prescription)
                <x-card title="Resep" icon="bi-file-earmark-medical" class="mt-3">
                    <x-slot:actions>
                        <span class="badge bg-light border">{{ $queue->medicalRecord->prescription->code }}</span>
                    </x-slot:actions>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle jhs-table-compact">
                            <thead>
                                <tr>
                                    <th>Obat</th>
                                    <th class="text-center">Jumlah</th>
                                    <th>Aturan Pakai</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($queue->medicalRecord->prescription->details as $detail)
                                    <tr>
                                        <td class="small">{{ $detail->medicine->name }}</td>
                                        <td class="small text-center">{{ $detail->quantity }} {{ $detail->medicine->unit }}</td>
                                        <td class="small">{{ $detail->dosage ?? $detail->instructions ?? '-' }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="3" class="small text-muted-2">Resep belum memiliki detail obat.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </x-card>
            @endif
        </div>
    </div>
@endsection
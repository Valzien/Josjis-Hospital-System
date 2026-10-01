@extends('layouts.app')

@section('title', 'Antrean '.$queue->queue_number)

@section('content')
    <x-page-header :title="'Antrean '.$queue->queue_number"
                   :description="$queue->doctor?->name.' &middot; '.$queue->queue_date?->translatedFormat('l, d F Y')"
                   icon="bi-ticket-perforated">
        <x-slot:actions>
            <a href="{{ route('patient.queue.index') }}" class="btn btn-light border btn-sm">
                <i class="bi bi-arrow-left me-1"></i>Antrean Saya
            </a>
            @if ($queue->isOpen())
                <button type="button" class="btn btn-outline-danger btn-sm" data-confirm
                        data-confirm-message="Batalkan antrean {{ $queue->queue_number }}? Tindakan ini tidak dapat dibatalkan."
                        data-confirm-variant="danger"
                        data-confirm-form="#cancel-form">
                    <i class="bi bi-x-circle me-1"></i>Batalkan Antrean
                </button>
            @endif
        </x-slot:actions>
    </x-page-header>

    <div class="row g-3">
        <div class="col-xl-5">
            <x-card title="Nomor Antrean Anda" icon="bi-ticket-perforated" class="text-center">
                <div class="display-3 fw-bold text-primary my-2">{{ $queue->queue_number }}</div>
                <div class="mb-3"><x-status-badge :status="$queue->status" /></div>

                @if ($queue->isOpen())
                    <div class="border-top pt-3">
                        @if ($position)
                            <x-progress :current="$position - 1" :total="max($position, 1)" color="bg-primary" />
                            <div class="small text-muted-2 mt-2">
                                Posisi Anda <strong>ke-{{ $position }}</strong> &middot;
                                perkiraan menunggu <strong>{{ $estimatedMinutes ?? 0 }} menit</strong>
                            </div>
                        @else
                            <div class="small text-muted-2">Nomor Anda sedang dipanggil. Silakan menuju ruang praktik.</div>
                        @endif
                    </div>
                @else
                    <div class="border-top pt-3 small text-muted-2">
                        Antrean ini sudah selesai dengan status {{ $queue->status?->label() }}.
                    </div>
                @endif
            </x-card>

            <x-card title="Informasi Antrean" icon="bi-info-circle" class="mt-3">
                <dl class="row mb-0 small">
                    <dt class="col-5 text-muted-2 fw-normal">Dokter</dt>
                    <dd class="col-7">{{ $queue->doctor?->name ?? '-' }}</dd>

                    <dt class="col-5 text-muted-2 fw-normal">Spesialisasi</dt>
                    <dd class="col-7">{{ $queue->doctor?->specialization ?? '-' }}</dd>

                    <dt class="col-5 text-muted-2 fw-normal">Tanggal</dt>
                    <dd class="col-7">{{ $queue->queue_date?->translatedFormat('d F Y') ?? '-' }}</dd>

                    <dt class="col-5 text-muted-2 fw-normal">Jam Layanan</dt>
                    <dd class="col-7">{{ $queue->doctorSchedule?->timeRange() ?? '-' }}</dd>

                    <dt class="col-5 text-muted-2 fw-normal">Ruang</dt>
                    <dd class="col-7">{{ $queue->doctorSchedule?->room ?? '-' }}</dd>

                    <dt class="col-5 text-muted-2 fw-normal">Keluhan</dt>
                    <dd class="col-7">{{ $queue->complaint_note ?? '-' }}</dd>

                    <dt class="col-5 text-muted-2 fw-normal">Diambil</dt>
                    <dd class="col-7">{{ $queue->created_at?->translatedFormat('d M Y H:i') ?? '-' }}</dd>

                    @if ($queue->called_at)
                        <dt class="col-5 text-muted-2 fw-normal">Dipanggil</dt>
                        <dd class="col-7">{{ $queue->called_at->translatedFormat('H:i') }}</dd>
                    @endif

                    @if ($queue->completed_at)
                        <dt class="col-5 text-muted-2 fw-normal">Selesai</dt>
                        <dd class="col-7">{{ $queue->completed_at->translatedFormat('d M Y H:i') }}</dd>
                    @endif
                </dl>
            </x-card>

            @if ($queue->medicalRecord)
                <x-card title="Hasil Pemeriksaan" icon="bi-journal-medical" class="mt-3">
                    <div class="fs-7 text-muted-2 mb-1">Diagnosis</div>
                    <div class="fw-semibold mb-2">{{ $queue->medicalRecord->diagnosis ?? '-' }}</div>
                    <div class="small text-muted-2 mb-3">
                        {{ $queue->medicalRecord->doctor?->name ?? 'Dokter' }} &middot;
                        {{ $queue->medicalRecord->examined_at?->translatedFormat('d F Y') ?? '-' }}
                    </div>

                    @if ($queue->medicalRecord->prescription)
                        <a href="{{ route('patient.prescriptions.show', $queue->medicalRecord->prescription) }}"
                           class="btn btn-sm btn-light border">
                            <i class="bi bi-file-earmark-medical me-1"></i>Lihat Resep
                        </a>
                    @else
                        <a href="{{ route('patient.history.show', $queue->medicalRecord) }}" class="btn btn-sm btn-light border">
                            <i class="bi bi-eye me-1"></i>Lihat Rekam Medis
                        </a>
                    @endif
                </x-card>
            @endif
        </div>

        <div class="col-xl-7">
            <x-card title="Papan Pemanggilan" icon="bi-display"
                    subtitle="Pasien yang sedang dan akan dipanggil"
                    data-live-refresh="{{ config('jhs.queue.auto_call_refresh') }}">
                <x-slot:actions>
                    <button class="btn btn-sm btn-light border" type="button" data-live-refresh-toggle="[data-live-region]">
                        <i class="bi bi-arrow-clockwise me-1"></i>Segarkan
                    </button>
                </x-slot:actions>

                <div data-live-region>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <div class="fs-7 text-muted-2 mb-1">Sedang Dilayani</div>
                        @if ($servingNow)
                            <div class="border rounded-3 p-2 border-success bg-light-subtle">
                                <div class="fw-bold text-success fs-5">{{ $servingNow->queue_number }}</div>
                                <div class="small text-truncate">{{ $servingNow->patient?->name ?? '-' }}</div>
                            </div>
                        @else
                            <div class="border rounded-3 p-2 text-center text-muted-2 small">
                                Belum ada yang dipanggil
                            </div>
                        @endif
                    </div>
                    <div class="col-md-6">
                        <div class="fs-7 text-muted-2 mb-1">Antrean Anda</div>
                        <div class="border rounded-3 p-2 border-primary bg-light-subtle">
                            <div class="fw-bold text-primary fs-5">{{ $queue->queue_number }}</div>
                            <div class="small text-truncate">{{ $queue->patient?->name ?? '-' }}</div>
                        </div>
                    </div>
                </div>

                <div class="fs-7 text-muted-2 mb-2">Pemanggilan berikutnya</div>
                <div class="vstack gap-1">
                    @forelse ($nextUp as $item)
                        <div class="d-flex justify-content-between align-items-center border rounded-3 px-2 py-1 small">
                            <span class="text-truncate {{ $item->id === $queue->id ? 'fw-bold text-primary' : '' }}">
                                {{ $item->patient?->name ?? '-' }}
                            </span>
                            <span class="jhs-queue-number">{{ $item->queue_number }}</span>
                        </div>
                    @empty
                        <div class="small text-muted-2 py-2">Tidak ada antrean lain yang menunggu.</div>
                    @endforelse
                </div>
                </div>
            </x-card>
        </div>
    </div>

    @if ($queue->isOpen())
        <form method="POST" action="{{ route('patient.queue.cancel', $queue) }}" id="cancel-form" class="d-none">
            @csrf
        </form>
    @endif
@endsection
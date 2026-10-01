@extends('layouts.app')

@section('title', 'Antrean '.$queue->queue_number)

@section('content')
    <x-page-header title="Antrean {{ $queue->queue_number }}"
                   description="{{ $queue->patient->name }} &middot; {{ $queue->queue_date?->translatedFormat('l, d F Y') }}"
                   icon="bi-ticket-perforated">
        <x-slot:actions>
            <a href="{{ route('doctor.queues.index') }}" class="btn btn-light border btn-sm">
                <i class="bi bi-arrow-left me-1"></i>Antrean Saya
            </a>
            <button type="button" class="btn btn-light border btn-sm" data-print>
                <i class="bi bi-printer me-1"></i>Cetak
            </button>
        </x-slot:actions>
    </x-page-header>

    <div class="row g-3">
        <div class="col-xl-4">
            <x-card title="Status Antrean" icon="bi-ticket">
                <div class="text-center py-2">
                    <div class="jhs-queue-number jhs-queue-number-lg">{{ $queue->queue_number }}</div>
                    <div class="mt-2"><x-status-badge :status="$queue->status" /></div>
                    @if ($position)
                        <div class="small text-muted-2 mt-2">Posisi antrean: {{ $position }}</div>
                    @endif
                </div>

                <div class="d-grid gap-2 mt-3">
                    @if ($queue->isWaiting())
                        <form method="POST" action="{{ route('doctor.queues.start', $queue) }}">
                            @csrf @method('PATCH')
                            <button class="btn btn-primary w-100" type="submit">
                                <i class="bi bi-play-circle me-1"></i>Mulai Pemeriksaan
                            </button>
                        </form>
                    @elseif ($queue->status === \App\Enums\QueueStatus::Called)
                        <a href="{{ route('doctor.examinations.create', $queue) }}" class="btn btn-primary">
                            <i class="bi bi-clipboard2-pulse me-1"></i>Isi Rekam Medis
                        </a>
                        <form method="POST" action="{{ route('doctor.queues.start', $queue) }}">
                            @csrf @method('PATCH')
                            <button class="btn btn-light border w-100" type="submit">Tandai Mulai Diperiksa</button>
                        </form>
                    @elseif ($queue->status === \App\Enums\QueueStatus::InExamination)
                        @if ($queue->medicalRecord)
                            <a href="{{ route('doctor.examinations.show', $queue->medicalRecord) }}" class="btn btn-primary">
                                <i class="bi bi-file-earmark-medical me-1"></i>Lihat Rekam Medis
                            </a>
                        @else
                            <a href="{{ route('doctor.examinations.create', $queue) }}" class="btn btn-primary">
                                <i class="bi bi-clipboard2-pulse me-1"></i>Isi Rekam Medis
                            </a>
                        @endif
                        <form method="POST" action="{{ route('doctor.queues.complete', $queue) }}">
                            @csrf @method('PATCH')
                            <button class="btn btn-light border w-100" type="submit">Selesaikan Tanpa Resep</button>
                        </form>
                    @endif
                </div>
            </x-card>

            <x-card title="Data Pasien" icon="bi-person" class="mt-3">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <x-avatar :name="$queue->patient->name" />
                    <div class="min-w-0">
                        <div class="fw-bold text-truncate">{{ $queue->patient->name }}</div>
                        <div class="fs-7 text-muted-2">{{ $queue->patient->medical_record_number }}</div>
                    </div>
                </div>

                @if ($queue->patient->allergies)
                    <div class="alert alert-danger py-2 small">
                        <i class="bi bi-exclamation-triangle me-1"></i>
                        <strong>Alergi:</strong> {{ $queue->patient->allergies }}
                    </div>
                @endif

                <dl class="row mb-0 small">
                    <dt class="col-5 text-muted-2 fw-normal">Jenis Kelamin</dt>
                    <dd class="col-7">{{ $queue->patient->genderLabel() }}</dd>

                    <dt class="col-5 text-muted-2 fw-normal">Usia</dt>
                    <dd class="col-7">{{ $queue->patient->age() ? $queue->patient->age().' tahun' : '-' }}</dd>

                    <dt class="col-5 text-muted-2 fw-normal">Golongan Darah</dt>
                    <dd class="col-7">{{ $queue->patient->blood_type?->label() ?? '-' }}</dd>

                    <dt class="col-5 text-muted-2 fw-normal">Telepon</dt>
                    <dd class="col-7">{{ $queue->patient->phone ?? '-' }}</dd>
                </dl>
            </x-card>
        </div>

        <div class="col-xl-8">
            <x-card title="Keluhan Pasien" icon="bi-chat-left-text">
                <p class="mb-0">{{ $queue->complaint_note ?? 'Pasien tidak memberikan keluhan.' }}</p>
                <div class="fs-7 text-muted-2 mt-2">
                    Dibuat {{ $queue->created_at?->translatedFormat('d M Y H:i') }}
                    oleh {{ $queue->createdBy?->name ?? 'sistem' }}
                </div>
            </x-card>

            <x-card title="Riwayat Pemeriksaan" subtitle="5 pemeriksaan terakhir pasien ini" icon="bi-journal-medical" class="mt-3">
                <div class="vstack gap-2">
                    @forelse ($history as $record)
                        <div class="border rounded-3 p-2">
                            <div class="d-flex align-items-center justify-content-between gap-2">
                                <div class="min-w-0">
                                    <div class="small fw-semibold">{{ $record->diagnosis ?? 'Diagnosis belum diisi' }}</div>
                                    <div class="fs-7 text-muted-2">{{ $record->doctor?->name }}</div>
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="fs-7 text-muted-2 text-nowrap">{{ $record->examined_at?->translatedFormat('d M Y') }}</span>
                                    <a href="{{ route('doctor.medical-records.show', $record) }}" class="btn btn-sm btn-light border">Detail</a>
                                </div>
                            </div>
                        </div>
                    @empty
                        <x-empty-state icon="bi-journal-medical" title="Belum ada riwayat"
                                       text="Pasien ini belum pernah melakukan pemeriksaan." />
                    @endforelse
                </div>
            </x-card>
        </div>
    </div>
@endsection
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
            <a href="{{ route('patient.queue.ticket', $queue) }}" target="_blank" class="btn btn-outline-primary btn-sm">
                <i class="bi bi-ticket-perforated me-1"></i>Karcis Antrean
            </a>
            @if ($queue->medicalRecord)
                <a href="{{ route('patient.history.show', $queue->medicalRecord) }}" class="btn btn-primary btn-sm">
                    <i class="bi bi-journal-medical me-1"></i>Rekam Medis Lengkap
                </a>
                @if ($queue->medicalRecord->prescription)
                    <a href="{{ route('patient.prescriptions.show', $queue->medicalRecord->prescription) }}" class="btn btn-outline-primary btn-sm">
                        <i class="bi bi-capsule me-1"></i>Resep Obat
                    </a>
                @endif
                <button type="button" class="btn btn-light border btn-sm" data-print>
                    <i class="bi bi-printer me-1"></i>Cetak
                </button>
            @endif
            @if ($queue->isOpen())
                <button type="button" class="btn btn-outline-danger btn-sm" data-confirm
                        data-confirm-message="Batalkan antrean {{ $queue->queue_number }}? Tindakan ini tidak dapat dibatalkan."
                        data-confirm-variant="danger"
                        data-confirm-form="cancel-form">
                    <i class="bi bi-x-circle me-1"></i>Batalkan Antrean
                </button>
            @endif
        </x-slot:actions>
    </x-page-header>

    @if ($queue->medicalRecord)
        <div class="alert alert-success d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3 p-3 border-success-subtle bg-success-subtle text-success-emphasis rounded-3">
            <div class="d-flex align-items-center gap-3">
                <div class="rounded-circle bg-success text-white p-2 d-flex align-items-center justify-content-center flex-shrink-0" style="width: 42px; height: 42px;">
                    <i class="bi bi-check-lg fs-4"></i>
                </div>
                <div>
                    <div class="fw-bold">Pemeriksaan Selesai &mdash; No. Rekam Medis {{ $queue->medicalRecord->record_number }}</div>
                    <div class="small">Pemeriksaan Anda telah selesai dilakukan oleh {{ $queue->doctor?->name }} pada {{ $queue->medicalRecord->examined_at?->translatedFormat('d F Y, H:i') ?? $queue->completed_at?->translatedFormat('d F Y, H:i') }}.</div>
                </div>
            </div>
            <div class="d-flex align-items-center gap-2">
                <a href="{{ route('patient.history.show', $queue->medicalRecord) }}" class="btn btn-sm btn-success">
                    <i class="bi bi-eye me-1"></i>Buka Rekam Medis
                </a>
                @if ($queue->medicalRecord->prescription)
                    <a href="{{ route('patient.prescriptions.show', $queue->medicalRecord->prescription) }}" class="btn btn-sm btn-outline-success bg-white">
                        <i class="bi bi-file-earmark-medical me-1"></i>Lihat Resep
                    </a>
                @endif
            </div>
        </div>

        <div class="row g-3">
            {{-- Kolom Kiri: Ringkasan Antrean & Akses Cepat --}}
            <div class="col-xl-4">
                <x-card title="Nomor Antrean Anda" icon="bi-ticket-perforated" class="text-center">
                    <div class="display-3 fw-bold text-primary my-2">{{ $queue->queue_number }}</div>
                    <div class="mb-3"><x-status-badge :status="$queue->status" /></div>
                    <div class="border-top pt-3 small text-muted-2">
                        Antrean ini sudah selesai pada {{ $queue->completed_at?->translatedFormat('d M Y H:i') ?? $queue->updated_at->translatedFormat('d M Y H:i') }}.
                    </div>
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

                <x-card title="Akses Cepat" icon="bi-lightning-charge" class="mt-3">
                    <div class="vstack gap-2">
                        <a href="{{ route('patient.history.show', $queue->medicalRecord) }}"
                           class="d-flex align-items-center gap-3 p-2 rounded-3 border text-decoration-none text-dark bg-light-subtle hover-bg-light">
                            <div class="rounded-circle bg-primary-subtle text-primary p-2 d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">
                                <i class="bi bi-journal-medical"></i>
                            </div>
                            <div class="flex-grow-1 min-w-0">
                                <div class="fw-semibold small text-truncate">Rekam Medis Digital</div>
                                <div class="fs-8 text-muted-2">{{ $queue->medicalRecord->record_number }}</div>
                            </div>
                            <i class="bi bi-chevron-right text-muted-2 small"></i>
                        </a>

                        @if ($queue->medicalRecord->prescription)
                            <a href="{{ route('patient.prescriptions.show', $queue->medicalRecord->prescription) }}"
                               class="d-flex align-items-center gap-3 p-2 rounded-3 border text-decoration-none text-dark bg-light-subtle hover-bg-light">
                                <div class="rounded-circle bg-success-subtle text-success p-2 d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">
                                    <i class="bi bi-capsule"></i>
                                </div>
                                <div class="flex-grow-1 min-w-0">
                                    <div class="fw-semibold small text-truncate">Resep Obat</div>
                                    <div class="fs-8 text-muted-2">{{ $queue->medicalRecord->prescription->code }} &middot; {{ $queue->medicalRecord->prescription->status?->label() }}</div>
                                </div>
                                <i class="bi bi-chevron-right text-muted-2 small"></i>
                            </a>
                        @endif
                    </div>
                </x-card>
            </div>

            {{-- Kolom Kanan: Detail Lengkap Hasil Pemeriksaan --}}
            <div class="col-xl-8">
                <x-card title="Hasil Pemeriksaan Dokter" icon="bi-clipboard2-pulse"
                        :subtitle="($queue->medicalRecord->doctor?->name ?? $queue->doctor?->name).' &middot; '.($queue->medicalRecord->examined_at?->translatedFormat('d F Y H:i') ?? '-')">
                    <x-slot:actions>
                        <a href="{{ route('patient.history.show', $queue->medicalRecord) }}" class="btn btn-sm btn-light border">
                            <i class="bi bi-eye me-1"></i>Buka Detail
                        </a>
                        <button type="button" class="btn btn-sm btn-light border" data-print>
                            <i class="bi bi-printer me-1"></i>Cetak
                        </button>
                    </x-slot:actions>

                    {{-- Kotak Diagnosis Menonjol --}}
                    <div class="p-3 rounded-3 mb-3 border border-primary-subtle bg-primary-subtle">
                        <div class="d-flex align-items-center gap-3">
                            <div class="rounded-circle bg-white text-primary p-2 d-flex align-items-center justify-content-center shadow-xs flex-shrink-0" style="width: 48px; height: 48px;">
                                <i class="bi bi-heart-pulse fs-3"></i>
                            </div>
                            <div class="flex-grow-1 min-w-0">
                                <div class="fs-8 fw-bold text-primary text-uppercase" style="letter-spacing: 0.5px;">Diagnosis Dokter</div>
                                <div class="fs-4 fw-bold text-dark mb-0 text-break">{{ $queue->medicalRecord->diagnosis ?? '-' }}</div>
                            </div>
                        </div>
                    </div>

                    {{-- Vital Signs Grid --}}
                    <div class="row g-2 mb-3">
                        <div class="col-sm-3 col-6">
                            <div class="border rounded-3 p-2 bg-light-subtle text-center">
                                <div class="fs-8 text-muted-2 text-uppercase fw-semibold">Tekanan Darah</div>
                                <div class="fw-bold fs-6 text-dark mt-1">
                                    {{ $queue->medicalRecord->blood_pressure ? $queue->medicalRecord->blood_pressure.' mmHg' : '-' }}
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-3 col-6">
                            <div class="border rounded-3 p-2 bg-light-subtle text-center">
                                <div class="fs-8 text-muted-2 text-uppercase fw-semibold">Suhu Tubuh</div>
                                <div class="fw-bold fs-6 text-dark mt-1">
                                    {{ $queue->medicalRecord->temperature ? $queue->medicalRecord->temperature.' &#176;C' : '-' }}
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-3 col-6">
                            <div class="border rounded-3 p-2 bg-light-subtle text-center">
                                <div class="fs-8 text-muted-2 text-uppercase fw-semibold">Berat Badan</div>
                                <div class="fw-bold fs-6 text-dark mt-1">
                                    {{ $queue->medicalRecord->weight ? $queue->medicalRecord->weight.' kg' : '-' }}
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-3 col-6">
                            <div class="border rounded-3 p-2 bg-light-subtle text-center">
                                <div class="fs-8 text-muted-2 text-uppercase fw-semibold">Tinggi Badan</div>
                                <div class="fw-bold fs-6 text-dark mt-1">
                                    {{ $queue->medicalRecord->height ? $queue->medicalRecord->height.' cm' : '-' }}
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Rincian Pemeriksaan & Saran --}}
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <div class="border rounded-3 p-3 h-100 bg-white">
                                <div class="d-flex align-items-center gap-2 text-muted-2 fs-7 fw-semibold mb-2">
                                    <i class="bi bi-chat-left-text text-primary"></i> Keluhan Pasien
                                </div>
                                <div class="small text-dark">{{ $queue->medicalRecord->complaint ?: ($queue->complaint_note ?: '-') }}</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="border rounded-3 p-3 h-100 bg-white">
                                <div class="d-flex align-items-center gap-2 text-muted-2 fs-7 fw-semibold mb-2">
                                    <i class="bi bi-search text-primary"></i> Temuan / Hasil Pemeriksaan Fisik
                                </div>
                                <div class="small text-dark" style="white-space: pre-line;">{{ $queue->medicalRecord->examination_result ?? '-' }}</div>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="border rounded-3 p-3 bg-white">
                                <div class="d-flex align-items-center gap-2 text-muted-2 fs-7 fw-semibold mb-2">
                                    <i class="bi bi-clipboard2-heart text-primary"></i> Tindakan / Terapi & Saran Dokter
                                </div>
                                <div class="small text-dark" style="white-space: pre-line;">{{ $queue->medicalRecord->treatment ?? '-' }}</div>
                            </div>
                        </div>
                        @if ($queue->medicalRecord->notes)
                            <div class="col-12">
                                <div class="border rounded-3 p-3 bg-light-subtle">
                                    <div class="d-flex align-items-center gap-2 text-muted-2 fs-7 fw-semibold mb-2">
                                        <i class="bi bi-sticky text-warning"></i> Catatan Khusus Dokter
                                    </div>
                                    <div class="small text-dark" style="white-space: pre-line;">{{ $queue->medicalRecord->notes }}</div>
                                </div>
                            </div>
                        @endif
                    </div>

                    {{-- Resep Obat --}}
                    @if ($queue->medicalRecord->prescription)
                        @php $prescription = $queue->medicalRecord->prescription; @endphp
                        <div class="border rounded-3 p-3 mb-3 bg-white">
                            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                                <div class="d-flex align-items-center gap-2">
                                    <i class="bi bi-capsule text-primary fs-5"></i>
                                    <div>
                                        <div class="fw-bold small">Resep Obat: {{ $prescription->code }}</div>
                                        <div class="fs-8 text-muted-2">{{ $prescription->created_at?->translatedFormat('d F Y, H:i') }}</div>
                                    </div>
                                </div>
                                <x-status-badge :status="$prescription->status" />
                            </div>

                            <div class="table-responsive mb-2">
                                <table class="table table-sm align-middle jhs-table-compact mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="ps-2">Nama Obat</th>
                                            <th class="text-center">Jumlah</th>
                                            <th>Dosis</th>
                                            <th>Aturan Pakai</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($prescription->details as $detail)
                                            <tr>
                                                <td class="small fw-semibold ps-2">{{ $detail->medicine?->name ?? '-' }}</td>
                                                <td class="small text-center">{{ $detail->quantity }} {{ $detail->medicine?->unit }}</td>
                                                <td class="small">{{ $detail->dosage ?? '-' }}</td>
                                                <td class="small">{{ $detail->instructions ?? '-' }}</td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="4" class="small text-muted-2 text-center py-2">Belum ada daftar item obat.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>

                            @if ($prescription->notes)
                                <div class="fs-7 text-muted-2 mt-2 mb-2">
                                    <strong>Catatan Resep:</strong> {{ $prescription->notes }}
                                </div>
                            @endif

                            <div class="d-flex justify-content-end mt-2">
                                <a href="{{ route('patient.prescriptions.show', $prescription) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-file-earmark-medical me-1"></i>Lihat Detail Resep & Status Pengambilan
                                </a>
                            </div>
                        </div>
                    @endif

                    {{-- Footer Action Buttons --}}
                    <div class="d-flex flex-wrap gap-2 pt-3 border-top">
                        <a href="{{ route('patient.history.show', $queue->medicalRecord) }}" class="btn btn-primary btn-sm">
                            <i class="bi bi-journal-medical me-1"></i>Lihat Rekam Medis Lengkap
                        </a>
                        @if ($queue->medicalRecord->prescription)
                            <a href="{{ route('patient.prescriptions.show', $queue->medicalRecord->prescription) }}" class="btn btn-outline-primary btn-sm">
                                <i class="bi bi-capsule me-1"></i>Lihat Resep Obat
                            </a>
                        @endif
                        <button type="button" class="btn btn-light border btn-sm ms-auto" data-print>
                            <i class="bi bi-printer me-1"></i>Cetak Hasil Pemeriksaan
                        </button>
                    </div>
                </x-card>
            </div>
        </div>
    @else
        {{-- Antrean yang belum selesai / belum ada pemeriksaan dokter --}}
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
            </div>

            <div class="col-xl-7">
                @if ($queue->isOpen())
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
                @else
                    <x-card title="Informasi Layanan" icon="bi-info-circle">
                        <x-empty-state icon="bi-clipboard-check" title="Pemeriksaan Telah Selesai"
                                       text="Antrean ini telah selesai atau ditutup. Riwayat pemeriksaan Anda dapat dilihat melalui menu Riwayat Pemeriksaan." />
                    </x-card>
                @endif
            </div>
        </div>
    @endif

    @if ($queue->isOpen())
        <form method="POST" action="{{ route('patient.queue.cancel', $queue) }}" id="cancel-form" class="d-none">
            @csrf
        </form>
    @endif
@endsection

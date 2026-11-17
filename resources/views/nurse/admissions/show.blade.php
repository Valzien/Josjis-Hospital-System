@extends('layouts.app')

@section('title', 'Rawat Inap '.$admission->code)

@section('content')
    <x-page-header :title="'Rawat Inap '.$admission->code"
                   :description="($admission->patient?->name ?? '-').' · '.($admission->room?->name ?? '-').' · '.($admission->admitted_at?->translatedFormat('d F Y H:i') ?? '-')"
                   icon="bi-hospital">
        <x-slot:actions>
            <a href="{{ route('nurse.admissions.index') }}" class="btn btn-light border btn-sm">
                <i class="bi bi-arrow-left me-1"></i>Daftar Rawat Inap
            </a>
        </x-slot:actions>
    </x-page-header>

    @if ($admission->isActive())
        <div class="alert alert-primary d-flex align-items-center gap-2">
            <i class="bi bi-hospital"></i>
            <div>Pasien sedang dirawat inap. Hari ke-<strong>{{ $dayCount }}</strong>.</div>
        </div>
    @elseif ($admission->status === \App\Enums\AdmissionStatus::Discharged)
        <div class="alert alert-success d-flex align-items-center gap-2">
            <i class="bi bi-box-arrow-right"></i>
            <div>Pasien telah <strong>dipulangkan</strong> pada {{ $admission->discharged_at?->translatedFormat('d F Y H:i') ?? '-' }}.</div>
        </div>
    @else
        <div class="alert alert-danger d-flex align-items-center gap-2">
            <i class="bi bi-x-circle"></i>
            <div>Rawat inap ini telah <strong>dibatalkan</strong>.</div>
        </div>
    @endif

    <div class="row g-3">
        <div class="col-xl-7">
            <x-card title="Informasi Rawat Inap" icon="bi-info-circle">
                <div class="row g-3 small">
                    <div class="col-sm-6">
                        <div class="text-muted-2 fs-7">Pasien</div>
                        <div class="fw-semibold">{{ $admission->patient?->name ?? '-' }}</div>
                        <div class="fs-7 text-muted-2">{{ $admission->patient?->medical_record_number ?? '-' }}</div>
                    </div>
                    <div class="col-sm-6">
                        <div class="text-muted-2 fs-7">Status</div>
                        <div><x-status-badge :status="$admission->status" /></div>
                    </div>
                    <div class="col-sm-6">
                        <div class="text-muted-2 fs-7">Dokter Penanggung Jawab</div>
                        <div class="fw-semibold">{{ $admission->doctor?->name ?? '-' }}</div>
                        <div class="fs-7 text-muted-2">{{ $admission->doctor?->specialization }}</div>
                    </div>
                    <div class="col-sm-6">
                        <div class="text-muted-2 fs-7">Kamar / Tempat Tidur</div>
                        <div class="fw-semibold">
                            {{ $admission->room?->name ?? '-' }}
                            @if ($admission->bed)
                                / {{ $admission->bed->code }}
                            @endif
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="text-muted-2 fs-7">Waktu Masuk</div>
                        <div class="fw-semibold">{{ $admission->admitted_at?->translatedFormat('d F Y H:i') ?? '-' }}</div>
                    </div>
                    <div class="col-sm-6">
                        <div class="text-muted-2 fs-7">Lama Inap</div>
                        <div class="fw-semibold">{{ $dayCount }} hari</div>
                    </div>
                    @if ($admission->complaint)
                        <div class="col-12">
                            <div class="text-muted-2 fs-7">Keluhan Utama</div>
                            <div class="text-pre-line">{{ $admission->complaint }}</div>
                        </div>
                    @endif
                    @if ($admission->diagnosis)
                        <div class="col-12">
                            <div class="text-muted-2 fs-7">Diagnosis Awal</div>
                            <div class="text-pre-line">{{ $admission->diagnosis }}</div>
                        </div>
                    @endif
                    @if ($admission->notes)
                        <div class="col-12">
                            <div class="text-muted-2 fs-7">Catatan Masuk</div>
                            <div class="text-pre-line">{{ $admission->notes }}</div>
                        </div>
                    @endif
                </div>
            </x-card>

            <x-card title="Riwayat Visite & Tanda Vital" subtitle="{{ $admission->visits->count() }} catatan" icon="bi-clipboard2-pulse" class="mt-3">
                <div class="jhs-timeline">
                    @forelse ($admission->visits as $visit)
                        <div class="border-start border-2 ps-3 mb-3">
                            <div class="d-flex flex-wrap justify-content-between gap-2">
                                <div class="fw-semibold small">
                                    {{ $visit->createdBy?->name ?? $visit->doctor?->name ?? $admission->doctor?->name ?? 'Perawat' }}
                                </div>
                                <div class="fs-7 text-muted-2">{{ $visit->visited_at?->translatedFormat('d F Y H:i') }}</div>
                            </div>
                            <div class="small text-pre-line">{{ $visit->notes }}</div>
                            @if ($visit->vitalSigns() !== [])
                                <div class="d-flex flex-wrap gap-1 mt-2">
                                    @foreach ($visit->vitalSigns() as $label => $value)
                                        <span class="badge text-bg-light border">{{ $label }}: {{ $value }}</span>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @empty
                        <x-empty-state icon="bi-clipboard2-pulse" title="Belum ada catatan visite"
                                       text="Catat perkembangan dan tanda vital pasien pada formulir di samping." />
                    @endforelse
                </div>
            </x-card>
        </div>

        <div class="col-xl-5">
            @if ($errors->any())
                <div class="alert alert-danger">
                    <div class="fw-semibold mb-1"><i class="bi bi-exclamation-triangle me-1"></i>Periksa isian visite:</div>
                    <ul class="mb-0 ps-3 small">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if ($admission->isActive())
                <x-card title="Catat Visite & Tanda Vital" icon="bi-clipboard2-plus">
                    <form method="POST" action="{{ route('nurse.admissions.visit', $admission) }}">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label" for="visited_at">Waktu Visite</label>
                            <input type="datetime-local" name="visited_at" id="visited_at"
                                   value="{{ old('visited_at', now()->format('Y-m-d\TH:i')) }}"
                                   class="form-control form-control-sm">
                        </div>
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label class="form-label fs-7 mb-1" for="temperature">Suhu (°C)</label>
                                <input type="number" step="0.1" min="30" max="45" name="temperature" id="temperature"
                                       value="{{ old('temperature') }}" class="form-control form-control-sm">
                            </div>
                            <div class="col-6">
                                <label class="form-label fs-7 mb-1" for="blood_pressure">Tekanan (mmHg)</label>
                                <input type="number" step="0.1" min="50" max="250" name="blood_pressure" id="blood_pressure"
                                       value="{{ old('blood_pressure') }}" class="form-control form-control-sm">
                            </div>
                            <div class="col-6">
                                <label class="form-label fs-7 mb-1" for="pulse">Nadi (x/menit)</label>
                                <input type="number" min="20" max="250" name="pulse" id="pulse"
                                       value="{{ old('pulse') }}" class="form-control form-control-sm">
                            </div>
                            <div class="col-6">
                                <label class="form-label fs-7 mb-1" for="respiratory_rate">Pernapasan (x/menit)</label>
                                <input type="number" min="5" max="80" name="respiratory_rate" id="respiratory_rate"
                                       value="{{ old('respiratory_rate') }}" class="form-control form-control-sm">
                            </div>
                            <div class="col-6">
                                <label class="form-label fs-7 mb-1" for="oxygen_saturation">SpO₂ (%)</label>
                                <input type="number" min="50" max="100" name="oxygen_saturation" id="oxygen_saturation"
                                       value="{{ old('oxygen_saturation') }}" class="form-control form-control-sm">
                            </div>
                            <div class="col-6">
                                <label class="form-label fs-7 mb-1" for="weight">Berat (kg)</label>
                                <input type="number" step="0.1" min="1" max="400" name="weight" id="weight"
                                       value="{{ old('weight') }}" class="form-control form-control-sm">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="visit_notes">Catatan Perkembangan <span class="text-danger">*</span></label>
                            <textarea name="notes" id="visit_notes" rows="4" maxlength="2000" required
                                      class="form-control form-control-sm"
                                      placeholder="Keluhan, pemeriksaan fisik, respons terapi">{{ old('notes') }}</textarea>
                        </div>
                        <button type="submit" class="btn btn-primary btn-sm w-100">
                            <i class="bi bi-check-lg me-1"></i>Simpan Visite
                        </button>
                    </form>
                </x-card>
            @else
                <x-card title="Visite Ditutup" icon="bi-lock">
                    <div class="alert alert-light border small mb-0">
                        <i class="bi bi-info-circle me-1"></i>
                        Visite dan tanda vital hanya dapat dicatat pada rawat inap yang masih aktif.
                    </div>
                </x-card>
            @endif

            <x-card title="Perpindahan Tempat Tidur" subtitle="Riwayat singkat" icon="bi-arrow-left-right" class="mt-3">
                <div class="jhs-timeline">
                    @forelse ($admission->movements as $movement)
                        <div class="border-start border-2 ps-3 mb-3">
                            <div class="small fw-semibold">{{ $movement->reason }}</div>
                            <div class="fs-7 text-muted-2">{{ $movement->moved_at?->translatedFormat('d M Y H:i') }}</div>
                        </div>
                    @empty
                        <x-empty-state icon="bi-arrow-left-right" title="Belum ada perpindahan" />
                    @endforelse
                </div>
            </x-card>
        </div>
    </div>
@endsection

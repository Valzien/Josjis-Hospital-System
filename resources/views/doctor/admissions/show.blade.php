@extends('layouts.app')

@section('title', 'Rawat Inap '.$admission->code)

@section('content')
    <x-page-header :title="'Rawat Inap '.$admission->code"
                   :description="($admission->patient?->name ?? '-').' · '.($admission->room?->name ?? '-').' · '.($admission->admitted_at?->translatedFormat('d F Y H:i') ?? '-')"
                   icon="bi-hospital">
        <x-slot:actions>
            @if ($admission->hasDischargeSummary() || $admission->status === \App\Enums\AdmissionStatus::Discharged)
                <a href="{{ route('doctor.admissions.summary.print', $admission) }}" target="_blank" class="btn btn-light border btn-sm">
                    <i class="bi bi-printer me-1"></i>Ringkasan Pulang
                </a>
            @endif
            <a href="{{ route('doctor.admissions.index') }}" class="btn btn-light border btn-sm">
                <i class="bi bi-arrow-left me-1"></i>Daftar Rawat Inap
            </a>
            <a href="{{ route('doctor.patients.show', $admission->patient_id) }}" class="btn btn-light border btn-sm">
                <i class="bi bi-person-vcard me-1"></i>Profil Pasien
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
        <div class="col-xl-8">
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
                        <div class="text-muted-2 fs-7">Kamar / Tempat Tidur</div>
                        <div class="fw-semibold">
                            {{ $admission->room?->name ?? '-' }}
                            @if ($admission->bed)
                                / {{ $admission->bed->code }}
                            @endif
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="text-muted-2 fs-7">Lama Inap</div>
                        <div class="fw-semibold">{{ $dayCount }} hari</div>
                    </div>
                    <div class="col-sm-6">
                        <div class="text-muted-2 fs-7">Masuk</div>
                        <div class="fw-semibold">{{ $admission->admitted_at?->translatedFormat('d F Y H:i') ?? '-' }}</div>
                    </div>
                    <div class="col-sm-6">
                        <div class="text-muted-2 fs-7">Pulang</div>
                        <div class="fw-semibold">{{ $admission->discharged_at?->translatedFormat('d F Y H:i') ?? '-' }}</div>
                    </div>
                    @if ($admission->complaint)
                        <div class="col-12">
                            <div class="text-muted-2 fs-7">Keluhan Utama</div>
                            <div class="text-pre-line">{{ $admission->complaint }}</div>
                        </div>
                    @endif
                    @if ($admission->diagnosis)
                        <div class="col-12">
                            <div class="text-muted-2 fs-7">Diagnosis</div>
                            <div class="text-pre-line">{{ $admission->diagnosis }}</div>
                        </div>
                    @endif
                    @if ($admission->discharge_notes)
                        <div class="col-12">
                            <div class="text-muted-2 fs-7">Catatan Pulang</div>
                            <div class="text-pre-line">{{ $admission->discharge_notes }}</div>
                        </div>
                    @endif
                </div>
            </x-card>

            <x-card title="Diagnosis ICD-10" icon="bi-journal-medical" class="mt-3">
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Kode</th>
                                <th>Diagnosis</th>
                                <th>Jenis</th>
                                <th>Dicatat</th>
                                <th class="text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($admission->diagnoses as $diagnosis)
                                <tr>
                                    <td class="small fw-semibold text-nowrap">{{ $diagnosis->code }}</td>
                                    <td class="small">
                                        <div class="fw-semibold">{{ $diagnosis->name }}</div>
                                        @if ($diagnosis->notes)
                                            <div class="fs-7 text-muted-2 text-pre-line">{{ $diagnosis->notes }}</div>
                                        @endif
                                    </td>
                                    <td><x-status-badge :status="$diagnosis->type" /></td>
                                    <td class="small">
                                        {{ $diagnosis->doctor?->name ?? $admission->doctor?->name ?? '-' }}
                                        <div class="fs-7 text-muted-2">{{ $diagnosis->diagnosed_at?->translatedFormat('d/m/Y H:i') ?? '-' }}</div>
                                    </td>
                                    <td class="text-end">
                                        @if ($admission->isActive())
                                            <form method="POST"
                                                  action="{{ route('doctor.admissions.diagnosis.destroy', [$admission, $diagnosis]) }}"
                                                  onsubmit="return confirm('Hapus diagnosis {{ $diagnosis->code }}?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Hapus diagnosis">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5">
                                        <x-empty-state icon="bi-journal-medical" title="Belum ada diagnosis"
                                                       text="Pilih kode ICD-10 pada formulir di bawah." />
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($admission->isActive() && $icd10Codes->isNotEmpty())
                    <hr>
                    <form method="POST" action="{{ route('doctor.admissions.diagnosis', $admission) }}">
                        @csrf
                        <div class="row g-2 align-items-end">
                            <div class="col-lg-6">
                                <label class="form-label fs-7 mb-1" for="icd10_code_id">Kode ICD-10 <span class="text-danger">*</span></label>
                                <select name="icd10_code_id" id="icd10_code_id" required class="form-select form-select-sm">
                                    <option value="">Pilih diagnosis</option>
                                    @foreach ($icd10Codes as $category => $codes)
                                        <optgroup label="{{ $category }}">
                                            @foreach ($codes as $code)
                                                <option value="{{ $code->id }}" @selected((string) old('icd10_code_id') === (string) $code->id)>
                                                    {{ $code->code }} — {{ $code->name }}
                                                </option>
                                            @endforeach
                                        </optgroup>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-lg-3">
                                <label class="form-label fs-7 mb-1" for="diagnosis_type">Jenis</label>
                                <select name="type" id="diagnosis_type" class="form-select form-select-sm">
                                    @foreach (\App\Enums\DiagnosisType::options() as $value => $label)
                                        <option value="{{ $value }}" @selected(old('type', \App\Enums\DiagnosisType::Secondary->value) === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-lg-3">
                                <button type="submit" class="btn btn-primary btn-sm w-100">
                                    <i class="bi bi-plus-lg me-1"></i>Tambah Diagnosis
                                </button>
                            </div>
                            <div class="col-12">
                                <label class="form-label fs-7 mb-1" for="diagnosis_notes">Catatan</label>
                                <input type="text" name="notes" id="diagnosis_notes" maxlength="1000"
                                       class="form-control form-control-sm" value="{{ old('notes') }}" placeholder="Opsional">
                            </div>
                        </div>
                    </form>
                @endif
            </x-card>

            <x-card title="Tindakan & Biaya" icon="bi-list-check" class="mt-3">
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Komponen</th>
                                <th class="text-center">Qty</th>
                                <th class="text-end">Harga</th>
                                <th class="text-end">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($admission->charges->sortBy('charged_at') as $charge)
                                <tr>
                                    <td>
                                        <div class="small fw-semibold">{{ $charge->description }}</div>
                                        <x-status-badge :status="$charge->type" class="mt-1" />
                                    </td>
                                    <td class="text-center small">{{ $charge->quantity }}</td>
                                    <td class="text-end small text-nowrap">Rp {{ number_format((float) $charge->unit_price, 0, ',', '.') }}</td>
                                    <td class="text-end small fw-semibold text-nowrap">Rp {{ number_format((float) $charge->subtotal, 0, ',', '.') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4">
                                        <x-empty-state icon="bi-list-check" title="Belum ada tindakan"
                                                       text="Tambahkan tindakan pada formulir di samping." />
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                        @if ($admission->charges->isNotEmpty())
                            <tfoot>
                                <tr>
                                    <td colspan="3" class="text-end fw-semibold">Total Sementara</td>
                                    <td class="text-end fw-bold text-nowrap">Rp {{ number_format($chargesTotal, 0, ',', '.') }}</td>
                                </tr>
                            </tfoot>
                        @endif
                    </table>
                </div>
            </x-card>

            <x-card title="Riwayat Visite" icon="bi-clipboard2-pulse" class="mt-3">
                <div class="jhs-timeline">
                    @forelse ($admission->visits as $visit)
                        <div class="border-start border-2 ps-3 mb-3">
                            <div class="d-flex flex-wrap justify-content-between gap-2">
                                <div class="fw-semibold small">{{ $visit->doctor?->name ?? $admission->doctor?->name ?? 'Dokter' }}</div>
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
                                       text="Catat perkembangan pasien pada formulir visite." />
                    @endforelse
                </div>
            </x-card>
        </div>

        <div class="col-xl-4">
            <x-card title="Ringkasan Biaya" icon="bi-calculator">
                <div class="d-flex justify-content-between small mb-2">
                    <span class="text-muted-2">Lama Inap</span>
                    <span class="fw-semibold">{{ $dayCount }} hari</span>
                </div>
                <div class="d-flex justify-content-between small mb-2">
                    <span class="text-muted-2">Total Biaya Tercatat</span>
                    <span class="fw-semibold">Rp {{ number_format($chargesTotal, 0, ',', '.') }}</span>
                </div>
                @if ($admission->invoice)
                    <div class="d-flex justify-content-between small mb-2">
                        <span class="text-muted-2">Tagihan</span>
                        <span class="fw-semibold">{{ $admission->invoice->code }}</span>
                    </div>
                    <div class="d-flex justify-content-between small border-top pt-2 mb-0">
                        <span class="fw-semibold">Total Tagihan</span>
                        <span class="fw-bold">Rp {{ number_format((float) $admission->invoice->total, 0, ',', '.') }}</span>
                    </div>
                @elseif ($admission->isActive())
                    <div class="alert alert-light border small mb-0">
                        <i class="bi bi-hourglass-split me-1"></i>
                        Tagihan dibuat otomatis saat pasien dipulangkan.
                    </div>
                @endif
            </x-card>

            @if ($admission->isActive())
                <x-card title="Catat Visite" icon="bi-clipboard2-plus" class="mt-3">
                    <form method="POST" action="{{ route('doctor.admissions.visit', $admission) }}">
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
                                      placeholder="Keluhan, pemeriksaan fisik, rencana terapi">{{ old('notes') }}</textarea>
                        </div>
                        <button type="submit" class="btn btn-primary btn-sm w-100">
                            <i class="bi bi-check-lg me-1"></i>Simpan Visite
                        </button>
                    </form>
                </x-card>

                <x-card title="Tambah Tindakan" icon="bi-tags" class="mt-3">
                    @if ($services->isEmpty())
                        <div class="alert alert-warning small mb-0">
                            <i class="bi bi-exclamation-triangle me-1"></i>
                            Belum ada tarif tindakan aktif.
                        </div>
                    @else
                        <form method="POST" action="{{ route('doctor.admissions.charge', $admission) }}">
                            @csrf
                            <div class="mb-3">
                                <label class="form-label" for="service_id">Tindakan <span class="text-danger">*</span></label>
                                <select name="service_id" id="service_id" required
                                        class="form-select form-select-sm">
                                    <option value="">Pilih tindakan</option>
                                    @foreach ($services->groupBy('category') as $category => $categoryServices)
                                        <optgroup label="{{ $category }}">
                                            @foreach ($categoryServices as $service)
                                                <option value="{{ $service->id }}" data-price="{{ (float) $service->price }}">
                                                    {{ $service->name }} &mdash; Rp {{ number_format((float) $service->price, 0, ',', '.') }}
                                                </option>
                                            @endforeach
                                        </optgroup>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label" for="quantity">Jumlah <span class="text-danger">*</span></label>
                                <input type="number" name="quantity" id="quantity" min="1" max="100" value="{{ old('quantity', 1) }}"
                                       class="form-control form-control-sm" required>
                            </div>
                            <div class="d-flex justify-content-between small mb-3">
                                <span class="text-muted-2">Perkiraan Subtotal</span>
                                <span class="fw-semibold" id="charge-preview">Rp 0</span>
                            </div>
                            <button type="submit" class="btn btn-primary btn-sm w-100">
                                <i class="bi bi-plus-lg me-1"></i>Tambah Tindakan
                            </button>
                        </form>
                    @endif
                </x-card>
            @endif

            @if ($admission->status !== \App\Enums\AdmissionStatus::Cancelled)
                <x-card title="Ringkasan Pulang" icon="bi-file-earmark-medical" class="mt-3"
                        subtitle="Diisi dokter sebelum atau sesudah pasien pulang.">
                    <form method="POST" action="{{ route('doctor.admissions.summary.update', $admission) }}">
                        @csrf
                        @method('PUT')
                        <div class="mb-3">
                            <label class="form-label" for="discharge_condition">Kondisi Saat Pulang</label>
                            <textarea name="discharge_condition" id="discharge_condition" rows="3" maxlength="2000"
                                      class="form-control form-control-sm"
                                      placeholder="Keadaan umum, kesadaran, keluhan sisa">{{ old('discharge_condition', $admission->discharge_condition) }}</textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="discharge_plan">Rencana Tindak Lanjut</label>
                            <textarea name="discharge_plan" id="discharge_plan" rows="3" maxlength="2000"
                                      class="form-control form-control-sm"
                                      placeholder="Obat pulang, diet, aktivitas, edukasi">{{ old('discharge_plan', $admission->discharge_plan) }}</textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="follow_up_date">Tanggal Kontrol</label>
                            <input type="date" name="follow_up_date" id="follow_up_date"
                                   value="{{ old('follow_up_date', $admission->follow_up_date?->format('Y-m-d')) }}"
                                   class="form-control form-control-sm">
                        </div>
                        <button type="submit" class="btn btn-primary btn-sm w-100">
                            <i class="bi bi-check-lg me-1"></i>Simpan Ringkasan
                        </button>
                    </form>
                    @if ($admission->hasDischargeSummary())
                        <a href="{{ route('doctor.admissions.summary.print', $admission) }}" target="_blank"
                           class="btn btn-light border btn-sm w-100 mt-2">
                            <i class="bi bi-printer me-1"></i>Cetak Ringkasan Pulang
                        </a>
                    @endif
                </x-card>
            @endif
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (function () {
            const select = document.getElementById('service_id');
            const quantity = document.getElementById('quantity');
            const preview = document.getElementById('charge-preview');
            if (!select || !quantity || !preview) return;

            const format = (value) => 'Rp ' + new Intl.NumberFormat('id-ID').format(Math.round(value));

            function update() {
                const option = select.selectedOptions[0];
                const price = option ? parseFloat(option.dataset.price || '0') : 0;
                const qty = Math.max(1, parseInt(quantity.value || '1', 10));
                preview.textContent = format(price * qty);
            }

            select.addEventListener('change', update);
            quantity.addEventListener('input', update);
            update();
        })();
    </script>
@endpush

@extends('layouts.app')

@section('title', 'Rawat Inap '.$admission->code)

@section('content')
    <x-page-header :title="'Rawat Inap '.$admission->code"
                   :description="($admission->patient?->name ?? '-').' · '.($admission->room?->name ?? '-').' · '.($admission->admitted_at?->translatedFormat('d F Y H:i') ?? '-')"
                   icon="bi-hospital">
        <x-slot:actions>
            <a href="{{ route('reception.admissions.index') }}" class="btn btn-light border btn-sm">
                <i class="bi bi-arrow-left me-1"></i>Daftar Rawat Inap
            </a>
            <a href="{{ route('reception.patients.show', $admission->patient_id) }}" class="btn btn-light border btn-sm">
                <i class="bi bi-person-vcard me-1"></i>Data Pasien
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
                        <div class="text-muted-2 fs-7">Dokter Penanggung Jawab</div>
                        <div class="fw-semibold">{{ $admission->doctor?->name ?? '-' }}</div>
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
                        <div class="text-muted-2 fs-7">Masuk</div>
                        <div class="fw-semibold">{{ $admission->admitted_at?->translatedFormat('d F Y H:i') ?? '-' }}</div>
                    </div>
                    <div class="col-sm-6">
                        <div class="text-muted-2 fs-7">Pulang</div>
                        <div class="fw-semibold">{{ $admission->discharged_at?->translatedFormat('d F Y H:i') ?? '-' }}</div>
                    </div>
                    <div class="col-sm-6">
                        <div class="text-muted-2 fs-7">Didaftarkan Oleh</div>
                        <div class="fw-semibold">{{ $admission->createdBy?->name ?? 'Sistem' }}</div>
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
                            <div class="text-muted-2 fs-7">Catatan</div>
                            <div class="text-pre-line">{{ $admission->notes }}</div>
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

            <x-card title="Rincian Biaya" icon="bi-list-check" class="mt-3">
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
                                        <x-empty-state icon="bi-list-check" title="Belum ada rincian biaya"
                                                       text="Biaya kamar otomatis dihitung saat pasien pulang. Tindakan dicatat oleh dokter." />
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
                @if ($admission->isActive())
                    <div class="alert alert-light border small mt-3 mb-0">
                        <i class="bi bi-info-circle me-1"></i>
                        Biaya kamar ({{ $admission->room?->rateFormatted() ?? '-' }}/hari) akan ditambahkan otomatis saat pasien dipulangkan.
                    </div>
                @endif
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
                        </div>
                    @empty
                        <x-empty-state icon="bi-clipboard2-pulse" title="Belum ada catatan visite"
                                       text="Catatan visite diisi oleh dokter yang merawat." />
                    @endforelse
                </div>
            </x-card>

            <x-card title="Riwayat Tempat Tidur" icon="bi-arrow-left-right" class="mt-3">
                <div class="table-responsive">
                    <table class="table table-hover align-middle jhs-table-compact mb-0">
                        <thead>
                            <tr>
                                <th>Waktu</th>
                                <th>Dari</th>
                                <th>Ke</th>
                                <th>Alasan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($admission->movements as $movement)
                                <tr>
                                    <td class="small text-nowrap">{{ $movement->moved_at?->format('d/m/Y H:i') ?? '-' }}</td>
                                    <td class="small">
                                        {{ $movement->fromBed?->room?->name ?? '-' }}
                                        {{ $movement->fromBed ? '/ '.$movement->fromBed->code : '' }}
                                    </td>
                                    <td class="small">
                                        {{ $movement->toBed?->room?->name ?? '-' }}
                                        {{ $movement->toBed ? '/ '.$movement->toBed->code : '' }}
                                    </td>
                                    <td class="small text-muted-2">{{ $movement->reason ?? '-' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4">
                                        <x-empty-state icon="bi-arrow-left-right" title="Belum ada perpindahan"
                                                       text="Riwayat masuk, pindah, dan pulang akan tampil di sini." />
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
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
                    <div class="d-flex justify-content-between small mb-2">
                        <span class="text-muted-2">Total Tagihan</span>
                        <span class="fw-semibold">Rp {{ number_format((float) $admission->invoice->total, 0, ',', '.') }}</span>
                    </div>
                    <div class="d-flex justify-content-between border-top pt-2 mb-2">
                        <span class="fw-semibold">Sisa Tagihan</span>
                        <span class="fw-bold {{ $admission->invoice->remainingAmount() > 0 ? 'text-danger' : 'text-success' }}">
                            Rp {{ number_format($admission->invoice->remainingAmount(), 0, ',', '.') }}
                        </span>
                    </div>
                    @if (auth()->user()->isAdmin() || auth()->user()->isCashier())
                        <a href="{{ route('cashier.invoices.show', $admission->invoice) }}" class="btn btn-light border btn-sm w-100">
                            <i class="bi bi-receipt me-1"></i>Lihat Tagihan
                        </a>
                    @endif
                @elseif ($admission->isActive())
                    <div class="alert alert-light border small mb-0">
                        <i class="bi bi-hourglass-split me-1"></i>
                        Tagihan dibuat otomatis saat pasien dipulangkan.
                    </div>
                @endif
            </x-card>

            @if ($admission->isActive())
                <x-card title="Pindahkan Tempat Tidur" icon="bi-arrow-left-right" class="mt-3">
                    @if ($availableBeds->isEmpty())
                        <div class="alert alert-warning small mb-0">
                            <i class="bi bi-exclamation-triangle me-1"></i>
                            Tidak ada tempat tidur lain yang tersedia.
                        </div>
                    @else
                        <form method="POST" action="{{ route('reception.admissions.transfer', $admission) }}">
                            @csrf
                            <div class="mb-3">
                                <label class="form-label" for="transfer_bed_id">Tempat Tidur Tujuan</label>
                                <select name="bed_id" id="transfer_bed_id" class="form-select form-select-sm" required>
                                    <option value="">Pilih tempat tidur</option>
                                    @foreach ($availableBeds->groupBy(fn ($bed) => $bed->room?->name ?? 'Tanpa Kamar') as $roomName => $roomBeds)
                                        <optgroup label="{{ $roomName }}">
                                            @foreach ($roomBeds as $bed)
                                                <option value="{{ $bed->id }}">
                                                    {{ $bed->code }} &mdash; {{ $bed->room?->classLabel() }}
                                                </option>
                                            @endforeach
                                        </optgroup>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label" for="transfer_reason">Alasan</label>
                                <input type="text" name="reason" id="transfer_reason" maxlength="500"
                                       class="form-control form-control-sm" placeholder="Opsional">
                            </div>
                            <button type="submit" class="btn btn-primary btn-sm w-100">
                                <i class="bi bi-arrow-left-right me-1"></i>Pindahkan
                            </button>
                        </form>
                    @endif
                </x-card>

                <x-card title="Pulangkan Pasien" icon="bi-box-arrow-right" class="mt-3">
                    <form method="POST" action="{{ route('reception.admissions.discharge', $admission) }}">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label" for="discharged_at">Waktu Pulang</label>
                            <input type="datetime-local" name="discharged_at" id="discharged_at"
                                   value="{{ old('discharged_at', now()->format('Y-m-d\TH:i')) }}"
                                   class="form-control form-control-sm">
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="discharge_notes">Catatan Pulang</label>
                            <textarea name="discharge_notes" id="discharge_notes" rows="3" maxlength="2000"
                                      class="form-control form-control-sm"
                                      placeholder="Instruksi pulang, kontrol ulang, atau catatan lain"></textarea>
                        </div>
                        <button type="submit" class="btn btn-success w-100"
                                data-confirm-title="Pulangkan Pasien"
                                data-confirm-message="Biaya kamar akan dihitung dan tagihan dibuat otomatis. Pulangkan {{ $admission->patient?->name }}?">
                            <i class="bi bi-box-arrow-right me-1"></i>Pulangkan &amp; Buat Tagihan
                        </button>
                    </form>
                </x-card>

                <x-card title="Batalkan Rawat Inap" icon="bi-x-circle" class="mt-3">
                    <form method="POST" action="{{ route('reception.admissions.cancel', $admission) }}">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label" for="cancel_reason">Alasan Pembatalan</label>
                            <input type="text" name="reason" id="cancel_reason" maxlength="500"
                                   class="form-control form-control-sm" placeholder="Contoh: salah input pasien">
                        </div>
                        <button type="submit" class="btn btn-outline-danger w-100"
                                data-confirm-title="Batalkan Rawat Inap"
                                data-confirm-message="Rawat inap {{ $admission->code }} akan dibatalkan dan tempat tidur dikosongkan. Lanjutkan?">
                            <i class="bi bi-x-circle me-1"></i>Batalkan
                        </button>
                    </form>
                </x-card>
            @endif
        </div>
    </div>
@endsection

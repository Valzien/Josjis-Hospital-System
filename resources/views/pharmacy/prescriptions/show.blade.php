@extends('layouts.app')

@section('title', 'Resep '.$prescription->code)

@section('content')
    <x-page-header :title="'Resep '.$prescription->code"
                   :description="$prescription->patient->name.' &middot; '.$prescription->doctor->name"
                   icon="bi-file-earmark-medical">
        <x-slot:actions>
            <a href="{{ route('pharmacy.prescriptions.index') }}" class="btn btn-light border btn-sm">
                <i class="bi bi-arrow-left me-1"></i>Daftar Resep
            </a>
            <a href="{{ route('pharmacy.prescriptions.print', $prescription) }}" target="_blank" rel="noopener"
               class="btn btn-light border btn-sm">
                <i class="bi bi-printer me-1"></i>Cetak
            </a>
        </x-slot:actions>
    </x-page-header>

    @php $status = $prescription->status; @endphp

    <x-card class="mb-3">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div class="d-flex align-items-center gap-3">
                <x-status-badge :status="$status" />
                <div class="fs-7 text-muted-2">
                    Dibuat {{ $prescription->created_at?->translatedFormat('d F Y H:i') }}
                    @if ($prescription->processed_at)
                        &middot; Diproses {{ $prescription->processed_at->translatedFormat('d F Y H:i') }}
                        oleh {{ $prescription->processedBy?->name ?? 'Sistem' }}
                    @endif
                </div>
            </div>

            <div class="d-flex gap-2 flex-wrap">
                @if ($status?->value === 'PENDING')
                    <form method="POST" action="{{ route('pharmacy.prescriptions.process', $prescription) }}">
                        @csrf @method('PATCH')
                        <button class="btn btn-primary" type="submit"><i class="bi bi-arrow-repeat me-1"></i>Mulai Proses</button>
                    </form>
                @elseif ($status?->value === 'PROCESSING')
                    <form method="POST" action="{{ route('pharmacy.prescriptions.ready', $prescription) }}">
                        @csrf @method('PATCH')
                        <button class="btn btn-primary" type="submit"><i class="bi bi-bag-check me-1"></i>Tandai Siap Diambil</button>
                    </form>
                @elseif ($status?->value === 'READY')
                    <form method="POST" action="{{ route('pharmacy.prescriptions.complete', $prescription) }}">
                        @csrf @method('PATCH')
                        <button class="btn btn-success" type="submit" data-confirm-title="Serahkan Obat"
                                data-confirm-message="Stok obat akan dikurangi dan resep ditandai selesai. Lanjutkan?">
                            <i class="bi bi-check-lg me-1"></i>Serahkan Obat
                        </button>
                    </form>
                @endif

                @if ($status?->canTransitionTo(\App\Enums\PrescriptionStatus::Cancelled))
                    <form method="POST" action="{{ route('pharmacy.prescriptions.cancel', $prescription) }}"
                          class="d-flex gap-1">
                        @csrf @method('PATCH')
                        <input type="text" name="reason" class="form-control form-control-sm" style="width:220px"
                               placeholder="Alasan pembatalan (opsional)" maxlength="300">
                        <button class="btn btn-outline-danger btn-sm text-nowrap" type="submit"
                                data-confirm-title="Batalkan Resep"
                                data-confirm-message="Resep akan dibatalkan dan tidak dapat dipulihkan. Lanjutkan?">
                            Batalkan
                        </button>
                    </form>
                @endif
            </div>
        </div>
    </x-card>

    @if (! empty($shortages))
        <div class="alert alert-warning">
            <div class="fw-semibold mb-1"><i class="bi bi-exclamation-triangle me-1"></i>Stok obat tidak mencukupi</div>
            <ul class="mb-0 ps-3 small">
                @foreach ($shortages as $shortage)
                    <li>{{ $shortage }}</li>
                @endforeach
            </ul>
            <div class="small mt-2">
                Lengkapi stok terlebih dahulu melalui
                <a href="{{ route('pharmacy.stock.index') }}" class="text-decoration-none">Manajemen Stok</a>.
            </div>
        </div>
    @endif

    <div class="row g-3">
        <div class="col-xl-4">
            <x-card title="Pasien" icon="bi-person">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <x-avatar :name="$prescription->patient->name" />
                    <div class="min-w-0">
                        <div class="fw-bold text-truncate">{{ $prescription->patient->name }}</div>
                        <div class="fs-7 text-muted-2">{{ $prescription->patient->medical_record_number }}</div>
                    </div>
                </div>

                @if ($prescription->patient->allergies)
                    <div class="alert alert-danger py-2 small mb-3">
                        <i class="bi bi-exclamation-triangle me-1"></i>
                        <strong>Alergi:</strong> {{ $prescription->patient->allergies }}
                    </div>
                @endif

                <dl class="row mb-0 small">
                    <dt class="col-5 text-muted-2 fw-normal">Jenis Kelamin</dt>
                    <dd class="col-7">{{ $prescription->patient->genderLabel() }}</dd>

                    <dt class="col-5 text-muted-2 fw-normal">Usia</dt>
                    <dd class="col-7">{{ $prescription->patient->age() ? $prescription->patient->age().' tahun' : '-' }}</dd>

                    <dt class="col-5 text-muted-2 fw-normal">Telepon</dt>
                    <dd class="col-7">{{ $prescription->patient->phone ?? '-' }}</dd>
                </dl>
            </x-card>

            @if ($prescription->medicalRecord)
                <x-card title="Keterangan Klinis" icon="bi-clipboard2-pulse" class="mt-3">
                    <dl class="row mb-0 small">
                        <dt class="col-5 text-muted-2 fw-normal">Diagnosis</dt>
                        <dd class="col-7">{{ $prescription->medicalRecord->diagnosis ?? '-' }}</dd>

                        <dt class="col-5 text-muted-2 fw-normal">Tindakan</dt>
                        <dd class="col-7">{{ $prescription->medicalRecord->treatment ?? '-' }}</dd>

                        <dt class="col-5 text-muted-2 fw-normal">Waktu</dt>
                        <dd class="col-7">{{ $prescription->medicalRecord->examined_at?->translatedFormat('d M Y H:i') }}</dd>
                    </dl>
                </x-card>
            @endif
        </div>

        <div class="col-xl-8">
            <x-card title="Detail Obat" icon="bi-capsule">
                <div class="table-responsive">
                    <table class="table table-hover align-middle jhs-table-compact">
                        <thead>
                            <tr>
                                <th>Obat</th>
                                <th class="text-center">Diminta</th>
                                <th class="text-center">Stok</th>
                                <th class="text-center">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($prescription->details as $detail)
                                <tr>
                                    <td class="small fw-semibold">{{ $detail->medicine->name }}</td>
                                    <td class="small text-center">{{ $detail->quantity }} {{ $detail->medicine->unit }}</td>
                                    <td class="small text-center">
                                        <span class="{{ $detail->medicine->stock < $detail->quantity ? 'text-danger fw-bold' : 'text-muted-2' }}">
                                            {{ $detail->medicine->stock }}
                                        </span>
                                    </td>
                                    <td class="small text-end">{{ \Illuminate\Support\Number::currency($detail->subtotal, 'IDR', 'id') }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="small text-muted-2">Resep belum memiliki detail obat.</td></tr>
                            @endforelse
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="3" class="text-end fw-semibold">Total</td>
                                <td class="text-end fw-bold">{{ \Illuminate\Support\Number::currency($prescription->total_price, 'IDR', 'id') }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                @if ($prescription->notes)
                    <div class="alert alert-light border small mt-3 mb-0">
                        <div class="fw-semibold mb-1">Catatan dokter</div>
                        {{ $prescription->notes }}
                    </div>
                @endif
            </x-card>
        </div>
    </div>
@endsection
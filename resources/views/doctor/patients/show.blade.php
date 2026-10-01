@extends('layouts.app')

@section('title', $patient->name)

@section('content')
    <x-page-header :title="$patient->name"
                   :description="'No. RM '.$patient->medical_record_number.' &middot; '.($patient->age() ? $patient->age().' tahun' : 'Usia belum tercatat')"
                   icon="bi-person-vcard">
        <x-slot:actions>
            <a href="{{ route('doctor.patients.index') }}" class="btn btn-light border btn-sm">
                <i class="bi bi-arrow-left me-1"></i>Pasien Saya
            </a>
        </x-slot:actions>
    </x-page-header>

    @if ($patient->allergies)
        <div class="alert alert-danger">
            <i class="bi bi-exclamation-triangle me-1"></i>
            <strong>Peringatan Alergi:</strong> {{ $patient->allergies }}
        </div>
    @endif

    <div class="row g-3">
        <div class="col-xl-4">
            <x-card title="Data Pasien" icon="bi-person">
                <div class="text-center mb-3">
                    <x-avatar :name="$patient->name" size="lg" class="mb-2" />
                    <div class="fw-bold">{{ $patient->name }}</div>
                    <div class="fs-7 text-muted-2">{{ $patient->medical_record_number }}</div>
                </div>

                <dl class="row mb-0 small">
                    <dt class="col-5 text-muted-2 fw-normal">NIK</dt>
                    <dd class="col-7">{{ $patient->nik ?? '-' }}</dd>

                    <dt class="col-5 text-muted-2 fw-normal">Jenis Kelamin</dt>
                    <dd class="col-7">{{ $patient->genderLabel() }}</dd>

                    <dt class="col-5 text-muted-2 fw-normal">Tanggal Lahir</dt>
                    <dd class="col-7">{{ $patient->birth_date?->translatedFormat('d M Y') ?? '-' }}</dd>

                    <dt class="col-5 text-muted-2 fw-normal">Golongan Darah</dt>
                    <dd class="col-7">{{ $patient->blood_type?->label() ?? '-' }}</dd>

                    <dt class="col-5 text-muted-2 fw-normal">Telepon</dt>
                    <dd class="col-7">{{ $patient->phone ?? '-' }}</dd>

                    <dt class="col-5 text-muted-2 fw-normal">Alamat</dt>
                    <dd class="col-7">{{ $patient->address ?? '-' }}</dd>

                    <dt class="col-5 text-muted-2 fw-normal">Kontak Darurat</dt>
                    <dd class="col-7">{{ $patient->emergency_contact_name ?? '-' }}</dd>
                </dl>
            </x-card>

            <x-card title="Riwayat Antrean" subtitle="10 antrean terakhir" icon="bi-list-ol" class="mt-3">
                <div class="vstack gap-2">
                    @forelse ($queues as $queue)
                        <div class="d-flex align-items-center justify-content-between gap-2 border rounded-3 p-2">
                            <div class="d-flex align-items-center gap-2 min-w-0">
                                <span class="jhs-queue-number">{{ $queue->queue_number }}</span>
                                <div class="min-w-0">
                                    <div class="fs-7 text-muted-2">{{ $queue->doctor->name }}</div>
                                    <div class="fs-7 text-muted-2">{{ $queue->queue_date?->translatedFormat('d M Y') }}</div>
                                </div>
                            </div>
                            <x-status-badge :status="$queue->status" />
                        </div>
                    @empty
                        <x-empty-state icon="bi-list-ol" title="Belum ada antrean" />
                    @endforelse
                </div>
            </x-card>
        </div>

        <div class="col-xl-8">
            <x-card title="Riwayat Pemeriksaan Saya" subtitle="{{ $myRecords->count() }} pemeriksaan" icon="bi-journal-medical">
                <div class="vstack gap-2">
                    @forelse ($myRecords as $record)
                        <div class="border rounded-3 p-3">
                            <div class="d-flex align-items-center justify-content-between gap-2 mb-1">
                                <span class="fw-semibold">{{ $record->diagnosis ?? 'Diagnosis belum diisi' }}</span>
                                <span class="fs-7 text-muted-2 text-nowrap">{{ $record->examined_at?->translatedFormat('d M Y H:i') }}</span>
                            </div>
                            @if ($record->complaint)
                                <div class="fs-7 text-muted-2">Keluhan: {{ $record->complaint }}</div>
                            @endif
                            @if ($record->treatment)
                                <div class="fs-7 text-muted-2">Tindakan: {{ $record->treatment }}</div>
                            @endif
                            @if ($record->prescription)
                                <div class="mt-2">
                                    <x-badge :text="count($record->prescription->details).' obat dalam resep'"
                                             icon="bi-file-earmark-medical" color="primary" />
                                </div>
                            @endif
                            <a href="{{ route('doctor.examinations.show', $record) }}" class="btn btn-sm btn-light border mt-2">Detail</a>
                        </div>
                    @empty
                        <x-empty-state icon="bi-journal-medical" title="Belum ada pemeriksaan"
                                       text="Anda belum memeriksa pasien ini." />
                    @endforelse
                </div>
            </x-card>

            <x-card title="Seluruh Riwayat Pasien" subtitle="Rekam medis dari semua dokter" icon="bi-clipboard2-pulse" class="mt-3">
                <div class="table-responsive">
                    <table class="table table-hover align-middle jhs-table-compact">
                        <thead>
                            <tr>
                                <th>Tanggal</th>
                                <th>Dokter</th>
                                <th>Diagnosis</th>
                                <th>Resep</th>
                                <th class="text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($records as $record)
                                <tr>
                                    <td class="small text-nowrap">{{ $record->examined_at?->translatedFormat('d M Y') }}</td>
                                    <td class="small">{{ $record->doctor?->name }}</td>
                                    <td class="small">{{ \Illuminate\Support\Str::limit($record->diagnosis ?? '-', 40) }}</td>
                                    <td>
                                        @if ($record->hasPrescription())
                                            <x-badge text="Ada" icon="bi-check-lg" color="primary" />
                                        @else
                                            <span class="fs-7 text-muted-2">-</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <a href="{{ route('doctor.medical-records.show', $record) }}" class="btn btn-sm btn-light border">Detail</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5"><x-empty-state icon="bi-clipboard2-pulse" title="Belum ada rekam medis" /></td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($records->hasPages())
                    <div class="mt-3">{{ $records->links('pagination::bootstrap-5') }}</div>
                @endif
            </x-card>
        </div>
    </div>
@endsection
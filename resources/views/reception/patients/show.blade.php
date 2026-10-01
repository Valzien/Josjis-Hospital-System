@extends('layouts.app')

@section('title', $patient->name)

@section('content')
    <x-page-header :title="$patient->name" :description="'No. RM '.$patient->medical_record_number.' &middot; Terdaftar '.($patient->created_at?->translatedFormat('d F Y'))"
                   icon="bi-person-vcard">
        <x-slot:actions>
            <a href="{{ route('reception.patients.index') }}" class="btn btn-light border btn-sm">
                <i class="bi bi-arrow-left me-1"></i>Daftar Pasien
            </a>
            <a href="{{ route('reception.registration.edit', $patient) }}" class="btn btn-light border btn-sm">
                <i class="bi bi-pencil me-1"></i>Ubah Data
            </a>
            <a href="{{ route('reception.queues.create') }}" class="btn btn-primary btn-sm">
                <i class="bi bi-plus-lg me-1"></i>Buat Antrean
            </a>
        </x-slot:actions>
    </x-page-header>

    @if ($openQueue)
        <div class="alert alert-warning d-flex flex-wrap align-items-center justify-content-between gap-2">
            <div>
                <i class="bi bi-hourglass-split me-1"></i>
                Pasien ini masih memiliki antrean aktif
                <strong>{{ $openQueue->queue_number }}</strong>
                di {{ $openQueue->doctor->name }}
                ({{ $openQueue->statusLabel() }}).
            </div>
            <a href="{{ route('reception.queues.show', $openQueue) }}" class="btn btn-sm btn-warning">Lihat Antrean</a>
        </div>
    @endif

    <div class="row g-3">
        <div class="col-xl-4">
            <x-card title="Data Pasien" icon="bi-person">
                <div class="text-center mb-3">
                    <x-avatar :name="$patient->name" size="lg" class="mb-2" />
                    <div class="fw-bold">{{ $patient->name }}</div>
                    <div class="fs-7 text-muted-2">{{ $patient->medical_record_number }}</div>
                    @if ($patient->user)
                        <x-badge text="Akun Pasien Aktif" icon="bi-person-check" color="success" class="mt-2" />
                    @endif
                </div>

                <dl class="row mb-0 small">
                    <dt class="col-5 text-muted-2 fw-normal">NIK</dt>
                    <dd class="col-7">{{ $patient->nik ?? '-' }}</dd>

                    <dt class="col-5 text-muted-2 fw-normal">Jenis Kelamin</dt>
                    <dd class="col-7">{{ $patient->genderLabel() }}</dd>

                    <dt class="col-5 text-muted-2 fw-normal">Usia</dt>
                    <dd class="col-7">{{ $patient->age() ? $patient->age().' tahun' : '-' }}</dd>

                    <dt class="col-5 text-muted-2 fw-normal">Golongan Darah</dt>
                    <dd class="col-7">{{ $patient->blood_type?->label() ?? '-' }}</dd>

                    <dt class="col-5 text-muted-2 fw-normal">Telepon</dt>
                    <dd class="col-7">{{ $patient->phone ?? '-' }}</dd>

                    <dt class="col-5 text-muted-2 fw-normal">Alamat</dt>
                    <dd class="col-7">{{ $patient->address ?? '-' }}</dd>

                    @if ($patient->allergies)
                        <dt class="col-5 text-muted-2 fw-normal">Alergi</dt>
                        <dd class="col-7 text-danger">{{ $patient->allergies }}</dd>
                    @endif

                    <dt class="col-5 text-muted-2 fw-normal">Kontak Darurat</dt>
                    <dd class="col-7">
                        {{ $patient->emergency_contact_name ?? '-' }}
                        @if ($patient->emergency_contact_phone)
                            <div class="fs-7 text-muted-2">{{ $patient->emergency_contact_phone }}</div>
                        @endif
                    </dd>
                </dl>
            </x-card>
        </div>

        <div class="col-xl-8">
            <div class="row g-3">
                <div class="col-12">
                    <x-card title="Riwayat Antrean" subtitle="10 antrean terakhir" icon="bi-list-ol">
                        <x-slot:actions>
                            <a href="{{ route('reception.queues.index') }}" class="btn btn-sm btn-light border">Semua Antrean</a>
                        </x-slot:actions>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle jhs-table-compact">
                                <thead>
                                    <tr>
                                        <th>Nomor</th>
                                        <th>Tanggal</th>
                                        <th>Dokter</th>
                                        <th>Status</th>
                                        <th class="text-end">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($queues as $queue)
                                        <tr>
                                            <td class="fw-bold small">{{ $queue->queue_number }}</td>
                                            <td class="small">{{ $queue->queue_date?->translatedFormat('d M Y') }}</td>
                                            <td class="small">{{ $queue->doctor->name }}</td>
                                            <td><x-status-badge :status="$queue->status" /></td>
                                            <td class="text-end">
                                                <a href="{{ route('reception.queues.show', $queue) }}" class="btn btn-sm btn-light border">Detail</a>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5">
                                                <x-empty-state icon="bi-list-ol" title="Belum ada antrean"
                                                               text="Pasien ini belum pernah mengambil antrean." />
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </x-card>
                </div>

                <div class="col-lg-6">
                    <x-card title="Rekam Medis" subtitle="5 pemeriksaan terakhir" icon="bi-clipboard2-pulse">
                        <div class="vstack gap-2">
                            @forelse ($records as $record)
                                <div class="border rounded-3 p-2">
                                    <div class="d-flex justify-content-between gap-2">
                                        <span class="fw-semibold small">{{ $record->diagnosis ?? 'Diagnosis belum diisi' }}</span>
                                        <span class="fs-7 text-muted-2 text-nowrap">{{ $record->examined_at?->translatedFormat('d M Y') }}</span>
                                    </div>
                                    <div class="fs-7 text-muted-2">{{ $record->doctor?->name }}</div>
                                </div>
                            @empty
                                <x-empty-state icon="bi-clipboard2-pulse" title="Belum ada rekam medis" />
                            @endforelse
                        </div>
                    </x-card>
                </div>

                <div class="col-lg-6">
                    <x-card title="Resep" subtitle="5 resep terakhir" icon="bi-file-earmark-medical">
                        <div class="vstack gap-2">
                            @forelse ($prescriptions as $prescription)
                                <div class="border rounded-3 p-2">
                                    <div class="d-flex justify-content-between gap-2">
                                        <span class="fw-semibold small">{{ $prescription->code }}</span>
                                        <span class="fs-7 text-muted-2 text-nowrap">{{ $prescription->created_at?->translatedFormat('d M Y') }}</span>
                                    </div>
                                    <div class="fs-7 text-muted-2">{{ $prescription->doctor?->name }}</div>
                                </div>
                            @empty
                                <x-empty-state icon="bi-file-earmark-medical" title="Belum ada resep" />
                            @endforelse
                        </div>
                    </x-card>
                </div>
            </div>
        </div>
    </div>
@endsection
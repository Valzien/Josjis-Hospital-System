@extends('layouts.app')

@section('title', 'Antrean Hari Ini')

@section('content')
    <x-page-header title="Antrean Hari Ini"
                   description="Daftar pasien yang memiliki antrean pada {{ now()->translatedFormat('d F Y') }}."
                   icon="bi-clock-history">
        <x-slot:actions>
            <a href="{{ route('reception.patients.index') }}" class="btn btn-light border btn-sm">
                <i class="bi bi-people me-1"></i>Semua Pasien
            </a>
            <a href="{{ route('reception.queues.board') }}" class="btn btn-primary btn-sm">
                <i class="bi bi-broadcast me-1"></i>Papan Antrean
            </a>
        </x-slot:actions>
    </x-page-header>

    <x-card class="mb-3">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-lg-6">
                <label class="form-label small mb-1" for="q">Cari Pasien</label>
                <input type="search" name="q" id="q" value="{{ $q }}" class="form-control form-control-sm"
                       placeholder="Nama atau NIK" data-search-submit>
            </div>
            <div class="col-lg-6 d-flex gap-2">
                <button class="btn btn-primary btn-sm" type="submit"><i class="bi bi-search me-1"></i>Cari</button>
                <a href="{{ route('reception.registration.history') }}" class="btn btn-light border btn-sm">Reset</a>
            </div>
        </form>
    </x-card>

    <x-card title="Pasien dengan Antrean" subtitle="{{ $patients->count() }} pasien" icon="bi-people">
        <div class="table-responsive">
            <table class="table table-hover align-middle jhs-table-compact">
                <thead>
                    <tr>
                        <th>Pasien</th>
                        <th>No. RM</th>
                        <th>Antrean Hari Ini</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($patients as $patient)
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <x-avatar :name="$patient->name" size="sm" />
                                    <div class="min-w-0">
                                        <div class="small fw-semibold text-truncate">{{ $patient->name }}</div>
                                        <div class="fs-7 text-muted-2">{{ $patient->nik ?? 'NIK belum diisi' }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="small fw-semibold">{{ $patient->medical_record_number }}</td>
                            <td class="small">{{ $patient->queues_count }} antrean</td>
                            <td class="text-end">
                                <a href="{{ route('reception.patients.show', $patient) }}" class="btn btn-sm btn-light border">Detail</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4">
                                <x-empty-state icon="bi-people" title="Tidak ada antrean hari ini"
                                               text="Belum ada pasien yang mengambil antrean pada tanggal ini." />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-card>
@endsection
@extends('layouts.app')

@section('title', 'Riwayat Pemeriksaan')

@section('content')
    <x-page-header title="Riwayat Pemeriksaan"
                   :description="'Seluruh rekam medis Anda, No. RM '.($patient?->medical_record_number ?? '-').'.'"
                   icon="bi-journal-medical">
        <x-slot:actions>
            <a href="{{ route('patient.prescriptions.index') }}" class="btn btn-light border btn-sm">
                <i class="bi bi-file-earmark-medical me-1"></i>Resep Saya
            </a>
        </x-slot:actions>
    </x-page-header>

    <x-card class="mb-3">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-lg-5">
                <label class="form-label small mb-1" for="q">Cari</label>
                <input type="search" name="q" id="q" value="{{ $filters['q'] ?? '' }}"
                       class="form-control form-control-sm" placeholder="Keluhan, diagnosis, atau tindakan" data-search-submit>
            </div>
            <div class="col-lg-3">
                <label class="form-label small mb-1" for="from">Dari Tanggal</label>
                <input type="date" name="from" id="from" value="{{ $filters['from'] ?? '' }}" class="form-control form-control-sm">
            </div>
            <div class="col-lg-3">
                <label class="form-label small mb-1" for="to">Sampai Tanggal</label>
                <input type="date" name="to" id="to" value="{{ $filters['to'] ?? '' }}" class="form-control form-control-sm">
            </div>
            <div class="col-lg-1 d-grid">
                <button class="btn btn-primary btn-sm" type="submit"><i class="bi bi-funnel"></i></button>
            </div>
        </form>
    </x-card>

    <x-data-table :rows="$records" :columns="[
        ['label' => 'Tanggal', 'key' => 'examined_at'],
        ['label' => 'Dokter', 'key' => 'doctor'],
        ['label' => 'Diagnosis', 'key' => 'diagnosis'],
    ]" empty-title="Belum ada riwayat" empty-icon="bi-journal-medical"
       empty-text="Riwayat pemeriksaan akan muncul setelah dokter menyelesaikan pemeriksaan Anda.">
        @forelse ($records as $record)
            <tr onclick="window.location.href='{{ route('patient.history.show', $record) }}'">
                <td class="small text-nowrap">
                    <div class="fw-semibold">{{ $record->examined_at?->translatedFormat('d M Y') ?? '-' }}</div>
                    <div class="fs-8 text-muted-2">{{ $record->examined_at?->format('H:i') ?? '-' }}</div>
                </td>
                <td>
                    <div class="small fw-semibold text-truncate">{{ $record->doctor?->name ?? '-' }}</div>
                    <div class="fs-8 text-muted-2">{{ $record->doctor?->specialization ?? '-' }}</div>
                </td>
                <td>
                    <div class="small text-truncate">{{ $record->diagnosis ?? '-' }}</div>
                    @if ($record->prescription)
                        <x-badge text="Ada resep" icon="bi-file-earmark-medical" color="info" class="mt-1" />
                    @endif
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="3">
                    <x-empty-state icon="bi-journal-medical" title="Belum ada riwayat"
                                  text="Riwayat pemeriksaan akan muncul setelah dokter menyelesaikan pemeriksaan Anda." />
                </td>
            </tr>
        @endforelse
    </x-data-table>
@endsection
@extends('layouts.app')

@section('title', 'Pemeriksaan')

@section('content')
    <x-page-header title="Rekam Medis" description="Riwayat pemeriksaan yang Anda tangani." icon="bi-clipboard2-pulse">
        <x-slot:actions>
            <a href="{{ route('doctor.medical-records.index') }}" class="btn btn-light border btn-sm">
                <i class="bi bi-journal-medical me-1"></i>Semua Rekam Medis
            </a>
        </x-slot:actions>
    </x-page-header>

    <div class="row g-3 mb-3">
        <div class="col-4">
            <x-stat-card label="Bulan Ini" :value="$monthlyCount" icon="bi-calendar-month" color="primary" />
        </div>
        <div class="col-4">
            <x-stat-card label="Dengan Resep" :value="$prescriptionCount" icon="bi-file-earmark-medical" color="info" />
        </div>
        <div class="col-4">
            <x-stat-card label="Total" :value="$totalCount" icon="bi-journal-medical" color="secondary" />
        </div>
    </div>

    <x-card class="mb-3">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-lg-4">
                <label class="form-label small mb-1" for="q">Cari</label>
                <input type="search" name="q" id="q" value="{{ $filters['q'] ?? '' }}"
                       class="form-control form-control-sm" placeholder="Nama pasien atau diagnosis" data-search-submit>
            </div>
            <div class="col-lg-2">
                <label class="form-label small mb-1" for="from">Dari</label>
                <input type="date" name="from" id="from" value="{{ $filters['from'] ?? '' }}" class="form-control form-control-sm">
            </div>
            <div class="col-lg-2">
                <label class="form-label small mb-1" for="to">Sampai</label>
                <input type="date" name="to" id="to" value="{{ $filters['to'] ?? '' }}" class="form-control form-control-sm">
            </div>
            <div class="col-lg-4 d-flex gap-2">
                <button class="btn btn-primary btn-sm" type="submit"><i class="bi bi-funnel me-1"></i>Terapkan</button>
                <a href="{{ route('doctor.examinations.index') }}" class="btn btn-light border btn-sm">Reset</a>
            </div>
        </form>
    </x-card>

    <x-data-table :rows="$records" :columns="[
        ['label' => 'No. Rekam', 'key' => 'record_number'],
        ['label' => 'Tanggal', 'key' => 'examined_at'],
        ['label' => 'Pasien', 'key' => 'patient'],
        ['label' => 'Diagnosis', 'key' => 'diagnosis'],
        ['label' => 'Resep', 'key' => 'prescription'],
    ]" empty-title="Belum ada pemeriksaan" empty-icon="bi-clipboard2-pulse">
        @forelse ($records as $record)
            <tr onclick="window.location.href='{{ route('doctor.examinations.show', $record) }}'">
                <td class="small fw-semibold">{{ $record->record_number }}</td>
                <td class="small text-nowrap">{{ $record->examined_at?->translatedFormat('d M Y H:i') }}</td>
                <td>
                    <div class="small fw-semibold text-truncate">{{ $record->patient->name }}</div>
                    <div class="fs-7 text-muted-2">{{ $record->patient->medical_record_number }}</div>
                </td>
                <td class="small">{{ \Illuminate\Support\Str::limit($record->diagnosis ?? '-', 40) }}</td>
                <td>
                    @if ($record->hasPrescription())
                        <x-badge :text="$record->prescription->code" icon="bi-file-earmark-medical" color="primary" />
                    @else
                        <span class="fs-7 text-muted-2">-</span>
                    @endif
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="5"><x-empty-state icon="bi-clipboard2-pulse" title="Belum ada pemeriksaan" /></td>
            </tr>
        @endforelse
    </x-data-table>
@endsection
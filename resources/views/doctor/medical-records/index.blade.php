@extends('layouts.app')

@section('title', 'Rekam Medis')

@section('content')
    <x-page-header title="Arsip Rekam Medis"
                   description="Seluruh rekam medis rumah sakit. Gunakan filter untuk mempersempit pencarian."
                   icon="bi-journal-medical">
        <x-slot:actions>
            <a href="{{ route('doctor.examinations.index') }}" class="btn btn-light border btn-sm">
                <i class="bi bi-clipboard2-pulse me-1"></i>Pemeriksaan Saya
            </a>
        </x-slot:actions>
    </x-page-header>

    <x-card class="mb-3">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-lg-3">
                <label class="form-label small mb-1" for="q">Cari</label>
                <input type="search" name="q" id="q" value="{{ $filters['q'] ?? '' }}"
                       class="form-control form-control-sm" placeholder="Nama pasien, diagnosis, atau catatan" data-search-submit>
            </div>
            <div class="col-lg-3">
                <label class="form-label small mb-1" for="doctor_id">Dokter</label>
                <select name="doctor_id" id="doctor_id" class="form-select form-select-sm">
                    <option value="">Semua Dokter</option>
                    @foreach ($doctors as $doctor)
                        <option value="{{ $doctor->id }}" @selected((string) ($filters['doctor_id'] ?? '') === (string) $doctor->id)>
                            {{ $doctor->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-lg-2">
                <label class="form-label small mb-1" for="from">Dari</label>
                <input type="date" name="from" value="{{ $filters['from'] ?? '' }}" class="form-control form-control-sm">
            </div>
            <div class="col-lg-2">
                <label class="form-label small mb-1" for="to">Sampai</label>
                <input type="date" name="to" value="{{ $filters['to'] ?? '' }}" class="form-control form-control-sm">
            </div>
            <div class="col-lg-2 d-flex gap-2">
                <button class="btn btn-primary btn-sm" type="submit"><i class="bi bi-funnel me-1"></i>Terapkan</button>
                <a href="{{ route('doctor.medical-records.index') }}" class="btn btn-light border btn-sm">Reset</a>
            </div>
            <div class="col-12">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="mine" value="1" id="mine"
                           @checked($filters['mine'] ?? false)>
                    <label class="form-check-label small" for="mine">Tampilkan hanya rekam medis saya</label>
                </div>
            </div>
        </form>
    </x-card>

    <x-data-table :rows="$records" :columns="[
        ['label' => 'No. Rekam', 'key' => 'record_number'],
        ['label' => 'Tanggal', 'key' => 'examined_at'],
        ['label' => 'Pasien', 'key' => 'patient'],
        ['label' => 'Dokter', 'key' => 'doctor'],
        ['label' => 'Diagnosis', 'key' => 'diagnosis'],
        ['label' => 'Resep', 'key' => 'prescription'],
    ]" empty-title="Belum ada rekam medis" empty-icon="bi-journal-medical">
        @forelse ($records as $record)
            <tr onclick="window.location.href='{{ route('doctor.medical-records.show', $record) }}'">
                <td class="small fw-semibold">{{ $record->record_number }}</td>
                <td class="small text-nowrap">{{ $record->examined_at?->translatedFormat('d M Y H:i') }}</td>
                <td>
                    <div class="small fw-semibold text-truncate">{{ $record->patient->name }}</div>
                    <div class="fs-7 text-muted-2">{{ $record->patient->medical_record_number }}</div>
                </td>
                <td class="small">{{ $record->doctor?->name }}</td>
                <td class="small">{{ \Illuminate\Support\Str::limit($record->diagnosis ?? '-', 40) }}</td>
                <td>
                    @if ($record->hasPrescription())
                        <x-badge text="Ada" icon="bi-check-lg" color="primary" />
                    @else
                        <span class="fs-7 text-muted-2">-</span>
                    @endif
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="6"><x-empty-state icon="bi-journal-medical" title="Belum ada rekam medis" /></td>
            </tr>
        @endforelse
    </x-data-table>
@endsection
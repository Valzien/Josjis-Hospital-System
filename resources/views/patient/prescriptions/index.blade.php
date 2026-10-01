@extends('layouts.app')

@section('title', 'Resep Saya')

@section('content')
    <x-page-header title="Resep Saya" description="Daftar resep yang pernah diberikan oleh dokter kepada Anda." icon="bi-file-earmark-medical">
        <x-slot:actions>
            <a href="{{ route('patient.history.index') }}" class="btn btn-light border btn-sm">
                <i class="bi bi-journal-medical me-1"></i>Riwayat Pemeriksaan
            </a>
        </x-slot:actions>
    </x-page-header>

    <x-card class="mb-3">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-lg-4">
                <label class="form-label small mb-1" for="status">Status</label>
                <select name="status" id="status" class="form-select form-select-sm">
                    <option value="">Semua Status</option>
                    @foreach ($statuses as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-lg-3">
                <label class="form-label small mb-1" for="from">Dari Tanggal</label>
                <input type="date" name="from" id="from" value="{{ $filters['from'] ?? '' }}" class="form-control form-control-sm">
            </div>
            <div class="col-lg-3">
                <label class="form-label small mb-1" for="to">Sampai Tanggal</label>
                <input type="date" name="to" id="to" value="{{ $filters['to'] ?? '' }}" class="form-control form-control-sm">
            </div>
            <div class="col-lg-2 d-grid">
                <button class="btn btn-primary btn-sm" type="submit"><i class="bi bi-funnel me-1"></i>Terapkan</button>
            </div>
        </form>
    </x-card>

    <x-data-table :rows="$prescriptions" :columns="[
        ['label' => 'No. Resep', 'key' => 'code'],
        ['label' => 'Tanggal', 'key' => 'created_at'],
        ['label' => 'Dokter', 'key' => 'doctor'],
        ['label' => 'Obat', 'key' => 'details_count'],
        ['label' => 'Status', 'key' => 'status'],
    ]" empty-title="Belum ada resep" empty-icon="bi-file-earmark-medical"
       empty-text="Resep akan muncul setelah dokter memberikan resep pada pemeriksaan Anda.">
        @forelse ($prescriptions as $prescription)
            <tr onclick="window.location.href='{{ route('patient.prescriptions.show', $prescription) }}'">
                <td class="small fw-semibold">{{ $prescription->code }}</td>
                <td class="small text-nowrap text-muted-2">{{ $prescription->created_at?->translatedFormat('d M Y') }}</td>
                <td>
                    <div class="small fw-semibold text-truncate">{{ $prescription->doctor?->name ?? '-' }}</div>
                    <div class="fs-8 text-muted-2">{{ $prescription->doctor?->specialization ?? '-' }}</div>
                </td>
                <td class="small text-center">{{ $prescription->details_count }} jenis</td>
                <td><x-status-badge :status="$prescription->status" /></td>
            </tr>
        @empty
            <tr>
                <td colspan="5">
                    <x-empty-state icon="bi-file-earmark-medical" title="Belum ada resep"
                                  text="Resep akan muncul setelah dokter memberikan resep pada pemeriksaan Anda." />
                </td>
            </tr>
        @endforelse
    </x-data-table>
@endsection
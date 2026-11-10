@extends('layouts.app')

@section('title', 'Resep Farmasi')

@section('content')
    <x-page-header title="Resep" description="Proses resep dari dokter hingga obat diserahkan kepada pasien." icon="bi-file-earmark-medical" />

    <x-card class="mb-3">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-lg-3">
                <label class="form-label small mb-1" for="q">Cari Resep</label>
                <input type="search" name="q" id="q" value="{{ $filters['q'] ?? '' }}" class="form-control form-control-sm"
                       placeholder="Kode resep atau pasien" aria-label="Cari" data-search-submit>
            </div>
            <div class="col-lg-2">
                <label class="form-label small mb-1" for="status">Status</label>
                <select name="status" id="status" class="form-select form-select-sm" aria-label="Status">
                    <option value="">Semua Status</option>
                    @foreach ($statuses as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-lg-2">
                <label class="form-label small mb-1" for="doctor_id">Dokter</label>
                <select name="doctor_id" id="doctor_id" class="form-select form-select-sm" aria-label="Dokter">
                    <option value="">Semua Dokter</option>
                    @foreach ($doctors as $doctor)
                        <option value="{{ $doctor->id }}" @selected((string) ($filters['doctor_id'] ?? '') === (string) $doctor->id)>{{ $doctor->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-lg-3">
                <label class="form-label small mb-1">Periode</label>
                <div class="input-group input-group-sm">
                    <input type="date" name="from" value="{{ $filters['from'] ?? '' }}" class="form-control" title="Dari tanggal" aria-label="Dari">
                    <input type="date" name="to" value="{{ $filters['to'] ?? '' }}" class="form-control" title="Sampai tanggal" aria-label="Sampai">
                </div>
            </div>
            <div class="col-lg-2 d-flex gap-2">
                <button class="btn btn-primary btn-sm flex-grow-1" type="submit"><i class="bi bi-funnel me-1"></i>Terapkan</button>
                <a href="{{ route('pharmacy.prescriptions.index') }}" class="btn btn-light border btn-sm">Reset</a>
            </div>
        </form>
    </x-card>

    <div class="row g-3 mb-3">
        @foreach ($statuses as $value => $label)
            <div class="col-6 col-xl">
                <a href="{{ route('pharmacy.prescriptions.index', array_merge(request()->only('q', 'doctor_id', 'from', 'to'), ['status' => $value])) }}"
                   class="card border-0 shadow-sm h-100 text-decoration-none">
                    <div class="card-body p-3">
                        <div class="fs-7 text-muted-2 text-uppercase">{{ $label }}</div>
                        <div class="fs-5 fw-bold text-dark">{{ $summary[$value] ?? 0 }}</div>
                    </div>
                </a>
            </div>
        @endforeach
    </div>

    <x-data-table :rows="$prescriptions" :columns="[
        ['label' => 'Kode Resep', 'key' => 'code'],
        ['label' => 'Tanggal', 'key' => 'created_at'],
        ['label' => 'Pasien', 'key' => 'patient'],
        ['label' => 'Dokter', 'key' => 'doctor'],
        ['label' => 'Item', 'key' => 'details_count'],
        ['label' => 'Total', 'key' => 'total_price'],
        ['label' => 'Status', 'key' => 'status'],
    ]" empty-title="Belum ada resep" empty-icon="bi-file-earmark-medical">
        @forelse ($prescriptions as $prescription)
            <tr onclick="window.location.href='{{ route('pharmacy.prescriptions.show', $prescription) }}'">
                <td class="small fw-semibold">{{ $prescription->code }}</td>
                <td class="small text-nowrap">{{ $prescription->created_at?->translatedFormat('d M Y H:i') }}</td>
                <td>
                    <div class="small fw-semibold text-truncate">{{ $prescription->patient->name }}</div>
                    <div class="fs-7 text-muted-2">{{ $prescription->patient->medical_record_number }}</div>
                </td>
                <td class="small">{{ $prescription->doctor->name }}</td>
                <td class="small">{{ $prescription->details_count }} obat</td>
                <td class="small">{{ \Illuminate\Support\Number::currency($prescription->total_price, 'IDR', 'id') }}</td>
                <td><x-status-badge :status="$prescription->status" /></td>
            </tr>
        @empty
            <tr>
                <td colspan="7"><x-empty-state icon="bi-file-earmark-medical" title="Belum ada resep" /></td>
            </tr>
        @endforelse
    </x-data-table>
@endsection
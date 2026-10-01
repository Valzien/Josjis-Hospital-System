@extends('layouts.app')

@section('title', 'Resep Farmasi')

@section('content')
    <x-page-header title="Resep" description="Proses resep dari dokter hingga obat diserahkan kepada pasien." icon="bi-file-earmark-medical">
        <x-slot:actions>
            <form method="GET" class="d-flex gap-2 flex-wrap">
                <input type="search" name="q" value="{{ $filters['q'] ?? '' }}" class="form-control form-control-sm"
                       placeholder="Kode resep atau pasien" aria-label="Cari" data-search-submit>
                <select name="status" class="form-select form-select-sm" aria-label="Status">
                    <option value="">Semua Status</option>
                    @foreach ($statuses as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <select name="doctor_id" class="form-select form-select-sm" aria-label="Dokter">
                    <option value="">Semua Dokter</option>
                    @foreach ($doctors as $doctor)
                        <option value="{{ $doctor->id }}" @selected((string) ($filters['doctor_id'] ?? '') === (string) $doctor->id)>{{ $doctor->name }}</option>
                    @endforeach
                </select>
                <input type="date" name="from" value="{{ $filters['from'] ?? '' }}" class="form-control form-control-sm"
                       aria-label="Dari" title="Dari tanggal">
                <input type="date" name="to" value="{{ $filters['to'] ?? '' }}" class="form-control form-control-sm"
                       aria-label="Sampai" title="Sampai tanggal">
                <button class="btn btn-light border btn-sm" type="submit">Terapkan</button>
            </form>
        </x-slot:actions>
    </x-page-header>

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
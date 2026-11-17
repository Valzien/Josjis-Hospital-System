@extends('layouts.app')

@section('title', 'Pasien Rawat Inap')

@section('content')
    <x-page-header title="Pasien Rawat Inap"
                   description="Daftar pasien yang dirawat inap, visite, dan pencatatan tanda vital."
                   icon="bi-hospital">
        <x-slot:actions>
            <a href="{{ route('nurse.admissions.create') }}" class="btn btn-primary btn-sm">
                <i class="bi bi-plus-lg me-1"></i>Daftarkan Rawat Inap
            </a>
        </x-slot:actions>
    </x-page-header>

    <div class="row g-3 mb-3">
        <div class="col-6 col-xl-4">
            <x-stat-card label="Pasien Dirawat" :value="$summary['active']" icon="bi-person-fill" color="primary" />
        </div>
        <div class="col-6 col-xl-4">
            <x-stat-card label="Masuk Hari Ini" :value="$summary['today']" icon="bi-box-arrow-in-right" color="info" />
        </div>
        <div class="col-6 col-xl-4">
            <x-stat-card label="Tempat Tidur Tersedia" :value="$summary['available_beds']" icon="bi-layout-text-window-reverse" color="teal" />
        </div>
    </div>

    <x-card class="mb-3">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-lg-4">
                <label class="form-label small mb-1" for="q">Cari Pasien</label>
                <input type="search" name="q" id="q" value="{{ $filters['q'] ?? '' }}"
                       class="form-control form-control-sm" placeholder="Nama, No. RM, atau kode rawat inap" data-search-submit>
            </div>
            <div class="col-lg-2">
                <label class="form-label small mb-1" for="status">Status</label>
                <select name="status" id="status" class="form-select form-select-sm">
                    <option value="">Semua Status</option>
                    @foreach ($statuses as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-lg-3">
                <label class="form-label small mb-1" for="doctor_id">Dokter</label>
                <select name="doctor_id" id="doctor_id" class="form-select form-select-sm">
                    <option value="">Semua Dokter</option>
                    @foreach ($doctors as $doctor)
                        <option value="{{ $doctor->id }}" @selected((string) ($filters['doctor_id'] ?? '') === (string) $doctor->id)>{{ $doctor->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-lg-3">
                <label class="form-label small mb-1" for="date">Tanggal Masuk</label>
                <input type="date" name="date" id="date" value="{{ $filters['date'] ?? '' }}" class="form-control form-control-sm">
            </div>
            <div class="col-12 d-flex gap-2 justify-content-end">
                <button class="btn btn-primary btn-sm" type="submit"><i class="bi bi-funnel me-1"></i>Terapkan</button>
                <a href="{{ route('nurse.admissions.index') }}" class="btn btn-light border btn-sm">Reset</a>
            </div>
        </form>
    </x-card>

    <x-data-table :rows="$admissions" :columns="[
        ['label' => 'Kode', 'key' => 'code'],
        ['label' => 'Pasien', 'key' => 'patient'],
        ['label' => 'Dokter', 'key' => 'doctor'],
        ['label' => 'Kamar / Tempat Tidur', 'key' => 'bed'],
        ['label' => 'Masuk', 'key' => 'admitted'],
        ['label' => 'Visite Terakhir', 'key' => 'visit'],
        ['label' => 'Status', 'key' => 'status'],
        ['label' => 'Aksi', 'key' => 'actions', 'class' => 'text-end'],
    ]" empty-title="Belum ada rawat inap" empty-icon="bi-hospital"
       empty-text="Daftarkan pasien rawat inap agar perawatan dan tanda vital tercatat.">
        @forelse ($admissions as $admission)
            <tr>
                <td class="small fw-semibold text-nowrap">{{ $admission->code }}</td>
                <td>
                    <div class="small fw-semibold text-truncate">{{ $admission->patient?->name ?? '-' }}</div>
                    <div class="fs-7 text-muted-2">{{ $admission->patient?->medical_record_number ?? '-' }}</div>
                </td>
                <td class="small text-truncate">{{ $admission->doctor?->name ?? '-' }}</td>
                <td class="small">
                    <div>{{ $admission->room?->name ?? '-' }}</div>
                    <div class="fs-7 text-muted-2">{{ $admission->bed?->code ? 'Tempat tidur '.$admission->bed->code : '-' }}</div>
                </td>
                <td class="small text-nowrap">
                    <div>{{ $admission->admitted_at?->format('d/m/Y') }}</div>
                    <div class="fs-7 text-muted-2">{{ $admission->admitted_at?->format('H:i') }}</div>
                </td>
                <td class="small text-nowrap">
                    @if ($admission->latestVisit)
                        {{ $admission->latestVisit->visited_at?->translatedFormat('d M Y H:i') }}
                    @else
                        <span class="text-muted-2">&mdash;</span>
                    @endif
                </td>
                <td><x-status-badge :status="$admission->status" /></td>
                <td class="text-end">
                    <a href="{{ route('nurse.admissions.show', $admission) }}" class="btn btn-sm btn-light border">
                        <i class="bi bi-eye me-1"></i>Buka
                    </a>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="8">
                    <x-empty-state icon="bi-hospital" title="Belum ada rawat inap"
                                   text="Daftarkan pasien rawat inap agar perawatan dan tanda vital tercatat.">
                        <x-slot:action>
                            <a href="{{ route('nurse.admissions.create') }}" class="btn btn-primary btn-sm mt-2">Daftarkan Rawat Inap</a>
                        </x-slot:action>
                    </x-empty-state>
                </td>
            </tr>
        @endforelse
    </x-data-table>
@endsection

@extends('layouts.app')

@section('title', 'Pasien Rawat Inap')

@section('content')
    <x-page-header title="Pasien Rawat Inap"
                   description="Pantau pasien yang Anda rawat inap, catat visite, dan tambahkan tindakan."
                   icon="bi-hospital" />

    <div class="row g-3 mb-3">
        <div class="col-6 col-xl-4">
            <x-stat-card label="Sedang Dirawat" :value="$summary['active']" icon="bi-person-fill" color="primary" />
        </div>
        <div class="col-6 col-xl-4">
            <x-stat-card label="Sudah Pulang" :value="$summary['discharged']" icon="bi-box-arrow-right" color="success" />
        </div>
        <div class="col-6 col-xl-4">
            <x-stat-card label="Total Rawat Inap" :value="$summary['active'] + $summary['discharged']" icon="bi-hospital" color="teal" />
        </div>
    </div>

    <x-card class="mb-3">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-lg-5">
                <label class="form-label small mb-1" for="q">Cari Pasien</label>
                <input type="search" name="q" id="q" value="{{ $filters['q'] ?? '' }}"
                       class="form-control form-control-sm" placeholder="Nama, No. RM, atau kode rawat inap" data-search-submit>
            </div>
            <div class="col-lg-3">
                <label class="form-label small mb-1" for="status">Status</label>
                <select name="status" id="status" class="form-select form-select-sm">
                    <option value="">Semua Status</option>
                    @foreach ($statuses as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-lg-4 d-flex gap-2">
                <button class="btn btn-primary btn-sm flex-grow-1" type="submit"><i class="bi bi-funnel me-1"></i>Terapkan</button>
                <a href="{{ route('doctor.admissions.index') }}" class="btn btn-light border btn-sm">Reset</a>
            </div>
        </form>
    </x-card>

    <x-data-table :rows="$admissions" :columns="[
        ['label' => 'Kode', 'key' => 'code'],
        ['label' => 'Pasien', 'key' => 'patient'],
        ['label' => 'Kamar / Tempat Tidur', 'key' => 'bed'],
        ['label' => 'Masuk', 'key' => 'admitted'],
        ['label' => 'Lama', 'key' => 'days', 'class' => 'text-center'],
        ['label' => 'Status', 'key' => 'status'],
        ['label' => 'Aksi', 'key' => 'actions', 'class' => 'text-end'],
    ]" empty-title="Belum ada pasien rawat inap" empty-icon="bi-hospital"
       empty-text="Pasien yang Anda rawat inap akan tampil di sini.">
        @forelse ($admissions as $admission)
            <tr>
                <td class="small fw-semibold text-nowrap">{{ $admission->code }}</td>
                <td>
                    <div class="small fw-semibold text-truncate">{{ $admission->patient?->name ?? '-' }}</div>
                    <div class="fs-7 text-muted-2">{{ $admission->patient?->medical_record_number ?? '-' }}</div>
                </td>
                <td class="small">
                    <div>{{ $admission->room?->name ?? '-' }}</div>
                    <div class="fs-7 text-muted-2">{{ $admission->bed?->code ? 'Tempat tidur '.$admission->bed->code : '-' }}</div>
                </td>
                <td class="small text-nowrap">{{ $admission->admitted_at?->format('d/m/Y H:i') ?? '-' }}</td>
                <td class="text-center small">{{ $admission->dayCount() }} hari</td>
                <td><x-status-badge :status="$admission->status" /></td>
                <td class="text-end">
                    <a href="{{ route('doctor.admissions.show', $admission) }}" class="btn btn-sm btn-light border" title="Detail">
                        <i class="bi bi-eye"></i>
                    </a>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="7">
                    <x-empty-state icon="bi-hospital" title="Belum ada pasien rawat inap"
                                   text="Pasien yang Anda rawat inap akan tampil di sini." />
                </td>
            </tr>
        @endforelse
    </x-data-table>
@endsection

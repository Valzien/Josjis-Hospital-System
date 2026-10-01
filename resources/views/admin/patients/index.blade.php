@extends('layouts.app')

@section('title', 'Data Pasien')

@section('content')
    <x-page-header title="Data Pasien" description="Seluruh data pasien terdaftar beserta nomor rekam medis." icon="bi-person-vcard">
        <x-slot:actions>
            <a href="{{ route('admin.patients.create') }}" class="btn btn-primary btn-sm">
                <i class="bi bi-person-plus me-1"></i>Daftarkan Pasien
            </a>
        </x-slot:actions>
    </x-page-header>

    <x-card class="mb-3">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-lg-5">
                <label class="form-label small mb-1" for="q">Cari Pasien</label>
                <input type="search" name="q" id="q" value="{{ $filters['q'] ?? '' }}"
                       class="form-control form-control-sm" placeholder="Nama, NIK, atau nomor rekam medis" data-search-submit>
            </div>
            <div class="col-lg-3">
                <label class="form-label small mb-1" for="gender">Jenis Kelamin</label>
                <select name="gender" id="gender" class="form-select form-select-sm">
                    <option value="">Semua</option>
                    @foreach ($genders as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['gender'] ?? '') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-lg-4 d-flex gap-2">
                <button class="btn btn-primary btn-sm flex-grow-1" type="submit"><i class="bi bi-funnel me-1"></i>Terapkan</button>
                <a href="{{ route('admin.patients.index') }}" class="btn btn-light border btn-sm">Reset</a>
            </div>
        </form>
    </x-card>

    <x-data-table :rows="$patients" :columns="[
        ['label' => 'Pasien', 'key' => 'name'],
        ['label' => 'No. Rekam Medis', 'key' => 'mrn'],
        ['label' => 'Demographics', 'key' => 'demographic'],
        ['label' => 'Kontak', 'key' => 'contact'],
        ['label' => 'Pelayanan', 'key' => 'services'],
        ['label' => 'Aksi', 'key' => 'actions', 'cellClass' => 'text-end'],
    ]" empty-title="Belum ada pasien" empty-icon="bi-person-vcard">
        @forelse ($patients as $patient)
            <tr onclick="window.location.href='{{ route('admin.patients.show', $patient) }}'">
                <td>
                    <div class="d-flex align-items-center gap-2">
                        <x-avatar :name="$patient->name" size="sm" />
                        <div class="min-w-0">
                            <div class="fw-semibold small text-truncate">{{ $patient->name }}</div>
                            <div class="fs-7 text-muted-2">{{ $patient->genderLabel() }}</div>
                        </div>
                    </div>
                </td>
                <td class="small fw-semibold">{{ $patient->medical_record_number }}</td>
                <td class="small text-muted-2">
                    {{ $patient->age() ? $patient->age().' tahun' : '-' }}
                    @if ($patient->blood_type)<span class="badge text-bg-light ms-1">{{ $patient->blood_type->label() }}</span>@endif
                </td>
                <td class="small text-muted-2">{{ $patient->phone ?? '-' }}</td>
                <td class="small">
                    <span class="badge text-bg-light me-1">{{ $patient->medical_records_count }} rekam</span>
                    <span class="badge text-bg-light">{{ $patient->queues_count }} antrean</span>
                </td>
                <td class="text-end">
                    <div class="btn-group btn-group-sm">
                        <a href="{{ route('admin.patients.show', $patient) }}" class="btn btn-light border" title="Detail">
                            <i class="bi bi-eye"></i>
                        </a>
                        <a href="{{ route('admin.patients.edit', $patient) }}" class="btn btn-light border" title="Ubah">
                            <i class="bi bi-pencil"></i>
                        </a>
                        <form method="POST" action="{{ route('admin.patients.destroy', $patient) }}"
                              data-confirm-submit data-confirm-title="Hapus Pasien"
                              data-confirm-message="Hapus data pasien {{ $patient->name }}?" data-confirm-variant="danger">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-light border" title="Hapus">
                                <i class="bi bi-trash"></i>
                            </button>
                        </form>
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="6"><x-empty-state icon="bi-person-vcard" title="Belum ada pasien" /></td>
            </tr>
        @endforelse
    </x-data-table>
@endsection
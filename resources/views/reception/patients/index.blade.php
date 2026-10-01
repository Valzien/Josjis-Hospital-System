@extends('layouts.app')

@section('title', 'Daftar Pasien')

@section('content')
    <x-page-header title="Data Pasien" description="Cari, perbarui, dan tinjau riwayat pasien terdaftar." icon="bi-person-vcard">
        <x-slot:actions>
            <a href="{{ route('reception.registration.history') }}" class="btn btn-light border btn-sm">
                <i class="bi bi-clock-history me-1"></i>Antrean Hari Ini
            </a>
            <a href="{{ route('reception.registration.create') }}" class="btn btn-primary btn-sm">
                <i class="bi bi-person-plus me-1"></i>Daftarkan Pasien
            </a>
        </x-slot:actions>
    </x-page-header>

    <x-card class="mb-3">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-lg-5">
                <label class="form-label small mb-1" for="q">Cari Pasien</label>
                <input type="search" name="q" id="q" value="{{ $filters['q'] ?? '' }}"
                       class="form-control form-control-sm" placeholder="Nama, NIK, atau No. RM" data-search-submit>
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
                <div class="form-check align-self-center">
                    <input class="form-check-input" type="checkbox" name="incomplete" value="1" id="incomplete"
                           @checked($filters['incomplete'] ?? false)>
                    <label class="form-check-label small" for="incomplete">Data belum lengkap</label>
                </div>
                <button class="btn btn-primary btn-sm" type="submit"><i class="bi bi-funnel me-1"></i>Terapkan</button>
                <a href="{{ route('reception.patients.index') }}" class="btn btn-light border btn-sm">Reset</a>
            </div>
        </form>
    </x-card>

    <x-data-table :rows="$patients" :columns="[
        ['label' => 'Pasien', 'key' => 'name'],
        ['label' => 'No. RM', 'key' => 'medical_record_number'],
        ['label' => 'Jenis Kelamin', 'key' => 'gender'],
        ['label' => 'Usia', 'key' => 'age'],
        ['label' => 'Telepon', 'key' => 'phone'],
        ['label' => 'Terdaftar', 'key' => 'created_at'],
    ]" empty-title="Belum ada pasien" empty-icon="bi-person-vcard">
        @forelse ($patients as $patient)
            <tr onclick="window.location.href='{{ route('reception.patients.show', $patient) }}'">
                <td>
                    <div class="d-flex align-items-center gap-2">
                        <x-avatar :name="$patient->name" size="sm" />
                        <div class="min-w-0">
                            <div class="small fw-semibold text-truncate">{{ $patient->name }}</div>
                            <div class="fs-7 text-muted-2">{{ $patient->address ? \Illuminate\Support\Str::limit($patient->address, 40) : 'Alamat belum diisi' }}</div>
                        </div>
                    </div>
                </td>
                <td class="small fw-semibold">{{ $patient->medical_record_number }}</td>
                <td class="small">{{ $patient->genderLabel() }}</td>
                <td class="small">{{ $patient->age() ? $patient->age().' tahun' : '-' }}</td>
                <td class="small">{{ $patient->phone ?? '-' }}</td>
                <td class="small text-muted-2">{{ $patient->created_at?->translatedFormat('d M Y') }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="6"><x-empty-state icon="bi-person-vcard" title="Belum ada pasien" /></td>
            </tr>
        @endforelse
    </x-data-table>
@endsection
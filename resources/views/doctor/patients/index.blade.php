@extends('layouts.app')

@section('title', 'Pasien Saya')

@section('content')
    <x-page-header title="Pasien Saya"
                   description="Pasien yang pernah Anda periksa. Daftar hanya menampilkan pasien dengan rekam medis atas nama Anda."
                   icon="bi-person-vcard">
        <x-slot:actions>
            <form method="GET" class="d-flex gap-2">
                <input type="search" name="q" value="{{ $filters['q'] ?? '' }}" class="form-control form-control-sm"
                       placeholder="Nama, NIK, atau No. RM" aria-label="Cari" data-search-submit>
                <button class="btn btn-light border btn-sm" type="submit"><i class="bi bi-search me-1"></i>Cari</button>
            </form>
        </x-slot:actions>
    </x-page-header>

    <x-data-table :rows="$patients" :columns="[
        ['label' => 'Pasien', 'key' => 'name'],
        ['label' => 'No. RM', 'key' => 'medical_record_number'],
        ['label' => 'Jenis Kelamin', 'key' => 'gender'],
        ['label' => 'Usia', 'key' => 'age'],
        ['label' => 'Pemeriksaan Saya', 'key' => 'medical_records_count'],
    ]" empty-title="Belum ada pasien"
                empty-icon="bi-person-vcard"
                empty-text="Pasien akan muncul setelah Anda menyelesaikan pemeriksaan pertama.">
        @forelse ($patients as $patient)
            <tr onclick="window.location.href='{{ route('doctor.patients.show', $patient) }}'">
                <td>
                    <div class="d-flex align-items-center gap-2">
                        <x-avatar :name="$patient->name" size="sm" />
                        <div class="min-w-0">
                            <div class="small fw-semibold text-truncate">{{ $patient->name }}</div>
                            <div class="fs-7 text-muted-2">{{ $patient->nik ?? 'NIK belum diisi' }}</div>
                        </div>
                    </div>
                </td>
                <td class="small fw-semibold">{{ $patient->medical_record_number }}</td>
                <td class="small">{{ $patient->genderLabel() }}</td>
                <td class="small">{{ $patient->age() ? $patient->age().' tahun' : '-' }}</td>
                <td class="small">{{ $patient->medical_records_count }} kali</td>
            </tr>
        @empty
            <tr>
                <td colspan="5"><x-empty-state icon="bi-person-vcard" title="Belum ada pasien" /></td>
            </tr>
        @endforelse
    </x-data-table>
@endsection
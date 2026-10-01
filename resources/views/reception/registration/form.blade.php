@extends('layouts.app')

@section('title', $mode === 'create' ? 'Daftarkan Pasien' : 'Ubah Data Pasien')

@section('content')
    <x-page-header :title="$mode === 'create' ? 'Pendaftaran Pasien Baru' : 'Ubah Data Pasien'"
                   :description="$mode === 'create' ? 'Lengkapi data identitas pasien. Data kontak darurat bersifat opsional.' : 'Perbarui data pasien '.$patient->name.'.'"
                   icon="bi-person-plus">
        <x-slot:actions>
            <a href="{{ route('reception.patients.index') }}" class="btn btn-light border btn-sm">
                <i class="bi bi-arrow-left me-1"></i>Daftar Pasien
            </a>
            @if ($mode === 'edit')
                <a href="{{ route('reception.patients.show', $patient) }}" class="btn btn-primary btn-sm">
                    <i class="bi bi-person-vcard me-1"></i>Lihat Data
                </a>
            @endif
        </x-slot:actions>
    </x-page-header>

    @if (session('success') && $mode === 'create')
        <x-card class="mb-3">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                <div>
                    <div class="fw-semibold text-success">
                        <i class="bi bi-check-circle me-1"></i>{{ session('success') }}
                    </div>
                    <div class="fs-7 text-muted-2 mt-1">Lanjutkan dengan membuat antrean untuk pasien tersebut.</div>
                </div>
                <a href="{{ route('reception.queues.create') }}" class="btn btn-primary btn-sm">
                    <i class="bi bi-plus-lg me-1"></i>Buat Antrean
                </a>
            </div>
        </x-card>
    @endif

    <form method="POST"
          action="{{ $mode === 'create' ? route('reception.registration.store') : route('reception.registration.update', $patient) }}">
        @csrf
        @if ($mode === 'edit')
            @method('PUT')
        @endif

        @if ($errors->any())
            <div class="alert alert-danger">
                <div class="fw-semibold mb-1"><i class="bi bi-exclamation-triangle me-1"></i>Periksa kembali isian berikut:</div>
                <ul class="mb-0 ps-3 small">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="row g-3">
            <div class="col-xl-8">
                <x-card title="Identitas Pasien" subtitle="Wajib diisi: nama lengkap" icon="bi-person">
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label" for="name">Nama Lengkap <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="name" maxlength="100" required
                                   value="{{ old('name', $patient->name) }}"
                                   class="form-control @error('name') is-invalid @enderror"
                                   placeholder="Contoh: Siti Rahmawati">
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="medical_record_number">Nomor Rekam Medis</label>
                            <input type="text" id="medical_record_number"
                                   value="{{ $patient->medical_record_number }}" class="form-control" readonly>
                            <div class="form-text">Dibuat otomatis oleh sistem.</div>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label" for="nik">NIK</label>
                            <input type="text" name="nik" id="nik" maxlength="20" inputmode="numeric"
                                   value="{{ old('nik', $patient->nik) }}"
                                   class="form-control @error('nik') is-invalid @enderror">
                            @error('nik')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="gender">Jenis Kelamin</label>
                            <select name="gender" id="gender" class="form-select @error('gender') is-invalid @enderror">
                                <option value="">Pilih</option>
                                @foreach (\App\Enums\Gender::options() as $value => $label)
                                    <option value="{{ $value }}" @selected(old('gender', $patient->gender?->value) === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('gender')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="birth_date">Tanggal Lahir</label>
                            <input type="date" name="birth_date" id="birth_date" max="{{ now()->toDateString() }}"
                                   value="{{ old('birth_date', $patient->birth_date?->toDateString()) }}"
                                   class="form-control @error('birth_date') is-invalid @enderror">
                            @error('birth_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="blood_type">Golongan Darah</label>
                            <select name="blood_type" id="blood_type" class="form-select @error('blood_type') is-invalid @enderror">
                                <option value="">Pilih</option>
                                @foreach (\App\Enums\BloodType::options() as $value => $label)
                                    <option value="{{ $value }}" @selected(old('blood_type', $patient->blood_type?->value) === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('blood_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </x-card>

                <x-card title="Kontak & Alamat" icon="bi-telephone">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label" for="phone">Nomor Telepon</label>
                            <input type="text" name="phone" id="phone" maxlength="30" inputmode="tel"
                                   value="{{ old('phone', $patient->phone) }}"
                                   class="form-control @error('phone') is-invalid @enderror" placeholder="08xxxxxxxxxx">
                            @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-8">
                            <label class="form-label" for="address">Alamat</label>
                            <textarea name="address" id="address" rows="2" maxlength="500"
                                      class="form-control @error('address') is-invalid @enderror"
                                      placeholder="Alamat lengkap sesuai KTP">{{ old('address', $patient->address) }}</textarea>
                            @error('address')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </x-card>
            </div>

            <div class="col-xl-4">
                <x-card title="Kontak Darurat" subtitle="Opsional, digunakan saat keadaan darurat" icon="bi-telephone-inbound">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label" for="emergency_contact_name">Nama Kontak Darurat</label>
                            <input type="text" name="emergency_contact_name" id="emergency_contact_name" maxlength="100"
                                   value="{{ old('emergency_contact_name', $patient->emergency_contact_name) }}"
                                   class="form-control @error('emergency_contact_name') is-invalid @enderror">
                            @error('emergency_contact_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="emergency_contact_phone">Telepon Kontak Darurat</label>
                            <input type="text" name="emergency_contact_phone" id="emergency_contact_phone" maxlength="30"
                                   inputmode="tel"
                                   value="{{ old('emergency_contact_phone', $patient->emergency_contact_phone) }}"
                                   class="form-control @error('emergency_contact_phone') is-invalid @enderror">
                            @error('emergency_contact_phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="alert alert-light border small mt-3 mb-0">
                        <i class="bi bi-info-circle me-1"></i>
                        NIK yang sudah terdaftar tidak dapat diduplikasi. Gunakan pencarian pada halaman
                        <a href="{{ route('reception.patients.index') }}" class="text-decoration-none">daftar pasien</a>
                        untuk membuka data lama.
                    </div>
                </x-card>

                <div class="d-grid gap-2 mt-3">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-lg me-1"></i>{{ $mode === 'create' ? 'Daftarkan Pasien' : 'Simpan Perubahan' }}
                    </button>
                    <a href="{{ $mode === 'create' ? route('reception.patients.index') : route('reception.patients.show', $patient) }}"
                       class="btn btn-light border">Batal</a>
                </div>
            </div>
        </div>
    </form>
@endsection
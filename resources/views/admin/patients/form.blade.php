@extends('layouts.app')

@section('title', ($patient->exists ? 'Ubah' : 'Daftar').' Pasien')

@section('content')
    <x-page-header :title="$patient->exists ? 'Ubah Data Pasien' : 'Daftarkan Pasien'"
                   :description="$patient->exists ? 'Perbarui data pasien '.$patient->name.'.' : 'Nomor rekam medis dibuat otomatis setelah data disimpan.'"
                   icon="bi-person-vcard" />

    <div class="row g-3">
        <div class="col-xl-8">
            <x-card title="Data Pasien" icon="bi-person">
                <form method="POST" action="{{ $patient->exists ? route('admin.patients.update', $patient) : route('admin.patients.store') }}">
                    @csrf
                    @if ($patient->exists) @method('PUT') @endif

                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label" for="name">Nama Lengkap <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="name" value="{{ old('name', $patient->name) }}"
                                   class="form-control @error('name') is-invalid @enderror" required>
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label" for="medical_record_number">No. Rekam Medis</label>
                            <input type="text" name="medical_record_number" id="medical_record_number"
                                   value="{{ $patient->medical_record_number ?? 'Otomatis setelah disimpan' }}"
                                   class="form-control bg-body-tertiary" readonly>
                            <div class="form-text">Dibuat otomatis oleh sistem.</div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" for="gender">Jenis Kelamin <span class="text-danger">*</span></label>
                            <select name="gender" id="gender" class="form-select @error('gender') is-invalid @enderror" required>
                                <option value="">Pilih...</option>
                                @foreach (\App\Enums\Gender::options() as $value => $label)
                                    <option value="{{ $value }}" @selected(old('gender', $patient->gender?->value) === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('gender')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" for="birth_date">Tanggal Lahir <span class="text-danger">*</span></label>
                            <input type="date" name="birth_date" id="birth_date"
                                   value="{{ old('birth_date', $patient->birth_date?->toDateString()) }}"
                                   class="form-control @error('birth_date') is-invalid @enderror" max="{{ date('Y-m-d') }}" required>
                            @error('birth_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" for="nik">NIK</label>
                            <input type="text" name="nik" id="nik" value="{{ old('nik', $patient->nik) }}"
                                   class="form-control @error('nik') is-invalid @enderror" maxlength="16">
                            @error('nik')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" for="blood_type">Golongan Darah</label>
                            <select name="blood_type" id="blood_type" class="form-select @error('blood_type') is-invalid @enderror">
                                <option value="">Tidak diketahui</option>
                                @foreach (\App\Enums\BloodType::options() as $value => $label)
                                    <option value="{{ $value }}" @selected(old('blood_type', $patient->blood_type?->value) === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('blood_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" for="phone">Nomor Telepon <span class="text-danger">*</span></label>
                            <input type="text" name="phone" id="phone" value="{{ old('phone', $patient->phone) }}"
                                   class="form-control @error('phone') is-invalid @enderror" required>
                            @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" for="emergency_contact_name">Nama Kontak Darurat</label>
                            <input type="text" name="emergency_contact_name" id="emergency_contact_name"
                                   value="{{ old('emergency_contact_name', $patient->emergency_contact_name) }}"
                                   class="form-control @error('emergency_contact_name') is-invalid @enderror">
                            @error('emergency_contact_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" for="emergency_contact_phone">Telepon Kontak Darurat</label>
                            <input type="text" name="emergency_contact_phone" id="emergency_contact_phone"
                                   value="{{ old('emergency_contact_phone', $patient->emergency_contact_phone) }}"
                                   class="form-control @error('emergency_contact_phone') is-invalid @enderror" placeholder="08xxxxxxxxxx">
                            @error('emergency_contact_phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-12">
                            <label class="form-label" for="address">Alamat <span class="text-danger">*</span></label>
                            <textarea name="address" id="address" rows="2"
                                      class="form-control @error('address') is-invalid @enderror" required>{{ old('address', $patient->address) }}</textarea>
                            @error('address')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-12">
                            <label class="form-label" for="allergies">Riwayat Alergi</label>
                            <textarea name="allergies" id="allergies" rows="2"
                                      class="form-control @error('allergies') is-invalid @enderror"
                                      placeholder="Tulis riwayat alergi obat atau bahan; jika tidak ada, kosongkan.">{{ old('allergies', $patient->allergies) }}</textarea>
                            @error('allergies')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="d-flex gap-2 mt-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-lg me-1"></i>{{ $patient->exists ? 'Simpan Perubahan' : 'Daftarkan' }}
                        </button>
                        <a href="{{ route('admin.patients.index') }}" class="btn btn-light border">Batal</a>
                    </div>
                </form>
            </x-card>
        </div>

        <div class="col-xl-4">
            <x-card title="Informasi" icon="bi-info-circle">
                <ul class="small text-muted-2 mb-0" style="line-height:1.7">
                    <li>Nomor rekam medis dibuat otomatis dan tidak dapat diubah.</li>
                    <li>NIK bersifat unik; biarkan kosong jika pasien belum memiliki NIK.</li>
                    <li>Data yang belum lengkap akan menampilkan peringatan di dashboard pasien.</li>
                </ul>
            </x-card>
        </div>
    </div>
@endsection
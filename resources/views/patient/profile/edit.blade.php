@extends('layouts.app')

@section('title', 'Profil Saya')

@section('content')
    <x-page-header title="Profil Saya" description="Kelola data diri dan keamanan akun Anda." icon="bi-person-gear" />

    @if (! empty($missing))
        <div class="alert alert-warning">
            <i class="bi bi-exclamation-triangle me-1"></i>
            <strong>Lengkapi profil Anda.</strong>
            Data berikut masih kosong: {{ implode(', ', $missing) }}.
            Profil yang lengkap wajib untuk dapat mengambil antrean.
        </div>
    @else
        <div class="alert alert-success">
            <i class="bi bi-check-circle me-1"></i>
            Profil Anda sudah lengkap. Anda dapat mengambil antrean kapan saja.
        </div>
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
                <form method="POST" action="{{ route('patient.profile.update') }}">
                    @csrf
                    @method('PUT')

                <x-card title="Data Diri" icon="bi-person">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" for="name">Nama Lengkap <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="name" required maxlength="100"
                                   value="{{ old('name', $patient?->name) }}"
                                   class="form-control @error('name') is-invalid @enderror">
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" for="email">Email <span class="text-danger">*</span></label>
                            <input type="email" name="email" id="email" required maxlength="255"
                                   value="{{ old('email', auth()->user()->email) }}"
                                   class="form-control @error('email') is-invalid @enderror">
                            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" for="nik">NIK</label>
                            <input type="text" name="nik" id="nik" maxlength="20"
                                   value="{{ old('nik', $patient?->nik) }}"
                                   class="form-control @error('nik') is-invalid @enderror">
                            @error('nik')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" for="phone">Nomor Telepon <span class="text-danger">*</span></label>
                            <input type="text" name="phone" id="phone" required maxlength="30"
                                   value="{{ old('phone', $patient?->phone) }}"
                                   class="form-control @error('phone') is-invalid @enderror" placeholder="08xxxxxxxxxx">
                            @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label" for="gender">Jenis Kelamin <span class="text-danger">*</span></label>
                            <select name="gender" id="gender" required class="form-select @error('gender') is-invalid @enderror">
                                <option value="">Pilih Jenis Kelamin</option>
                                @foreach (\App\Enums\Gender::options() as $value => $label)
                                    <option value="{{ $value }}" @selected(old('gender', $patient?->gender?->value) === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('gender')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label" for="birth_date">Tanggal Lahir <span class="text-danger">*</span></label>
                            <input type="date" name="birth_date" id="birth_date" required
                                   max="{{ now()->toDateString() }}"
                                   value="{{ old('birth_date', $patient?->birth_date?->toDateString()) }}"
                                   class="form-control @error('birth_date') is-invalid @enderror">
                            @error('birth_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label" for="blood_type">Golongan Darah</label>
                            <select name="blood_type" id="blood_type" class="form-select @error('blood_type') is-invalid @enderror">
                                <option value="">Tidak Diisi</option>
                                @foreach (\App\Enums\BloodType::options() as $value => $label)
                                    <option value="{{ $value }}" @selected(old('blood_type', $patient?->blood_type?->value) === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('blood_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-12">
                            <label class="form-label" for="address">Alamat <span class="text-danger">*</span></label>
                            <textarea name="address" id="address" rows="3" required maxlength="500"
                                      class="form-control @error('address') is-invalid @enderror">{{ old('address', $patient?->address) }}</textarea>
                            @error('address')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="border-top mt-3 pt-3">
                        <div class="fw-semibold small mb-3">Kontak Darurat</div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" for="emergency_contact_name">Nama Kontak Darurat</label>
                                <input type="text" name="emergency_contact_name" id="emergency_contact_name" maxlength="100"
                                       value="{{ old('emergency_contact_name', $patient?->emergency_contact_name) }}"
                                       class="form-control @error('emergency_contact_name') is-invalid @enderror">
                                @error('emergency_contact_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="emergency_contact_phone">Telepon Kontak Darurat</label>
                                <input type="text" name="emergency_contact_phone" id="emergency_contact_phone" maxlength="30"
                                       value="{{ old('emergency_contact_phone', $patient?->emergency_contact_phone) }}"
                                       class="form-control @error('emergency_contact_phone') is-invalid @enderror">
                                @error('emergency_contact_phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </div>

                    <div class="d-grid mt-3">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-lg me-1"></i>Simpan Perubahan
                        </button>
                    </div>
                </x-card>
                </form>
            </div>

            <div class="col-xl-4">
                <x-card title="Data Rekam Medis" icon="bi-journal-medical">
                    <dl class="row mb-0 small">
                        <dt class="col-5 text-muted-2 fw-normal">No. Rekam Medis</dt>
                        <dd class="col-7 fw-semibold">{{ $patient?->medical_record_number ?? '-' }}</dd>

                        <dt class="col-5 text-muted-2 fw-normal">Usia</dt>
                        <dd class="col-7">{{ $patient?->age() ? $patient->age().' tahun' : '-' }}</dd>

                        <dt class="col-5 text-muted-2 fw-normal">Jenis Kelamin</dt>
                        <dd class="col-7">{{ $patient?->genderLabel() ?? '-' }}</dd>

                        <dt class="col-5 text-muted-2 fw-normal">Terdaftar</dt>
                        <dd class="col-7">{{ $patient?->created_at?->translatedFormat('d M Y') ?? '-' }}</dd>

                        <dt class="col-5 text-muted-2 fw-normal">Status Akun</dt>
                        <dd class="col-7">
                            @if (auth()->user()->isActive())
                                <x-badge text="Aktif" icon="bi-check-circle-fill" color="success" />
                            @else
                                <x-badge text="Nonaktif" icon="bi-pause-circle-fill" color="secondary" />
                            @endif
                        </dd>
                    </dl>

                    <div class="border-top mt-3 pt-3 small text-muted-2">
                        Nomor rekam medis dibuat oleh sistem dan tidak dapat diubah.
                    </div>
                </x-card>

                <x-card title="Ganti Kata Sandi" icon="bi-shield-lock" class="mt-3">
                    <form method="POST" action="{{ route('patient.profile.password') }}">
                        @csrf
                        @method('PUT')

                        <div class="mb-3">
                            <label class="form-label" for="current_password">Kata Sandi Saat Ini</label>
                            <div class="input-group">
                                <input type="password" name="current_password" id="current_password" required
                                       class="form-control @error('current_password') is-invalid @enderror"
                                       data-toggle-password="#current_password">
                                <button class="btn btn-light border" type="button" data-toggle-password="#current_password">
                                    <i class="bi bi-eye"></i>
                                </button>
                                @error('current_password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="new_password">Kata Sandi Baru</label>
                            <div class="input-group">
                                <input type="password" name="password" id="new_password" required minlength="8"
                                       class="form-control @error('password') is-invalid @enderror"
                                       data-toggle-password="#new_password">
                                <button class="btn btn-light border" type="button" data-toggle-password="#new_password">
                                    <i class="bi bi-eye"></i>
                                </button>
                                @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="form-text">Minimal 8 karakter, mengandung huruf dan angka.</div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="password_confirmation">Konfirmasi Kata Sandi Baru</label>
                            <input type="password" name="password_confirmation" id="password_confirmation" required minlength="8"
                                   class="form-control">
                        </div>

                        <button type="submit" class="btn btn-light border w-100">
                            <i class="bi bi-key me-1"></i>Perbarui Kata Sandi
                        </button>
                    </form>
                </x-card>
            </div>
        </div>
@endsection
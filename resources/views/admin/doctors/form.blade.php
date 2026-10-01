@extends('layouts.app')

@section('title', ($doctor->exists ? 'Ubah' : 'Tambah').' Dokter')

@section('content')
    <x-page-header :title="$doctor->exists ? 'Ubah Dokter' : 'Tambah Dokter'"
                   :description="$doctor->exists ? 'Perbarui data dokter '.$doctor->name.'.' : 'Daftarkan dokter baru beserta spesialisasinya.'"
                   icon="bi-heart-pulse" />

    <div class="row g-3">
        <div class="col-xl-8">
            <x-card title="Data Dokter" icon="bi-person-vcard">
                <form method="POST" action="{{ $doctor->exists ? route('admin.doctors.update', $doctor) : route('admin.doctors.store') }}">
                    @csrf
                    @if ($doctor->exists) @method('PUT') @endif

                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label" for="doctor_code">Kode Dokter <span class="text-danger">*</span></label>
                            <input type="text" name="doctor_code" id="doctor_code" value="{{ old('doctor_code', $doctor->doctor_code) }}"
                                   class="form-control text-uppercase @error('doctor_code') is-invalid @enderror"
                                   placeholder="DR-001" required>
                            @error('doctor_code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-8">
                            <label class="form-label" for="name">Nama Lengkap <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="name" value="{{ old('name', $doctor->name) }}"
                                   class="form-control @error('name') is-invalid @enderror" placeholder="dr. Nama Dokter, Sp." required>
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" for="specialization">Spesialisasi <span class="text-danger">*</span></label>
                            <input type="text" name="specialization" id="specialization" list="specializationList"
                                   value="{{ old('specialization', $doctor->specialization) }}"
                                   class="form-control @error('specialization') is-invalid @enderror" required>
                            <datalist id="specializationList">
                                @foreach ($specializations ?? [] as $item)
                                    <option value="{{ $item }}"></option>
                                @endforeach
                            </datalist>
                            @error('specialization')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" for="status">Status <span class="text-danger">*</span></label>
                            <select name="status" id="status" class="form-select @error('status') is-invalid @enderror" required>
                                @foreach (\App\Enums\ActiveStatus::options() as $value => $label)
                                    <option value="{{ $value }}" @selected(old('status', $doctor->status?->value) === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" for="email">Email</label>
                            <input type="email" name="email" id="email" value="{{ old('email', $doctor->email) }}"
                                   class="form-control @error('email') is-invalid @enderror">
                            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" for="phone">Nomor Telepon</label>
                            <input type="text" name="phone" id="phone" value="{{ old('phone', $doctor->phone) }}"
                                   class="form-control @error('phone') is-invalid @enderror" placeholder="08xxxxxxxxxx">
                            @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" for="user_id">Akun Pengguna</label>
                            <select name="user_id" id="user_id" class="form-select @error('user_id') is-invalid @enderror">
                                <option value="">Belum ditautkan</option>
                                @if ($doctor->user)
                                    <option value="{{ $doctor->user->id }}" selected>{{ $doctor->user->name }} ({{ $doctor->user->email }})</option>
                                @endif
                                @foreach ($availableUsers as $user)
                                    <option value="{{ $user->id }}" @selected(old('user_id') == $user->id)>{{ $user->name }} ({{ $user->email }})</option>
                                @endforeach
                            </select>
                            <div class="form-text">Digunakan agar dokter dapat login dan memeriksa pasien.</div>
                            @error('user_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-12">
                            <label class="form-label" for="bio">Profil Singkat</label>
                            <textarea name="bio" id="bio" rows="3" class="form-control @error('bio') is-invalid @enderror"
                                      placeholder="Pendidikan, pengalaman, dan fokus layanan dokter.">{{ old('bio', $doctor->bio) }}</textarea>
                            @error('bio')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="d-flex gap-2 mt-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-lg me-1"></i>{{ $doctor->exists ? 'Simpan Perubahan' : 'Tambah Dokter' }}
                        </button>
                        <a href="{{ route('admin.doctors.index') }}" class="btn btn-light border">Batal</a>
                    </div>
                </form>
            </x-card>
        </div>

        <div class="col-xl-4">
            <x-card title="Informasi" icon="bi-info-circle">
                <ul class="small text-muted-2 mb-0" style="line-height:1.7">
                    <li>Kode dokter digunakan pada jadwal dan nomor antrean.</li>
                    <li>Jadwal praktik dibuat terpisah pada menu Jadwal Dokter.</li>
                    <li>Dokter nonaktif tidak dapat dipilih untuk jadwal baru.</li>
                    <li>Akun pengguna ditautkan agar dokter dapat masuk ke portal.</li>
                </ul>
            </x-card>
        </div>
    </div>
@endsection
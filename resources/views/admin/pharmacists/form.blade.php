@extends('layouts.app')

@section('title', ($pharmacist->exists ? 'Ubah' : 'Tambah').' Apoteker')

@section('content')
    <x-page-header :title="$pharmacist->exists ? 'Ubah Apoteker' : 'Tambah Apoteker'"
                   :description="$pharmacist->exists ? 'Perbarui data apoteker '.$pharmacist->name.'.' : 'Daftarkan apoteker baru untuk menangani resep.'"
                   icon="bi-capsule" />

    <div class="row g-3">
        <div class="col-xl-8">
            <x-card title="Data Apoteker" icon="bi-person-vcard">
                <form method="POST" action="{{ $pharmacist->exists ? route('admin.pharmacists.update', $pharmacist) : route('admin.pharmacists.store') }}">
                    @csrf
                    @if ($pharmacist->exists) @method('PUT') @endif

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" for="name">Nama Lengkap <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="name" value="{{ old('name', $pharmacist->name) }}"
                                   class="form-control @error('name') is-invalid @enderror" required>
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" for="license_number">Nomor STR / SIPA</label>
                            <input type="text" name="license_number" id="license_number"
                                   value="{{ old('license_number', $pharmacist->license_number) }}"
                                   class="form-control @error('license_number') is-invalid @enderror">
                            @error('license_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" for="phone">Nomor Telepon</label>
                            <input type="text" name="phone" id="phone" value="{{ old('phone', $pharmacist->phone) }}"
                                   class="form-control @error('phone') is-invalid @enderror" placeholder="08xxxxxxxxxx">
                            @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" for="status">Status <span class="text-danger">*</span></label>
                            <select name="status" id="status" class="form-select @error('status') is-invalid @enderror" required>
                                @foreach ($statuses as $value => $label)
                                    <option value="{{ $value }}" @selected(old('status', $pharmacist->status?->value) === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" for="user_id">Akun Pengguna</label>
                            <select name="user_id" id="user_id" class="form-select @error('user_id') is-invalid @enderror">
                                <option value="">Belum ditautkan</option>
                                @if ($pharmacist->user)
                                    <option value="{{ $pharmacist->user->id }}" selected>{{ $pharmacist->user->name }} ({{ $pharmacist->user->email }})</option>
                                @endif
                                @foreach ($availableUsers as $user)
                                    <option value="{{ $user->id }}" @selected(old('user_id') == $user->id)>{{ $user->name }} ({{ $user->email }})</option>
                                @endforeach
                            </select>
                            <div class="form-text">Akun dengan role Apoteker yang belum ditautkan.</div>
                            @error('user_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="d-flex gap-2 mt-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-lg me-1"></i>{{ $pharmacist->exists ? 'Simpan Perubahan' : 'Tambah Apoteker' }}
                        </button>
                        <a href="{{ route('admin.pharmacists.index') }}" class="btn btn-light border">Batal</a>
                    </div>
                </form>
            </x-card>
        </div>

        <div class="col-xl-4">
            <x-card title="Informasi" icon="bi-info-circle">
                <ul class="small text-muted-2 mb-0" style="line-height:1.7">
                    <li>Nomor STR/SIPA wajib diisi sesuai izin praktik.</li>
                    <li>Akun pengguna diperlukan agar apoteker dapat memproses resep.</li>
                    <li>Apoteker nonaktif tidak dapat memproses resep.</li>
                </ul>
            </x-card>
        </div>
    </div>
@endsection
@extends('layouts.app')

@section('title', ($receptionist->exists ? 'Ubah' : 'Tambah').' Resepsionis')

@section('content')
    <x-page-header :title="$receptionist->exists ? 'Ubah Resepsionis' : 'Tambah Resepsionis'"
                   :description="$receptionist->exists ? 'Perbarui data resepsionis '.$receptionist->name.'.' : 'Daftarkan resepsionis baru untuk menangani pasien dan antrean.'"
                   icon="bi-headset" />

    <div class="row g-3">
        <div class="col-xl-8">
            <x-card title="Data Resepsionis" icon="bi-person-vcard">
                <form method="POST" action="{{ $receptionist->exists ? route('admin.receptionists.update', $receptionist) : route('admin.receptionists.store') }}">
                    @csrf
                    @if ($receptionist->exists) @method('PUT') @endif

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" for="name">Nama Lengkap <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="name" value="{{ old('name', $receptionist->name) }}"
                                   class="form-control @error('name') is-invalid @enderror" required>
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" for="shift">Shift</label>
                            <input type="text" name="shift" id="shift" value="{{ old('shift', $receptionist->shift) }}"
                                   class="form-control @error('shift') is-invalid @enderror" placeholder="Pagi / Siang / Malam">
                            @error('shift')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" for="phone">Nomor Telepon</label>
                            <input type="text" name="phone" id="phone" value="{{ old('phone', $receptionist->phone) }}"
                                   class="form-control @error('phone') is-invalid @enderror" placeholder="08xxxxxxxxxx">
                            @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" for="status">Status <span class="text-danger">*</span></label>
                            <select name="status" id="status" class="form-select @error('status') is-invalid @enderror" required>
                                @foreach ($statuses as $value => $label)
                                    <option value="{{ $value }}" @selected(old('status', $receptionist->status?->value) === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" for="user_id">Akun Pengguna</label>
                            <select name="user_id" id="user_id" class="form-select @error('user_id') is-invalid @enderror">
                                <option value="">Belum ditautkan</option>
                                @if ($receptionist->user)
                                    <option value="{{ $receptionist->user->id }}" selected>{{ $receptionist->user->name }} ({{ $receptionist->user->email }})</option>
                                @endif
                                @foreach ($availableUsers as $user)
                                    <option value="{{ $user->id }}" @selected(old('user_id') == $user->id)>{{ $user->name }} ({{ $user->email }})</option>
                                @endforeach
                            </select>
                            <div class="form-text">Akun dengan role Resepsionis yang belum ditautkan.</div>
                            @error('user_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="d-flex gap-2 mt-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-lg me-1"></i>{{ $receptionist->exists ? 'Simpan Perubahan' : 'Tambah Resepsionis' }}
                        </button>
                        <a href="{{ route('admin.receptionists.index') }}" class="btn btn-light border">Batal</a>
                    </div>
                </form>
            </x-card>
        </div>

        <div class="col-xl-4">
            <x-card title="Informasi" icon="bi-info-circle">
                <ul class="small text-muted-2 mb-0" style="line-height:1.7">
                    <li>Resepsionis bertanggung jawab atas pendaftaran pasien dan pemanggilan antrean.</li>
                    <li>Akun pengguna diperlukan untuk dapat masuk ke sistem.</li>
                    <li>Resepsionis nonaktif tidak dapat memproses antrean.</li>
                </ul>
            </x-card>
        </div>
    </div>
@endsection
@extends('layouts.app')

@section('title', ($user->exists ? 'Ubah' : 'Tambah').' Pengguna')

@section('content')
    <x-page-header :title="$user->exists ? 'Ubah Pengguna' : 'Tambah Pengguna'"
                   :description="$user->exists ? 'Perbarui data akun '.$user->name.'.' : 'Buat akun baru untuk petugas atau pasien.'"
                   icon="bi-person-gear" />

    <div class="row g-3">
        <div class="col-xl-8">
            <x-card title="Data Akun" icon="bi-person-vcard">
                <form method="POST"
                      action="{{ $user->exists ? route('admin.users.update', $user) : route('admin.users.store') }}">
                    @csrf
                    @if ($user->exists) @method('PUT') @endif

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" for="name">Nama Lengkap <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="name" value="{{ old('name', $user->name) }}"
                                   class="form-control @error('name') is-invalid @enderror" required>
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" for="email">Email <span class="text-danger">*</span></label>
                            <input type="email" name="email" id="email" value="{{ old('email', $user->email) }}"
                                   class="form-control @error('email') is-invalid @enderror" required>
                            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" for="role">Role <span class="text-danger">*</span></label>
                            <select name="role" id="role" class="form-select @error('role') is-invalid @enderror"
                                    @disabled($user->id === auth()->id()) required>
                                @foreach (\App\Enums\UserRole::options() as $value => $label)
                                    <option value="{{ $value }}" @selected(old('role', $user->role?->value) === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            @if ($user->id === auth()->id())
                                <div class="form-text">Role akun sendiri tidak dapat diubah.</div>
                            @else
                                @error('role')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            @endif
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" for="phone">Nomor Telepon</label>
                            <input type="text" name="phone" id="phone" value="{{ old('phone', $user->phone) }}"
                                   class="form-control @error('phone') is-invalid @enderror" placeholder="08xxxxxxxxxx">
                            @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" for="password">
                                Kata Sandi @if (! $user->exists)<span class="text-danger">*</span>@endif
                            </label>
                            <div class="input-group">
                                <input type="password" name="password" id="password"
                                       class="form-control @error('password') is-invalid @enderror"
                                       @required(! $user->exists) autocomplete="new-password">
                                <button class="btn btn-light border" type="button" data-toggle-password="#password">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                            @if ($user->exists)
                                <div class="form-text">Kosongkan jika tidak ingin mengubah kata sandi.</div>
                            @endif
                            @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" for="password_confirmation">Konfirmasi Kata Sandi</label>
                            <input type="password" name="password_confirmation" id="password_confirmation"
                                   class="form-control" autocomplete="new-password">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" for="status">Status Akun</label>
                            <select name="status" id="status" class="form-select" @disabled($user->id === auth()->id())>
                                @foreach (\App\Enums\ActiveStatus::options() as $value => $label)
                                    <option value="{{ $value }}" @selected(old('status', $user->status?->value) === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="d-flex gap-2 mt-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-lg me-1"></i>{{ $user->exists ? 'Simpan Perubahan' : 'Buat Pengguna' }}
                        </button>
                        <a href="{{ route('admin.users.index') }}" class="btn btn-light border">Batal</a>
                    </div>
                </form>
            </x-card>
        </div>

        <div class="col-xl-4">
            <x-card title="Informasi" icon="bi-info-circle">
                <ul class="small text-muted-2 mb-0" style="line-height:1.7">
                    <li>Role menentukan menu dan hak akses pengguna.</li>
                    <li>Pengguna nonaktif tidak dapat masuk ke sistem.</li>
                    <li>Pengaturan ulang kata sandi dapat dilakukan dari daftar pengguna.</li>
                    <li>Penghapusan akun bersifat permanen dan tercatat pada audit log.</li>
                </ul>
            </x-card>

            @if ($user->exists)
                <x-card title="Riwayat Akun" icon="bi-clock-history" class="mt-3">
                    <dl class="row small mb-0">
                        <dt class="col-5 text-muted-2 fw-normal">Dibuat</dt>
                        <dd class="col-7">{{ $user->created_at?->translatedFormat('d M Y H:i') ?? '-' }}</dd>
                        <dt class="col-5 text-muted-2 fw-normal">Login Terakhir</dt>
                        <dd class="col-7">{{ $user->last_login_at?->translatedFormat('d M Y H:i') ?? '-' }}</dd>
                    </dl>
                </x-card>
            @endif
        </div>
    </div>
@endsection
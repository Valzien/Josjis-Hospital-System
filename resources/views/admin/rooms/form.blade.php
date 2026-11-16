@extends('layouts.app')

@section('title', ($room->exists ? 'Ubah' : 'Tambah').' Kamar')

@section('content')
    <x-page-header :title="$room->exists ? 'Ubah Kamar' : 'Tambah Kamar'"
                   :description="$room->exists ? 'Perbarui data kamar '.$room->name.'.' : 'Daftarkan kamar rawat inap beserta kelas dan tarif hariannya.'"
                   icon="bi-hospital">
        @if ($room->exists)
            <x-slot:actions>
                <a href="{{ route('admin.rooms.show', $room) }}" class="btn btn-light border btn-sm">
                    <i class="bi bi-arrow-left me-1"></i>Detail Kamar
                </a>
            </x-slot:actions>
        @endif
    </x-page-header>

    <div class="row g-3">
        <div class="col-xl-8">
            <x-card title="Data Kamar" icon="bi-hospital">
                <form method="POST" action="{{ $room->exists ? route('admin.rooms.update', $room) : route('admin.rooms.store') }}">
                    @csrf
                    @if ($room->exists) @method('PUT') @endif

                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label" for="code">Kode Kamar <span class="text-danger">*</span></label>
                            <input type="text" name="code" id="code" value="{{ old('code', $room->code) }}"
                                   class="form-control @error('code') is-invalid @enderror" required maxlength="30"
                                   placeholder="KMR-001">
                            @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-8">
                            <label class="form-label" for="name">Nama Kamar <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="name" value="{{ old('name', $room->name) }}"
                                   class="form-control @error('name') is-invalid @enderror" required maxlength="150"
                                   placeholder="Contoh: Melati 1">
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label" for="class">Kelas <span class="text-danger">*</span></label>
                            <select name="class" id="class" class="form-select @error('class') is-invalid @enderror" required>
                                @foreach ($classes as $value => $label)
                                    <option value="{{ $value }}" @selected(old('class', $room->class?->value) === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('class')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label" for="floor">Lantai</label>
                            <input type="text" name="floor" id="floor" value="{{ old('floor', $room->floor) }}"
                                   class="form-control @error('floor') is-invalid @enderror" maxlength="20"
                                   placeholder="2">
                            @error('floor')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label" for="rate_per_day">Tarif per Hari (Rp) <span class="text-danger">*</span></label>
                            <input type="number" name="rate_per_day" id="rate_per_day" min="0" step="1"
                                   value="{{ old('rate_per_day', (int) $room->rate_per_day) }}"
                                   class="form-control @error('rate_per_day') is-invalid @enderror" required>
                            @error('rate_per_day')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label" for="status">Status <span class="text-danger">*</span></label>
                            <select name="status" id="status" class="form-select @error('status') is-invalid @enderror" required>
                                @foreach ($statuses as $value => $label)
                                    <option value="{{ $value }}" @selected(old('status', $room->status?->value) === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-12">
                            <label class="form-label" for="notes">Keterangan</label>
                            <textarea name="notes" id="notes" rows="3" maxlength="1000"
                                      class="form-control @error('notes') is-invalid @enderror"
                                      placeholder="Fasilitas atau catatan lain (opsional)">{{ old('notes', $room->notes) }}</textarea>
                            @error('notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="d-flex gap-2 mt-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-lg me-1"></i>{{ $room->exists ? 'Simpan Perubahan' : 'Tambah Kamar' }}
                        </button>
                        <a href="{{ $room->exists ? route('admin.rooms.show', $room) : route('admin.rooms.index') }}"
                           class="btn btn-light border">Batal</a>
                    </div>
                </form>
            </x-card>
        </div>

        <div class="col-xl-4">
            <x-card title="Informasi" icon="bi-info-circle">
                <ul class="small text-muted-2 mb-0" style="line-height:1.7">
                    <li>Satu kamar dapat memiliki beberapa tempat tidur.</li>
                    <li>Tarif harian dihitung otomatis sebagai biaya kamar saat pasien pulang.</li>
                    <li>Kamar nonaktif tidak muncul sebagai pilihan tempat tidur baru.</li>
                    <li>Kamar dengan riwayat rawat inap tidak dapat dihapus, cukup dinonaktifkan.</li>
                </ul>
            </x-card>
        </div>
    </div>
@endsection

@extends('layouts.app')

@section('title', ($service->exists ? 'Ubah' : 'Tambah').' Tarif Layanan')

@section('content')
    <x-page-header :title="$service->exists ? 'Ubah Tarif Layanan' : 'Tambah Tarif Layanan'"
                   :description="$service->exists ? 'Perbarui tarif '.$service->name.'.' : 'Daftarkan tarif tindakan atau layanan baru.'"
                   icon="bi-tags" />

    <div class="row g-3">
        <div class="col-xl-8">
            <x-card title="Data Tarif" icon="bi-tags">
                <form method="POST" action="{{ $service->exists ? route('admin.services.update', $service) : route('admin.services.store') }}">
                    @csrf
                    @if ($service->exists) @method('PUT') @endif

                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label" for="code">Kode Layanan <span class="text-danger">*</span></label>
                            <input type="text" name="code" id="code" value="{{ old('code', $service->code) }}"
                                   class="form-control @error('code') is-invalid @enderror" required maxlength="30"
                                   placeholder="SVC-001">
                            @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-8">
                            <label class="form-label" for="name">Nama Layanan <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="name" value="{{ old('name', $service->name) }}"
                                   class="form-control @error('name') is-invalid @enderror" required maxlength="150"
                                   placeholder="Contoh: Nebulisasi">
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" for="category">Kategori <span class="text-danger">*</span></label>
                            <input type="text" name="category" id="category" list="categoryOptions"
                                   value="{{ old('category', $service->category) }}"
                                   class="form-control @error('category') is-invalid @enderror" required maxlength="50"
                                   placeholder="Tindakan Medis">
                            <datalist id="categoryOptions">
                                @foreach ($categories as $category)
                                    <option value="{{ $category }}"></option>
                                @endforeach
                            </datalist>
                            @error('category')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-3">
                            <label class="form-label" for="unit">Satuan <span class="text-danger">*</span></label>
                            <input type="text" name="unit" id="unit" list="unitOptions"
                                   value="{{ old('unit', $service->unit) }}"
                                   class="form-control @error('unit') is-invalid @enderror" required maxlength="30">
                            <datalist id="unitOptions">
                                @foreach ($units as $unit)
                                    <option value="{{ $unit }}"></option>
                                @endforeach
                            </datalist>
                            @error('unit')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-3">
                            <label class="form-label" for="price">Tarif (Rp) <span class="text-danger">*</span></label>
                            <input type="number" name="price" id="price" min="0" step="1"
                                   value="{{ old('price', (int) $service->price) }}"
                                   class="form-control @error('price') is-invalid @enderror" required>
                            @error('price')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" for="status">Status <span class="text-danger">*</span></label>
                            <select name="status" id="status" class="form-select @error('status') is-invalid @enderror" required>
                                @foreach ($statuses as $value => $label)
                                    <option value="{{ $value }}" @selected(old('status', $service->status?->value) === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-12">
                            <label class="form-label" for="description">Keterangan</label>
                            <textarea name="description" id="description" rows="3" maxlength="1000"
                                      class="form-control @error('description') is-invalid @enderror"
                                      placeholder="Opsional">{{ old('description', $service->description) }}</textarea>
                            @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="d-flex gap-2 mt-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-lg me-1"></i>{{ $service->exists ? 'Simpan Perubahan' : 'Tambah Tarif' }}
                        </button>
                        <a href="{{ route('admin.services.index') }}" class="btn btn-light border">Batal</a>
                    </div>
                </form>
            </x-card>
        </div>

        <div class="col-xl-4">
            <x-card title="Informasi" icon="bi-info-circle">
                <ul class="small text-muted-2 mb-0" style="line-height:1.7">
                    <li>Tarif aktif muncul pada form pemeriksaan dokter.</li>
                    <li>Tindakan yang dipilih otomatis menjadi rincian tagihan pasien.</li>
                    <li>Harga disalin ke tagihan saat pemeriksaan disimpan, sehingga perubahan tarif tidak mengubah tagihan lama.</li>
                    <li>Nonaktifkan tarif yang tidak dipakai lagi agar tidak membingungkan dokter.</li>
                </ul>
            </x-card>
        </div>
    </div>
@endsection

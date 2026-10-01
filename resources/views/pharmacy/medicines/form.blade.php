@extends('layouts.app')

@section('title', $medicine->exists ? 'Ubah Obat' : 'Tambah Obat')

@section('content')
    <x-page-header :title="$medicine->exists ? 'Ubah Data Obat' : 'Tambah Obat Baru'"
                   :description="$medicine->exists ? 'Perbarui informasi obat '.$medicine->name.'.' : 'Tambahkan obat baru ke katalog farmasi.'"
                   icon="bi-capsule">
        <x-slot:actions>
            <a href="{{ route('pharmacy.medicines.index') }}" class="btn btn-light border btn-sm">
                <i class="bi bi-arrow-left me-1"></i>Data Obat
            </a>
        </x-slot:actions>
    </x-page-header>

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

    <form method="POST"
          action="{{ $medicine->exists ? route('pharmacy.medicines.update', $medicine) : route('pharmacy.medicines.store') }}">
        @csrf
        @if ($medicine->exists)
            @method('PUT')
        @endif

        <div class="row g-3">
            <div class="col-xl-8">
                <x-card title="Informasi Obat" icon="bi-capsule">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label" for="medicine_code">Kode Obat <span class="text-danger">*</span></label>
                            <input type="text" name="medicine_code" id="medicine_code" required maxlength="30"
                                   value="{{ old('medicine_code', $medicine->medicine_code) }}"
                                   class="form-control text-uppercase @error('medicine_code') is-invalid @enderror"
                                   placeholder="AMD-001">
                            @error('medicine_code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-8">
                            <label class="form-label" for="name">Nama Obat <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="name" required maxlength="150"
                                   value="{{ old('name', $medicine->name) }}"
                                   class="form-control @error('name') is-invalid @enderror"
                                   placeholder="Contoh: Amoksisilin 500 mg">
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label" for="category">Kategori <span class="text-danger">*</span></label>
                            <input type="text" name="category" id="category" required list="categoryList" maxlength="60"
                                   value="{{ old('category', $medicine->category) }}"
                                   class="form-control @error('category') is-invalid @enderror"
                                   placeholder="Antibiotik">
                            <datalist id="categoryList">
                                @foreach ($categories as $category)
                                    <option value="{{ $category }}"></option>
                                @endforeach
                            </datalist>
                            @error('category')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="unit">Satuan <span class="text-danger">*</span></label>
                            <select name="unit" id="unit" required class="form-select @error('unit') is-invalid @enderror">
                                @foreach ($units as $value => $label)
                                    <option value="{{ $value }}" @selected(old('unit', $medicine->unit) === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('unit')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="status">Status <span class="text-danger">*</span></label>
                            <select name="status" id="status" required class="form-select @error('status') is-invalid @enderror">
                                @foreach ($statuses as $value => $label)
                                    <option value="{{ $value }}" @selected(old('status', $medicine->status?->value) === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-12">
                            <label class="form-label" for="description">Deskripsi</label>
                            <textarea name="description" id="description" rows="3" maxlength="1000"
                                      class="form-control @error('description') is-invalid @enderror">{{ old('description', $medicine->description) }}</textarea>
                            @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </x-card>
            </div>

            <div class="col-xl-4">
                <x-card title="Stok & Harga" icon="bi-box-seam">
                    <div class="row g-3">
                        @if (! $medicine->exists)
                            <div class="col-12">
                                <label class="form-label" for="stock">Stok Awal</label>
                                <input type="number" name="stock" id="stock" min="0" max="1000000"
                                       value="{{ old('stock', 0) }}" class="form-control @error('stock') is-invalid @enderror">
                                <div class="form-text">Dicatat sebagai transaksi barang masuk.</div>
                                @error('stock')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        @endif

                        <div class="col-12">
                            <label class="form-label" for="minimum_stock">Stok Minimum <span class="text-danger">*</span></label>
                            <input type="number" name="minimum_stock" id="minimum_stock" required min="0" max="1000000"
                                   value="{{ old('minimum_stock', $medicine->minimum_stock ?? 10) }}"
                                   class="form-control @error('minimum_stock') is-invalid @enderror">
                            <div class="form-text">Peringatan muncul saat stok menyentuh nilai ini.</div>
                            @error('minimum_stock')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-12">
                            <label class="form-label" for="price">Harga Satuan (Rp) <span class="text-danger">*</span></label>
                            <input type="number" name="price" id="price" required min="0" max="10000000" step="100"
                                   value="{{ old('price', $medicine->price ?? 0) }}"
                                   class="form-control @error('price') is-invalid @enderror">
                            @error('price')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        @if ($medicine->exists)
                            <div class="col-12">
                                <div class="alert alert-light border small mb-0">
                                    <div class="d-flex justify-content-between">
                                        <span>Stok saat ini</span>
                                        <strong>{{ $medicine->stock }} {{ $medicine->unit }}</strong>
                                    </div>
                                    <div class="small text-muted-2 mt-1">
                                        Ubah stok melalui menu
                                        <a href="{{ route('pharmacy.stock.index') }}" class="text-decoration-none">Manajemen Stok</a>
                                        agar riwayat transaksi tercatat.
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>

                    <div class="d-grid gap-2 mt-3">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-lg me-1"></i>{{ $medicine->exists ? 'Simpan Perubahan' : 'Tambah Obat' }}
                        </button>
                        <a href="{{ route('pharmacy.medicines.index') }}" class="btn btn-light border">Batal</a>
                    </div>
                </x-card>
            </div>
        </div>
    </form>
@endsection
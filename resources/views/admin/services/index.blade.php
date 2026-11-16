@extends('layouts.app')

@section('title', 'Tarif & Tindakan')

@section('content')
    <x-page-header title="Tarif & Tindakan"
                   description="Kelola master tarif layanan yang bisa dipilih dokter saat pemeriksaan dan otomatis masuk ke tagihan."
                   icon="bi-tags">
        <x-slot:actions>
            <a href="{{ route('admin.services.create') }}" class="btn btn-primary btn-sm">
                <i class="bi bi-plus-lg me-1"></i>Tambah Tarif
            </a>
        </x-slot:actions>
    </x-page-header>

    <x-card class="mb-3">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-lg-4">
                <label class="form-label small mb-1" for="q">Cari Layanan</label>
                <input type="search" name="q" id="q" value="{{ $filters['q'] ?? '' }}"
                       class="form-control form-control-sm" placeholder="Nama, kode, atau kategori" data-search-submit>
            </div>
            <div class="col-lg-3">
                <label class="form-label small mb-1" for="category">Kategori</label>
                <select name="category" id="category" class="form-select form-select-sm">
                    <option value="">Semua Kategori</option>
                    @foreach ($categories as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['category'] ?? '') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-lg-2">
                <label class="form-label small mb-1" for="status">Status</label>
                <select name="status" id="status" class="form-select form-select-sm">
                    <option value="">Semua Status</option>
                    @foreach ($statuses as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-lg-3 d-flex gap-2">
                <button class="btn btn-primary btn-sm flex-grow-1" type="submit"><i class="bi bi-funnel me-1"></i>Terapkan</button>
                <a href="{{ route('admin.services.index') }}" class="btn btn-light border btn-sm">Reset</a>
            </div>
        </form>
    </x-card>

    <x-data-table :rows="$services" :columns="[
        ['label' => 'Kode', 'key' => 'code'],
        ['label' => 'Layanan', 'key' => 'name'],
        ['label' => 'Kategori', 'key' => 'category'],
        ['label' => 'Satuan', 'key' => 'unit'],
        ['label' => 'Tarif', 'key' => 'price', 'cellClass' => 'text-end'],
        ['label' => 'Status', 'key' => 'status'],
        ['label' => 'Aksi', 'key' => 'actions', 'cellClass' => 'text-end'],
    ]" empty-title="Belum ada tarif layanan" empty-icon="bi-tags">
        @forelse ($services as $service)
            <tr>
                <td class="small fw-semibold text-nowrap">{{ $service->code }}</td>
                <td>
                    <div class="small fw-semibold">{{ $service->name }}</div>
                    @if ($service->description)
                        <div class="fs-7 text-muted-2">{{ \Illuminate\Support\Str::limit($service->description, 70) }}</div>
                    @endif
                </td>
                <td class="small">{{ $service->category }}</td>
                <td class="small text-muted-2">{{ $service->unit }}</td>
                <td class="small fw-semibold text-end text-nowrap">Rp {{ number_format((float) $service->price, 0, ',', '.') }}</td>
                <td>
                    <x-badge :text="$service->status->label()"
                             :color="$service->status === \App\Enums\ActiveStatus::Active ? 'success' : 'secondary'"
                             :icon="$service->status === \App\Enums\ActiveStatus::Active ? 'bi-check-circle-fill' : 'bi-slash-circle'" />
                </td>
                <td class="text-end">
                    <div class="btn-group btn-group-sm">
                        <a href="{{ route('admin.services.edit', $service) }}" class="btn btn-light border" title="Ubah">
                            <i class="bi bi-pencil"></i>
                        </a>
                        <form method="POST" action="{{ route('admin.services.status', $service) }}"
                              data-confirm-submit data-confirm-title="Ubah Status"
                              data-confirm-message="Ubah status tarif {{ $service->name }}?" data-confirm-variant="warning">
                            @csrf @method('PUT')
                            <button type="submit" class="btn btn-light border" title="Ubah Status">
                                <i class="bi bi-toggle-{{ $service->status === \App\Enums\ActiveStatus::Active ? 'on' : 'off' }}"></i>
                            </button>
                        </form>
                        <form method="POST" action="{{ route('admin.services.destroy', $service) }}"
                              data-confirm-submit data-confirm-title="Hapus Tarif"
                              data-confirm-message="Hapus tarif {{ $service->name }}? Tarif tidak akan muncul lagi pada pemeriksaan baru."
                              data-confirm-variant="danger">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-light border" title="Hapus">
                                <i class="bi bi-trash"></i>
                            </button>
                        </form>
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="7">
                    <x-empty-state icon="bi-tags" title="Belum ada tarif layanan"
                                   text="Tambahkan tarif agar dokter dapat memilih tindakan saat pemeriksaan.">
                        <x-slot:action>
                            <a href="{{ route('admin.services.create') }}" class="btn btn-primary btn-sm mt-2">Tambah Tarif</a>
                        </x-slot:action>
                    </x-empty-state>
                </td>
            </tr>
        @endforelse
    </x-data-table>
@endsection

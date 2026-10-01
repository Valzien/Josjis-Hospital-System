@extends('layouts.app')

@section('title', 'Manajemen Resepsionis')

@section('content')
    <x-page-header title="Resepsionis" description="Kelola data resepsionis dan shift kerja." icon="bi-headset">
        <x-slot:actions>
            <a href="{{ route('admin.receptionists.create') }}" class="btn btn-primary btn-sm">
                <i class="bi bi-plus-lg me-1"></i>Tambah Resepsionis
            </a>
        </x-slot:actions>
    </x-page-header>

    <x-card class="mb-3">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-lg-5">
                <label class="form-label small mb-1" for="q">Cari Resepsionis</label>
                <input type="search" name="q" id="q" value="{{ $filters['q'] ?? '' }}"
                       class="form-control form-control-sm" placeholder="Nama" data-search-submit>
            </div>
            <div class="col-lg-3">
                <label class="form-label small mb-1" for="status">Status</label>
                <select name="status" id="status" class="form-select form-select-sm">
                    <option value="">Semua Status</option>
                    @foreach ($statuses as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-lg-4 d-flex gap-2">
                <button class="btn btn-primary btn-sm flex-grow-1" type="submit"><i class="bi bi-funnel me-1"></i>Terapkan</button>
                <a href="{{ route('admin.receptionists.index') }}" class="btn btn-light border btn-sm">Reset</a>
            </div>
        </form>
    </x-card>

    <x-data-table :rows="$receptionists" :columns="[
        ['label' => 'Resepsionis', 'key' => 'name'],
        ['label' => 'Shift', 'key' => 'shift'],
        ['label' => 'Telepon', 'key' => 'phone'],
        ['label' => 'Antrean Dibuat', 'key' => 'created'],
        ['label' => 'Status', 'key' => 'status'],
        ['label' => 'Aksi', 'key' => 'actions', 'cellClass' => 'text-end'],
    ]" empty-title="Belum ada resepsionis" empty-icon="bi-headset">
        @forelse ($receptionists as $receptionist)
            <tr>
                <td>
                    <div class="d-flex align-items-center gap-2">
                        <x-avatar :name="$receptionist->name" size="sm" />
                        <div>
                            <div class="fw-semibold small">{{ $receptionist->name }}</div>
                            <div class="fs-7 text-muted-2">{{ $receptionist->user->email ?? 'Belum ditautkan' }}</div>
                        </div>
                    </div>
                </td>
                <td class="small">{{ $receptionist->shift ?? '-' }}</td>
                <td class="small text-muted-2">{{ $receptionist->phone ?? '-' }}</td>
                <td class="small">{{ $receptionist->created_queues_count }}</td>
                <td>
                    <x-badge :text="$receptionist->status->label()"
                            :color="$receptionist->status->value === 'active' ? 'success' : 'secondary'"
                            :icon="$receptionist->status->value === 'active' ? 'bi-check-circle-fill' : 'bi-slash-circle'" />
                </td>
                <td class="text-end">
                    <div class="btn-group btn-group-sm">
                        <a href="{{ route('admin.receptionists.edit', $receptionist) }}" class="btn btn-light border" title="Ubah">
                            <i class="bi bi-pencil"></i>
                        </a>
                        <form method="POST" action="{{ route('admin.receptionists.status', $receptionist) }}"
                              data-confirm-submit data-confirm-title="Ubah Status"
                              data-confirm-message="Ubah status resepsionis {{ $receptionist->name }}?" data-confirm-variant="warning">
                            @csrf @method('PUT')
                            <button type="submit" class="btn btn-light border" title="Ubah Status">
                                <i class="bi bi-toggle-{{ $receptionist->status->value === 'active' ? 'on' : 'off' }}"></i>
                            </button>
                        </form>
                        <form method="POST" action="{{ route('admin.receptionists.destroy', $receptionist) }}"
                              data-confirm-submit data-confirm-title="Hapus Resepsionis"
                              data-confirm-message="Hapus data resepsionis {{ $receptionist->name }}?" data-confirm-variant="danger">
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
                <td colspan="6"><x-empty-state icon="bi-headset" title="Belum ada resepsionis" /></td>
            </tr>
        @endforelse
    </x-data-table>
@endsection
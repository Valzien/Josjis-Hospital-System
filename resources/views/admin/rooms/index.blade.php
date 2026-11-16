@extends('layouts.app')

@section('title', 'Rawat Inap')

@section('content')
    <x-page-header title="Kamar & Tempat Tidur"
                   description="Kelola kamar rawat inap, kelas, tarif harian, dan ketersediaan tempat tidur."
                   icon="bi-hospital">
        <x-slot:actions>
            <a href="{{ route('admin.rooms.create') }}" class="btn btn-primary btn-sm">
                <i class="bi bi-plus-lg me-1"></i>Tambah Kamar
            </a>
        </x-slot:actions>
    </x-page-header>

    <x-card class="mb-3">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-lg-4">
                <label class="form-label small mb-1" for="q">Cari Kamar</label>
                <input type="search" name="q" id="q" value="{{ $filters['q'] ?? '' }}"
                       class="form-control form-control-sm" placeholder="Nama, kode, atau lantai" data-search-submit>
            </div>
            <div class="col-lg-3">
                <label class="form-label small mb-1" for="class">Kelas</label>
                <select name="class" id="class" class="form-select form-select-sm">
                    <option value="">Semua Kelas</option>
                    @foreach ($classes as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['class'] ?? '') === $value)>{{ $label }}</option>
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
                <a href="{{ route('admin.rooms.index') }}" class="btn btn-light border btn-sm">Reset</a>
            </div>
        </form>
    </x-card>

    <x-data-table :rows="$rooms" :columns="[
        ['label' => 'Kamar', 'key' => 'name'],
        ['label' => 'Kelas', 'key' => 'class'],
        ['label' => 'Tarif/Hari', 'key' => 'rate', 'class' => 'text-end'],
        ['label' => 'Tempat Tidur', 'key' => 'beds', 'class' => 'text-center'],
        ['label' => 'Status', 'key' => 'status'],
        ['label' => 'Aksi', 'key' => 'actions', 'class' => 'text-end'],
    ]" empty-title="Belum ada kamar rawat inap" empty-icon="bi-hospital">
        @forelse ($rooms as $room)
            <tr>
                <td>
                    <div class="small fw-semibold">{{ $room->name }}</div>
                    <div class="fs-7 text-muted-2">
                        {{ $room->code }}
                        @if ($room->floor)
                            &middot; Lantai {{ $room->floor }}
                        @endif
                    </div>
                </td>
                <td>
                    <x-badge :text="$room->classLabel()" :color="$room->class?->badge() ?? 'secondary'" />
                </td>
                <td class="small fw-semibold text-end text-nowrap">{{ $room->rateFormatted() }}</td>
                <td class="text-center small">
                    <span class="fw-semibold">{{ $room->occupied_beds_count }}</span>
                    <span class="text-muted-2">/ {{ $room->beds_count }}</span>
                </td>
                <td><x-status-badge :status="$room->status" /></td>
                <td class="text-end">
                    <div class="btn-group btn-group-sm">
                        <a href="{{ route('admin.rooms.show', $room) }}" class="btn btn-light border" title="Detail">
                            <i class="bi bi-eye"></i>
                        </a>
                        <a href="{{ route('admin.rooms.edit', $room) }}" class="btn btn-light border" title="Ubah">
                            <i class="bi bi-pencil"></i>
                        </a>
                        <form method="POST" action="{{ route('admin.rooms.status', $room) }}"
                              data-confirm-submit data-confirm-title="Ubah Status"
                              data-confirm-message="Ubah status kamar {{ $room->name }}?" data-confirm-variant="warning">
                            @csrf @method('PUT')
                            <button type="submit" class="btn btn-light border" title="Ubah Status">
                                <i class="bi bi-toggle-{{ $room->status === \App\Enums\ActiveStatus::Active ? 'on' : 'off' }}"></i>
                            </button>
                        </form>
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="6">
                    <x-empty-state icon="bi-hospital" title="Belum ada kamar rawat inap"
                                   text="Tambahkan kamar dan tempat tidur agar pasien dapat dirawat inap.">
                        <x-slot:action>
                            <a href="{{ route('admin.rooms.create') }}" class="btn btn-primary btn-sm mt-2">Tambah Kamar</a>
                        </x-slot:action>
                    </x-empty-state>
                </td>
            </tr>
        @endforelse
    </x-data-table>
@endsection

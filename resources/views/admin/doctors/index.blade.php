@extends('layouts.app')

@section('title', 'Manajemen Dokter')

@section('content')
    <x-page-header title="Dokter" description="Kelola data dokter, spesialisasi, dan status praktik." icon="bi-heart-pulse">
        <x-slot:actions>
            <a href="{{ route('admin.schedules.create') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-calendar-plus me-1"></i>Jadwal
            </a>
            <a href="{{ route('admin.doctors.create') }}" class="btn btn-primary btn-sm">
                <i class="bi bi-plus-lg me-1"></i>Tambah Dokter
            </a>
        </x-slot:actions>
    </x-page-header>

    <x-card class="mb-3">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-lg-4">
                <label class="form-label small mb-1" for="q">Cari Dokter</label>
                <input type="search" name="q" id="q" value="{{ $filters['q'] ?? '' }}"
                       class="form-control form-control-sm" placeholder="Nama atau kode" data-search-submit>
            </div>
            <div class="col-lg-3">
                <label class="form-label small mb-1" for="specialization">Spesialisasi</label>
                <select name="specialization" id="specialization" class="form-select form-select-sm">
                    <option value="">Semua Spesialisasi</option>
                    @foreach ($specializations as $specialization)
                        <option value="{{ $specialization }}" @selected(($filters['specialization'] ?? '') === $specialization)>{{ $specialization }}</option>
                    @endforeach
                </select>
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
            <div class="col-lg-2 d-flex gap-2">
                <button class="btn btn-primary btn-sm flex-grow-1" type="submit"><i class="bi bi-funnel me-1"></i>Terapkan</button>
                <a href="{{ route('admin.doctors.index') }}" class="btn btn-light border btn-sm">Reset</a>
            </div>
        </form>
    </x-card>

    <div class="row g-3">
        @forelse ($doctors as $doctor)
            <div class="col-md-6 col-xl-4">
                <x-card class="h-100">
                    <div class="d-flex align-items-start gap-3">
                        <x-avatar :name="$doctor->name" size="lg" />
                        <div class="flex-grow-1 min-w-0">
                            <h3 class="h6 fw-bold mb-1 text-truncate">{{ $doctor->name }}</h3>
                            <div class="small text-muted-2 mb-2">{{ $doctor->specialization }}</div>
                            <x-badge :text="$doctor->status->label()"
                                    :color="$doctor->isActive() ? 'success' : 'secondary'"
                                    :icon="$doctor->isActive() ? 'bi-check-circle-fill' : 'bi-slash-circle'" />
                        </div>
                    </div>

                    <div class="small text-muted-2 mt-3">
                        <div><i class="bi bi-upc me-1"></i>{{ $doctor->doctor_code }}</div>
                        <div><i class="bi bi-telephone me-1"></i>{{ $doctor->phone ?? '-' }}</div>
                        <div><i class="bi bi-calendar-week me-1"></i>{{ $doctor->schedules_count }} jadwal</div>
                    </div>

                    <div class="d-flex gap-2 mt-3 pt-3 border-top">
                        <a href="{{ route('admin.doctors.show', $doctor) }}" class="btn btn-sm btn-light border flex-grow-1">
                            Detail
                        </a>
                        <a href="{{ route('admin.doctors.edit', $doctor) }}" class="btn btn-sm btn-light border" title="Ubah">
                            <i class="bi bi-pencil"></i>
                        </a>
                        <form method="POST" action="{{ route('admin.doctors.status', $doctor) }}"
                              data-confirm-submit data-confirm-title="Ubah Status"
                              data-confirm-message="Ubah status dokter {{ $doctor->name }}?" data-confirm-variant="warning">
                            @csrf @method('PUT')
                            <button type="submit" class="btn btn-sm btn-light border" title="Ubah Status">
                                <i class="bi bi-{{ $doctor->isActive() ? 'toggle-on' : 'toggle-off' }}"></i>
                            </button>
                        </form>
                        <form method="POST" action="{{ route('admin.doctors.destroy', $doctor) }}"
                              data-confirm-submit data-confirm-title="Hapus Dokter"
                              data-confirm-message="Hapus dokter {{ $doctor->name }} beserta jadwal dan antreannya?"
                              data-confirm-variant="danger">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-light border" title="Hapus">
                                <i class="bi bi-trash"></i>
                            </button>
                        </form>
                    </div>
                </x-card>
            </div>
        @empty
            <div class="col-12">
                <x-card>
                    <x-empty-state icon="bi-heart-pulse" title="Belum ada dokter"
                                   text="Tambahkan dokter pertama untuk mulai membuat jadwal praktik.">
                        <x-slot:action>
                            <a href="{{ route('admin.doctors.create') }}" class="btn btn-primary btn-sm mt-2">Tambah Dokter</a>
                        </x-slot:action>
                    </x-empty-state>
                </x-card>
            </div>
        @endforelse
    </div>

    @if ($doctors->hasPages())
        <div class="mt-3">{{ $doctors->onEachSide(1)->links('pagination::bootstrap-5') }}</div>
    @endif
@endsection
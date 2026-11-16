@extends('layouts.app')

@section('title', 'Kamar '.$room->name)

@section('content')
    <x-page-header :title="'Kamar '.$room->name"
                   :description="$room->code.($room->floor ? ' · Lantai '.$room->floor : '').' · '.$room->classLabel()"
                   icon="bi-hospital">
        <x-slot:actions>
            <a href="{{ route('admin.rooms.edit', $room) }}" class="btn btn-primary btn-sm">
                <i class="bi bi-pencil me-1"></i>Ubah Kamar
            </a>
            <a href="{{ route('admin.rooms.index') }}" class="btn btn-light border btn-sm">
                <i class="bi bi-arrow-left me-1"></i>Daftar Kamar
            </a>
        </x-slot:actions>
    </x-page-header>

    <div class="row g-3 mb-3">
        <div class="col-6 col-xl-3">
            <x-stat-card label="Tarif per Hari" :value="$room->rateFormatted()" icon="bi-cash-coin" color="success" />
        </div>
        <div class="col-6 col-xl-3">
            <x-stat-card label="Tempat Tidur" :value="$room->beds->count()" icon="bi-layout-text-window-reverse" color="primary" />
        </div>
        <div class="col-6 col-xl-3">
            <x-stat-card label="Terisi" :value="$room->beds->where('status', \App\Enums\BedStatus::Occupied)->count()" icon="bi-person-fill" color="danger" />
        </div>
        <div class="col-6 col-xl-3">
            <x-stat-card label="Status Kamar" :value="$room->status?->label() ?? '-'"
                         icon="bi-toggle-on" :color="$room->status === \App\Enums\ActiveStatus::Active ? 'teal' : 'secondary'" />
        </div>
    </div>

    <x-card title="Tambah Tempat Tidur" icon="bi-plus-square" class="mb-3">
        <form method="POST" action="{{ route('admin.rooms.beds.store', $room) }}" class="row g-2 align-items-end">
            @csrf
            <div class="col-md-2">
                <label class="form-label small mb-1" for="bed_code">Kode <span class="text-danger">*</span></label>
                <input type="text" name="code" id="bed_code" value="{{ old('code') }}" maxlength="20" required
                       class="form-control form-control-sm @error('code') is-invalid @enderror" placeholder="A">
                @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-3">
                <label class="form-label small mb-1" for="bed_status">Status <span class="text-danger">*</span></label>
                <select name="status" id="bed_status" class="form-select form-select-sm @error('status') is-invalid @enderror" required>
                    @foreach ($bedStatuses as $value => $label)
                        <option value="{{ $value }}" @selected(old('status') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-4">
                <label class="form-label small mb-1" for="bed_notes">Catatan</label>
                <input type="text" name="notes" id="bed_notes" value="{{ old('notes') }}" maxlength="500"
                       class="form-control form-control-sm @error('notes') is-invalid @enderror" placeholder="Opsional">
                @error('notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-primary btn-sm w-100">
                    <i class="bi bi-plus-lg me-1"></i>Tambah Tempat Tidur
                </button>
            </div>
        </form>
    </x-card>

    <x-card title="Tempat Tidur" :subtitle="$room->beds->count().' tempat tidur'" icon="bi-layout-text-window-reverse" class="mb-3">
        <div class="table-responsive">
            <table class="table table-hover align-middle jhs-table-compact">
                <thead>
                    <tr>
                        <th>Kode</th>
                        <th>Status</th>
                        <th>Pasien Aktif</th>
                        <th>Catatan</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($room->beds as $bed)
                        @php($occupied = $bed->activeAdmission !== null)
                        <tr>
                            <td class="small fw-semibold">{{ $bed->code }}</td>
                            <td>
                                @if ($occupied)
                                    <x-status-badge :status="$bed->status" />
                                @else
                                    <form id="bed-update-{{ $bed->id }}" method="POST"
                                          action="{{ route('admin.rooms.beds.update', [$room, $bed]) }}">
                                        @csrf @method('PUT')
                                        <input type="hidden" name="code" value="{{ $bed->code }}">
                                        <select name="status" class="form-select form-select-sm" style="min-width:130px">
                                            @foreach ($bedStatuses as $value => $label)
                                                <option value="{{ $value }}" @selected($bed->status?->value === $value)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </form>
                                @endif
                            </td>
                            <td class="small">
                                @if ($occupied)
                                    <a href="{{ route('reception.admissions.show', $bed->activeAdmission) }}"
                                       class="text-decoration-none fw-semibold">
                                        {{ $bed->activeAdmission->patient?->name ?? '-' }}
                                    </a>
                                    <div class="fs-7 text-muted-2">{{ $bed->activeAdmission->code }}</div>
                                @else
                                    <span class="text-muted-2">-</span>
                                @endif
                            </td>
                            <td class="small text-muted-2">
                                @if ($occupied)
                                    {{ $bed->notes ?: '-' }}
                                @else
                                    <input type="text" name="notes" form="bed-update-{{ $bed->id }}"
                                           value="{{ $bed->notes }}" maxlength="500"
                                           class="form-control form-control-sm" placeholder="Opsional">
                                @endif
                            </td>
                            <td class="text-end">
                                @if (! $occupied)
                                    <div class="btn-group btn-group-sm">
                                        <button type="submit" form="bed-update-{{ $bed->id }}" class="btn btn-light border" title="Simpan">
                                            <i class="bi bi-check-lg"></i>
                                        </button>
                                        <form method="POST" action="{{ route('admin.rooms.beds.destroy', [$room, $bed]) }}"
                                              data-confirm-submit data-confirm-title="Hapus Tempat Tidur"
                                              data-confirm-message="Hapus tempat tidur {{ $bed->code }}?" data-confirm-variant="danger">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn btn-light border" title="Hapus">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5">
                                <x-empty-state icon="bi-layout-text-window-reverse" title="Belum ada tempat tidur"
                                               text="Tambahkan tempat tidur pada formulir di atas." />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-card>

    @if ($room->notes)
        <x-card title="Keterangan Kamar" icon="bi-info-circle" class="mb-3">
            <p class="small text-muted-2 mb-0 text-pre-line">{{ $room->notes }}</p>
        </x-card>
    @endif

    <x-card title="Riwayat Rawat Inap" :subtitle="'20 riwayat terakhir'" icon="bi-clock-history">
        <div class="table-responsive">
            <table class="table table-hover align-middle jhs-table-compact">
                <thead>
                    <tr>
                        <th>Kode</th>
                        <th>Pasien</th>
                        <th>Dokter</th>
                        <th>Masuk</th>
                        <th>Pulang</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($room->admissions as $admission)
                        <tr>
                            <td class="small fw-semibold text-nowrap">
                                <a href="{{ route('reception.admissions.show', $admission) }}" class="text-decoration-none">
                                    {{ $admission->code }}
                                </a>
                            </td>
                            <td class="small">{{ $admission->patient?->name ?? '-' }}</td>
                            <td class="small">{{ $admission->doctor?->name ?? '-' }}</td>
                            <td class="small text-nowrap">{{ $admission->admitted_at?->format('d/m/Y H:i') ?? '-' }}</td>
                            <td class="small text-nowrap">{{ $admission->discharged_at?->format('d/m/Y H:i') ?? '-' }}</td>
                            <td><x-status-badge :status="$admission->status" /></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6">
                                <x-empty-state icon="bi-clock-history" title="Belum ada riwayat"
                                               text="Pasien yang pernah dirawat di kamar ini akan tampil di sini." />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-card>
@endsection

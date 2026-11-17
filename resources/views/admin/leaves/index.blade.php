@extends('layouts.app')

@section('title', 'Cuti Dokter')

@section('content')
    <x-page-header title="Cuti Dokter"
                   description="Tandai tanggal dokter tidak praktik. Slot booking dan antrean pada tanggal tersebut otomatis ditutup."
                   icon="bi-calendar-x">
        <x-slot:actions>
            <a href="{{ route('admin.schedules.index') }}" class="btn btn-light border btn-sm">
                <i class="bi bi-calendar-week me-1"></i>Jadwal Dokter
            </a>
        </x-slot:actions>
    </x-page-header>

    <div class="row g-3">
        <div class="col-xl-4">
            <x-card title="Tambah Cuti" icon="bi-calendar-plus">
                <form method="POST" action="{{ route('admin.leaves.store') }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label" for="doctor_id">Dokter <span class="text-danger">*</span></label>
                        <select name="doctor_id" id="doctor_id" required
                                class="form-select form-select-sm @error('doctor_id') is-invalid @enderror">
                            <option value="">Pilih dokter</option>
                            @foreach ($doctors as $doctor)
                                <option value="{{ $doctor->id }}" @selected(old('doctor_id') == $doctor->id)>
                                    {{ $doctor->name }} &middot; {{ $doctor->specialization }}
                                </option>
                            @endforeach
                        </select>
                        @error('doctor_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="date">Tanggal <span class="text-danger">*</span></label>
                        <input type="date" name="date" id="date" required
                               min="{{ now()->toDateString() }}"
                               value="{{ old('date') }}"
                               class="form-control form-control-sm @error('date') is-invalid @enderror">
                        @error('date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="reason">Keterangan</label>
                        <input type="text" name="reason" id="reason" maxlength="255"
                               value="{{ old('reason') }}"
                               class="form-control form-control-sm @error('reason') is-invalid @enderror"
                               placeholder="Contoh: Cuti tahunan">
                        @error('reason')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <button type="submit" class="btn btn-primary btn-sm w-100">
                        <i class="bi bi-check-lg me-1"></i>Simpan Cuti
                    </button>
                    <p class="fs-7 text-muted-2 mb-0 mt-2">
                        <i class="bi bi-info-circle me-1"></i>Janji temu yang sudah terjadwal pada tanggal cuti tidak otomatis batal; koordinasikan dengan resepsionis.
                    </p>
                </form>
            </x-card>
        </div>

        <div class="col-xl-8">
            <x-card class="mb-3">
                <form method="GET" class="row g-2 align-items-end">
                    <div class="col-lg-4">
                        <label class="form-label small mb-1" for="filter_doctor">Dokter</label>
                        <select name="doctor_id" id="filter_doctor" class="form-select form-select-sm">
                            <option value="">Semua Dokter</option>
                            @foreach ($doctors as $doctor)
                                <option value="{{ $doctor->id }}" @selected(($filters['doctor_id'] ?? '') == $doctor->id)>{{ $doctor->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-3">
                        <label class="form-label small mb-1" for="period">Periode</label>
                        <select name="period" id="period" class="form-select form-select-sm">
                            <option value="upcoming" @selected($period === 'upcoming')>Akan Datang</option>
                            <option value="past" @selected($period === 'past')>Sudah Lewat</option>
                        </select>
                    </div>
                    <div class="col-lg-5 d-flex gap-2">
                        <button class="btn btn-primary btn-sm flex-grow-1" type="submit"><i class="bi bi-funnel me-1"></i>Terapkan</button>
                        <a href="{{ route('admin.leaves.index') }}" class="btn btn-light border btn-sm">Reset</a>
                    </div>
                </form>
            </x-card>

            <x-data-table :rows="$leaves"
                          :columns="[
                              ['label' => 'Tanggal'],
                              ['label' => 'Dokter'],
                              ['label' => 'Keterangan'],
                              ['label' => 'Dicatat'],
                              ['label' => 'Aksi', 'class' => 'text-end', 'cellClass' => 'text-end'],
                          ]"
                          empty-title="Belum ada cuti"
                          empty-text="Tambahkan tanggal cuti pada formulir di samping."
                          empty-icon="bi-calendar-x">
                @foreach ($leaves as $leave)
                    <tr>
                        <td class="small text-nowrap">
                            <div class="fw-semibold">{{ $leave->date?->translatedFormat('d F Y') ?? '-' }}</div>
                            <div class="fs-7 text-muted-2">{{ $leave->date?->translatedFormat('l') }}</div>
                        </td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <x-avatar :name="$leave->doctor?->name ?? 'Dokter'" size="sm" />
                                <div class="min-w-0">
                                    <div class="small fw-semibold text-truncate">{{ $leave->doctor?->name ?? '-' }}</div>
                                    <div class="fs-7 text-muted-2 text-truncate">{{ $leave->doctor?->specialization }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="small">{{ $leave->reason ?? '-' }}</td>
                        <td class="small">
                            {{ $leave->createdBy?->name ?? '-' }}
                            <div class="fs-7 text-muted-2">{{ $leave->created_at?->translatedFormat('d/m/Y H:i') }}</div>
                        </td>
                        <td class="text-end">
                            <form method="POST" action="{{ route('admin.leaves.destroy', $leave) }}"
                                  data-confirm-submit data-confirm-title="Hapus Cuti"
                                  data-confirm-message="Hapus cuti {{ $leave->doctor?->name }} pada {{ $leave->date?->translatedFormat('d F Y') }}?"
                                  data-confirm-variant="danger">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-light border" title="Hapus cuti">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </x-data-table>
        </div>
    </div>
@endsection

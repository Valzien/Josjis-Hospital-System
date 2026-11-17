@extends('layouts.app')

@section('title', 'Jadwal Praktik')

@section('content')
    <x-page-header title="Jadwal Praktik Saya"
                   description="Jadwal praktik, kuota antrean, dan ruang konsultasi yang assigned untuk Anda."
                   icon="bi-calendar-week">
        <x-slot:actions>
            <span class="badge bg-white text-dark border px-3 py-2 fw-medium shadow-xs">
                <i class="bi bi-calendar3 me-1 text-primary"></i>{{ now()->translatedFormat('l, d F Y') }}
            </span>
        </x-slot:actions>
    </x-page-header>

    <div class="row g-3 mb-3">
        <div class="col-6">
            <x-stat-card label="Antrean Hari Ini" :value="$todayStats['total']" icon="bi-list-ol" color="primary"
                         :href="route('doctor.queues.index')" />
        </div>
        <div class="col-6">
            <x-stat-card label="Selesai Hari Ini" :value="$todayStats['completed']" icon="bi-check2-circle" color="success"
                         :href="route('doctor.queues.index', ['status' => 'completed'])" />
        </div>
    </div>

    <x-card title="Jadwal Mingguan" subtitle="{{ $schedules->count() }} jadwal terdaftar" icon="bi-calendar-week">
        <div class="table-responsive">
            <table class="table table-hover align-middle jhs-table-compact">
                <thead>
                    <tr>
                        <th>Hari</th>
                        <th>Jam Praktik</th>
                        <th class="text-center">Kuota</th>
                        <th>Ruang</th>
                        <th class="text-end">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($schedules as $schedule)
                        <tr>
                            <td class="small fw-semibold">{{ $schedule->day?->label() ?? '-' }}</td>
                            <td class="small text-nowrap">{{ $schedule->timeRange() }}</td>
                            <td class="small text-center">{{ $schedule->quota }} pasien</td>
                            <td class="small">{{ $schedule->room ?? '-' }}</td>
                            <td class="text-end">
                                @if ($schedule->status?->value === 'active')
                                    <x-badge text="Aktif" icon="bi-check-circle-fill" color="success" />
                                @else
                                    <x-badge text="Nonaktif" icon="bi-pause-circle-fill" color="secondary" />
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5">
                                <x-empty-state icon="bi-calendar-x" title="Belum ada jadwal"
                                               text="Jadwal praktik ditetapkan oleh administrator." />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-card>

    <div class="row g-3 mt-1">
        <div class="col-xl-7">
            <x-card title="Tanggal Cuti" icon="bi-calendar-x"
                    subtitle="{{ $leaves->count() }} tanggal cuti mendatang">
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Tanggal</th>
                                <th>Keterangan</th>
                                <th class="text-center">Janji Temu</th>
                                <th class="text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($leaves as $leave)
                                @php $booked = (int) ($bookedCounts[$leave->date->toDateString()] ?? 0); @endphp
                                <tr>
                                    <td class="small text-nowrap">
                                        <div class="fw-semibold">{{ $leave->date->translatedFormat('d F Y') }}</div>
                                        <div class="fs-7 text-muted-2">{{ $leave->date->translatedFormat('l') }}</div>
                                    </td>
                                    <td class="small">{{ $leave->reason ?? '-' }}</td>
                                    <td class="text-center">
                                        @if ($booked > 0)
                                            <x-badge :text="$booked.' janji'" icon="bi-exclamation-triangle-fill" color="warning" />
                                        @else
                                            <span class="text-muted-2 small">-</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <form method="POST" action="{{ route('doctor.leaves.destroy', $leave) }}"
                                              data-confirm-submit data-confirm-title="Batalkan Cuti"
                                              data-confirm-message="Batalkan cuti {{ $leave->date->translatedFormat('d F Y') }}?"
                                              data-confirm-variant="danger">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-light border" title="Batalkan cuti">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4">
                                        <x-empty-state icon="bi-calendar-x" title="Belum ada tanggal cuti"
                                                       text="Tandai tanggal tidak praktik pada formulir di samping." />
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-card>
        </div>

        <div class="col-xl-5">
            <x-card title="Tandai Cuti" icon="bi-calendar-plus"
                    subtitle="Slot booking pada tanggal cuti otomatis ditutup.">
                <form method="POST" action="{{ route('doctor.leaves.store') }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label" for="leave_date">Tanggal <span class="text-danger">*</span></label>
                        <input type="date" name="date" id="leave_date" required
                               min="{{ now()->toDateString() }}"
                               value="{{ old('date') }}"
                               class="form-control form-control-sm @error('date') is-invalid @enderror">
                        @error('date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="leave_reason">Keterangan</label>
                        <input type="text" name="reason" id="leave_reason" maxlength="255"
                               value="{{ old('reason') }}"
                               class="form-control form-control-sm @error('reason') is-invalid @enderror"
                               placeholder="Contoh: Cuti tahunan">
                        @error('reason')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm w-100">
                        <i class="bi bi-check-lg me-1"></i>Simpan Cuti
                    </button>
                </form>
            </x-card>
        </div>
    </div>
@endsection
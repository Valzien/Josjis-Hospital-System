@extends('layouts.app')

@section('title', 'Jadwal Dokter')

@section('content')
    <x-page-header title="Jadwal Dokter" description="Atur jadwal praktik, kuota antrean, dan ruang konsultasi setiap dokter." icon="bi-calendar-week">
        <x-slot:actions>
            <a href="{{ route('admin.schedules.create') }}" class="btn btn-primary btn-sm">
                <i class="bi bi-plus-lg me-1"></i>Tambah Jadwal
            </a>
        </x-slot:actions>
    </x-page-header>

    <x-card class="mb-3">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-lg-3">
                <label class="form-label small mb-1" for="q">Cari Dokter</label>
                <input type="search" name="q" id="q" value="{{ $filters['q'] ?? '' }}"
                       class="form-control form-control-sm" placeholder="Nama dokter" data-search-submit>
            </div>
            <div class="col-lg-3">
                <label class="form-label small mb-1" for="doctor_id">Dokter</label>
                <select name="doctor_id" id="doctor_id" class="form-select form-select-sm">
                    <option value="">Semua Dokter</option>
                    @foreach ($doctors as $doctor)
                        <option value="{{ $doctor->id }}" @selected(($filters['doctor_id'] ?? '') == $doctor->id)>{{ $doctor->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-lg-2">
                <label class="form-label small mb-1" for="day">Hari</label>
                <select name="day" id="day" class="form-select form-select-sm">
                    <option value="">Semua Hari</option>
                    @foreach ($days as $value => $label)
                        <option value="{{ $value }}" @selected((string) ($filters['day'] ?? '') === (string) $value)>{{ $label }}</option>
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
            <div class="col-lg-2 d-flex gap-2">
                <button class="btn btn-primary btn-sm flex-grow-1" type="submit"><i class="bi bi-funnel me-1"></i>Terapkan</button>
                <a href="{{ route('admin.schedules.index') }}" class="btn btn-light border btn-sm">Reset</a>
            </div>
        </form>
    </x-card>

    <x-card title="Jadwal Mingguan" subtitle="{{\App\Models\DoctorSchedule::query()->count() }} jadwal terdaftar" icon="bi-calendar3">
        <x-slot:actions>
            <div class="small text-muted-2">
                <i class="bi bi-circle-fill text-success me-1" style="font-size:.5rem"></i>Hari ini
            </div>
        </x-slot:actions>

        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th>Hari</th>
                        <th>Dokter</th>
                        <th>Waktu</th>
                        <th>Ruang</th>
                        <th>Kuota</th>
                        <th>Antrean Hari Ini</th>
                        <th>Status</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($schedules as $schedule)
                        @php $todayUsage = $schedule->remainingQuota(); @endphp
                        <tr @class(['table-light' => $schedule->isToday()])>
                            <td>
                                <span class="small fw-semibold">{{ $schedule->dayLabel() }}</span>
                                @if ($schedule->isToday())
                                    <span class="badge text-bg-success ms-1">Hari Ini</span>
                                @endif
                            </td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <x-avatar :name="$schedule->doctor->name" size="sm" />
                                    <div class="min-w-0">
                                        <div class="small fw-semibold text-truncate">{{ $schedule->doctor->name }}</div>
                                        <div class="fs-7 text-muted-2 text-truncate">{{ $schedule->doctor->specialization }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="small">{{ $schedule->timeRange() }}</td>
                            <td class="small">{{ $schedule->room ?? '-' }}</td>
                            <td class="small">{{ $schedule->quota }}</td>
                            <td class="small">
                                @if ($schedule->isToday())
                                    <x-badge :text="$todayUsage.' tersisa'" :color="$todayUsage > 0 ? 'success' : 'danger'"
                                            :icon="$todayUsage > 0 ? 'bi-check-circle-fill' : 'bi-x-circle-fill'" />
                                @else
                                    <span class="text-muted-2">-</span>
                                @endif
                            </td>
                            <td>
                                <x-badge :text="$schedule->status->label()"
                                        :color="$schedule->isActive() ? 'success' : 'secondary'"
                                        :icon="$schedule->isActive() ? 'bi-check-circle-fill' : 'bi-slash-circle'" />
                            </td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ route('admin.schedules.edit', $schedule) }}" class="btn btn-light border" title="Ubah">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <form method="POST" action="{{ route('admin.schedules.destroy', $schedule) }}"
                                          data-confirm-submit data-confirm-title="Hapus Jadwal"
                                          data-confirm-message="Hapus jadwal {{ $schedule->dayLabel() }} pukul {{ $schedule->timeRange() }}?"
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
                            <td colspan="8">
                                <x-empty-state icon="bi-calendar-x" title="Belum ada jadwal"
                                               text="Tambahkan jadwal dokter agar pasien dapat mengambil antrean online.">
                                    <x-slot:action>
                                        <a href="{{ route('admin.schedules.create') }}" class="btn btn-primary btn-sm mt-2">Tambah Jadwal</a>
                                    </x-slot:action>
                                </x-empty-state>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-card>
@endsection

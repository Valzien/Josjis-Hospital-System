@extends('layouts.app')

@section('title', ($schedule->exists ? 'Ubah' : 'Tambah').' Jadwal')

@section('content')
    <x-page-header :title="$schedule->exists ? 'Ubah Jadwal' : 'Tambah Jadwal'"
                   :description="$schedule->exists ? 'Perbarui jadwal praktik dokter.' : 'Atur hari praktik, jam layanan, dan kuota antrean.'"
                   icon="bi-calendar-week" />

    <div class="row g-3">
        <div class="col-xl-8">
            <x-card title="Detail Jadwal" icon="bi-calendar3">
                <form method="POST" action="{{ $schedule->exists ? route('admin.schedules.update', $schedule) : route('admin.schedules.store') }}">
                    @csrf
                    @if ($schedule->exists) @method('PUT') @endif

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" for="doctor_id">Dokter <span class="text-danger">*</span></label>
                            <select name="doctor_id" id="doctor_id" class="form-select @error('doctor_id') is-invalid @enderror" required>
                                <option value="">Pilih dokter...</option>
                                @foreach ($doctors as $doctor)
                                    <option value="{{ $doctor->id }}"
                                        @selected(old('doctor_id', $schedule->doctor_id ?? request('doctor_id')) == $doctor->id)>
                                        {{ $doctor->name }} &mdash; {{ $doctor->specialization }}
                                    </option>
                                @endforeach
                            </select>
                            @error('doctor_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" for="day">Hari Praktik <span class="text-danger">*</span></label>
                            <select name="day" id="day" class="form-select @error('day') is-invalid @enderror" required>
                                @foreach ($days as $value => $label)
                                    <option value="{{ $value }}" @selected(old('day', $schedule->day?->value ?? now()->dayOfWeek) == $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('day')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-3">
                            <label class="form-label" for="start_time">Mulai <span class="text-danger">*</span></label>
                            <input type="time" name="start_time" id="start_time"
                                   value="{{ old('start_time', \Illuminate\Support\Str::substr((string) $schedule->start_time, 0, 5)) }}"
                                   class="form-control @error('start_time') is-invalid @enderror" required>
                            @error('start_time')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-3">
                            <label class="form-label" for="end_time">Selesai <span class="text-danger">*</span></label>
                            <input type="time" name="end_time" id="end_time"
                                   value="{{ old('end_time', \Illuminate\Support\Str::substr((string) $schedule->end_time, 0, 5)) }}"
                                   class="form-control @error('end_time') is-invalid @enderror" required>
                            @error('end_time')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-3">
                            <label class="form-label" for="room">Ruang</label>
                            <input type="text" name="room" id="room" value="{{ old('room', $schedule->room) }}"
                                   class="form-control @error('room') is-invalid @enderror" placeholder="Poli 1">
                            @error('room')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-3">
                            <label class="form-label" for="quota">Kuota Antrean <span class="text-danger">*</span></label>
                            <input type="number" name="quota" id="quota" min="1" max="200"
                                   value="{{ old('quota', $schedule->quota ?? 30) }}"
                                   class="form-control @error('quota') is-invalid @enderror" required>
                            @error('quota')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" for="status">Status <span class="text-danger">*</span></label>
                            <select name="status" id="status" class="form-select @error('status') is-invalid @enderror" required>
                                @foreach ($statuses as $value => $label)
                                    <option value="{{ $value }}" @selected(old('status', $schedule->status?->value) === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="d-flex gap-2 mt-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-lg me-1"></i>{{ $schedule->exists ? 'Simpan Perubahan' : 'Tambah Jadwal' }}
                        </button>
                        <a href="{{ route('admin.schedules.index') }}" class="btn btn-light border">Batal</a>
                    </div>
                </form>
            </x-card>
        </div>

        <div class="col-xl-4">
            <x-card title="Informasi" icon="bi-info-circle">
                <ul class="small text-muted-2 mb-0" style="line-height:1.7">
                    <li>Pasien hanya dapat mengambil antrean pada jam layanan.</li>
                    <li>Kuota membatasi jumlah antrean per jadwal per hari.</li>
                    <li>Nomor antrean dihitung per dokter per hari, bukan per jadwal.</li>
                    <li>Jadwal nonaktif tidak muncul di portal pasien.</li>
                </ul>
            </x-card>
        </div>
    </div>
@endsection
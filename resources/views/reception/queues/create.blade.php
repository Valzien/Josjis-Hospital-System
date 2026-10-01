@extends('layouts.app')

@section('title', 'Buat Antrean')

@section('content')
    <x-page-header title="Buat Antrean Baru"
                   description="Pilih pasien dan jadwal dokter yang masih menerima antrean pada hari ini."
                   icon="bi-plus-circle">
        <x-slot:actions>
            <a href="{{ route('reception.queues.index') }}" class="btn btn-light border btn-sm">
                <i class="bi bi-arrow-left me-1"></i>Daftar Antrean
            </a>
        </x-slot:actions>
    </x-page-header>

    @if ($errors->any())
        <div class="alert alert-danger">
            <div class="fw-semibold mb-1"><i class="bi bi-exclamation-triangle me-1"></i>Antrean tidak dapat dibuat:</div>
            <ul class="mb-0 ps-3 small">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('reception.queues.store') }}">
        @csrf

        <div class="row g-3">
            <div class="col-xl-7">
                <x-card title="Pilih Pasien" icon="bi-person">
                    <div class="mb-3">
                        <label class="form-label" for="patient_search">Cari Pasien</label>
                        <input type="search" id="patient_search" class="form-control" placeholder="Ketik nama atau No. RM">
                        <div class="form-text">Gunakan pencarian di atas untuk memfilter daftar pasien.</div>
                    </div>

                    <label class="form-label" for="patient_id">Pasien <span class="text-danger">*</span></label>
                    <select name="patient_id" id="patient_id" required
                            class="form-select @error('patient_id') is-invalid @enderror">
                        <option value="">Pilih pasien terdaftar</option>
                        @foreach ($patients as $item)
                            <option value="{{ $item->id }}" data-search="{{ \Illuminate\Support\Str::lower($item->name.' '.$item->medical_record_number.' '.($item->nik ?? '')) }}"
                                    @selected((string) old('patient_id', request('patient_id')) === (string) $item->id)>
                                {{ $item->medical_record_number }} &mdash; {{ $item->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('patient_id')<div class="invalid-feedback">{{ $message }}</div>@enderror

                    <div class="form-text mt-1">
                        Pasien baru?
                        <a href="{{ route('reception.registration.create') }}" class="text-decoration-none">Daftarkan terlebih dahulu</a>.
                    </div>
                </x-card>

                <x-card title="Keluhan Utama" subtitle="Opsional, membantu dokter sebelum pemeriksaan" icon="bi-chat-left-text" class="mt-3">
                    <label class="form-label" for="complaint_note">Keluhan</label>
                    <textarea name="complaint_note" id="complaint_note" rows="3" maxlength="500"
                              class="form-control @error('complaint_note') is-invalid @enderror"
                              placeholder="Contoh: Demam sejak tiga hari disertai pilek">{{ old('complaint_note') }}</textarea>
                    @error('complaint_note')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </x-card>
            </div>

            <div class="col-xl-5">
                <x-card title="Pilih Jadwal Dokter" icon="bi-calendar-week">
                    @php
                        $scheduleGroups = $doctors->mapWithKeys(fn ($doctor) => [
                            $doctor->id => [
                                'doctor' => $doctor,
                                'schedules' => \App\Models\DoctorSchedule::query()
                                    ->where('doctor_id', $doctor->id)
                                    ->active()
                                    ->forDay(now())
                                    ->orderBy('start_time')
                                    ->get(),
                            ],
                        ])->filter(fn ($row) => $row['schedules']->isNotEmpty());
                    @endphp

                    <label class="form-label" for="doctor_schedule_id">Jadwal <span class="text-danger">*</span></label>
                    <select name="doctor_schedule_id" id="doctor_schedule_id" required
                            class="form-select @error('doctor_schedule_id') is-invalid @enderror">
                        <option value="">Pilih jadwal</option>
                        @forelse ($scheduleGroups as $row)
                            @foreach ($row['schedules'] as $schedule)
                                <option value="{{ $schedule->id }}"
                                        @selected((string) old('doctor_schedule_id') === (string) $schedule->id)
                                        @disabled(! $schedule->isOpen())>
                                    {{ $row['doctor']->name }} &mdash; {{ $schedule->timeRange() }}
                                    ({{ $schedule->isOpen() ? 'sisa '.$schedule->remainingQuota() : 'sudah ditutup' }})
                                </option>
                            @endforeach
                        @empty
                            <option value="" disabled>Tidak ada jadwal aktif hari ini</option>
                        @endforelse
                    </select>
                    @error('doctor_schedule_id')<div class="invalid-feedback">{{ $message }}</div>@enderror

                    <div class="alert alert-light border small mt-3 mb-0">
                        <i class="bi bi-info-circle me-1"></i>
                        Nomor antrean dibuat otomatis, unik per dokter dan tanggal.
                        Setiap pasien hanya dapat memiliki satu antrean aktif per hari.
                    </div>
                </x-card>

                <div class="d-grid gap-2 mt-3">
                    <button type="submit" class="btn btn-primary" @disabled($scheduleGroups->isEmpty())>
                        <i class="bi bi-check-lg me-1"></i>Buat Antrean
                    </button>
                    <a href="{{ route('reception.queues.index') }}" class="btn btn-light border">Batal</a>
                </div>
            </div>
        </div>
    </form>
@endsection

@push('scripts')
    <script>
        (function () {
            const search = document.getElementById('patient_search');
            const select = document.getElementById('patient_id');
            if (!search || !select) return;

            search.addEventListener('input', function () {
                const term = this.value.trim().toLowerCase();
                let visible = 0;

                for (const option of select.options) {
                    if (!option.value) continue;
                    const haystack = (option.dataset.search || option.textContent).toLowerCase();
                    const match = term === '' || haystack.includes(term);
                    option.hidden = !match;
                    option.disabled = !match;
                    if (match) visible++;
                }

                select.classList.toggle('is-invalid', term !== '' && visible === 0);
            });
        })();
    </script>
@endpush
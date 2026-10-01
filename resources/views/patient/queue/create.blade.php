@extends('layouts.app')

@section('title', 'Ambil Antrean')

@section('content')
    <x-page-header title="Ambil Antrean" description="Pilih dokter yang tersedia hari ini untuk mendapat nomor antrean." icon="bi-ticket-perforated">
        <x-slot:actions>
            <a href="{{ route('patient.queue.index') }}" class="btn btn-light border btn-sm">
                <i class="bi bi-list-ol me-1"></i>Antrean Saya
            </a>
        </x-slot:actions>
    </x-page-header>

    @if ($existingQueue)
        <div class="alert alert-info d-flex flex-wrap align-items-center justify-content-between gap-2">
            <div>
                <i class="bi bi-info-circle me-1"></i>
                Anda masih memiliki antrean aktif
                (<strong>{{ $existingQueue->queue_number }}</strong> pada {{ $existingQueue->doctor?->name }}).
                Selesaikan atau batalkan sebelum mengambil antrean baru.
            </div>
            <a href="{{ route('patient.queue.show', $existingQueue) }}" class="btn btn-sm btn-info">Lihat Antrean</a>
        </div>
    @endif

    @if (! $isProfileComplete)
        <div class="alert alert-warning">
            <i class="bi bi-exclamation-triangle me-1"></i>
            Lengkapi data <strong>{{ implode(', ', $missingFields) }}</strong> terlebih dahulu sebelum mengambil antrean.
            <a href="{{ route('patient.profile.edit') }}" class="alert-link ms-1">Lengkapi profil sekarang</a>
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger">
            <div class="fw-semibold mb-1"><i class="bi bi-exclamation-triangle me-1"></i>Antrean gagal dibuat:</div>
            <ul class="mb-0 ps-3 small">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="row g-3">
        <div class="col-xl-4">
            <x-card title="Pilih Dokter" icon="bi-heart-pulse"
                    subtitle="{{ $doctors->count() }} dokter bertugas hari ini">
                <div class="vstack gap-2">
                    @forelse ($doctors as $doctor)
                        <button type="button"
                                class="btn btn-light border text-start w-100 js-doctor-filter"
                                data-doctor-id="{{ $doctor->id }}"
                                data-active="{{ (int) ((int) $selectedDoctor === $doctor->id) }}">
                            <div class="d-flex align-items-center justify-content-between gap-2">
                                <div class="min-w-0">
                                    <div class="small fw-semibold text-dark text-truncate">{{ $doctor->name }}</div>
                                    <div class="fs-7 text-muted-2">{{ $doctor->specialization }}</div>
                                </div>
                                <span class="jhs-avatar jhs-avatar-sm">{{ $doctor->initials() }}</span>
                            </div>
                        </button>
                    @empty
                        <x-empty-state icon="bi-calendar-x" title="Tidak ada dokter bertugas hari ini"
                                      text="Coba lagi pada hari lain." />
                    @endforelse
                </div>
            </x-card>
        </div>

        <div class="col-xl-8">
            <x-card title="Jadwal Tersedia" icon="bi-calendar-week" subtitle="{{ now()->translatedFormat('l, d F Y') }}">
                @if (! $isProfileComplete)
                    <x-empty-state icon="bi-lock" title="Profil belum lengkap"
                                  text="Lengkapi data profil terlebih dahulu untuk mengambil antrean." />
                @elseif ($schedules->isEmpty())
                    <x-empty-state icon="bi-calendar-x" title="Belum ada jadwal tersedia"
                                  text="Tidak ada dokter yang menerima antrean pada hari ini.">
                        <a href="{{ route('patient.schedules.index') }}" class="btn btn-sm btn-light border mt-2">
                            Lihat Jadwal Mingguan
                        </a>
                    </x-empty-state>
                @else
                    <form method="POST" action="{{ route('patient.queue.store') }}" id="queueForm">
                        @csrf
                        <input type="hidden" name="doctor_schedule_id" id="doctor_schedule_id"
                               value="{{ old('doctor_schedule_id') }}">

                        <div class="vstack gap-2 mb-3">
                            @foreach ($schedules as $schedule)
                                @php $remaining = $schedule->remainingQuota(); @endphp
                                <label class="border rounded-3 p-2 d-flex align-items-center gap-2 js-schedule-option {{ old('doctor_schedule_id') == $schedule->id ? 'border-primary bg-light-subtle' : '' }}"
                                       data-schedule-id="{{ $schedule->id }}"
                                       data-doctor-id="{{ $schedule->doctor_id }}"
                                       data-remaining="{{ $remaining }}"
                                       style="cursor:pointer">
                                    <input class="form-check-input flex-shrink-0 js-schedule-radio" type="radio"
                                           name="schedule_choice" value="{{ $schedule->id }}" @checked(old('doctor_schedule_id') == $schedule->id)>
                                    <div class="min-w-0 flex-grow-1">
                                        <div class="small fw-semibold">{{ $schedule->doctor?->name ?? 'Dokter' }}</div>
                                        <div class="fs-7 text-muted-2">
                                            {{ $schedule->dayLabel() }} &middot; {{ $schedule->timeRange() }}
                                            @if ($schedule->room) &middot; {{ $schedule->room }} @endif
                                        </div>
                                    </div>
                                    @if ($remaining > 0)
                                        <x-badge :text="$remaining.' slot tersisa'" icon="bi-check-circle-fill" color="success" />
                                    @else
                                        <x-badge text="Kuota penuh" icon="bi-x-circle-fill" color="danger" />
                                    @endif
                                </label>
                            @endforeach
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="complaint_note">Keluhan Singkat</label>
                            <textarea name="complaint_note" id="complaint_note" rows="3" maxlength="500"
                                      class="form-control @error('complaint_note') is-invalid @enderror"
                                      placeholder="Contoh: Demam dan sakit kepala sejak dua hari.">{{ old('complaint_note') }}</textarea>
                            <div class="form-text">Opsional, membantu dokter bersiap sebelum giliran Anda.</div>
                            @error('complaint_note')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <button type="submit" class="btn btn-primary" @disabled($existingQueue !== null)>
                            <i class="bi bi-check-lg me-1"></i>Ambil Antrean
                        </button>
                    </form>
                @endif
            </x-card>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (function () {
            const hidden = document.getElementById('doctor_schedule_id');
            const options = Array.from(document.querySelectorAll('.js-schedule-option'));
            const doctorFilters = Array.from(document.querySelectorAll('.js-doctor-filter'));

            function selectSchedule(id) {
                hidden.value = id;
                options.forEach(function (option) {
                    const active = option.dataset.scheduleId === String(id);
                    option.classList.toggle('border-primary', active);
                    option.classList.toggle('bg-light-subtle', active);
                    const radio = option.querySelector('.js-schedule-radio');
                    if (radio) radio.checked = active;
                });
            }

            options.forEach(function (option) {
                option.addEventListener('click', function () {
                    selectSchedule(option.dataset.scheduleId);
                });
            });

            doctorFilters.forEach(function (button) {
                button.addEventListener('click', function () {
                    const doctorId = button.dataset.doctorId;

                    doctorFilters.forEach(function (item) {
                        item.classList.toggle('border-primary', item === button);
                        item.classList.toggle('bg-light-subtle', item === button);
                    });

                    let firstVisible = null;
                    options.forEach(function (option) {
                        const visible = option.dataset.doctorId === doctorId;
                        option.classList.toggle('d-none', !visible);
                        if (visible && !firstVisible) firstVisible = option;
                    });

                    if (firstVisible) {
                        selectSchedule(firstVisible.dataset.scheduleId);
                    } else {
                        selectSchedule('');
                    }
                });
            });

            @if ($selectedDoctor)
                const initial = document.querySelector('.js-doctor-filter[data-doctor-id="{{ (int) $selectedDoctor }}"]');
                if (initial) initial.click();
            @endif
        })();
    </script>
@endpush
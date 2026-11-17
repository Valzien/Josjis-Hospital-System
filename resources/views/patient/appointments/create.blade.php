@extends('layouts.app')

@section('title', 'Daftar Berobat')

@section('content')
    <x-page-header title="Daftar Berobat"
                   description="Pilih tanggal kunjungan. Hari ini langsung dapat nomor antrean; tanggal ke depan menjadi janji temu."
                   icon="bi-calendar-plus">
        <x-slot:actions>
            <a href="{{ route('patient.appointments.index') }}" class="btn btn-light border btn-sm">
                <i class="bi bi-calendar-check me-1"></i>Janji Temu Saya
            </a>
            <a href="{{ route('patient.queue.index') }}" class="btn btn-light border btn-sm">
                <i class="bi bi-list-ol me-1"></i>Antrean Saya
            </a>
        </x-slot:actions>
    </x-page-header>

    @if (! $isProfileComplete)
        <div class="alert alert-warning">
            <i class="bi bi-exclamation-triangle me-1"></i>
            Lengkapi data <strong>{{ implode(', ', $missingFields) }}</strong> terlebih dahulu sebelum mendaftar berobat.
            <a href="{{ route('patient.profile.edit') }}" class="alert-link ms-1">Lengkapi profil sekarang</a>
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger">
            <div class="fw-semibold mb-1"><i class="bi bi-exclamation-triangle me-1"></i>Pendaftaran gagal:</div>
            <ul class="mb-0 ps-3 small">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if ($isToday && $existingQueue)
        <div class="alert alert-info d-flex flex-wrap align-items-center justify-content-between gap-2">
            <div>
                <i class="bi bi-info-circle me-1"></i>
                Anda masih memiliki antrean aktif
                (<strong>{{ $existingQueue->queue_number }}</strong> pada {{ $existingQueue->doctor?->name }}).
                Selesaikan atau batalkan sebelum mendaftar lagi.
            </div>
            <a href="{{ route('patient.queue.show', $existingQueue) }}" class="btn btn-sm btn-info">Lihat Antrean</a>
        </div>
    @elseif ($isToday && $todayAppointment)
        <div class="alert alert-info d-flex flex-wrap align-items-center justify-content-between gap-2">
            <div>
                <i class="bi bi-calendar-check me-1"></i>
                Anda sudah punya janji temu hari ini pukul
                <strong>{{ $todayAppointment->slotLabel() }}</strong> dengan {{ $todayAppointment->doctor?->name }}.
                Silakan check-in di resepsionis untuk mendapat nomor antrean.
            </div>
            <a href="{{ route('patient.appointments.index') }}" class="btn btn-sm btn-info">Lihat Janji Temu</a>
        </div>
    @endif

    <x-card class="mb-3">
        <form method="GET" action="{{ route('patient.appointments.create') }}" class="d-flex flex-wrap align-items-end gap-2">
            <div>
                <label class="form-label small mb-1" for="date">Tanggal Kunjungan</label>
                <input type="date" name="date" id="date" class="form-control form-control-sm"
                       value="{{ $date->toDateString() }}"
                       min="{{ now()->toDateString() }}" max="{{ $latestDate->toDateString() }}">
            </div>
            <div class="d-flex align-items-center gap-1">
                <a href="{{ route('patient.appointments.create', ['date' => $date->copy()->subDay()->toDateString()]) }}"
                   class="btn btn-light border btn-sm {{ $date->isSameDay(now()) ? 'disabled' : '' }}" title="Hari sebelumnya">
                    <i class="bi bi-chevron-left"></i>
                </a>
                <a href="{{ route('patient.appointments.create', ['date' => $date->copy()->addDay()->toDateString()]) }}"
                   class="btn btn-light border btn-sm {{ $date->isSameDay($latestDate) ? 'disabled' : '' }}" title="Hari berikutnya">
                    <i class="bi bi-chevron-right"></i>
                </a>
            </div>
            <button type="submit" class="btn btn-primary btn-sm">
                <i class="bi bi-search me-1"></i>Tampilkan Jadwal
            </button>
            <span class="small text-muted-2 ms-auto">
                <i class="bi bi-info-circle me-1"></i>{{ $date->translatedFormat('l, d F Y') }}
                &middot; maksimal {{ (int) $latestDate->diffInDays(now()->startOfDay()) }} hari ke depan
            </span>
        </form>
    </x-card>

    @if ($isToday)
        {{-- Kunjungan hari ini: pilih dokter, nomor antrean langsung terbit. --}}
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
                                          text="Pilih tanggal lain untuk membuat janji temu." />
                        @endforelse
                    </div>
                </x-card>
            </div>

            <div class="col-xl-8">
                <x-card title="Jadwal Hari Ini" icon="bi-calendar-week" subtitle="{{ $date->translatedFormat('l, d F Y') }}">
                    @if (! $isProfileComplete)
                        <x-empty-state icon="bi-lock" title="Profil belum lengkap"
                                      text="Lengkapi data profil terlebih dahulu untuk mendaftar berobat." />
                    @elseif ($schedules->isEmpty())
                        <x-empty-state icon="bi-calendar-x" title="Belum ada jadwal tersedia"
                                      text="Tidak ada dokter yang menerima pasien pada hari ini.">
                            <x-slot:action>
                                <a href="{{ route('patient.schedules.index') }}" class="btn btn-sm btn-light border mt-2">
                                    <i class="bi bi-calendar-week me-1"></i>Jadwal Mingguan
                                </a>
                            </x-slot:action>
                        </x-empty-state>
                    @else
                        <form method="POST" action="{{ route('patient.appointments.store') }}" id="visitForm">
                            @csrf
                            <input type="hidden" name="doctor_schedule_id" id="doctor_schedule_id" value="{{ old('doctor_schedule_id') }}">
                            <input type="hidden" name="appointment_date" value="{{ $date->toDateString() }}">

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
                                                {{ $schedule->timeRange() }}
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

                            <button type="submit" class="btn btn-primary"
                                    @disabled($existingQueue !== null || $todayAppointment !== null || ! old('doctor_schedule_id'))>
                                <i class="bi bi-ticket-perforated me-1"></i>Ambil Nomor Antrean
                            </button>
                            <p class="fs-7 text-muted-2 mb-0 mt-2">
                                <i class="bi bi-info-circle me-1"></i>Nomor antrean diterbitkan langsung untuk kunjungan hari ini.
                            </p>
                        </form>
                    @endif
                </x-card>
            </div>
        </div>
    @else
        {{-- Tanggal ke depan: pilih slot jam sebagai janji temu. --}}
        @if ($schedules->isEmpty())
            <x-card>
                <x-empty-state icon="bi-calendar-x" title="Tidak ada jadwal dokter pada tanggal ini"
                              text="Pilih tanggal lain atau lihat jadwal mingguan dokter.">
                    <x-slot:action>
                        <a href="{{ route('patient.schedules.index') }}" class="btn btn-sm btn-light border mt-2">
                            <i class="bi bi-calendar-week me-1"></i>Jadwal Mingguan
                        </a>
                    </x-slot:action>
                </x-empty-state>
            </x-card>
        @else
            <form method="POST" action="{{ route('patient.appointments.store') }}" id="visitForm">
                @csrf
                <input type="hidden" name="doctor_schedule_id" id="doctor_schedule_id" value="{{ old('doctor_schedule_id') }}">
                <input type="hidden" name="appointment_date" value="{{ $date->toDateString() }}">
                <input type="hidden" name="appointment_time" id="appointment_time" value="{{ old('appointment_time') }}">

                <div class="row g-3">
                    <div class="col-xl-8 order-xl-1 order-2">
                        <div class="vstack gap-3">
                            @foreach ($schedules as $doctorSchedules)
                                @php $doctor = $doctorSchedules->first()?->doctor; @endphp
                                <x-card :title="$doctor?->name ?? 'Dokter'" icon="bi-heart-pulse"
                                        :subtitle="$doctor?->specialization"
                                        :body-class="'pt-2'">
                                    @if ($doctor?->bio)
                                        <p class="fs-7 text-muted-2 mb-2">{{ $doctor->bio }}</p>
                                    @endif

                                    <div class="vstack gap-3">
                                        @foreach ($doctorSchedules as $schedule)
                                            <div class="border rounded-3 p-2">
                                                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
                                                    <div class="small">
                                                        <span class="fw-semibold">{{ $schedule->timeRange() }}</span>
                                                        @if ($schedule->room)
                                                            <span class="text-muted-2">&middot; {{ $schedule->room }}</span>
                                                        @endif
                                                    </div>
                                                    @if ($schedule->on_leave ?? false)
                                                        <x-badge text="Dokter cuti" icon="bi-calendar-x" color="secondary" />
                                                    @elseif ($schedule->remaining_quota > 0)
                                                        <x-badge :text="$schedule->remaining_quota.' slot tersisa'" icon="bi-check-circle-fill" color="success" />
                                                    @else
                                                        <x-badge text="Kuota penuh" icon="bi-x-circle-fill" color="danger" />
                                                    @endif
                                                </div>

                                                @if (empty($schedule->slots))
                                                    <p class="fs-7 text-muted-2 mb-0">Tidak ada slot pada sesi ini.</p>
                                                @else
                                                    <div class="d-flex flex-wrap gap-1">
                                                        @foreach ($schedule->slots as $slot)
                                                            @if ($slot['available'])
                                                                <button type="button"
                                                                        class="btn btn-sm btn-light border js-slot"
                                                                        data-schedule-id="{{ $schedule->id }}"
                                                                        data-time="{{ $slot['time'] }}"
                                                                        title="Pilih {{ $slot['time'] }}">
                                                                    {{ $slot['time'] }}
                                                                </button>
                                                            @else
                                                                <button type="button" class="btn btn-sm btn-light border text-muted-2" disabled
                                                                        title="{{ $slot['reason'] }}">
                                                                    {{ $slot['time'] }}
                                                                </button>
                                                            @endif
                                                        @endforeach
                                                    </div>
                                                    <div class="fs-7 text-muted-2 mt-1">
                                                        <i class="bi bi-clock me-1"></i>Slot setiap {{ $slotMinutes }} menit.
                                                    </div>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                </x-card>
                            @endforeach
                        </div>
                    </div>

                    <div class="col-xl-4 order-xl-2 order-1">
                        <div style="position: sticky; top: 1rem;">
                            <x-card title="Ringkasan Janji Temu" icon="bi-clipboard-check">
                                <dl class="row small mb-3">
                                    <dt class="col-5 text-muted-2 fw-normal">Tanggal</dt>
                                    <dd class="col-7 fw-semibold mb-2">{{ $date->translatedFormat('l, d F Y') }}</dd>
                                    <dt class="col-5 text-muted-2 fw-normal">Dokter</dt>
                                    <dd class="col-7 mb-2" id="summaryDoctor">Belum dipilih</dd>
                                    <dt class="col-5 text-muted-2 fw-normal">Jam</dt>
                                    <dd class="col-7 mb-2" id="summaryTime">Belum dipilih</dd>
                                </dl>

                                <div class="mb-3">
                                    <label class="form-label" for="complaint_note">Keluhan Singkat</label>
                                    <textarea name="complaint_note" id="complaint_note" rows="3" maxlength="500"
                                              class="form-control @error('complaint_note') is-invalid @enderror"
                                              placeholder="Contoh: Nyeri dada sejak kemarin.">{{ old('complaint_note') }}</textarea>
                                    <div class="form-text">Opsional, membantu dokter bersiap.</div>
                                    @error('complaint_note')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>

                                <button type="submit" class="btn btn-primary w-100" id="submitAppointment"
                                        @disabled(! old('doctor_schedule_id'))>
                                    <i class="bi bi-calendar-check me-1"></i>Konfirmasi Janji Temu
                                </button>
                                <p class="fs-7 text-muted-2 mb-0 mt-2">
                                    <i class="bi bi-info-circle me-1"></i>Check-in di resepsionis pada hari kunjungan.
                                </p>
                            </x-card>
                        </div>
                    </div>
                </div>
            </form>
        @endif
    @endif
@endsection

@push('scripts')
    <script>
        (function () {
            const form = document.getElementById('visitForm');
            if (! form) return;

            const scheduleInput = document.getElementById('doctor_schedule_id');
            const timeInput = document.getElementById('appointment_time');
            const summaryDoctor = document.getElementById('summaryDoctor');
            const summaryTime = document.getElementById('summaryTime');
            const submit = document.getElementById('submitAppointment');

            const options = Array.from(document.querySelectorAll('.js-schedule-option'));
            const doctorFilters = Array.from(document.querySelectorAll('.js-doctor-filter'));
            const slots = Array.from(document.querySelectorAll('.js-slot'));

            function selectSchedule(id) {
                if (! scheduleInput) return;

                scheduleInput.value = id;
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
                    if (submit) submit.disabled = false;
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

            slots.forEach(function (button) {
                button.addEventListener('click', function () {
                    slots.forEach(function (item) {
                        item.classList.remove('btn-primary', 'text-white');
                        item.classList.add('btn-light');
                    });

                    button.classList.remove('btn-light');
                    button.classList.add('btn-primary', 'text-white');

                    selectSchedule(button.dataset.scheduleId);
                    if (timeInput) timeInput.value = button.dataset.time;

                    const card = button.closest('.jhs-card');
                    const doctorName = card ? card.querySelector('.jhs-card-title') : null;
                    if (summaryDoctor) summaryDoctor.textContent = doctorName ? doctorName.textContent.trim() : 'Dokter';
                    if (summaryTime) summaryTime.textContent = button.dataset.time + ' WIB';
                    if (submit) submit.disabled = false;
                });
            });

            @if ($selectedDoctor)
                const initial = document.querySelector('.js-doctor-filter[data-doctor-id="{{ (int) $selectedDoctor }}"]');
                if (initial) initial.click();
            @endif
        })();
    </script>
@endpush

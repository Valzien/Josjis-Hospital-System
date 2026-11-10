@extends('layouts.app')

@section('title', 'Buat Janji Temu')

@section('content')
    <x-page-header title="Buat Janji Temu" description="Pilih dokter, tanggal, dan slot jam yang tersedia." icon="bi-calendar-plus">
        <x-slot:actions>
            <a href="{{ route('patient.appointments.index') }}" class="btn btn-light border btn-sm">
                <i class="bi bi-calendar-check me-1"></i>Janji Temu Saya
            </a>
        </x-slot:actions>
    </x-page-header>

    @if (! $isProfileComplete)
        <div class="alert alert-warning">
            <i class="bi bi-exclamation-triangle me-1"></i>
            Lengkapi data <strong>{{ implode(', ', $missingFields) }}</strong> terlebih dahulu sebelum membuat janji temu.
            <a href="{{ route('patient.profile.edit') }}" class="alert-link ms-1">Lengkapi profil sekarang</a>
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger">
            <div class="fw-semibold mb-1"><i class="bi bi-exclamation-triangle me-1"></i>Janji temu gagal dibuat:</div>
            <ul class="mb-0 ps-3 small">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
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
                &middot; booking maksimal {{ (int) $latestDate->diffInDays(now()->startOfDay()) }} hari ke depan
            </span>
        </form>
    </x-card>

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
        <form method="POST" action="{{ route('patient.appointments.store') }}" id="appointmentForm">
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
                                                @if ($schedule->remaining_quota > 0)
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
@endsection

@push('scripts')
    <script>
        (function () {
            const form = document.getElementById('appointmentForm');
            if (! form) return;

            const scheduleInput = document.getElementById('doctor_schedule_id');
            const timeInput = document.getElementById('appointment_time');
            const summaryDoctor = document.getElementById('summaryDoctor');
            const summaryTime = document.getElementById('summaryTime');
            const submit = document.getElementById('submitAppointment');
            const slots = Array.from(document.querySelectorAll('.js-slot'));

            slots.forEach(function (button) {
                button.addEventListener('click', function () {
                    slots.forEach(function (item) {
                        item.classList.remove('btn-primary', 'text-white');
                        item.classList.add('btn-light');
                    });

                    button.classList.remove('btn-light');
                    button.classList.add('btn-primary', 'text-white');

                    scheduleInput.value = button.dataset.scheduleId;
                    timeInput.value = button.dataset.time;

                    const card = button.closest('.jhs-card');
                    const doctorName = card ? card.querySelector('.jhs-card-title') : null;
                    if (summaryDoctor) summaryDoctor.textContent = doctorName ? doctorName.textContent.trim() : 'Dokter';
                    if (summaryTime) summaryTime.textContent = button.dataset.time + ' WIB';
                    if (submit) submit.disabled = false;
                });
            });
        })();
    </script>
@endpush

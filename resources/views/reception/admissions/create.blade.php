@extends('layouts.app')

@section('title', 'Daftarkan Rawat Inap')

@section('content')
    <x-page-header title="Daftarkan Rawat Inap"
                   description="Pilih pasien, dokter penanggung jawab, dan tempat tidur yang tersedia."
                   icon="bi-box-arrow-in-right">
        <x-slot:actions>
            <a href="{{ route('reception.admissions.index') }}" class="btn btn-light border btn-sm">
                <i class="bi bi-arrow-left me-1"></i>Daftar Rawat Inap
            </a>
        </x-slot:actions>
    </x-page-header>

    @if ($errors->any())
        <div class="alert alert-danger">
            <div class="fw-semibold mb-1"><i class="bi bi-exclamation-triangle me-1"></i>Rawat inap tidak dapat dibuat:</div>
            <ul class="mb-0 ps-3 small">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if ($beds->isEmpty())
        <div class="alert alert-warning d-flex flex-wrap align-items-center justify-content-between gap-2">
            <div><i class="bi bi-exclamation-triangle me-1"></i>Tidak ada tempat tidur yang tersedia. Tambahkan tempat tidur terlebih dahulu.</div>
            @if (auth()->user()->isAdmin())
                <a href="{{ route('admin.rooms.index') }}" class="btn btn-sm btn-outline-dark">Kelola Kamar</a>
            @endif
        </div>
    @endif

    <form method="POST" action="{{ route('reception.admissions.store') }}">
        @csrf

        <div class="row g-3">
            <div class="col-xl-7">
                <x-card title="Data Pasien" icon="bi-person">
                    <div class="mb-3">
                        <label class="form-label" for="patient_search">Cari Pasien</label>
                        <input type="search" id="patient_search" class="form-control" placeholder="Ketik nama atau No. RM">
                        <div class="form-text">Gunakan pencarian di atas untuk memfilter daftar pasien.</div>
                    </div>

                    <label class="form-label" for="patient_id">Pasien <span class="text-danger">*</span></label>
                    <select name="patient_id" id="patient_id" required
                            class="form-select @error('patient_id') is-invalid @enderror">
                        <option value="">Pilih pasien terdaftar</option>
                        @foreach ($patients as $patient)
                            <option value="{{ $patient->id }}"
                                    data-search="{{ \Illuminate\Support\Str::lower($patient->name.' '.$patient->medical_record_number.' '.($patient->nik ?? '')) }}"
                                    @selected((string) old('patient_id', $selectedPatientId) === (string) $patient->id)>
                                {{ $patient->medical_record_number }} &mdash; {{ $patient->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('patient_id')<div class="invalid-feedback">{{ $message }}</div>@enderror

                    <div class="form-text mt-1">
                        Pasien baru?
                        <a href="{{ route('reception.registration.create') }}" class="text-decoration-none">Daftarkan terlebih dahulu</a>.
                    </div>
                </x-card>

                <x-card title="Keluhan & Diagnosis Awal" subtitle="Opsional" icon="bi-chat-left-text" class="mt-3">
                    <div class="mb-3">
                        <label class="form-label" for="complaint">Keluhan Utama</label>
                        <textarea name="complaint" id="complaint" rows="3" maxlength="1000"
                                  class="form-control @error('complaint') is-invalid @enderror"
                                  placeholder="Contoh: Demam tinggi dan sesak napas sejak dua hari">{{ old('complaint') }}</textarea>
                        @error('complaint')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="diagnosis">Diagnosis Awal</label>
                        <textarea name="diagnosis" id="diagnosis" rows="2" maxlength="1000"
                                  class="form-control @error('diagnosis') is-invalid @enderror"
                                  placeholder="Contoh: Observasi demam tifoid">{{ old('diagnosis') }}</textarea>
                        @error('diagnosis')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div>
                        <label class="form-label" for="notes">Catatan</label>
                        <textarea name="notes" id="notes" rows="2" maxlength="1000"
                                  class="form-control @error('notes') is-invalid @enderror"
                                  placeholder="Opsional">{{ old('notes') }}</textarea>
                        @error('notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </x-card>
            </div>

            <div class="col-xl-5">
                <x-card title="Dokter & Tempat Tidur" icon="bi-hospital">
                    <div class="mb-3">
                        <label class="form-label" for="doctor_id">Dokter Penanggung Jawab <span class="text-danger">*</span></label>
                        <select name="doctor_id" id="doctor_id" required
                                class="form-select @error('doctor_id') is-invalid @enderror">
                            <option value="">Pilih dokter</option>
                            @foreach ($doctors as $doctor)
                                <option value="{{ $doctor->id }}" @selected((string) old('doctor_id') === (string) $doctor->id)>
                                    {{ $doctor->name }} &mdash; {{ $doctor->specialization }}
                                </option>
                            @endforeach
                        </select>
                        @error('doctor_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="bed_id">Tempat Tidur <span class="text-danger">*</span></label>
                        <select name="bed_id" id="bed_id" required class="form-select @error('bed_id') is-invalid @enderror">
                            <option value="">Pilih tempat tidur tersedia</option>
                            @foreach ($beds->groupBy(fn ($bed) => $bed->room?->name ?? 'Tanpa Kamar') as $roomName => $roomBeds)
                                <optgroup label="{{ $roomName }}">
                                    @foreach ($roomBeds as $bed)
                                        <option value="{{ $bed->id }}" @selected((string) old('bed_id') === (string) $bed->id)>
                                            {{ $bed->code }} &mdash; {{ $bed->room?->classLabel() }} (Rp {{ number_format((float) $bed->room?->rate_per_day, 0, ',', '.') }}/hari)
                                        </option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </select>
                        @error('bed_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="admitted_at">Waktu Masuk</label>
                        <input type="datetime-local" name="admitted_at" id="admitted_at"
                               value="{{ old('admitted_at', now()->format('Y-m-d\TH:i')) }}"
                               class="form-control @error('admitted_at') is-invalid @enderror">
                        <div class="form-text">Kosongkan untuk memakai waktu sekarang.</div>
                        @error('admitted_at')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="alert alert-light border small mb-0">
                        <i class="bi bi-info-circle me-1"></i>
                        Biaya kamar dihitung otomatis saat pasien pulang. Tindakan dan biaya lain dicatat oleh dokter.
                    </div>
                </x-card>

                <div class="d-grid gap-2 mt-3">
                    <button type="submit" class="btn btn-primary" @disabled($beds->isEmpty())>
                        <i class="bi bi-check-lg me-1"></i>Daftarkan Rawat Inap
                    </button>
                    <a href="{{ route('reception.admissions.index') }}" class="btn btn-light border">Batal</a>
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

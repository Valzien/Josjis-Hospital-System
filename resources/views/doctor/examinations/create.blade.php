@extends('layouts.app')

@section('title', 'Pemeriksaan Pasien')

@section('content')
    <x-page-header title="Pemeriksaan {{ $queue->queue_number }}"
                   description="{{ $patient->name }} &middot; {{ $patient->medical_record_number }}"
                   icon="bi-clipboard2-pulse">
        <x-slot:actions>
            <a href="{{ route('doctor.queues.show', $queue) }}" class="btn btn-light border btn-sm">
                <i class="bi bi-arrow-left me-1"></i>Detail Antrean
            </a>
        </x-slot:actions>
    </x-page-header>

    @if ($errors->any())
        <div class="alert alert-danger">
            <div class="fw-semibold mb-1"><i class="bi bi-exclamation-triangle me-1"></i>Periksa kembali isian berikut:</div>
            <ul class="mb-0 ps-3 small">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('doctor.examinations.store', $queue) }}">
        @csrf

        <div class="row g-3">
            <div class="col-xl-8">
                <x-card title="Data Pasien" icon="bi-person">
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                        <div class="d-flex align-items-center gap-3">
                            <x-avatar :name="$patient->name" />
                            <div class="min-w-0">
                                <div class="fw-bold">{{ $patient->name }}</div>
                                <div class="fs-7 text-muted-2">
                                    {{ $patient->genderLabel() }}
                                    @if ($patient->age()) &middot; {{ $patient->age() }} tahun @endif
                                    @if ($patient->blood_type) &middot; {{ $patient->blood_type->label() }} @endif
                                </div>
                            </div>
                        </div>

                        <div class="row g-2 flex-grow-1" style="max-width:520px">
                            <div class="col-6 col-md-3">
                                <label class="form-label fs-7 mb-1" for="temperature">Suhu (&#176;C)</label>
                                <input type="number" step="0.1" name="temperature" id="temperature"
                                       value="{{ old('temperature') }}" min="30" max="45"
                                       class="form-control form-control-sm @error('temperature') is-invalid @enderror">
                                @error('temperature')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-6 col-md-3">
                                <label class="form-label fs-7 mb-1" for="blood_pressure">Tekanan (mmHg)</label>
                                <input type="number" name="blood_pressure" id="blood_pressure"
                                       value="{{ old('blood_pressure') }}" min="50" max="250"
                                       class="form-control form-control-sm @error('blood_pressure') is-invalid @enderror">
                                @error('blood_pressure')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-6 col-md-3">
                                <label class="form-label fs-7 mb-1" for="weight">Berat (kg)</label>
                                <input type="number" name="weight" id="weight"
                                       value="{{ old('weight') }}" min="1" max="400"
                                       class="form-control form-control-sm @error('weight') is-invalid @enderror">
                                @error('weight')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-6 col-md-3">
                                <label class="form-label fs-7 mb-1" for="height">Tinggi (cm)</label>
                                <input type="number" step="0.1" name="height" id="height"
                                       value="{{ old('height') }}" min="30" max="250"
                                       class="form-control form-control-sm @error('height') is-invalid @enderror">
                                @error('height')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </div>

                    @if ($patient->allergies)
                        <div class="alert alert-danger py-2 small mb-0 mt-3">
                            <i class="bi bi-exclamation-triangle me-1"></i>
                            <strong>Alergi:</strong> {{ $patient->allergies }}
                        </div>
                    @endif
                </x-card>

                <x-card title="Hasil Pemeriksaan" subtitle="Nomor rekam: {{ $recordNumberPreview }}" icon="bi-file-earmark-medical" class="mt-3">
                    <div class="mb-3">
                        <label class="form-label" for="complaint">Keluhan <span class="text-danger">*</span></label>
                        <textarea name="complaint" id="complaint" rows="2" required maxlength="1000"
                                  class="form-control @error('complaint') is-invalid @enderror"
                                  placeholder="Keluhan utama pasien">{{ old('complaint', $queue->complaint_note) }}</textarea>
                        @error('complaint')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="examination_result">Hasil Pemeriksaan <span class="text-danger">*</span></label>
                        <textarea name="examination_result" id="examination_result" rows="4" required maxlength="2000"
                                  class="form-control @error('examination_result') is-invalid @enderror"
                                  placeholder="Temuan fisik dan hasil pemeriksaan">{{ old('examination_result') }}</textarea>
                        @error('examination_result')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" for="diagnosis">Diagnosis <span class="text-danger">*</span></label>
                            <textarea name="diagnosis" id="diagnosis" rows="2" required maxlength="1000"
                                      class="form-control @error('diagnosis') is-invalid @enderror"
                                      placeholder="Contoh: Infeksi saluran pernapasan atas">{{ old('diagnosis') }}</textarea>
                            @error('diagnosis')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="treatment">Tindakan</label>
                            <textarea name="treatment" id="treatment" rows="2" maxlength="1000"
                                      class="form-control @error('treatment') is-invalid @enderror">{{ old('treatment') }}</textarea>
                            @error('treatment')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="notes">Catatan Dokter</label>
                            <textarea name="notes" id="notes" rows="2" maxlength="1000"
                                      class="form-control @error('notes') is-invalid @enderror"
                                      placeholder="Catatan tambahan untuk pasien atau farmasi">{{ old('notes') }}</textarea>
                            @error('notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="examined_at">Waktu Pemeriksaan</label>
                            <input type="datetime-local" name="examined_at" id="examined_at"
                                   value="{{ old('examined_at', now()->format('Y-m-d\TH:i')) }}"
                                   class="form-control @error('examined_at') is-invalid @enderror">
                            @error('examined_at')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </x-card>

                <x-card title="Resep" icon="bi-capsule" class="mt-3">
                    <x-slot:actions>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" role="switch" name="with_prescription"
                                   value="1" id="with_prescription" @checked(old('with_prescription'))>
                            <label class="form-check-label small" for="with_prescription">Buat resep</label>
                        </div>
                    </x-slot:actions>

                    <div id="prescriptionPanel" class="{{ old('with_prescription') ? '' : 'd-none' }}">
                        <div class="table-responsive">
                            <table class="table table-sm align-middle jhs-table-compact" id="itemTable">
                                <thead>
                                    <tr>
                                        <th>Obat</th>
                                        <th style="width:110px">Jumlah</th>
                                        <th style="width:180px">Dosis</th>
                                        <th>Aturan Pakai</th>
                                        <th style="width:44px"></th>
                                    </tr>
                                </thead>
                                <tbody id="itemRows"></tbody>
                            </table>
                        </div>

                        <button type="button" class="btn btn-sm btn-light border" id="addItem">
                            <i class="bi bi-plus-lg me-1"></i>Tambah Obat
                        </button>

                        <div class="mt-3">
                            <label class="form-label" for="prescription_notes">Catatan Resep</label>
                            <textarea name="prescription_notes" id="prescription_notes" rows="2" maxlength="1000"
                                      class="form-control">{{ old('prescription_notes') }}</textarea>
                        </div>

                        <template id="itemTemplate">
                            <tr>
                                <td>
                                    <select class="form-select form-select-sm" name="items[__INDEX__][medicine_id]">
                                        <option value="">Pilih obat</option>
                                        @foreach ($medicines as $medicine)
                                            <option value="{{ $medicine->id }}" data-price="{{ $medicine->price }}" data-stock="{{ $medicine->stock }}">
                                                {{ $medicine->name }} ({{ $medicine->stock }} {{ $medicine->unit }})
                                            </option>
                                        @endforeach
                                    </select>
                                </td>
                                <td>
                                    <input type="number" class="form-control form-control-sm" name="items[__INDEX__][quantity]"
                                           value="1" min="1" max="1000">
                                </td>
                                <td>
                                    <input type="text" class="form-control form-control-sm" name="items[__INDEX__][dosage]"
                                           placeholder="Contoh: 3x sehari" maxlength="60">
                                </td>
                                <td>
                                    <input type="text" class="form-control form-control-sm" name="items[__INDEX__][instructions]"
                                           placeholder="Sesudah makan" maxlength="500">
                                </td>
                                <td class="text-end">
                                    <button type="button" class="btn btn-sm btn-link text-danger" data-remove-row aria-label="Hapus baris">
                                        <i class="bi bi-trash3"></i>
                                    </button>
                                </td>
                            </tr>
                        </template>
                    </div>
                </x-card>
            </div>

            <div class="col-xl-4">
                <x-card title="Pemeriksaan Terakhir" subtitle="3 rekam medis terakhir" icon="bi-clock-history">
                    <div class="vstack gap-2">
                        @forelse ($recentRecords as $record)
                            <div class="border rounded-3 p-2">
                                <div class="d-flex justify-content-between gap-2">
                                    <span class="small fw-semibold">{{ $record->diagnosis ?? '-' }}</span>
                                    <span class="fs-7 text-muted-2 text-nowrap">{{ $record->examined_at?->translatedFormat('d M Y') }}</span>
                                </div>
                                <div class="fs-7 text-muted-2">{{ $record->doctor?->name }}</div>
                            </div>
                        @empty
                            <x-empty-state icon="bi-clock-history" title="Belum ada riwayat" />
                        @endforelse
                    </div>
                </x-card>

                <x-card title="Keluhan dari Resepsionis" icon="bi-chat-left-text" class="mt-3">
                    <p class="small mb-0">{{ $queue->complaint_note ?? 'Tidak ada keluhan yang dicatat.' }}</p>
                </x-card>

                <div class="d-grid gap-2 mt-3">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-lg me-1"></i>Simpan &amp; Selesaikan
                    </button>
                    <a href="{{ route('doctor.queues.show', $queue) }}" class="btn btn-light border">Batal</a>
                </div>

                <div class="alert alert-light border small mt-3 mb-0">
                    <i class="bi bi-info-circle me-1"></i>
                    Menyimpan rekam medis akan menandai antrean sebagai selesai. Resep yang dibuat langsung dikirim ke apoteker.
                </div>
            </div>
        </div>
    </form>
@endsection

@push('scripts')
    <script>
        (function () {
            const toggle = document.getElementById('with_prescription');
            const panel = document.getElementById('prescriptionPanel');
            const rows = document.getElementById('itemRows');
            const template = document.getElementById('itemTemplate');
            if (!toggle || !panel || !rows || !template) return;

            let index = 0;

            function syncPanel() {
                panel.classList.toggle('d-none', !toggle.checked);
                if (toggle.checked && rows.children.length === 0) addRow();
            }

            function addRow() {
                const html = template.innerHTML.replaceAll('__INDEX__', String(index++));
                rows.insertAdjacentHTML('beforeend', html);
            }

            toggle.addEventListener('change', syncPanel);

            document.getElementById('addItem').addEventListener('click', addRow);

            rows.addEventListener('click', function (event) {
                const button = event.target.closest('[data-remove-row]');
                if (!button) return;
                button.closest('tr').remove();
            });

            syncPanel();
        })();
    </script>
@endpush
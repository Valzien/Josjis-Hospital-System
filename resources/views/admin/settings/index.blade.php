@extends('layouts.app')

@section('title', 'Pengaturan Sistem')

@section('content')
    <x-page-header title="Pengaturan" description="Identitas rumah sakit, aturan antrean, dan fitur yang tersedia untuk pasien." icon="bi-gear" />

    <form method="POST" action="{{ route('admin.settings.update') }}">
        @csrf @method('PUT')

        <div class="row g-3">
            <div class="col-xl-8">
                @foreach ($groups as $group => $fields)
                    <x-card class="mb-3" :title="$groupLabels[$group]['title'] ?? ucfirst($group)"
                            :subtitle="$groupLabels[$group]['desc'] ?? null" :icon="$groupLabels[$group]['icon'] ?? 'bi-sliders'">
                        @if ($group === 'antrean')
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label" for="queue_prefix">Prefix Nomor Antrean</label>
                                    <input type="text" name="queue_prefix" id="queue_prefix" maxlength="3"
                                           value="{{ old('queue_prefix', $fields[0]['value'] ?? 'A') }}"
                                           class="form-control @error('queue_prefix') is-invalid @enderror">
                                    <div class="form-text">Contoh: A-001</div>
                                    @error('queue_prefix')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label" for="estimated_minutes">Estimasi Durasi (menit)</label>
                                    <input type="number" name="estimated_minutes" id="estimated_minutes" min="1" max="120"
                                           value="{{ old('estimated_minutes', $fields[1]['value'] ?? 10) }}"
                                           class="form-control @error('estimated_minutes') is-invalid @enderror">
                                    @error('estimated_minutes')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>
                        @endif

                        <div class="row g-3">
                            @foreach ($fields as $field)
                                @if (in_array($field['key'], ['queue_prefix', 'estimated_minutes'], true))
                                    @continue
                                @endif

                                <div class="{{ $field['type'] === 'text' ? 'col-12' : 'col-md-6' }}">
                                    @if ($field['type'] === 'boolean')
                                        <div class="form-check form-switch mt-md-4">
                                            <input type="hidden" name="settings[{{ $field['key'] }}]" value="0">
                                            <input class="form-check-input" type="checkbox" role="switch"
                                                   id="{{ $field['key'] }}" name="settings[{{ $field['key'] }}]" value="1"
                                                   @checked(old('settings.'.$field['key'], $field['value']) === '1' || $field['value'] === true)>
                                            <label class="form-check-label" for="{{ $field['key'] }}">{{ $field['label'] }}</label>
                                        </div>
                                    @else
                                        <label class="form-label" for="{{ $field['key'] }}">{{ $field['label'] }}</label>
                                        @if ($field['type'] === 'text')
                                            <textarea name="settings[{{ $field['key'] }}]" id="{{ $field['key'] }}" rows="2"
                                                      class="form-control">{{ old('settings.'.$field['key'], $field['value']) }}</textarea>
                                        @else
                                            <input type="{{ $field['type'] === 'number' ? 'number' : 'text' }}"
                                                   name="settings[{{ $field['key'] }}]" id="{{ $field['key'] }}"
                                                   value="{{ old('settings.'.$field['key'], $field['value']) }}"
                                                   class="form-control">
                                        @endif
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </x-card>
                @endforeach

                <div class="d-flex gap-2 mb-4">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-lg me-1"></i>Simpan Pengaturan
                    </button>
                    <a href="{{ route('admin.settings.index') }}" class="btn btn-light border">Batal</a>
                </div>
            </div>

            <div class="col-xl-4">
                <x-card title="Status Penyimpanan" icon="bi-database-check">
                    <dl class="row small mb-0">
                        <dt class="col-7 text-muted-2 fw-normal">Jumlah setting tersimpan</dt>
                        <dd class="col-5 text-end">{{ $rawCount }}</dd>
                        <dt class="col-7 text-muted-2 fw-normal">Cache</dt>
                        <dd class="col-5 text-end">Otomatis</dd>
                        <dt class="col-7 text-muted-2 fw-normal">Perubahan tercatat di</dt>
                        <dd class="col-5 text-end">Audit Log</dd>
                    </dl>
                </x-card>

                <x-card title="Peringatan" icon="bi-exclamation-triangle" class="mt-3">
                    <p class="small text-muted-2 mb-0">
                        Pengaturan antrean yang diubah berlaku untuk nomor antrean baru.
                        Antrean yang sudah dibuat tidak berubah retroactive.
                    </p>
                </x-card>
            </div>
        </div>
    </form>
@endsection
@extends('layouts.app')

@section('title', 'Laporan')

@section('content')
    <x-page-header title="Laporan" description="Ringkasan pelayanan, penjualan, dan aktivitas farmasi berdasarkan periode." icon="bi-graph-up-arrow" />

    <x-card class="mb-3">
        <form method="GET" action="{{ route('admin.reports.index') }}" class="row g-2 align-items-end">
            <div class="col-lg-2">
                <label class="form-label small mb-1" for="from">Dari Tanggal</label>
                <input type="date" name="from" id="from" value="{{ $filters['from'] }}" class="form-control form-control-sm">
            </div>
            <div class="col-lg-2">
                <label class="form-label small mb-1" for="to">Sampai Tanggal</label>
                <input type="date" name="to" id="to" value="{{ $filters['to'] }}" class="form-control form-control-sm">
            </div>
            <div class="col-lg-3">
                <label class="form-label small mb-1" for="doctor_id">Dokter</label>
                <select name="doctor_id" id="doctor_id" class="form-select form-select-sm">
                    <option value="">Semua Dokter</option>
                    @foreach ($doctors as $doctor)
                        <option value="{{ $doctor->id }}" @selected((string) $filters['doctor_id'] === (string) $doctor->id)>{{ $doctor->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-lg-2">
                <label class="form-label small mb-1">&nbsp;</label>
                <button class="btn btn-primary btn-sm w-100" type="submit"><i class="bi bi-funnel me-1"></i>Terapkan</button>
            </div>
            <div class="col-lg-3 text-lg-end">
                <a href="{{ route('admin.reports.index', ['from' => now()->subDays(6)->toDateString(), 'to' => now()->toDateString()]) }}"
                   class="btn btn-light border btn-sm">7 Hari</a>
                <a href="{{ route('admin.reports.index', ['from' => now()->subDays(29)->toDateString(), 'to' => now()->toDateString()]) }}"
                   class="btn btn-light border btn-sm">30 Hari</a>
                <a href="{{ route('admin.reports.index', ['from' => now()->startOfMonth()->toDateString(), 'to' => now()->toDateString()]) }}"
                   class="btn btn-light border btn-sm">Bulan Ini</a>
            </div>
        </form>
    </x-card>

    <div class="row g-3 mb-3">
        <div class="col-6 col-xl-2">
            <x-stat-card label="Pasien Baru" :value="$summary['patients']" icon="bi-person-plus" color="primary"
                         :href="route('admin.reports.show', ['report' => 'patients'] + request()->only('from', 'to', 'doctor_id'))" />
        </div>
        <div class="col-6 col-xl-2">
            <x-stat-card label="Antrean" :value="$summary['queues']" icon="bi-list-ol" color="info"
                         :href="route('admin.reports.show', ['report' => 'queues'] + request()->only('from', 'to', 'doctor_id'))" />
        </div>
        <div class="col-6 col-xl-2">
            <x-stat-card label="Pemeriksaan" :value="$summary['examinations']" icon="bi-clipboard2-pulse" color="success"
                         :href="route('admin.reports.show', ['report' => 'examinations'] + request()->only('from', 'to', 'doctor_id'))" />
        </div>
        <div class="col-6 col-xl-2">
            <x-stat-card label="Resep" :value="$summary['prescriptions']" icon="bi-file-earmark-medical" color="warning"
                         :href="route('admin.reports.show', ['report' => 'prescriptions'] + request()->only('from', 'to', 'doctor_id'))" />
        </div>
        <div class="col-6 col-xl-2">
            <x-stat-card label="Obat Masuk" :value="$summary['medicines_in']" icon="bi-box-arrow-in-down" color="teal"
                         :href="route('admin.reports.show', ['report' => 'transactions'] + request()->only('from', 'to', 'doctor_id'))" />
        </div>
        <div class="col-6 col-xl-2">
            <x-stat-card label="Obat Keluar" :value="$summary['medicines_out']" icon="bi-box-arrow-up" color="danger"
                         :href="route('admin.reports.show', ['report' => 'transactions'] + request()->only('from', 'to', 'doctor_id'))" />
        </div>
    </div>

    <div class="row g-3">
        <div class="col-xl-7">
            <x-card title="Tren Antrean" subtitle="{{ now()->parse($filters['from'])->translatedFormat('d M Y') }} &mdash; {{ now()->parse($filters['to'])->translatedFormat('d M Y') }}" icon="bi-graph-up">
                <div style="min-height:280px">
                    <canvas id="chartQueueTrend"></canvas>
                </div>
            </x-card>
        </div>
        <div class="col-xl-5">
            <x-card title="Pilih Jenis Laporan" icon="bi-file-earmark-bar-graph">
                <div class="row g-2">
                    @foreach ($available as $key => $label)
                        <div class="col-6">
                            <a href="{{ route('admin.reports.show', array_merge(['report' => $key], $filters)) }}"
                               class="d-block p-3 border rounded-3 text-decoration-none h-100">
                                <div class="d-flex align-items-center gap-2 mb-1">
                                    <i class="bi {{ match ($key) {
                                        'patients' => 'bi-person-vcard',
                                        'queues' => 'bi-list-ol',
                                        'examinations' => 'bi-clipboard2-pulse',
                                        'prescriptions' => 'bi-file-earmark-medical',
                                        'medicines' => 'bi-capsule',
                                        default => 'bi-arrow-left-right',
                                    } }} text-primary"></i>
                                    <span class="small fw-semibold text-dark">{{ $label }}</span>
                                </div>
                                <span class="fs-7 text-muted-2">Lihat &amp; ekspor</span>
                            </a>
                        </div>
                    @endforeach
                </div>
            </x-card>
        </div>

        <div class="col-12">
            <x-card title="Tren Pasien Baru" icon="bi-person-lines-fill">
                <div style="min-height:240px">
                    <canvas id="chartPatientTrend"></canvas>
                </div>
            </x-card>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        window.jhsChart('chartQueueTrend', {
            labels: @json($queueTrend['labels']),
            datasets: [{
                label: 'Antrean',
                data: @json($queueTrend['values']),
                borderColor: '#0d9488',
                backgroundColor: 'rgba(13,148,136,.12)',
                fill: true,
                tension: .35,
                borderWidth: 2,
                pointRadius: 2,
            }]
        });

        window.jhsChart('chartPatientTrend', {
            labels: @json($patientTrend['labels']),
            datasets: [{
                label: 'Pasien Baru',
                data: @json($patientTrend['values']),
                borderColor: '#0d6efd',
                backgroundColor: 'rgba(13,110,253,.12)',
                fill: true,
                tension: .35,
                borderWidth: 2,
                pointRadius: 2,
            }]
        });
    </script>
@endpush
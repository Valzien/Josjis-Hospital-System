@extends('layouts.app')

@section('title', $title)

@section('content')
    <x-page-header :title="$title"
                   :description="now()->parse($filters['from'])->translatedFormat('d M Y').' &mdash; '.now()->parse($filters['to'])->translatedFormat('d M Y')"
                   icon="bi-file-earmark-bar-graph">
        <x-slot:actions>
            <a href="{{ route('admin.reports.index') }}" class="btn btn-light border btn-sm">
                <i class="bi bi-arrow-left me-1"></i>Semua Laporan
            </a>
            @foreach (['pdf' => ['bi-file-earmark-pdf', 'PDF'], 'csv' => ['bi-filetype-csv', 'CSV'], 'xlsx' => ['bi-file-earmark-excel', 'Excel']] as $format => [$icon, $label])
                <a href="{{ route('admin.reports.export', array_merge(['report' => $report, 'format' => $format], $filters)) }}"
                   class="btn btn-outline-secondary btn-sm">
                    <i class="bi {{ $icon }} me-1"></i>{{ $label }}
                </a>
            @endforeach
            <button type="button" class="btn btn-primary btn-sm" data-print>
                <i class="bi bi-printer me-1"></i>Cetak
            </button>
        </x-slot:actions>
    </x-page-header>

    <x-card class="mb-3">
        <form method="GET" action="{{ route('admin.reports.show', $report) }}" class="row g-2 align-items-end">
            <input type="hidden" name="report" value="{{ $report }}">
            <div class="col-lg-2">
                <label class="form-label small mb-1" for="from">Dari</label>
                <input type="date" name="from" id="from" value="{{ $filters['from'] }}" class="form-control form-control-sm">
            </div>
            <div class="col-lg-2">
                <label class="form-label small mb-1" for="to">Sampai</label>
                <input type="date" name="to" id="to" value="{{ $filters['to'] }}" class="form-control form-control-sm">
            </div>

            @if (in_array($report, ['queues', 'examinations', 'prescriptions'], true))
                <div class="col-lg-3">
                    <label class="form-label small mb-1" for="doctor_id">Dokter</label>
                    <select name="doctor_id" id="doctor_id" class="form-select form-select-sm">
                        <option value="">Semua Dokter</option>
                        @foreach ($doctors as $doctor)
                            <option value="{{ $doctor->id }}" @selected((string) $filters['doctor_id'] === (string) $doctor->id)>{{ $doctor->name }}</option>
                        @endforeach
                    </select>
                </div>
            @endif

            @if ($report === 'prescriptions')
                <div class="col-lg-2">
                    <label class="form-label small mb-1" for="status">Status</label>
                    <select name="status" id="status" class="form-select form-select-sm">
                        <option value="">Semua Status</option>
                        @foreach (\App\Enums\PrescriptionStatus::options() as $value => $label)
                            <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            @endif

            @if (in_array($report, ['medicines', 'transactions'], true))
                <div class="col-lg-3">
                    <label class="form-label small mb-1" for="medicine_id">Obat</label>
                    <select name="medicine_id" id="medicine_id" class="form-select form-select-sm">
                        <option value="">Semua Obat</option>
                        @foreach ($medicines as $medicine)
                            <option value="{{ $medicine->id }}" @selected((string) $filters['medicine_id'] === (string) $medicine->id)>{{ $medicine->name }}</option>
                        @endforeach
                    </select>
                </div>
            @endif

            <div class="col-lg-2">
                <label class="form-label small mb-1">&nbsp;</label>
                <button class="btn btn-primary btn-sm w-100" type="submit"><i class="bi bi-funnel me-1"></i>Terapkan</button>
            </div>
        </form>
    </x-card>

    @if (! empty($totals))
        <x-card class="mb-3" title="Ringkasan" icon="bi-clipboard-data">
            <div class="row g-3">
                @foreach ($totals as $label => $value)
                    <div class="col-6 col-md-3">
                        <div class="small text-muted-2">{{ $label }}</div>
                        <div class="fw-bold">{{ is_numeric($value) ? number_format((float) $value, 0, ',', '.') : $value }}</div>
                    </div>
                @endforeach
            </div>
        </x-card>
    @endif

    @if (! empty($chart['labels']))
        <x-card class="mb-3" title="Grafik" icon="bi-bar-chart-line">
            <div style="min-height:260px">
                <canvas id="chartReport"></canvas>
            </div>
        </x-card>
    @endif

    <x-card title="Data {{ $title }}" subtitle="{{ count($rows) }} baris data" icon="bi-table">
        <div class="table-responsive">
            <table class="table table-hover align-middle jhs-table-compact">
                <thead>
                    <tr>
                        <th class="text-muted-2">#</th>
                        @foreach ($columns as $column)
                            <th>{{ $column }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rows as $index => $row)
                        <tr>
                            <td class="text-muted-2 small">{{ $index + 1 }}</td>
                            @foreach ($row as $cell)
                                <td class="small">{{ $cell }}</td>
                            @endforeach
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ count($columns) + 1 }}">
                                <x-empty-state icon="bi-inbox" title="Tidak ada data"
                                               text="Tidak ada data pada periode dan filter yang dipilih." />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-card>
@endsection

@push('scripts')
    @if (! empty($chart['labels']))
        <script>
            window.jhsChart('chartReport', {
                labels: @json($chart['labels']),
                datasets: [{
                    label: @json($title),
                    data: @json($chart['values']),
                    borderColor: '#0d6efd',
                    backgroundColor: 'rgba(13,110,253,.12)',
                    fill: true,
                    tension: .35,
                    borderWidth: 2,
                    pointRadius: 2,
                }]
            });
        </script>
    @endif
@endpush
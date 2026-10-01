@extends('layouts.app')

@section('title', 'Dashboard Admin')

@section('content')
    <x-page-header title="Dashboard" :description="'Selamat datang, '.auth()->user()->displayName().'.'" icon="bi-speedometer2">
        <x-slot:actions>
            <a href="{{ route('admin.reports.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-graph-up-arrow me-1"></i>Laporan
            </a>
            <a href="{{ route('admin.users.create') }}" class="btn btn-primary btn-sm">
                <i class="bi bi-person-plus me-1"></i>Tambah Pengguna
            </a>
        </x-slot:actions>
    </x-page-header>

    <div class="row g-3 mb-4">
        <div class="col-6 col-xl-3">
            <x-stat-card label="Pasien Terdaftar" :value="number_format($counts['patients'], 0, ',', '.')"
                         icon="bi-person-vcard" color="primary" meta="Seluruh waktu"
                         :href="route('admin.patients.index')" />
        </div>
        <div class="col-6 col-xl-3">
            <x-stat-card label="Antrean Hari Ini" :value="$counts['queues_today']" icon="bi-list-ol" color="info"
                         :meta="$counts['waiting_today'].' masih menunggu'" />
        </div>
        <div class="col-6 col-xl-3">
            <x-stat-card label="Pemeriksaan Hari Ini" :value="$counts['exams_today']" icon="bi-clipboard2-pulse"
                         color="success" :meta="$counts['prescriptions_today'].' resep dibuat'" />
        </div>
        <div class="col-6 col-xl-3">
            <x-stat-card label="Stok Obat Menipis" :value="$counts['low_stock']" icon="bi-capsule"
                         :color="$counts['low_stock'] > 0 ? 'danger' : 'success'"
                         :meta="'Nilai persediaan Rp '.number_format($inventoryValue, 0, ',', '.')" />
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-xl-8">
            <x-card title="Pasien Baru 12 Bulan Terakhir" subtitle=" Tren pendaftaran pasien" icon="bi-graph-up-arrow">
                <div style="min-height:280px">
                    <canvas id="chartPatients"></canvas>
                </div>
            </x-card>
        </div>

        <div class="col-xl-4">
            <x-card title="Status Antrean Hari Ini" icon="bi-pie-chart">
                <ul class="list-unstyled mb-0">
                    @foreach (\App\Enums\QueueStatus::cases() as $status)
                        @php $total = (int) ($queueStatus[$status->value] ?? 0); @endphp
                        <li class="d-flex justify-content-between align-items-center py-2 border-bottom">
                            <span class="small"><x-status-badge :status="$status" /></span>
                            <span class="badge text-bg-light">{{ $total }}</span>
                        </li>
                    @endforeach
                </ul>
            </x-card>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-xl-6">
            <x-card title="Antrean Hari Ini" icon="bi-list-ol">
                <x-slot:actions>
                    <a href="{{ route('admin.reports.index', ['report' => 'queues']) }}" class="btn btn-sm btn-light border">Laporan</a>
                </x-slot:actions>
                <ul class="list-unstyled mb-0">
                    @forelse ($todayQueues as $queue)
                        <li class="d-flex justify-content-between align-items-center py-2 border-bottom">
                            <div class="d-flex align-items-center gap-2 min-w-0">
                                <span class="badge text-bg-primary">{{ $queue->queue_number }}</span>
                                <div class="min-w-0">
                                    <div class="small fw-semibold text-truncate">{{ $queue->patient->name }}</div>
                                    <div class="fs-7 text-muted-2 text-truncate">{{ $queue->doctor->name }}</div>
                                </div>
                            </div>
                            <x-status-badge :status="$queue->status" />
                        </li>
                    @empty
                        <li><x-empty-state icon="bi-list-ol" title="Belum ada antrean" text="Tidak ada antrean pada hari ini." /></li>
                    @endforelse
                </ul>
            </x-card>
        </div>

        <div class="col-xl-6">
            <x-card title="Aktivitas Terbaru" icon="bi-clock-history">
                <x-slot:actions>
                    <a href="{{ route('admin.audit-logs.index') }}" class="btn btn-sm btn-light border">Audit Log</a>
                </x-slot:actions>
                <ul class="list-unstyled mb-0">
                    @forelse ($recentActivities as $activity)
                        <li class="d-flex gap-3 py-2 border-bottom">
                            <span class="jhs-stat-icon jhs-icon-soft-{{ $activity->moduleBadge() }}" style="width:32px;height:32px;font-size:.85rem;border-radius:10px">
                                <i class="bi {{ $activity->moduleIcon() }}"></i>
                            </span>
                            <div class="flex-grow-1 min-w-0">
                                <div class="small text-truncate">{{ $activity->description }}</div>
                                <div class="fs-7 text-muted-2">{{ $activity->user->name ?? 'Sistem' }}</div>
                            </div>
                            <div class="fs-7 text-muted-2 text-nowrap">{{ $activity->created_at?->diffForHumans() }}</div>
                        </li>
                    @empty
                        <li><x-empty-state icon="bi-clock-history" title="Belum ada aktivitas" /></li>
                    @endforelse
                </ul>
            </x-card>
        </div>

        <div class="col-xl-6">
            <x-card title="Dokter Teramai Dilayani" subtitle="Berdasarkan jumlah pemeriksaan" icon="bi-heart-pulse">
                <ul class="list-unstyled mb-0">
                    @forelse ($topDoctors as $doctor)
                        <li class="d-flex justify-content-between align-items-center py-2 border-bottom">
                            <div class="d-flex align-items-center gap-2">
                                <x-avatar :name="$doctor->name" size="sm" />
                                <div>
                                    <div class="small fw-semibold">{{ $doctor->name }}</div>
                                    <div class="fs-7 text-muted-2">{{ $doctor->specialization }}</div>
                                </div>
                            </div>
                            <span class="badge text-bg-light">{{ $doctor->medical_records_count }} pemeriksaan</span>
                        </li>
                    @empty
                        <li><x-empty-state icon="bi-heart-pulse" title="Belum ada data dokter" /></li>
                    @endforelse
                </ul>
            </x-card>
        </div>

        <div class="col-xl-6">
            <x-card title="Spesialisasi Terlayani" subtitle="30 hari terakhir" icon="bi-clipboard2-data">
                <div style="min-height:260px">
                    <canvas id="chartServices"></canvas>
                </div>
            </x-card>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        window.jhsChart('chartPatients', {
            labels: @json(array_column($monthlyPatients, 'period')),
            datasets: [{
                label: 'Pasien Baru',
                data: @json(array_column($monthlyPatients, 'total')),
                borderColor: '#0d6efd',
                backgroundColor: 'rgba(13,110,253,.12)',
                fill: true,
                tension: .35,
                borderWidth: 2,
                pointRadius: 3,
            }]
        });

        window.jhsChart('chartServices', {
            type: 'doughnut',
            labels: @json($serviceDistribution->pluck('specialization')->all()),
            datasets: [{
                data: @json($serviceDistribution->pluck('total')->all()),
                backgroundColor: ['#0d6efd', '#20c997', '#ffc107', '#dc3545', '#6f42c1', '#0dcaf0'],
            }]
        }, {
            legend: {position: 'bottom'},
            cutout: '62%'
        });
    </script>
@endpush
@extends('layouts.app')

@section('title', 'Dashboard Perawat')

@section('content')
    <x-page-header title="Dashboard Perawat"
                   description="Ringkasan pasien rawat inap dan pencatatan tanda vital untuk {{ now()->translatedFormat('l, d F Y') }}."
                   icon="bi-person-badge">
        <x-slot:actions>
            <a href="{{ route('nurse.admissions.create') }}" class="btn btn-primary btn-sm">
                <i class="bi bi-plus-lg me-1"></i>Daftarkan Rawat Inap
            </a>
            <a href="{{ route('nurse.admissions.index') }}" class="btn btn-light border btn-sm">
                <i class="bi bi-hospital me-1"></i>Pasien Rawat Inap
            </a>
        </x-slot:actions>
    </x-page-header>

    <div class="row g-3 mb-3">
        <div class="col-6 col-xl-3">
            <x-stat-card label="Pasien Dirawat" :value="$summary['active']" icon="bi-person-fill" color="primary"
                         :href="route('nurse.admissions.index', ['status' => 'active'])" />
        </div>
        <div class="col-6 col-xl-3">
            <x-stat-card label="Masuk Hari Ini" :value="$summary['admitted_today']" icon="bi-box-arrow-in-right" color="info"
                         :href="route('nurse.admissions.index', ['date' => now()->toDateString()])" />
        </div>
        <div class="col-6 col-xl-3">
            <x-stat-card label="Visite Hari Ini" :value="$summary['visits_today']" icon="bi-clipboard2-pulse" color="teal" />
        </div>
        <div class="col-6 col-xl-3">
            <x-stat-card label="Tempat Tidur Tersedia" :value="$summary['available_beds']" icon="bi-layout-text-window-reverse" color="success" />
        </div>
    </div>

    <div class="row g-3">
        <div class="col-xl-7">
            <x-card title="Pasien Dirawat Saat Ini" subtitle="10 pasien teratas" icon="bi-hospital">
                <x-slot:actions>
                    <a href="{{ route('nurse.admissions.index') }}" class="btn btn-sm btn-light border">Lihat Semua</a>
                </x-slot:actions>
                <div class="table-responsive">
                    <table class="table table-hover align-middle jhs-table-compact">
                        <thead>
                            <tr>
                                <th>Pasien</th>
                                <th>Kamar / Tempat Tidur</th>
                                <th>Dokter</th>
                                <th>Visite Terakhir</th>
                                <th class="text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($activeAdmissions as $admission)
                                <tr>
                                    <td>
                                        <div class="small fw-semibold">{{ $admission->patient?->name ?? '-' }}</div>
                                        <div class="fs-7 text-muted-2">{{ $admission->code }}</div>
                                    </td>
                                    <td class="small">
                                        <div>{{ $admission->room?->name ?? '-' }}</div>
                                        <div class="fs-7 text-muted-2">{{ $admission->bed?->code ? 'Tempat tidur '.$admission->bed->code : '-' }}</div>
                                    </td>
                                    <td class="small text-truncate">{{ $admission->doctor?->name ?? '-' }}</td>
                                    <td class="small text-nowrap">
                                        @if ($admission->latestVisit)
                                            {{ $admission->latestVisit->visited_at?->translatedFormat('d M Y H:i') }}
                                        @else
                                            <span class="text-muted-2">&mdash;</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <a href="{{ route('nurse.admissions.show', $admission) }}" class="btn btn-sm btn-light border">
                                            <i class="bi bi-eye me-1"></i>Buka
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5">
                                        <x-empty-state icon="bi-hospital" title="Belum ada pasien dirawat"
                                                       text="Pasien rawat inap akan muncul di sini setelah didaftarkan." />
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-card>
        </div>

        <div class="col-xl-5">
            <x-card title="Visite & Tanda Vital Terakhir" subtitle="Dicatat hari ini" icon="bi-clipboard2-pulse">
                <div class="jhs-timeline">
                    @forelse ($recentVisits as $visit)
                        <div class="border-start border-2 ps-3 mb-3">
                            <div class="d-flex flex-wrap justify-content-between gap-2">
                                <div class="fw-semibold small">{{ $visit->admission?->patient?->name ?? '-' }}</div>
                                <div class="fs-7 text-muted-2">{{ $visit->visited_at?->translatedFormat('d M Y H:i') }}</div>
                            </div>
                            <div class="fs-7 text-muted-2">{{ $visit->admission?->code }} &middot; {{ $visit->createdBy?->name ?? '-' }}</div>
                            <div class="small text-pre-line">{{ $visit->notes }}</div>
                            @if ($visit->vitalSigns() !== [])
                                <div class="d-flex flex-wrap gap-1 mt-2">
                                    @foreach ($visit->vitalSigns() as $label => $value)
                                        <span class="badge text-bg-light border">{{ $label }}: {{ $value }}</span>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @empty
                        <x-empty-state icon="bi-clipboard2-pulse" title="Belum ada visite hari ini"
                                       text="Buka pasien rawat inap untuk mencatat visite dan tanda vital." />
                    @endforelse
                </div>
            </x-card>
        </div>
    </div>
@endsection

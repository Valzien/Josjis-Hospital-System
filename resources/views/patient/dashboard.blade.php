@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    <x-page-header title="Halo, {{ \Illuminate\Support\Str::before($patient?->name ?? auth()->user()->name, ' ') }}"
                   :description="'Selamat datang di '.config('jhs.short_name').'. Pantau antrean dan riwayat pelayanan Anda di sini.'"
                   icon="bi-house-heart">
        <x-slot:actions>
            @if (! $queue)
                <a href="{{ route('patient.queue.create') }}" class="btn btn-primary btn-sm">
                    <i class="bi bi-ticket-perforated me-1"></i>Ambil Antrean
                </a>
            @else
                <a href="{{ route('patient.queue.show', $queue) }}" class="btn btn-primary btn-sm">
                    <i class="bi bi-ticket-perforated me-1"></i>Lihat Antrean Saya
                </a>
            @endif
        </x-slot:actions>
    </x-page-header>

    @if (! $isProfileComplete)
        <div class="alert alert-warning d-flex flex-wrap align-items-center justify-content-between gap-2">
            <div>
                <i class="bi bi-exclamation-triangle me-1"></i>
                Profil Anda belum lengkap. Lengkapi data agar dapat mengambil antrean.
            </div>
            <a href="{{ route('patient.profile.edit') }}" class="btn btn-sm btn-warning">Lengkapi Profil</a>
        </div>
    @endif

    <div class="row g-3 mb-3">
        <div class="col-6 col-xl-3">
            <x-stat-card label="Dokter Aktif" :value="$stats['doctors']" icon="bi-heart-pulse" color="success"
                         :meta="$stats['doctors_today'].' bertugas hari ini'" />
        </div>
        <div class="col-6 col-xl-3">
            <x-stat-card label="Riwayat Pemeriksaan" :value="$stats['records']" icon="bi-journal-medical" color="primary"
                         :href="route('patient.history.index')" />
        </div>
        <div class="col-6 col-xl-3">
            <x-stat-card label="Total Antrean" :value="$stats['queues']" icon="bi-list-ol" color="info"
                         :href="route('patient.queue.index')" />
        </div>
        <div class="col-6 col-xl-3">
            <x-stat-card label="Antrean Hari Ini" :value="$todayQueueCount" icon="bi-calendar-check" color="secondary" />
        </div>
    </div>

    <div class="row g-3">
        <div class="col-xl-7">
            <x-card title="Status Antrean Hari Ini" icon="bi-ticket-perforated"
                    subtitle="{{ now()->translatedFormat('l, d F Y') }}">
                <x-slot:actions>
                    <a href="{{ route('patient.queue.create') }}" class="btn btn-sm btn-primary">
                        <i class="bi bi-plus-lg me-1"></i>Ambil Antrean
                    </a>
                </x-slot:actions>

                @if ($queue)
                    <div class="text-center py-2">
                        <div class="text-muted-2 small mb-1">Nomor Antrean Anda</div>
                        <div class="display-4 fw-bold text-primary mb-1">{{ $queue->queue_number }}</div>
                        <div class="mb-3">
                            <x-status-badge :status="$queue->status" />
                        </div>

                        <div class="row g-2 text-start justify-content-center">
                            <div class="col-sm-4">
                                <div class="border rounded-3 p-2 h-100">
                                    <div class="fs-8 text-muted-2">Dokter</div>
                                    <div class="small fw-semibold">{{ $queue->doctor?->name ?? '-' }}</div>
                                    <div class="fs-8 text-muted-2">{{ $queue->doctor?->specialization ?? '-' }}</div>
                                </div>
                            </div>
                            <div class="col-sm-4">
                                <div class="border rounded-3 p-2 h-100">
                                    <div class="fs-8 text-muted-2">Posisi Anda</div>
                                    <div class="small fw-semibold">{{ $position ? 'Posisi ke-'.$position : '-' }}</div>
                                    <div class="fs-8 text-muted-2">perkiraan {{ $estimatedMinutes ?? 0 }} menit</div>
                                </div>
                            </div>
                            <div class="col-sm-4">
                                <div class="border rounded-3 p-2 h-100">
                                    <div class="fs-8 text-muted-2">Sedang Dilayani</div>
                                    <div class="small fw-semibold">{{ $servingNow?->queue_number ?? 'Belum ada' }}</div>
                                    <div class="fs-8 text-muted-2 text-truncate">{{ $servingNow?->patient?->name ?? 'Ruang tunggu' }}</div>
                                </div>
                            </div>
                        </div>

                        <a href="{{ route('patient.queue.show', $queue) }}" class="btn btn-primary btn-sm mt-3">
                            <i class="bi bi-eye me-1"></i>Detail Antrean
                        </a>
                    </div>

                    @if ($nextUp)
                        <hr class="my-3">
                        <div class="fs-7 text-muted-2 mb-2">Pemanggilan berikutnya</div>
                        <div class="vstack gap-1">
                            @foreach ($nextUp as $item)
                                <div class="d-flex justify-content-between align-items-center small border rounded-3 px-2 py-1">
                                    <span class="text-truncate">{{ $item->patient?->name ?? '-' }}</span>
                                    <span class="jhs-queue-number">{{ $item->queue_number }}</span>
                                </div>
                            @endforeach
                        </div>
                    @endif
                @else
                    <x-empty-state icon="bi-ticket-perforated" title="Belum ada antrean hari ini"
                                  text="Ambil antrean untuk konsultasi dengan dokter pilihan Anda.">
                        <a href="{{ route('patient.queue.create') }}" class="btn btn-primary btn-sm mt-2">
                            <i class="bi bi-plus-lg me-1"></i>Ambil Antrean Sekarang
                        </a>
                    </x-empty-state>
                @endif
            </x-card>

            <x-card title="Riwayat Pemeriksaan Terakhir" icon="bi-journal-medical" class="mt-3">
                <x-slot:actions>
                    <a href="{{ route('patient.history.index') }}" class="btn btn-sm btn-light border">Semua Riwayat</a>
                </x-slot:actions>

                <div class="vstack gap-2">
                    @forelse ($recentRecords as $record)
                        <a href="{{ route('patient.history.show', $record) }}"
                           class="d-flex align-items-center justify-content-between gap-2 border rounded-3 p-2 text-decoration-none">
                            <div class="min-w-0">
                                <div class="small fw-semibold text-dark text-truncate">{{ $record->diagnosis ?? 'Pemeriksaan' }}</div>
                                <div class="fs-7 text-muted-2">
                                    {{ $record->doctor?->name ?? 'Dokter' }} &middot;
                                    {{ $record->examined_at?->translatedFormat('d M Y H:i') ?? '-' }}
                                </div>
                            </div>
                            @if ($record->prescription)
                                <x-badge text="Ada resep" icon="bi-file-earmark-medical" color="info" />
                            @endif
                        </a>
                    @empty
                        <x-empty-state icon="bi-journal-medical" title="Belum ada riwayat pemeriksaan" />
                    @endforelse
                </div>
            </x-card>
        </div>

        <div class="col-xl-5">
            <x-card title="Ringkasan Terakhir" icon="bi-clipboard2-pulse">
                @if ($latestRecord)
                    <div class="fs-7 text-muted-2 mb-1">Diagnosis Terakhir</div>
                    <div class="fw-semibold mb-2">{{ $latestRecord->diagnosis ?? '-' }}</div>
                    <div class="small text-muted-2 mb-3">
                        {{ $latestRecord->doctor?->name ?? 'Dokter' }} &middot;
                        {{ $latestRecord->examined_at?->translatedFormat('d F Y') ?? '-' }}
                    </div>
                    <a href="{{ route('patient.history.show', $latestRecord) }}" class="btn btn-sm btn-light border">
                        <i class="bi bi-journal-text me-1"></i>Lihat Rekam Medis
                    </a>
                @else
                    <x-empty-state icon="bi-clipboard2-pulse" title="Belum ada hasil pemeriksaan"
                                  text="Hasil pemeriksaan akan muncul di sini setelah dokter menyelesaikannya." />
                @endif
            </x-card>

            <x-card title="Resep Terakhir" icon="bi-file-earmark-medical" class="mt-3">
                @if ($latestPrescription)
                    <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                        <div class="min-w-0">
                            <div class="small fw-semibold text-truncate">{{ $latestPrescription->doctor?->name ?? 'Dokter' }}</div>
                            <div class="fs-7 text-muted-2">{{ $latestPrescription->created_at?->translatedFormat('d M Y') }}</div>
                        </div>
                        <x-status-badge :status="$latestPrescription->status" />
                    </div>
                    <a href="{{ route('patient.prescriptions.show', $latestPrescription) }}" class="btn btn-sm btn-light border">
                        <i class="bi bi-eye me-1"></i>Lihat Resep
                    </a>
                @else
                    <x-empty-state icon="bi-file-earmark-medical" title="Belum ada resep" />
                @endif
            </x-card>

            <x-card title="Pintasan" icon="bi-lightning-charge" class="mt-3">
                <div class="d-grid gap-2">
                    <a href="{{ route('patient.queue.create') }}" class="btn btn-light border text-start">
                        <i class="bi bi-ticket-perforated me-2"></i>Ambil Antrean
                    </a>
                    <a href="{{ route('patient.doctors.index') }}" class="btn btn-light border text-start">
                        <i class="bi bi-heart-pulse me-2"></i>Cari Dokter
                    </a>
                    <a href="{{ route('patient.schedules.index') }}" class="btn btn-light border text-start">
                        <i class="bi bi-calendar-week me-2"></i>Jadwal Dokter
                    </a>
                    <a href="{{ route('patient.medicines.index') }}" class="btn btn-light border text-start">
                        <i class="bi bi-capsule me-2"></i>Obat Diberikan
                    </a>
                </div>
            </x-card>
        </div>
    </div>
@endsection
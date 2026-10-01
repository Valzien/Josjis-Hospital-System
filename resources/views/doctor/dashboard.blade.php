@extends('layouts.app')

@section('title', 'Dashboard Dokter')

@section('content')
    @if (! $doctor)
        <x-card>
            <x-empty-state icon="bi-person-x" title="Profil Dokter Belum Terhubung"
                           text="Akun ini belum terhubung dengan data dokter. Hubungi administrator untuk menyelesaikan pengaturan." />
        </x-card>
    @else
        <x-page-header :title="'Halo, Dr. '.$doctor->name"
                       :description="$isOnDutyToday ? 'Anda bertugas hari ini. '.now()->translatedFormat('l, d F Y') : 'Tidak ada jadwal aktif hari ini. '.now()->translatedFormat('l, d F Y')"
                       icon="bi-heart-pulse">
            <x-slot:actions>
                <a href="{{ route('doctor.queues.index') }}" class="btn btn-light border btn-sm">
                    <i class="bi bi-list-ol me-1"></i>Antrean Saya
                </a>
                <a href="{{ route('doctor.prescriptions.index') }}" class="btn btn-light border btn-sm">
                    <i class="bi bi-file-earmark-medical me-1"></i>Resep
                </a>
            </x-slot:actions>
        </x-page-header>

        <div class="row g-3 mb-3">
            <div class="col-6 col-xl-3">
                <x-stat-card label="Menunggu" :value="$waitingCount" icon="bi-hourglass-split" color="warning"
                             :href="route('doctor.queues.index', ['status' => 'waiting'])" />
            </div>
            <div class="col-6 col-xl-3">
                <x-stat-card label="Selesai Hari Ini" :value="$completedCount" icon="bi-check2-circle" color="success"
                             :href="route('doctor.queues.index')" />
            </div>
            <div class="col-6 col-xl-3">
                <x-stat-card label="Pemeriksaan Bulan Ini" :value="$monthlyCount" icon="bi-clipboard2-pulse" color="primary"
                             :href="route('doctor.examinations.index')" />
            </div>
            <div class="col-6 col-xl-3">
                <x-stat-card label="Total Pemeriksaan" :value="$totalRecords" icon="bi-journal-medical" color="info"
                             :meta="$totalPrescriptions.' resep'" :href="route('doctor.medical-records.index')" />
            </div>
        </div>

        <div class="row g-3">
            <div class="col-xl-4">
                <x-card title="Sedang Diperiksa" icon="bi-person-check">
                    @if ($currentQueue)
                        <div class="text-center py-2">
                            <div class="jhs-queue-number jhs-queue-number-lg">{{ $currentQueue->queue_number }}</div>
                            <div class="fw-semibold mt-2">{{ $currentQueue->patient->name }}</div>
                            <div class="fs-7 text-muted-2">{{ $currentQueue->patient->medical_record_number }}</div>
                            <x-status-badge :status="$currentQueue->status" class="mt-2" />

                            <div class="d-grid gap-2 mt-3">
                                <a href="{{ route('doctor.examinations.create', $currentQueue) }}" class="btn btn-primary">
                                    <i class="bi bi-clipboard2-pulse me-1"></i>Isi Rekam Medis
                                </a>
                                <a href="{{ route('doctor.queues.show', $currentQueue) }}" class="btn btn-light border">Detail Antrean</a>
                            </div>
                        </div>
                    @else
                        <x-empty-state icon="bi-person-check" title="Belum ada pasien aktif"
                                       text="Ambil antrean berikutnya saat ada pasien yang menunggu." />
                    @endif
                </x-card>

                <x-card title="Berikutnya" subtitle="5 antrean teratas" icon="bi-forward" class="mt-3">
                    <div class="vstack gap-2">
                        @forelse ($nextUp as $queue)
                            <div class="d-flex align-items-center justify-content-between gap-2 border rounded-3 p-2">
                                <div class="d-flex align-items-center gap-2 min-w-0">
                                    <span class="jhs-queue-number">{{ $queue->queue_number }}</span>
                                    <div class="min-w-0">
                                        <div class="small fw-semibold text-truncate">{{ $queue->patient->name }}</div>
                                        <div class="fs-7 text-muted-2 text-truncate">{{ $queue->complaint_note ?? 'Tanpa keluhan' }}</div>
                                    </div>
                                </div>
                                <a href="{{ route('doctor.queues.show', $queue) }}" class="btn btn-sm btn-light border">Detail</a>
                            </div>
                        @empty
                            <x-empty-state icon="bi-check2-all" title="Antrean kosong" />
                        @endforelse
                    </div>
                </x-card>
            </div>

            <div class="col-xl-8">
                <x-card title="Antrean Menunggu" icon="bi-hourglass-split">
                    <x-slot:actions>
                        <a href="{{ route('doctor.queues.index') }}" class="btn btn-sm btn-light border">Semua Antrean</a>
                    </x-slot:actions>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle jhs-table-compact">
                            <thead>
                                <tr>
                                    <th>Nomor</th>
                                    <th>Pasien</th>
                                    <th>Keluhan</th>
                                    <th class="text-end">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($waitingQueues as $queue)
                                    <tr>
                                        <td><span class="jhs-queue-number">{{ $queue->queue_number }}</span></td>
                                        <td>
                                            <div class="small fw-semibold text-truncate">{{ $queue->patient->name }}</div>
                                            <div class="fs-7 text-muted-2">{{ $queue->patient->medical_record_number }}</div>
                                        </td>
                                        <td class="small text-muted-2">
                                            {{ \Illuminate\Support\Str::limit($queue->complaint_note ?? '-', 40) }}
                                        </td>
                                        <td class="text-end">
                                            <a href="{{ route('doctor.queues.show', $queue) }}" class="btn btn-sm btn-primary">Periksa</a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4">
                                            <x-empty-state icon="bi-hourglass" title="Tidak ada antrean menunggu"
                                                           text="Semua pasien hari ini sudah dilayani." />
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </x-card>

                <x-card title="Pemeriksaan Terakhir" subtitle="Hari ini" icon="bi-clipboard2-pulse" class="mt-3">
                    <x-slot:actions>
                        <a href="{{ route('doctor.examinations.index') }}" class="btn btn-sm btn-light border">Riwayat</a>
                    </x-slot:actions>
                    <div class="vstack gap-2">
                        @forelse ($todayRecords as $record)
                            <div class="d-flex align-items-center justify-content-between gap-2 border rounded-3 p-2">
                                <div class="min-w-0">
                                    <div class="small fw-semibold text-truncate">{{ $record->patient->name }}</div>
                                    <div class="fs-7 text-muted-2">
                                        {{ $record->diagnosis ?? 'Diagnosis belum diisi' }}
                                        &middot; {{ $record->examined_at?->translatedFormat('H:i') }}
                                    </div>
                                </div>
                                <div class="d-flex gap-2">
                                    @if ($record->hasPrescription())
                                        <x-badge text="Resep" icon="bi-file-earmark-medical" color="primary" />
                                    @endif
                                    <a href="{{ route('doctor.examinations.show', $record) }}" class="btn btn-sm btn-light border">Detail</a>
                                </div>
                            </div>
                        @empty
                            <x-empty-state icon="bi-clipboard2-pulse" title="Belum ada pemeriksaan hari ini" />
                        @endforelse
                    </div>
                </x-card>

                <x-card title="Jadwal Praktik" icon="bi-calendar-week" class="mt-3">
                    <x-slot:actions>
                        <a href="{{ route('doctor.schedules.index') }}" class="btn btn-sm btn-light border">Detail</a>
                    </x-slot:actions>
                    <div class="d-flex flex-wrap gap-2">
                        @forelse ($todaySchedules as $schedule)
                            <div class="border rounded-3 px-3 py-2">
                                <div class="small fw-semibold">{{ $schedule->day?->label() ?? '-' }}</div>
                                <div class="fs-7 text-muted-2">{{ $schedule->timeRange() }}</div>
                                <div class="fs-7 text-muted-2">Kuota {{ $schedule->quota }} &middot; {{ $schedule->room ?? 'Tanpa ruang' }}</div>
                            </div>
                        @empty
                            <span class="small text-muted-2">Belum ada jadwal praktik aktif.</span>
                        @endforelse
                    </div>
                </x-card>
            </div>
        </div>
    @endif
@endsection
@extends('layouts.app')

@section('title', 'Dashboard Farmasi')

@section('content')
    <x-page-header title="Dashboard Farmasi"
                   description="Antrean resep dan monitoring stok obat, {{ now()->translatedFormat('l, d F Y') }}."
                   icon="bi-capsule">
        <x-slot:actions>
            <a href="{{ route('pharmacy.prescriptions.index', ['status' => 'PENDING']) }}" class="btn btn-primary btn-sm">
                <i class="bi bi-inbox me-1"></i>Resep Menunggu ({{ $pendingCount }})
            </a>
            <a href="{{ route('pharmacy.stock.index', ['low_stock' => 1]) }}" class="btn btn-light border btn-sm">
                <i class="bi bi-exclamation-triangle me-1"></i>Stok Rendah ({{ $lowStockCount }})
            </a>
        </x-slot:actions>
    </x-page-header>

    <div class="row g-3 mb-3">
        <div class="col-6 col-xl-3">
            <x-stat-card label="Resep Menunggu" :value="$pendingCount" icon="bi-hourglass-split" color="warning"
                         :href="route('pharmacy.prescriptions.index', ['status' => 'PENDING'])" />
        </div>
        <div class="col-6 col-xl-3">
            <x-stat-card label="Sedang Diproses" :value="$processingCount" icon="bi-arrow-repeat" color="info"
                         :href="route('pharmacy.prescriptions.index', ['status' => 'PROCESSING'])" />
        </div>
        <div class="col-6 col-xl-3">
            <x-stat-card label="Siap Diambil" :value="$readyCount" icon="bi-bag-check" color="primary"
                         :href="route('pharmacy.prescriptions.index', ['status' => 'READY'])" />
        </div>
        <div class="col-6 col-xl-3">
            <x-stat-card label="Selesai Hari Ini" :value="$completedToday" icon="bi-check-circle" color="success"
                         :href="route('pharmacy.prescriptions.index', ['status' => 'COMPLETED'])" />
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-6 col-xl-3">
            <x-stat-card label="Jenis Obat Aktif" :value="$totalMedicines" icon="bi-box-seam" color="teal"
                         :href="route('pharmacy.medicines.index')" />
        </div>
        <div class="col-6 col-xl-3">
            <x-stat-card label="Stok Rendah" :value="$lowStockCount" icon="bi-exclamation-triangle" color="danger"
                         :href="route('pharmacy.stock.index', ['low_stock' => 1])" />
        </div>
        <div class="col-6 col-xl-3">
            <x-stat-card label="Transaksi Hari Ini" :value="$todayTransactions" icon="bi-arrow-left-right" color="secondary"
                         :meta="$todayOut.' unit keluar'" :href="route('pharmacy.transactions.index')" />
        </div>
        <div class="col-6 col-xl-3">
            <x-stat-card label="Nilai Inventaris" :value="\Illuminate\Support\Number::currency($inventoryValue, 'IDR', 'id')"
                         icon="bi-cash-stack" color="primary" :href="route('pharmacy.stock.index')" />
        </div>
    </div>

    <div class="row g-3">
        <div class="col-xl-8">
            <x-card title="Antrean Resep" subtitle="Resep yang perlu diproses" icon="bi-file-earmark-medical">
                <x-slot:actions>
                    <a href="{{ route('pharmacy.prescriptions.index') }}" class="btn btn-sm btn-light border">Semua Resep</a>
                </x-slot:actions>
                <div class="table-responsive">
                    <table class="table table-hover align-middle jhs-table-compact">
                        <thead>
                            <tr>
                                <th>Kode</th>
                                <th>Pasien</th>
                                <th>Dokter</th>
                                <th>Waktu</th>
                                <th>Status</th>
                                <th class="text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($pendingList as $prescription)
                                <tr>
                                    <td class="small fw-semibold">{{ $prescription->code }}</td>
                                    <td>
                                        <div class="small fw-semibold text-truncate">{{ $prescription->patient->name }}</div>
                                        <div class="fs-7 text-muted-2">{{ $prescription->patient->medical_record_number }}</div>
                                    </td>
                                    <td class="small">{{ $prescription->doctor->name }}</td>
                                    <td class="small text-nowrap">{{ $prescription->created_at?->translatedFormat('d M H:i') }}</td>
                                    <td><x-status-badge :status="$prescription->status" /></td>
                                    <td class="text-end">
                                        <a href="{{ route('pharmacy.prescriptions.show', $prescription) }}"
                                           class="btn btn-sm btn-primary">Proses</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6">
                                        <x-empty-state icon="bi-check2-all" title="Tidak ada resep menunggu"
                                                       text="Semua resep sudah diproses." />
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-card>

            <x-card title="Transaksi Terakhir" icon="bi-arrow-left-right" class="mt-3">
                <x-slot:actions>
                    <a href="{{ route('pharmacy.transactions.index') }}" class="btn btn-sm btn-light border">Riwayat</a>
                </x-slot:actions>
                <div class="table-responsive">
                    <table class="table table-hover align-middle jhs-table-compact">
                        <thead>
                            <tr>
                                <th>Waktu</th>
                                <th>Obat</th>
                                <th class="text-center">Tipe</th>
                                <th class="text-center">Jumlah</th>
                                <th class="text-center">Stok</th>
                                <th>Petugas</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($recentTransactions as $transaction)
                                <tr>
                                    <td class="small text-nowrap text-muted-2">{{ $transaction->created_at?->translatedFormat('d M H:i') }}</td>
                                    <td class="small">{{ $transaction->medicine->name }}</td>
                                    <td class="text-center">
                                        <x-badge :text="$transaction->type?->label() ?? '-'"
                                                 :icon="$transaction->type?->icon() ?? 'bi-dot'"
                                                 :color="match ($transaction->type?->value) {
                                                     'IN' => 'success',
                                                     'OUT' => 'danger',
                                                     'ADJUSTMENT' => 'info',
                                                     default => 'secondary',
                                                 }" />
                                    </td>
                                    <td class="small text-center">{{ abs($transaction->quantity) }}</td>
                                    <td class="small text-center text-muted-2">{{ $transaction->stock_before }} &rarr; {{ $transaction->stock_after }}</td>
                                    <td class="small text-muted-2">{{ $transaction->user?->name ?? 'Sistem' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6"><x-empty-state icon="bi-arrow-left-right" title="Belum ada transaksi" /></td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-card>
        </div>

        <div class="col-xl-4">
            <x-card title="Stok Rendah" subtitle="Perlu segera diisi ulang" icon="bi-exclamation-triangle">
                <x-slot:actions>
                    <a href="{{ route('pharmacy.stock.index', ['low_stock' => 1]) }}" class="btn btn-sm btn-light border">Semua</a>
                </x-slot:actions>
                <div class="vstack gap-2">
                    @forelse ($lowStock as $medicine)
                        <a href="{{ route('pharmacy.medicines.show', $medicine) }}"
                           class="d-flex align-items-center justify-content-between gap-2 border rounded-3 p-2 text-decoration-none">
                            <div class="min-w-0">
                                <div class="small fw-semibold text-dark text-truncate">{{ $medicine->name }}</div>
                                <div class="fs-7 text-muted-2">Minimum {{ $medicine->minimum_stock }} {{ $medicine->unit }}</div>
                            </div>
                            <x-badge :text="$medicine->stock.' '.$medicine->unit"
                                     icon="bi-box-seam"
                                     :color="$medicine->stock <= 0 ? 'danger' : 'warning'" />
                        </a>
                    @empty
                        <x-empty-state icon="bi-check-circle" title="Semua stok aman"
                                       text="Tidak ada obat yang berada di bawah stok minimum." />
                    @endforelse
                </div>
            </x-card>

            <x-card title="Resep Selesai Terakhir" icon="bi-bag-check" class="mt-3">
                <div class="vstack gap-2">
                    @forelse ($recentCompleted as $prescription)
                        <div class="d-flex align-items-center justify-content-between gap-2 border rounded-3 p-2">
                            <div class="min-w-0">
                                <div class="small fw-semibold">{{ $prescription->code }}</div>
                                <div class="fs-7 text-muted-2 text-truncate">{{ $prescription->patient->name }}</div>
                            </div>
                            <div class="text-end">
                                <div class="fs-7 text-muted-2">{{ $prescription->processed_at?->translatedFormat('d M H:i') }}</div>
                                <div class="fs-7 text-muted-2">{{ $prescription->processedBy?->name ?? 'Sistem' }}</div>
                            </div>
                        </div>
                    @empty
                        <x-empty-state icon="bi-bag-check" title="Belum ada resep selesai" />
                    @endforelse
                </div>
            </x-card>
        </div>
    </div>
@endsection
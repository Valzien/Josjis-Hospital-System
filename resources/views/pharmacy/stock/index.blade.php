@extends('layouts.app')

@section('title', 'Manajemen Stok')

@section('content')
    <x-page-header title="Manajemen Stok" description="Pantau stok obat, catat barang masuk/keluar, dan lakukan penyesuaian opname." icon="bi-box-seam">
        <x-slot:actions>
            <a href="{{ route('pharmacy.medicines.index') }}" class="btn btn-light border btn-sm">
                <i class="bi bi-capsule me-1"></i>Data Obat
            </a>
            <a href="{{ route('pharmacy.transactions.index') }}" class="btn btn-light border btn-sm">
                <i class="bi bi-arrow-left-right me-1"></i>Riwayat Transaksi
            </a>
        </x-slot:actions>
    </x-page-header>

    <div class="row g-3 mb-3">
        <div class="col-6 col-xl-3">
            <x-stat-card label="Jenis Obat" :value="$stats['total_types']" icon="bi-capsule" color="primary" />
        </div>
        <div class="col-6 col-xl-3">
            <x-stat-card label="Stok Rendah" :value="$stats['low_stock']" icon="bi-exclamation-triangle" color="warning"
                         :href="route('pharmacy.stock.index', ['low_stock' => 1])" />
        </div>
        <div class="col-6 col-xl-3">
            <x-stat-card label="Stok Habis" :value="$stats['out_of_stock']" icon="bi-x-octagon" color="danger"
                         :href="route('pharmacy.stock.index', ['out_of_stock' => 1])" />
        </div>
        <div class="col-6 col-xl-3">
            <x-stat-card label="Nilai Inventaris" :value="$stats['value']" icon="bi-cash-stack" color="success" />
        </div>
    </div>

    <x-card class="mb-3">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-lg-4">
                <label class="form-label small mb-1" for="q">Cari Obat</label>
                <input type="search" name="q" id="q" value="{{ $filters['q'] ?? '' }}"
                       class="form-control form-control-sm" placeholder="Nama atau kode obat" data-search-submit>
            </div>
            <div class="col-lg-3">
                <label class="form-label small mb-1" for="category">Kategori</label>
                <select name="category" id="category" class="form-select form-select-sm">
                    <option value="">Semua Kategori</option>
                    @foreach ($categories as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['category'] ?? '') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-lg-5 d-flex gap-3 align-items-center">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="low_stock" value="1" id="low_stock"
                           @checked($filters['low_stock'] ?? false)>
                    <label class="form-check-label small" for="low_stock">Stok rendah</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="out_of_stock" value="1" id="out_of_stock"
                           @checked($filters['out_of_stock'] ?? false)>
                    <label class="form-check-label small" for="out_of_stock">Stok habis</label>
                </div>
                <button class="btn btn-primary btn-sm ms-auto" type="submit"><i class="bi bi-funnel me-1"></i>Terapkan</button>
            </div>
        </form>
    </x-card>

    <x-card title="Stok Obat" subtitle="Diurutkan dari stok paling sedikit" icon="bi-box-seam">
        <div class="table-responsive">
            <table class="table table-hover align-middle jhs-table-compact">
                <thead>
                    <tr>
                        <th>Obat</th>
                        <th class="text-center">Stok</th>
                        <th style="width:180px">Level</th>
                        <th class="text-end">Aksi Stok</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($medicines as $medicine)
                        <tr>
                            <td>
                                <div class="small fw-semibold">{{ $medicine->name }}</div>
                                <div class="fs-7 text-muted-2">{{ $medicine->medicine_code }} &middot; {{ $medicine->category }}</div>
                            </td>
                            <td class="text-center">
                                <x-badge :text="$medicine->stock.' '.$medicine->unit"
                                         icon="bi-box-seam"
                                         :color="match (true) {
                                             $medicine->isOutOfStock() => 'danger',
                                             $medicine->isLowStock() => 'warning',
                                             default => 'success',
                                         }" />
                                <div class="fs-7 text-muted-2 mt-1">Min. {{ $medicine->minimum_stock }}</div>
                            </td>
                            <td>
                                <x-progress :current="$medicine->stock - $medicine->minimum_stock > 0 ? $medicine->minimum_stock : $medicine->stock"
                                            :total="max($medicine->minimum_stock * 2, $medicine->stock, 1)"
                                            color="{{ $medicine->isLowStock() ? 'bg-danger' : 'bg-success' }}" />
                            </td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm">
                                    <button type="button" class="btn btn-light border" data-bs-toggle="modal"
                                            data-bs-target="#stockInModal" data-medicine-id="{{ $medicine->id }}"
                                            data-medicine-name="{{ $medicine->name }}" data-unit="{{ $medicine->unit }}">
                                        <i class="bi bi-plus-circle me-1"></i>Masuk
                                    </button>
                                    <button type="button" class="btn btn-light border" data-bs-toggle="modal"
                                            data-bs-target="#stockOutModal" data-medicine-id="{{ $medicine->id }}"
                                            data-medicine-name="{{ $medicine->name }}" data-unit="{{ $medicine->unit }}"
                                            data-stock="{{ $medicine->stock }}">
                                        <i class="bi bi-dash-circle me-1"></i>Keluar
                                    </button>
                                    <button type="button" class="btn btn-light border" data-bs-toggle="modal"
                                            data-bs-target="#adjustModal" data-medicine-id="{{ $medicine->id }}"
                                            data-medicine-name="{{ $medicine->name }}" data-stock="{{ $medicine->stock }}">
                                        <i class="bi bi-sliders me-1"></i>Opname
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4"><x-empty-state icon="bi-box-seam" title="Belum ada obat" /></td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($medicines->hasPages())
            <div class="mt-3">{{ $medicines->links('pagination::bootstrap-5') }}</div>
        @endif
    </x-card>
@endsection

@push('scripts')
    <script>
        (function () {
            const urls = {
                stockInModal: @json(route('pharmacy.stock.in', ['medicine' => 0])),
                stockOutModal: @json(route('pharmacy.stock.out', ['medicine' => 0])),
                adjustModal: @json(route('pharmacy.stock.adjust', ['medicine' => 0])),
            };

            function wire(modalId, field) {
                const modal = document.getElementById(modalId);
                if (!modal) return;

                modal.addEventListener('show.bs.modal', function (event) {
                    const button = event.relatedTarget;
                    if (!button) return;

                    const id = button.dataset.medicineId;
                    const form = modal.querySelector('form');
                    form.action = urls[modalId].replace(/\/0(\?|$)/, '/' + id + '$1');
                    form.querySelector('[data-medicine-name]').textContent = button.dataset.medicineName;

                    const stockInfo = form.querySelector('[data-stock-info]');
                    if (stockInfo) stockInfo.textContent = button.dataset.stock ?? '-';

                    const input = form.querySelector(field);
                    input.value = field === 'new_stock' ? (button.dataset.stock || 0) : 1;
                });
            }

            wire('stockInModal', 'input[name="quantity"]');
            wire('stockOutModal', 'input[name="quantity"]');
            wire('adjustModal', 'input[name="new_stock"]');
        })();
    </script>
@endpush

@section('modals')
    <div class="modal fade" id="stockInModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <form class="modal-content" method="POST" id="stockInForm">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Barang Masuk</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-3">Obat: <strong data-medicine-name>-</strong></p>
                    <div class="mb-3">
                        <label class="form-label" for="in-quantity">Jumlah Masuk</label>
                        <input type="number" name="quantity" id="in-quantity" min="1" value="1" required class="form-control">
                    </div>
                    <div>
                        <label class="form-label" for="in-notes">Catatan</label>
                        <input type="text" name="notes" id="in-notes" maxlength="500" class="form-control"
                               placeholder="Contoh: Pembelian dari suppliers">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>

    <div class="modal fade" id="stockOutModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <form class="modal-content" method="POST" id="stockOutForm">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Barang Keluar</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-3">Obat: <strong data-medicine-name>-</strong> (stok: <span data-stock-info>-</span>)</p>
                    <div class="mb-3">
                        <label class="form-label" for="out-quantity">Jumlah Keluar</label>
                        <input type="number" name="quantity" id="out-quantity" min="1" value="1" required class="form-control">
                    </div>
                    <div>
                        <label class="form-label" for="out-notes">Catatan</label>
                        <input type="text" name="notes" id="out-notes" maxlength="500" class="form-control"
                               placeholder="Contoh: Rusak atau kedaluwarsa">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger">Kurangi Stok</button>
                </div>
            </form>
        </div>
    </div>

    <div class="modal fade" id="adjustModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <form class="modal-content" method="POST" id="adjustForm">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Penyesuaian Stok (Opname)</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-3">
                        Obat: <strong data-medicine-name>-</strong> &mdash; stok saat ini: <span data-stock-info>-</span>
                    </p>
                    <div class="mb-3">
                        <label class="form-label" for="new-stock">Stok Baru Setelah Hitungan Fisik</label>
                        <input type="number" name="new_stock" id="new-stock" min="0" required class="form-control">
                    </div>
                    <div>
                        <label class="form-label" for="adjust-notes">Catatan</label>
                        <input type="text" name="notes" id="adjust-notes" maxlength="500" class="form-control"
                               placeholder="Contoh: Hasil opname bulanan">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-warning">Simpan Penyesuaian</button>
                </div>
            </form>
        </div>
    </div>
@endsection
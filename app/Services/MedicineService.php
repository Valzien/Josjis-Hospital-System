<?php

namespace App\Services;

use App\Enums\TransactionType;
use App\Models\Medicine;
use App\Models\MedicineBatch;
use App\Models\MedicineTransaction;
use App\Models\Prescription;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Manajemen stok obat (brief §18).
 *
 * Aturan penting: stok HANYA boleh berubah melalui pencatatan transaksi,
 * sehingga setiap perubahan stok dapat diaudit dan dilaporkan.
 *
 * Barang masuk dapat dicatat per batch. Pemakaian stok mengikuti FEFO
 * (first expired, first out) dan menolak batch yang sudah kedaluwarsa.
 */
class MedicineService
{
    /**
     * Menaikkan stok obat (barang masuk).
     * Batch opsional dipakai untuk menjaga kompatibilitas data lama.
     */
    public function stockIn(
        Medicine $medicine,
        int $quantity,
        string $notes,
        ?int $userId = null,
        ?string $batchNumber = null,
        ?string $expiryDate = null,
    ): MedicineTransaction {
        if ($quantity <= 0) {
            throw new RuntimeException('Jumlah barang masuk harus lebih dari 0.');
        }

        $batchNumber = blank($batchNumber) ? null : trim((string) $batchNumber);
        $expiry = $this->normalizeExpiry($expiryDate);

        if (($batchNumber === null) !== ($expiry === null)) {
            throw new RuntimeException('Nomor batch dan tanggal kedaluwarsa harus diisi bersamaan.');
        }

        return DB::transaction(function () use ($medicine, $quantity, $notes, $userId, $batchNumber, $expiry) {
            $before = (int) $medicine->stock;
            $medicine->update(['stock' => $before + $quantity]);

            $batch = null;

            if ($batchNumber !== null) {
                $batch = MedicineBatch::query()
                    ->where('medicine_id', $medicine->id)
                    ->where('batch_number', $batchNumber)
                    ->lockForUpdate()
                    ->first();

                if ($batch && $batch->expiry_date?->toDateString() !== $expiry) {
                    throw new RuntimeException("Nomor batch {$batchNumber} sudah terdaftar dengan tanggal kedaluwarsa berbeda.");
                }

                if (! $batch) {
                    $batch = new MedicineBatch([
                        'medicine_id' => $medicine->id,
                        'batch_number' => $batchNumber,
                    ]);
                }

                $batch->quantity = (int) $batch->quantity + $quantity;
                $batch->expiry_date = $expiry;
                $batch->notes = $notes ?: null;
                $batch->save();
            }

            $transaction = MedicineTransaction::create([
                'medicine_id' => $medicine->id,
                'batch_id' => $batch?->id,
                'type' => TransactionType::In,
                'quantity' => $quantity,
                'stock_before' => $before,
                'stock_after' => $before + $quantity,
                'reference_type' => 'manual',
                'notes' => $batch ? $this->withBatchNote($notes, $batch) : $notes,
                'user_id' => $userId,
            ]);

            if ($batch) {
                $medicine->refreshExpiryFromBatches();
            }

            return $transaction;
        });
    }

    /**
     * Mengurangi stok obat (barang keluar / kadaluwarsa).
     * Stok per batch dikurangi secara FEFO bila batch tersedia.
     */
    public function stockOut(Medicine $medicine, int $quantity, string $notes, ?int $userId = null): MedicineTransaction
    {
        $transactions = $this->consume(
            $medicine,
            $quantity,
            $notes,
            'manual',
            null,
            $userId,
        );

        return $transactions[0];
    }

    /**
     * Penyesuaian stok ke nilai absolut (stock opname).
     *
     * Opname sengaja tidak mengubah rincian batch karena tidak diketahui
     * batch mana yang selisih. Selisih dicatat sebagai transaksi penyesuaian.
     */
    public function adjustStock(Medicine $medicine, int $newStock, string $notes, ?int $userId = null): MedicineTransaction
    {
        if ($newStock < 0) {
            throw new RuntimeException('Stok hasil penyesuaian tidak boleh negatif.');
        }

        return DB::transaction(function () use ($medicine, $newStock, $notes, $userId) {
            $before = (int) $medicine->stock;

            $medicine->update(['stock' => $newStock]);

            return MedicineTransaction::create([
                'medicine_id' => $medicine->id,
                'type' => TransactionType::Adjustment,
                'quantity' => $newStock - $before,
                'stock_before' => $before,
                'stock_after' => $newStock,
                'reference_type' => 'manual',
                'notes' => $notes,
                'user_id' => $userId,
            ]);
        });
    }

    /**
     * Mengurangi stok saat resep diserahkan ke pasien (dispense).
     */
    public function dispense(Medicine $medicine, int $quantity, int $prescriptionId, ?int $userId = null): MedicineTransaction
    {
        $transactions = $this->consume(
            $medicine,
            $quantity,
            'Penyerahan obat sesuai resep',
            Prescription::class,
            $prescriptionId,
            $userId,
        );

        return $transactions[0];
    }

    /**
     * Memulihkan stok (mis. pembatalan resep yang sudah diserahkan).
     * Stok batch dikembalikan ke batch asal bila transaksi memiliki batch.
     */
    public function refundDispensed(int $prescriptionId, ?int $userId = null): int
    {
        return DB::transaction(function () use ($prescriptionId, $userId) {
            $transactions = MedicineTransaction::query()
                ->forReference(Prescription::class, $prescriptionId)
                ->where('type', TransactionType::Out->value)
                ->orderBy('id')
                ->get();

            $restored = 0;

            foreach ($transactions as $transaction) {
                $medicine = Medicine::query()->lockForUpdate()->find($transaction->medicine_id);

                if (! $medicine) {
                    continue;
                }

                $quantity = abs((int) $transaction->quantity);

                if ($transaction->batch_id) {
                    $batch = MedicineBatch::query()->lockForUpdate()->find($transaction->batch_id);

                    if ($batch) {
                        $batch->increment('quantity', $quantity);
                    }
                }

                $before = (int) $medicine->stock;
                $medicine->update(['stock' => $before + $quantity]);

                MedicineTransaction::create([
                    'medicine_id' => $medicine->id,
                    'batch_id' => $transaction->batch_id,
                    'type' => TransactionType::In,
                    'quantity' => $quantity,
                    'stock_before' => $before,
                    'stock_after' => $before + $quantity,
                    'reference_type' => Prescription::class,
                    'reference_id' => $prescriptionId,
                    'notes' => 'Pengembalian stok karena resep dibatalkan',
                    'user_id' => $userId,
                ]);

                $medicine->refreshExpiryFromBatches();
                $restored++;
            }

            return $restored;
        });
    }

    /**
     * Memusnahkan batch kedaluwarsa dan mengurangi stok ringkasan.
     */
    public function disposeExpiredBatch(MedicineBatch $batch, ?int $userId = null): MedicineTransaction
    {
        return DB::transaction(function () use ($batch, $userId) {
            $batch = MedicineBatch::query()->lockForUpdate()->findOrFail($batch->id);

            if (! $batch->isExpired()) {
                throw new RuntimeException('Hanya batch yang sudah kedaluwarsa yang dapat dimusnahkan.');
            }

            if ($batch->quantity <= 0) {
                throw new RuntimeException('Batch ini sudah tidak memiliki stok.');
            }

            $medicine = Medicine::query()->lockForUpdate()->findOrFail($batch->medicine_id);
            $quantity = (int) $batch->quantity;
            $before = (int) $medicine->stock;
            $after = max(0, $before - $quantity);

            $batch->update(['quantity' => 0]);
            $medicine->update(['stock' => $after]);

            $transaction = MedicineTransaction::create([
                'medicine_id' => $medicine->id,
                'batch_id' => $batch->id,
                'type' => TransactionType::Out,
                'quantity' => -$quantity,
                'stock_before' => $before,
                'stock_after' => $after,
                'reference_type' => 'medicine_batch',
                'reference_id' => $batch->id,
                'notes' => "Pemusnahan batch kedaluwarsa {$batch->batch_number}",
                'user_id' => $userId,
            ]);

            $medicine->refreshExpiryFromBatches();

            return $transaction;
        });
    }

    /** @return array<int, Medicine> Obat dengan stok <= minimum_stock. */
    public function lowStockMedicines(?int $limit = null)
    {
        return Medicine::query()
            ->active()
            ->lowStock()
            ->orderBy('stock')
            ->when($limit, fn ($q) => $q->limit($limit))
            ->get();
    }

    public function lowStockCount(): int
    {
        return Medicine::query()->active()->lowStock()->count();
    }

    public function expiringSoonCount(int $days = 30): int
    {
        return Medicine::query()->active()->expiringSoon($days)->count();
    }

    public function expiredCount(): int
    {
        return Medicine::query()->active()->expired()->count();
    }

    public function expiredBatchCount(): int
    {
        return MedicineBatch::query()->expired()->count();
    }

    public function expiringBatchCount(int $days = 30): int
    {
        return MedicineBatch::query()->expiringSoon($days)->count();
    }

    public function expiredBatchQuantity(): int
    {
        return (int) (MedicineBatch::query()->expired()->sum('quantity') ?? 0);
    }

    public function expiringSoonMedicines(int $days = 30, ?int $limit = null)
    {
        return Medicine::query()
            ->active()
            ->expiringSoon($days)
            ->orderBy('expiry_date')
            ->when($limit, fn ($q) => $q->limit($limit))
            ->get();
    }

    /** Nilai persediaan obat (untuk laporan & dashboard). */
    public function inventoryValue(): float
    {
        return (float) (Medicine::query()
            ->active()
            ->selectRaw('COALESCE(SUM(stock * price), 0) AS total')
            ->value('total') ?? 0);
    }

    /**
     * @return array<int, MedicineTransaction>
     */
    private function consume(
        Medicine $medicine,
        int $quantity,
        string $notes,
        string $referenceType,
        ?int $referenceId,
        ?int $userId,
    ): array {
        if ($quantity <= 0) {
            throw new RuntimeException('Jumlah barang keluar harus lebih dari 0.');
        }

        return DB::transaction(function () use ($medicine, $quantity, $notes, $referenceType, $referenceId, $userId) {
            $before = (int) $medicine->stock;

            if ($before < $quantity) {
                throw new RuntimeException("Stok {$medicine->name} tidak mencukupi (tersedia {$before} {$medicine->unit}).");
            }

            $allocations = $this->allocateUsableBatches($medicine, $quantity);

            if ($allocations === null) {
                $medicine->update(['stock' => $before - $quantity]);

                return [MedicineTransaction::create([
                    'medicine_id' => $medicine->id,
                    'type' => TransactionType::Out,
                    'quantity' => -$quantity,
                    'stock_before' => $before,
                    'stock_after' => $before - $quantity,
                    'reference_type' => $referenceType,
                    'reference_id' => $referenceId,
                    'notes' => $notes,
                    'user_id' => $userId,
                ])];
            }

            $running = $before;
            $transactions = [];

            foreach ($allocations as $allocation) {
                /** @var MedicineBatch $batch */
                $batch = $allocation['batch'];
                $take = (int) $allocation['take'];

                $batch->decrement('quantity', $take);
                $running -= $take;

                $transactions[] = MedicineTransaction::create([
                    'medicine_id' => $medicine->id,
                    'batch_id' => $batch->id,
                    'type' => TransactionType::Out,
                    'quantity' => -$take,
                    'stock_before' => $running + $take,
                    'stock_after' => $running,
                    'reference_type' => $referenceType,
                    'reference_id' => $referenceId,
                    'notes' => $this->withBatchNote($notes, $batch),
                    'user_id' => $userId,
                ]);
            }

            $medicine->update(['stock' => $running]);
            $medicine->refreshExpiryFromBatches();

            return $transactions;
        });
    }

    /**
     * Membagi kebutuhan stok ke batch yang belum kedaluwarsa (FEFO).
     * Mengembalikan null bila obat tidak memakai pencatatan batch.
     *
     * @return Collection<int, array{batch: MedicineBatch, take: int}>|null
     */
    private function allocateUsableBatches(Medicine $medicine, int $quantity): ?Collection
    {
        $batches = MedicineBatch::query()
            ->where('medicine_id', $medicine->id)
            ->where('quantity', '>', 0)
            ->lockForUpdate()
            ->get();

        if ($batches->isEmpty()) {
            return null;
        }

        $usable = $batches
            ->filter(fn (MedicineBatch $batch) => ! $batch->isExpired())
            ->sortBy(fn (MedicineBatch $batch) => sprintf(
                '%s|%010d',
                $batch->expiry_date?->toDateString() ?? '9999-12-31',
                $batch->id,
            ))
            ->values();

        $allocations = collect();
        $remaining = $quantity;

        foreach ($usable as $batch) {
            if ($remaining <= 0) {
                break;
            }

            $take = min((int) $batch->quantity, $remaining);
            $allocations->push(['batch' => $batch, 'take' => $take]);
            $remaining -= $take;
        }

        if ($remaining > 0) {
            $usableQty = $quantity - $remaining;
            $expiredQty = (int) $batches->filter(fn (MedicineBatch $batch) => $batch->isExpired())->sum('quantity');

            $message = "Stok {$medicine->name} yang dapat dipakai hanya {$usableQty} {$medicine->unit}.";

            if ($expiredQty > 0) {
                $message .= " Terdapat {$expiredQty} {$medicine->unit} pada batch kedaluwarsa yang harus dimusnahkan.";
            }

            throw new RuntimeException($message);
        }

        return $allocations;
    }

    private function normalizeExpiry(mixed $value): ?string
    {
        if (blank($value)) {
            return null;
        }

        try {
            $date = Carbon::parse($value)->toDateString();
        } catch (\Throwable) {
            throw new RuntimeException('Tanggal kedaluwarsa tidak valid.');
        }

        if ($date < now()->toDateString()) {
            throw new RuntimeException('Tanggal kedaluwarsa barang masuk tidak boleh di masa lalu.');
        }

        return $date;
    }

    private function withBatchNote(string $notes, MedicineBatch $batch): string
    {
        $prefix = $notes !== '' ? $notes.'. ' : '';

        return $prefix.'Batch '.$batch->batch_number;
    }
}

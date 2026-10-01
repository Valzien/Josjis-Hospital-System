<?php

namespace App\Services;

use App\Enums\TransactionType;
use App\Models\Medicine;
use App\Models\MedicineTransaction;
use App\Models\Prescription;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Manajemen stok obat (brief §18).
 *
 * Aturan penting: stok HANYA boleh berubah melalui pencatatan transaksi,
 * sehingga setiap perubahan stok dapat diaudit dan dilaporkan.
 */
class MedicineService
{
    /**
     * Menaikkan stok obat (barang masuk).
     */
    public function stockIn(Medicine $medicine, int $quantity, string $notes, ?int $userId = null): MedicineTransaction
    {
        if ($quantity <= 0) {
            throw new RuntimeException('Jumlah barang masuk harus lebih dari 0.');
        }

        return DB::transaction(function () use ($medicine, $quantity, $notes, $userId) {
            $before = $medicine->stock;

            $medicine->update(['stock' => $before + $quantity]);

            return MedicineTransaction::create([
                'medicine_id' => $medicine->id,
                'type' => TransactionType::In,
                'quantity' => $quantity,
                'stock_before' => $before,
                'stock_after' => $before + $quantity,
                'reference_type' => 'manual',
                'notes' => $notes,
                'user_id' => $userId,
            ]);
        });
    }

    /**
     * Mengurangi stok obat (barang keluar / kadaluwarsa).
     */
    public function stockOut(Medicine $medicine, int $quantity, string $notes, ?int $userId = null): MedicineTransaction
    {
        if ($quantity <= 0) {
            throw new RuntimeException('Jumlah barang keluar harus lebih dari 0.');
        }

        return DB::transaction(function () use ($medicine, $quantity, $notes, $userId) {
            $before = $medicine->stock;

            if ($before < $quantity) {
                throw new RuntimeException("Stok {$medicine->name} tidak mencukupi (tersedia {$before} {$medicine->unit}).");
            }

            $medicine->update(['stock' => $before - $quantity]);

            return MedicineTransaction::create([
                'medicine_id' => $medicine->id,
                'type' => TransactionType::Out,
                'quantity' => -$quantity,
                'stock_before' => $before,
                'stock_after' => $before - $quantity,
                'reference_type' => 'manual',
                'notes' => $notes,
                'user_id' => $userId,
            ]);
        });
    }

    /**
     * Penyesuaian stok ke nilai absolut (stock opname).
     */
    public function adjustStock(Medicine $medicine, int $newStock, string $notes, ?int $userId = null): MedicineTransaction
    {
        if ($newStock < 0) {
            throw new RuntimeException('Stok hasil penyesuaian tidak boleh negatif.');
        }

        return DB::transaction(function () use ($medicine, $newStock, $notes, $userId) {
            $before = $medicine->stock;

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
        return DB::transaction(function () use ($medicine, $quantity, $prescriptionId, $userId) {
            $before = $medicine->stock;

            if ($before < $quantity) {
                throw new RuntimeException("Stok {$medicine->name} tidak mencukupi untuk resep ini.");
            }

            $medicine->update(['stock' => $before - $quantity]);

            return MedicineTransaction::create([
                'medicine_id' => $medicine->id,
                'type' => TransactionType::Out,
                'quantity' => -$quantity,
                'stock_before' => $before,
                'stock_after' => $before - $quantity,
                'reference_type' => Prescription::class,
                'reference_id' => $prescriptionId,
                'notes' => 'Penyerahan obat sesuai resep',
                'user_id' => $userId,
            ]);
        });
    }

    /**
     * Memulihkan stok (mis. pembatalan resep yang sudah diserahkan).
     *
     * @return array<int, MedicineTransaction>
     */
    public function refundDispensed(int $prescriptionId, ?int $userId = null): int
    {
        return DB::transaction(function () use ($prescriptionId, $userId) {
            $transactions = MedicineTransaction::query()
                ->forReference(Prescription::class, $prescriptionId)
                ->where('type', TransactionType::Out->value)
                ->get();

            $restored = 0;

            foreach ($transactions as $transaction) {
                $medicine = Medicine::query()->lockForUpdate()->find($transaction->medicine_id);

                if (! $medicine) {
                    continue;
                }

                $quantity = abs((int) $transaction->quantity);
                $medicine->update(['stock' => $medicine->stock + $quantity]);

                MedicineTransaction::create([
                    'medicine_id' => $medicine->id,
                    'type' => TransactionType::In,
                    'quantity' => $quantity,
                    'stock_before' => $transaction->stock_after,
                    'stock_after' => $medicine->stock,
                    'reference_type' => Prescription::class,
                    'reference_id' => $prescriptionId,
                    'notes' => 'Pengembalian stok karena resep dibatalkan',
                    'user_id' => $userId,
                ]);

                $restored++;
            }

            return $restored;
        });
    }

    /**
     * @return array<int, Medicine> Obat dengan stok <= minimum_stock.
     */
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

    /** Nilai persediaan obat (untuk laporan & dashboard). */
    public function inventoryValue(): float
    {
        return (float) (Medicine::query()
            ->active()
            ->selectRaw('COALESCE(SUM(stock * price), 0) AS total')
            ->value('total') ?? 0);
    }
}

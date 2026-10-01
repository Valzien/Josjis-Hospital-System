<?php

namespace App\Services;

use App\Enums\PrescriptionStatus;
use App\Models\MedicalRecord;
use App\Models\Medicine;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\PrescriptionDetail;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use RuntimeException;

/**
 * Logika bisnis resep & integrasi farmasi (brief §18).
 *
 * Alur: Dokter membuat resep (PENDING) → Apoteker cek stok → proses
 * (PROCESSING) → siap diambil (READY) → obat diserahkan (COMPLETED)
 * dan stok berkurang melalui MedicineService.
 */
class PrescriptionService
{
    public function __construct(
        private readonly MedicineService $medicines,
        private readonly NumberGenerator $numbers,
    ) {}

    /**
     * Membuat resep beserta detailnya dari hasil pemeriksaan dokter.
     *
     * @param  array<int, array{medicine_id:int, quantity:int, dosage?:string|null, instructions?:string|null}>  $items
     */
    public function createFromMedicalRecord(
        MedicalRecord $record,
        array $items,
        ?string $notes = null,
    ): Prescription {
        $this->assertDoctorCanPrescribe($record);

        $items = $this->validateItems($items);

        if ($items === []) {
            throw new RuntimeException('Resep harus berisi minimal satu obat.');
        }

        return DB::transaction(function () use ($record, $items, $notes) {
            $prescription = Prescription::create([
                'code' => $this->numbers->prescriptionCode(),
                'medical_record_id' => $record->id,
                'patient_id' => $record->patient_id,
                'doctor_id' => $record->doctor_id,
                'status' => PrescriptionStatus::Pending,
                'notes' => $notes,
                'total_price' => 0,
            ]);

            $total = 0;

            foreach ($items as $item) {
                $medicine = Medicine::query()->findOrFail($item['medicine_id']);
                $subtotal = $medicine->price * $item['quantity'];

                PrescriptionDetail::create([
                    'prescription_id' => $prescription->id,
                    'medicine_id' => $medicine->id,
                    'quantity' => $item['quantity'],
                    'dosage' => $item['dosage'] ?? null,
                    'instructions' => $item['instructions'] ?? null,
                    'price' => $medicine->price,
                    'subtotal' => $subtotal,
                ]);

                $total += $subtotal;
            }

            $prescription->update(['total_price' => $total]);

            return $prescription->load(['details.medicine', 'patient', 'doctor']);
        });
    }

    /**
     * Apoteker mulai memproses resep setelah memverifikasi stok.
     */
    public function markProcessing(Prescription $prescription): Prescription
    {
        $this->assertTransition($prescription, PrescriptionStatus::Processing);

        if (! $prescription->isStockAvailable()) {
            throw new RuntimeException('Stok obat belum mencukupi: '.implode(', ', $prescription->insufficientMedicines()));
        }

        $prescription->update(['status' => PrescriptionStatus::Processing]);

        return $prescription->refresh();
    }

    public function markReady(Prescription $prescription, ?int $userId = null): Prescription
    {
        $this->assertTransition($prescription, PrescriptionStatus::Ready);

        $prescription->update([
            'status' => PrescriptionStatus::Ready,
            'processed_by' => $userId ?? $prescription->processed_by,
        ]);

        return $prescription->refresh();
    }

    /**
     * Menyerahkan obat kepada pasien: mengurangi stok, mencatat transaksi,
     * dan menandai resep COMPLETED.
     */
    public function dispense(Prescription $prescription, ?int $userId = null): Prescription
    {
        return DB::transaction(function () use ($prescription, $userId) {
            $prescription->loadMissing('details.medicine');

            // Resep harus benar-benar SIAP. Menyerahkan ulang resep yang sudah
            // selesai akan mengurangi stok dua kali.
            if ($prescription->status !== PrescriptionStatus::Ready) {
                throw new RuntimeException("Resep dengan status {$prescription->status?->label()} tidak dapat diserahkan.");
            }

            $this->assertTransition($prescription, PrescriptionStatus::Completed);

            foreach ($prescription->details as $detail) {
                $this->medicines->dispense(
                    $detail->medicine,
                    $detail->quantity,
                    $prescription->id,
                    $userId,
                );
            }

            $prescription->update([
                'status' => PrescriptionStatus::Completed,
                'processed_at' => now(),
                'processed_by' => $userId ?? $prescription->processed_by,
            ]);

            return $prescription->refresh();
        });
    }

    public function cancel(Prescription $prescription, ?string $reason = null, ?int $userId = null): Prescription
    {
        return DB::transaction(function () use ($prescription, $reason, $userId) {
            if ($prescription->status === PrescriptionStatus::Completed) {
                // Pengembalian stok bila obat sudah diserahkan.
                $this->medicines->refundDispensed($prescription->id, $userId);
            } else {
                $this->assertTransition($prescription, PrescriptionStatus::Cancelled);
            }

            $prescription->update([
                'status' => PrescriptionStatus::Cancelled,
                'notes' => trim(($prescription->notes ?? '')."\nAlasan pembatalan: ".($reason ?? '-')),
                'processed_at' => now(),
                'processed_by' => $userId ?? $prescription->processed_by,
            ]);

            return $prescription->refresh();
        });
    }

    private function assertTransition(Prescription $prescription, PrescriptionStatus $target): void
    {
        $current = $prescription->status;

        if ($current === $target) {
            return;
        }

        if ($current === null || ! $current->canTransitionTo($target)) {
            throw new RuntimeException("Resep dengan status {$current?->label()} tidak dapat diubah menjadi {$target->label()}.");
        }
    }

    private function assertDoctorCanPrescribe(MedicalRecord $record): void
    {
        if (blank($record->diagnosis)) {
            throw new RuntimeException('Diagnosis wajib diisi sebelum membuat resep.');
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @return array<int, array{medicine_id:int, quantity:int, dosage:string|null, instructions:string|null}>
     */
    private function validateItems(array $items): array
    {
        $clean = [];

        foreach ($items as $item) {
            $medicineId = (int) ($item['medicine_id'] ?? 0);
            $quantity = (int) ($item['quantity'] ?? 0);

            if ($medicineId <= 0 || $quantity <= 0) {
                continue;
            }

            Validator::make(
                ['medicine_id' => $medicineId, 'quantity' => $quantity],
                ['medicine_id' => 'required|integer|exists:medicines,id', 'quantity' => 'required|integer|min:1|max:1000'],
            )->validate();

            $clean[] = [
                'medicine_id' => $medicineId,
                'quantity' => $quantity,
                'dosage' => $item['dosage'] ?? null,
                'instructions' => $item['instructions'] ?? null,
            ];
        }

        return $clean;
    }

    /**
     * Rekap antrean resep untuk dashboard apoteker.
     *
     * @return array<string, int>
     */
    public function statusSummary(): array
    {
        $rows = Prescription::query()
            ->select('status', DB::raw('count(*) as aggregate'))
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $summary = [];

        foreach (PrescriptionStatus::cases() as $case) {
            $summary[$case->value] = (int) ($rows[$case->value] ?? 0);
        }

        return $summary;
    }

    public function pendingCount(): int
    {
        return Prescription::query()->where('status', PrescriptionStatus::Pending->value)->count();
    }

    /**
     * Resep yang sudah diserahkan lengkap dengan obatnya (untuk halaman "Obat" pasien).
     *
     * @return Collection<int, array{prescription: Prescription, details: Collection}>
     */
    public function dispensedForPatient(Patient $patient, int $limit = 50)
    {
        $prescriptions = Prescription::query()
            ->with(['details.medicine', 'doctor'])
            ->where('patient_id', $patient->id)
            ->where('status', PrescriptionStatus::Completed->value)
            ->latest('processed_at')
            ->limit($limit)
            ->get();

        return $prescriptions;
    }
}

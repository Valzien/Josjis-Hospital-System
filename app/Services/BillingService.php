<?php

namespace App\Services;

use App\Enums\InvoiceStatus;
use App\Enums\PaymentMethod;
use App\Enums\PrescriptionStatus;
use App\Models\Invoice;
use App\Models\MedicalRecord;
use App\Models\Payment;
use App\Models\Queue;
use App\Models\Setting;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Logika tagihan & kasir.
 *
 * Satu kunjungan (antrean dengan rekam medis) menghasilkan satu invoice berisi
 * biaya konsultasi dan rincian obat dari resep. Pembayaran boleh dicicil;
 * status invoice mengikuti total pembayaran yang masuk.
 */
class BillingService
{
    public function __construct(private readonly NumberGenerator $numbers) {}

    public function consultationFee(): float
    {
        return (float) Setting::get('consultation_fee', config('jhs.billing.consultation_fee', 75000));
    }

    /**
     * Rincian tagihan yang akan dibuat untuk sebuah kunjungan (belum disimpan).
     *
     * @return array{record: MedicalRecord|null, items: array<int, array<string, mixed>>, subtotal: float}
     */
    public function previewForQueue(Queue $queue): array
    {
        $queue->loadMissing(['medicalRecord.prescription.details.medicine', 'doctor', 'patient']);

        $record = $queue->medicalRecord;

        if ($record === null) {
            return ['record' => null, 'items' => [], 'subtotal' => 0.0];
        }

        $items = [[
            'type' => 'consultation',
            'description' => 'Biaya konsultasi '.($queue->doctor?->name ?? 'dokter'),
            'quantity' => 1,
            'unit_price' => $this->consultationFee(),
            'subtotal' => $this->consultationFee(),
            'reference_type' => MedicalRecord::class,
            'reference_id' => $record->id,
        ]];

        $prescription = $record->prescription;

        if ($prescription !== null && $prescription->status !== PrescriptionStatus::Cancelled) {
            foreach ($prescription->details as $detail) {
                $subtotal = (float) $detail->subtotal;

                $items[] = [
                    'type' => 'medicine',
                    'description' => trim(($detail->medicine?->name ?? 'Obat').($detail->dosage ? ' — '.$detail->dosage : '')),
                    'quantity' => (int) $detail->quantity,
                    'unit_price' => (float) $detail->price,
                    'subtotal' => $subtotal,
                    'reference_type' => $detail::class,
                    'reference_id' => $detail->id,
                ];
            }
        }

        $subtotal = array_sum(array_column($items, 'subtotal'));

        return ['record' => $record, 'items' => $items, 'subtotal' => (float) $subtotal];
    }

    /**
     * @throws RuntimeException
     */
    public function createForQueue(Queue $queue, float $discount = 0, ?string $notes = null, ?int $issuedBy = null): Invoice
    {
        $preview = $this->previewForQueue($queue);

        if ($preview['record'] === null) {
            throw new RuntimeException('Kunjungan ini belum memiliki rekam medis sehingga belum dapat ditagih.');
        }

        if ($preview['items'] === []) {
            throw new RuntimeException('Tidak ada rincian biaya yang dapat ditagih.');
        }

        $existing = Invoice::query()->where('medical_record_id', $preview['record']->id)->first();

        if ($existing !== null) {
            throw new RuntimeException("Rekam medis ini sudah memiliki tagihan {$existing->code}.");
        }

        $subtotal = $preview['subtotal'];
        $discount = max(0, min($discount, $subtotal));
        $total = $subtotal - $discount;

        return DB::transaction(function () use ($queue, $preview, $discount, $subtotal, $total, $notes, $issuedBy) {
            $invoice = Invoice::create([
                'code' => $this->numbers->invoiceCode(),
                'patient_id' => $queue->patient_id,
                'queue_id' => $queue->id,
                'medical_record_id' => $preview['record']->id,
                'status' => $total <= 0 ? InvoiceStatus::Paid : InvoiceStatus::Unpaid,
                'subtotal' => $subtotal,
                'discount' => $discount,
                'total' => $total,
                'paid_amount' => 0,
                'notes' => $notes,
                'issued_by' => $issuedBy,
                'paid_at' => $total <= 0 ? now() : null,
            ]);

            foreach ($preview['items'] as $item) {
                $invoice->items()->create($item);
            }

            return $invoice->load(['items', 'patient', 'queue.doctor', 'medicalRecord']);
        });
    }

    /**
     * @throws RuntimeException
     */
    public function recordPayment(
        Invoice $invoice,
        float $amount,
        PaymentMethod|string $method,
        ?string $notes = null,
        ?int $userId = null,
    ): Payment {
        $method = $method instanceof PaymentMethod ? $method : PaymentMethod::from($method);

        if (! $invoice->isOpen()) {
            throw new RuntimeException("Tagihan {$invoice->code} tidak dapat dibayar karena status {$invoice->statusLabel()}.");
        }

        if ($amount <= 0) {
            throw new RuntimeException('Nominal pembayaran harus lebih dari nol.');
        }

        return DB::transaction(function () use ($invoice, $amount, $method, $notes, $userId) {
            /** @var Invoice $locked */
            $locked = Invoice::query()->lockForUpdate()->findOrFail($invoice->id);

            $remaining = $locked->remainingAmount();

            if ($amount > $remaining + 0.001) {
                throw new RuntimeException('Nominal melebihi sisa tagihan (Rp '.number_format($remaining, 0, ',', '.').').');
            }

            $payment = $locked->payments()->create([
                'code' => $this->numbers->paymentCode(),
                'method' => $method,
                'amount' => $amount,
                'notes' => $notes,
                'received_by' => $userId,
                'paid_at' => now(),
            ]);

            $paid = (float) $locked->paid_amount + $amount;

            $locked->update([
                'paid_amount' => $paid,
                'status' => $paid + 0.001 >= (float) $locked->total ? InvoiceStatus::Paid : InvoiceStatus::Partial,
                'paid_at' => $paid + 0.001 >= (float) $locked->total ? now() : null,
            ]);

            return $payment;
        });
    }

    /**
     * @throws RuntimeException
     */
    public function cancel(Invoice $invoice, ?string $reason = null): Invoice
    {
        if ((float) $invoice->paid_amount > 0) {
            throw new RuntimeException('Tagihan yang sudah memiliki pembayaran tidak dapat dibatalkan.');
        }

        if ($invoice->status === InvoiceStatus::Cancelled) {
            return $invoice;
        }

        $invoice->update([
            'status' => InvoiceStatus::Cancelled,
            'notes' => trim(($invoice->notes ?? '')."\nPembatalan: ".($reason ?? '-')),
        ]);

        return $invoice->refresh();
    }

    /** @return array<string, int|float> */
    public function todaySummary(): array
    {
        return [
            'revenue' => (float) Payment::query()->whereDate('paid_at', now()->toDateString())->sum('amount'),
            'payments' => Payment::query()->whereDate('paid_at', now()->toDateString())->count(),
            'invoices_today' => Invoice::query()->whereDate('created_at', now()->toDateString())->count(),
            'unpaid' => Invoice::query()->whereIn('status', InvoiceStatus::openValues())->count(),
            'billable' => Queue::query()
                ->whereHas('medicalRecord')
                ->whereDoesntHave('invoice')
                ->count(),
        ];
    }
}

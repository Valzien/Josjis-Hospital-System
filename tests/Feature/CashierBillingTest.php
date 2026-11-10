<?php

namespace Tests\Feature;

use App\Enums\InvoiceStatus;
use App\Enums\PaymentMethod;
use App\Models\Doctor;
use App\Models\DoctorSchedule;
use App\Models\Invoice;
use App\Models\MedicalRecord;
use App\Models\Medicine;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\Prescription;
use App\Models\PrescriptionDetail;
use App\Models\Queue;
use App\Models\User;
use App\Services\BillingService;

class CashierBillingTest extends SmokeTestCase
{
    private const CONSULTATION_FEE = 75000.0;

    protected function setUp(): void
    {
        parent::setUp();

        config(['jhs.billing.consultation_fee' => self::CONSULTATION_FEE]);
    }

    private function cashier(): User
    {
        return User::factory()->cashier()->create();
    }

    /**
     * Kunjungan selesai dengan rekam medis + satu item resep.
     *
     * @return array{queue: Queue, record: MedicalRecord, detail: PrescriptionDetail}
     */
    private function billableVisit(float $medicinePrice = 20000, int $quantity = 2): array
    {
        $doctor = Doctor::factory()->create();
        $patient = Patient::factory()->create();
        $schedule = DoctorSchedule::factory()->openAllDay()->for($doctor)->create();

        $queue = Queue::factory()->create([
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'doctor_schedule_id' => $schedule->id,
            'queue_number' => 'C-001',
            'queue_date' => now()->toDateString(),
        ]);

        $record = MedicalRecord::factory()->forQueue($queue)->create();

        $medicine = Medicine::factory()->create([
            'name' => 'Paracetamol 500 mg',
            'price' => $medicinePrice,
        ]);

        $prescription = Prescription::factory()->create([
            'medical_record_id' => $record->id,
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
        ]);

        $detail = PrescriptionDetail::factory()->create([
            'prescription_id' => $prescription->id,
            'medicine_id' => $medicine->id,
            'quantity' => $quantity,
            'price' => $medicinePrice,
            'subtotal' => $medicinePrice * $quantity,
        ]);

        return compact('queue', 'record', 'detail');
    }

    public function test_cashier_pages_render(): void
    {
        $cashier = $this->cashier();
        $this->actingAs($cashier);

        $data = $this->billableVisit();

        // Sebelum invoice dibuat, halaman pembuatan tagihan harus bisa dibuka.
        $this->get(route('cashier.invoices.create', ['queue_id' => $data['queue']->id]))
            ->assertOk()
            ->assertDontSee('Whoops', false);

        $billing = app(BillingService::class);
        $invoice = $billing->createForQueue($data['queue'], 0, null, $cashier->id);
        $billing->recordPayment($invoice, 50000, PaymentMethod::Cash, null, $cashier->id);

        $this->assertPagesRender([
            route('cashier.dashboard'),
            route('cashier.invoices.index'),
            route('cashier.invoices.pending'),
            route('cashier.invoices.show', $invoice),
            route('cashier.invoices.receipt', $invoice),
            route('cashier.payments.index'),
        ]);
    }

    public function test_invoice_contains_consultation_and_medicine_minus_discount(): void
    {
        $this->actingAs($this->cashier());

        $data = $this->billableVisit(medicinePrice: 20000, quantity: 2);

        // Subtotal = 75.000 konsultasi + (2 x 20.000) obat = 115.000
        // Potongan 15.000 -> total 100.000
        $response = $this->post(route('cashier.invoices.store'), [
            'queue_id' => $data['queue']->id,
            'discount' => 15000,
            'notes' => 'Potongan promo keluarga.',
        ]);

        $invoice = Invoice::query()->firstOrFail();

        $response->assertRedirect(route('cashier.invoices.show', $invoice));
        $response->assertSessionHas('success');

        $this->assertEqualsWithDelta(self::CONSULTATION_FEE + 40000, (float) $invoice->subtotal, 0.01);
        $this->assertEqualsWithDelta(15000, (float) $invoice->discount, 0.01);
        $this->assertEqualsWithDelta(100000, (float) $invoice->total, 0.01);
        $this->assertSame(InvoiceStatus::Unpaid, $invoice->status);
        $this->assertStringStartsWith('INV-', $invoice->code);

        $items = $invoice->items()->get();
        $this->assertCount(2, $items);

        $consultation = $items->firstWhere('type', 'consultation');
        $this->assertNotNull($consultation);
        $this->assertEqualsWithDelta(self::CONSULTATION_FEE, (float) $consultation->subtotal, 0.01);

        $medicine = $items->firstWhere('type', 'medicine');
        $this->assertNotNull($medicine);
        $this->assertSame(2, (int) $medicine->quantity);
        $this->assertEqualsWithDelta(40000, (float) $medicine->subtotal, 0.01);
    }

    public function test_partial_then_full_payment_updates_status(): void
    {
        $cashier = $this->cashier();
        $this->actingAs($cashier);

        $data = $this->billableVisit();
        $invoice = app(BillingService::class)->createForQueue($data['queue'], 0, null, $cashier->id);

        $this->assertEqualsWithDelta(115000, (float) $invoice->total, 0.01);

        // DP 50.000
        $this->post(route('cashier.invoices.payments.store', $invoice), [
            'amount' => 50000,
            'method' => PaymentMethod::Cash->value,
        ])->assertRedirect(route('cashier.invoices.show', $invoice))
            ->assertSessionHas('success');

        $invoice->refresh();
        $this->assertSame(InvoiceStatus::Partial, $invoice->status);
        $this->assertEqualsWithDelta(50000, (float) $invoice->paid_amount, 0.01);
        $this->assertEqualsWithDelta(65000, $invoice->remainingAmount(), 0.01);
        $this->assertNull($invoice->paid_at);

        // Pelunasan sisa 65.000
        $this->post(route('cashier.invoices.payments.store', $invoice), [
            'amount' => 65000,
            'method' => PaymentMethod::Transfer->value,
        ])->assertRedirect(route('cashier.invoices.show', $invoice))
            ->assertSessionHas('success');

        $invoice->refresh();
        $this->assertSame(InvoiceStatus::Paid, $invoice->status);
        $this->assertEqualsWithDelta(115000, (float) $invoice->paid_amount, 0.01);
        $this->assertEqualsWithDelta(0, $invoice->remainingAmount(), 0.01);
        $this->assertNotNull($invoice->paid_at);
        $this->assertDatabaseCount('payments', 2);
    }

    public function test_overpayment_is_rejected(): void
    {
        $cashier = $this->cashier();
        $this->actingAs($cashier);

        $data = $this->billableVisit();
        $invoice = app(BillingService::class)->createForQueue($data['queue'], 0, null, $cashier->id);

        $this->post(route('cashier.invoices.payments.store', $invoice), [
            'amount' => (int) $invoice->total + 5000,
            'method' => PaymentMethod::Cash->value,
        ])->assertSessionHas('error');

        $invoice->refresh();
        $this->assertSame(InvoiceStatus::Unpaid, $invoice->status);
        $this->assertEqualsWithDelta(0, (float) $invoice->paid_amount, 0.01);
        $this->assertSame(0, Payment::query()->count());
    }

    public function test_duplicate_invoice_for_the_same_visit_is_rejected(): void
    {
        $cashier = $this->cashier();
        $this->actingAs($cashier);

        $data = $this->billableVisit();
        app(BillingService::class)->createForQueue($data['queue'], 0, null, $cashier->id);

        $this->post(route('cashier.invoices.store'), [
            'queue_id' => $data['queue']->id,
            'discount' => 0,
        ])->assertSessionHas('error');

        $this->assertDatabaseCount('invoices', 1);
    }

    public function test_cancelled_invoice_cannot_receive_payment(): void
    {
        $cashier = $this->cashier();
        $this->actingAs($cashier);

        $data = $this->billableVisit();
        $invoice = app(BillingService::class)->createForQueue($data['queue'], 0, null, $cashier->id);

        $this->post(route('cashier.invoices.cancel', $invoice), [
            'reason' => 'Salah input kunjungan.',
        ])->assertRedirect(route('cashier.invoices.show', $invoice))
            ->assertSessionHas('success');

        $invoice->refresh();
        $this->assertSame(InvoiceStatus::Cancelled, $invoice->status);

        $this->post(route('cashier.invoices.payments.store', $invoice), [
            'amount' => 50000,
            'method' => PaymentMethod::Cash->value,
        ])->assertSessionHas('error');

        $this->assertSame(0, Payment::query()->count());
        $this->assertEqualsWithDelta(0, (float) $invoice->fresh()->paid_amount, 0.01);
    }

    public function test_patient_only_sees_own_invoices(): void
    {
        $user = User::factory()->patient()->create();
        $patient = Patient::factory()->create(['user_id' => $user->id]);
        $this->actingAs($user);

        $own = Invoice::factory()->create(['patient_id' => $patient->id]);
        $other = Invoice::factory()->create(['patient_id' => Patient::factory()->create()->id]);

        $this->get(route('patient.invoices.show', $own))->assertOk();

        $this->get(route('patient.invoices.show', $other))->assertForbidden();

        $this->get(route('patient.invoices.index'))
            ->assertOk()
            ->assertSee($own->code)
            ->assertDontSee($other->code);
    }
}

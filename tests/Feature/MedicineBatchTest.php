<?php

namespace Tests\Feature;

use App\Models\Medicine;
use App\Models\MedicineBatch;
use App\Models\MedicineTransaction;
use App\Models\Pharmacist;
use App\Models\Prescription;
use App\Models\PrescriptionDetail;
use App\Models\User;
use App\Services\MedicineService;
use App\Services\PrescriptionService;
use RuntimeException;

class MedicineBatchTest extends SmokeTestCase
{
    private function pharmacist(): User
    {
        $user = User::factory()->pharmacist()->create();
        Pharmacist::factory()->create(['user_id' => $user->id]);
        $this->actingAs($user);

        return $user;
    }

    public function test_stock_in_records_batch_and_updates_summary_expiry(): void
    {
        $this->pharmacist();
        $medicine = Medicine::factory()->create(['stock' => 0, 'expiry_date' => null]);
        $expiry = now()->addDays(10)->toDateString();

        $this->post(route('pharmacy.stock.in', $medicine), [
            'quantity' => 20,
            'batch_number' => 'BATCH-A',
            'expiry_date' => $expiry,
            'notes' => 'Pembelian supplier',
        ])->assertRedirect();

        $this->assertSame(20, $medicine->fresh()->stock);
        $this->assertSame($expiry, $medicine->fresh()->expiry_date?->toDateString());

        $batch = MedicineBatch::query()->firstOrFail();
        $this->assertSame('BATCH-A', $batch->batch_number);
        $this->assertSame(20, $batch->quantity);
        $this->assertSame($expiry, $batch->expiry_date?->toDateString());

        $transaction = MedicineTransaction::query()->firstOrFail();
        $this->assertSame($batch->id, $transaction->batch_id);
        $this->assertStringContainsString('BATCH-A', (string) $transaction->notes);

        $this->post(route('pharmacy.stock.in', $medicine), [
            'quantity' => 5,
            'batch_number' => 'BATCH-A',
            'expiry_date' => $expiry,
        ])->assertRedirect();

        $this->assertSame(25, $medicine->fresh()->stock);
        $this->assertSame(25, $batch->fresh()->quantity);
        $this->assertSame(1, MedicineBatch::query()->count());

        $this->post(route('pharmacy.stock.in', $medicine), [
            'quantity' => 2,
            'batch_number' => 'BATCH-A',
            'expiry_date' => now()->addDays(20)->toDateString(),
        ])->assertSessionHas('error');

        $this->assertSame(1, MedicineBatch::query()->count());
    }

    public function test_stock_in_rejects_expired_batch_and_incomplete_batch_data(): void
    {
        $this->pharmacist();
        $medicine = Medicine::factory()->create(['stock' => 0]);

        $this->post(route('pharmacy.stock.in', $medicine), [
            'quantity' => 5,
            'batch_number' => 'EXPIRED',
            'expiry_date' => now()->subDay()->toDateString(),
        ])->assertSessionHas('error');

        $this->post(route('pharmacy.stock.in', $medicine), [
            'quantity' => 5,
            'batch_number' => 'NO-EXPIRY',
        ])->assertSessionHasErrors('expiry_date');

        $this->assertSame(0, $medicine->fresh()->stock);
        $this->assertDatabaseCount('medicine_batches', 0);
    }

    public function test_dispense_follows_fefo_and_records_each_batch(): void
    {
        $medicine = Medicine::factory()->create(['stock' => 30, 'expiry_date' => null]);
        $soon = MedicineBatch::factory()->for($medicine)->create([
            'batch_number' => 'SOON',
            'quantity' => 10,
            'expiry_date' => now()->addDays(3)->toDateString(),
        ]);
        $later = MedicineBatch::factory()->for($medicine)->create([
            'batch_number' => 'LATER',
            'quantity' => 20,
            'expiry_date' => now()->addDays(120)->toDateString(),
        ]);

        app(MedicineService::class)->dispense($medicine, 15, 123);

        $this->assertSame(0, $soon->fresh()->quantity);
        $this->assertSame(15, $later->fresh()->quantity);
        $this->assertSame(15, $medicine->fresh()->stock);
        $this->assertTrue($medicine->fresh()->expiry_date->isSameDay($later->expiry_date));

        $out = MedicineTransaction::query()->where('type', 'OUT')->orderBy('id')->get();
        $this->assertCount(2, $out);
        $this->assertSame($soon->id, $out[0]->batch_id);
        $this->assertSame(-10, $out[0]->quantity);
        $this->assertSame($later->id, $out[1]->batch_id);
        $this->assertSame(-5, $out[1]->quantity);
        $this->assertSame(15, $out->last()->stock_after);
    }

    public function test_expired_batch_is_rejected_for_sales_and_reported_clearly(): void
    {
        $medicine = Medicine::factory()->create(['stock' => 8, 'expiry_date' => null]);
        $batch = MedicineBatch::factory()->for($medicine)->expired()->create([
            'batch_number' => 'EXPIRED-1',
            'quantity' => 8,
        ]);

        try {
            app(MedicineService::class)->stockOut($medicine, 3, 'Penjualan bebas');
            $this->fail('Batch kedaluwarsa tidak boleh dipakai.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('kedaluwarsa', $e->getMessage());
        }

        $this->assertSame(8, $medicine->fresh()->stock);
        $this->assertSame(8, $batch->fresh()->quantity);
        $this->assertDatabaseCount('medicine_transactions', 0);
    }

    public function test_expired_and_expiring_batches_drive_warnings_and_filters(): void
    {
        $user = $this->pharmacist();

        $expiredMedicine = Medicine::factory()->create(['expiry_date' => null]);
        $expiredBatch = MedicineBatch::factory()->for($expiredMedicine)->expired()->create([
            'batch_number' => 'WARN-EXPIRED',
            'quantity' => 3,
        ]);
        $soonMedicine = Medicine::factory()->create(['expiry_date' => null]);
        $soonBatch = MedicineBatch::factory()->for($soonMedicine)->expiring(5)->create([
            'batch_number' => 'WARN-SOON',
            'quantity' => 4,
        ]);

        $this->get(route('pharmacy.dashboard'))
            ->assertOk()
            ->assertSee('Batch Kedaluwarsa');

        $this->get(route('pharmacy.medicines.index', ['expiry' => 'expired']))
            ->assertOk()
            ->assertSee($expiredMedicine->name)
            ->assertDontSee($soonMedicine->name);

        $this->get(route('pharmacy.batches.index'))
            ->assertOk()
            ->assertSee($expiredBatch->batch_number)
            ->assertSee($soonBatch->batch_number);

        $expiredBatch->update(['quantity' => 0]);

        $this->get(route('pharmacy.dashboard'))
            ->assertOk()
            ->assertSee('Batch mendekati kedaluwarsa');

        $this->assertSame($user->id, auth()->id());
    }

    public function test_expired_batch_can_be_disposed_and_logged(): void
    {
        $user = $this->pharmacist();
        $medicine = Medicine::factory()->create(['stock' => 10, 'expiry_date' => null]);
        $batch = MedicineBatch::factory()->for($medicine)->expired()->create([
            'batch_number' => 'DISPOSE-1',
            'quantity' => 10,
        ]);

        $this->delete(route('pharmacy.batches.dispose', $batch))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame(0, $batch->fresh()->quantity);
        $this->assertSame(0, $medicine->fresh()->stock);

        $this->assertDatabaseHas('medicine_transactions', [
            'medicine_id' => $medicine->id,
            'batch_id' => $batch->id,
            'type' => 'OUT',
            'quantity' => -10,
            'reference_type' => 'medicine_batch',
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'module' => 'pharmacy',
            'action' => 'delete',
        ]);

        $safeBatch = MedicineBatch::factory()->for($medicine)->expiring(90)->create(['quantity' => 2]);
        $medicine->update(['stock' => 2]);

        $this->delete(route('pharmacy.batches.dispose', $safeBatch))->assertSessionHas('error');
        $this->assertSame(2, $safeBatch->fresh()->quantity);
    }

    public function test_initial_stock_creates_a_batch(): void
    {
        $this->pharmacist();

        $this->post(route('pharmacy.medicines.store'), [
            'medicine_code' => 'BATCH-MED',
            'name' => 'Obat Uji Batch',
            'category' => 'Uji',
            'unit' => 'Tablet',
            'stock' => 12,
            'minimum_stock' => 2,
            'price' => 15000,
            'batch_number' => 'AWAL-001',
            'expiry_date' => now()->addDays(60)->toDateString(),
            'status' => 'active',
        ])->assertRedirect();

        $medicine = Medicine::query()->where('medicine_code', 'BATCH-MED')->firstOrFail();
        $this->assertSame(12, $medicine->stock);
        $this->assertDatabaseHas('medicine_batches', [
            'medicine_id' => $medicine->id,
            'batch_number' => 'AWAL-001',
            'quantity' => 12,
        ]);
    }

    public function test_cancelling_prescription_restores_consumed_batch_stock(): void
    {
        $medicine = Medicine::factory()->create(['stock' => 10, 'expiry_date' => null]);
        $batch = MedicineBatch::factory()->for($medicine)->create([
            'batch_number' => 'REFUND-1',
            'quantity' => 10,
            'expiry_date' => now()->addDays(30)->toDateString(),
        ]);
        $prescription = Prescription::factory()->ready()->create();
        PrescriptionDetail::factory()->create([
            'prescription_id' => $prescription->id,
            'medicine_id' => $medicine->id,
            'quantity' => 4,
        ]);

        $service = app(PrescriptionService::class);
        $service->dispense($prescription);

        $this->assertSame(6, $batch->fresh()->quantity);
        $this->assertSame(6, $medicine->fresh()->stock);

        $service->cancel($prescription, 'Salah obat');

        $this->assertSame(10, $batch->fresh()->quantity);
        $this->assertSame(10, $medicine->fresh()->stock);
        $this->assertDatabaseHas('medicine_transactions', [
            'medicine_id' => $medicine->id,
            'batch_id' => $batch->id,
            'type' => 'IN',
            'quantity' => 4,
            'reference_type' => Prescription::class,
        ]);
    }
}

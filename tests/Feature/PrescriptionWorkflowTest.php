<?php

namespace Tests\Feature;

use App\Enums\PrescriptionStatus;
use App\Models\Doctor;
use App\Models\Medicine;
use App\Models\Patient;
use App\Models\Pharmacist;
use App\Models\Prescription;
use App\Models\PrescriptionDetail;
use App\Models\User;

class PrescriptionWorkflowTest extends SmokeTestCase
{
    private function pharmacist(): User
    {
        $user = User::factory()->pharmacist()->create();
        Pharmacist::factory()->create(['user_id' => $user->id]);

        return $user;
    }

    /**
     * Resep berstatus PENDING dengan satu item obat.
     *
     * @return array{0: Prescription, 1: Medicine}
     */
    private function prescription(int $stock = 100, int $quantity = 10): array
    {
        $medicine = Medicine::factory()->create(['stock' => $stock, 'price' => 10000]);

        $prescription = Prescription::factory()->create([
            'patient_id' => Patient::factory()->create()->id,
            'doctor_id' => Doctor::factory()->create()->id,
            'status' => PrescriptionStatus::Pending,
        ]);

        PrescriptionDetail::factory()->create([
            'prescription_id' => $prescription->id,
            'medicine_id' => $medicine->id,
            'quantity' => $quantity,
            'price' => 10000,
            'subtotal' => 10000 * $quantity,
        ]);

        return [$prescription, $medicine];
    }

    public function test_full_happy_path_process_ready_complete(): void
    {
        $this->actingAs($this->pharmacist());

        [$prescription, $medicine] = $this->prescription();

        $this->post(route('pharmacy.prescriptions.process', $prescription))->assertRedirect();
        $this->assertSame(PrescriptionStatus::Processing, $prescription->fresh()->status);

        $this->post(route('pharmacy.prescriptions.ready', $prescription))->assertRedirect();
        $this->assertSame(PrescriptionStatus::Ready, $prescription->fresh()->status);

        // Stok hanya berubah saat obat diserahkan.
        $this->assertSame(100, $medicine->fresh()->stock);

        $this->post(route('pharmacy.prescriptions.complete', $prescription))->assertRedirect();
        $this->assertSame(PrescriptionStatus::Completed, $prescription->fresh()->status);
        $this->assertSame(90, $medicine->fresh()->stock);

        $this->assertDatabaseHas('medicine_transactions', [
            'medicine_id' => $medicine->id,
            'type' => 'OUT',
            'quantity' => -10,
            'stock_before' => 100,
            'stock_after' => 90,
        ]);
    }

    public function test_pending_cannot_skip_straight_to_ready(): void
    {
        $this->actingAs($this->pharmacist());

        [$prescription, $medicine] = $this->prescription();

        $this->post(route('pharmacy.prescriptions.ready', $prescription))->assertSessionHas('error');

        $this->assertSame(PrescriptionStatus::Pending, $prescription->fresh()->status);
        $this->assertSame(100, $medicine->fresh()->stock);
    }

    public function test_cancelling_prescription_never_touches_stock(): void
    {
        $this->actingAs($this->pharmacist());

        [$prescription, $medicine] = $this->prescription();

        $this->post(route('pharmacy.prescriptions.cancel', $prescription))->assertRedirect();

        $this->assertSame(PrescriptionStatus::Cancelled, $prescription->fresh()->status);
        $this->assertSame(100, $medicine->fresh()->stock);
        $this->assertDatabaseCount('medicine_transactions', 0);
    }

    public function test_completed_prescription_cannot_be_processed_again(): void
    {
        $this->actingAs($this->pharmacist());

        [$prescription, $medicine] = $this->prescription();

        foreach (['process', 'ready', 'complete'] as $action) {
            $this->post(route("pharmacy.prescriptions.{$action}", $prescription));
        }

        $this->assertSame(90, $medicine->fresh()->stock);

        // Attempt kedua tidak boleh mengubah stok lagi.
        $this->post(route('pharmacy.prescriptions.process', $prescription))->assertSessionHas('error');
        $this->post(route('pharmacy.prescriptions.complete', $prescription))->assertSessionHas('error');

        $this->assertSame(90, $medicine->fresh()->stock);
        $this->assertDatabaseCount('medicine_transactions', 1);
    }

    public function test_processing_is_blocked_when_stock_is_insufficient(): void
    {
        $this->actingAs($this->pharmacist());

        [$prescription, $medicine] = $this->prescription(stock: 5, quantity: 10);

        $this->post(route('pharmacy.prescriptions.process', $prescription))->assertSessionHas('error');

        $this->assertSame(PrescriptionStatus::Pending, $prescription->fresh()->status);
        $this->assertSame(5, $medicine->fresh()->stock);
    }

    public function test_cancelled_prescription_cannot_be_completed(): void
    {
        $this->actingAs($this->pharmacist());

        [$prescription, $medicine] = $this->prescription();

        $this->post(route('pharmacy.prescriptions.cancel', $prescription));
        $this->post(route('pharmacy.prescriptions.complete', $prescription))->assertSessionHas('error');

        $this->assertSame(100, $medicine->fresh()->stock);
    }
}

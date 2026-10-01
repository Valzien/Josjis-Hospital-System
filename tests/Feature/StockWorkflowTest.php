<?php

namespace Tests\Feature;

use App\Models\Medicine;
use App\Models\Pharmacist;
use App\Models\User;

class StockWorkflowTest extends SmokeTestCase
{
    private function pharmacist(): User
    {
        $user = User::factory()->pharmacist()->create();
        Pharmacist::factory()->create(['user_id' => $user->id]);

        return $user;
    }

    public function test_stock_in_increases_quantity(): void
    {
        $this->actingAs($this->pharmacist());

        $medicine = Medicine::factory()->create(['stock' => 20]);

        $this->post(route('pharmacy.stock.in', $medicine), [
            'quantity' => 30,
            'notes' => 'Pembelian',
        ])->assertRedirect();

        $this->assertSame(50, $medicine->fresh()->stock);

        $this->assertDatabaseHas('medicine_transactions', [
            'medicine_id' => $medicine->id,
            'type' => 'IN',
            'quantity' => 30,
            'stock_before' => 20,
            'stock_after' => 50,
        ]);
    }

    public function test_stock_out_cannot_go_below_zero(): void
    {
        $this->actingAs($this->pharmacist());

        $medicine = Medicine::factory()->create(['stock' => 5]);

        $this->post(route('pharmacy.stock.out', $medicine), [
            'quantity' => 10,
            'notes' => 'Rusak',
        ]);

        $this->assertSame(5, $medicine->fresh()->stock);
        $this->assertDatabaseMissing('medicine_transactions', [
            'medicine_id' => $medicine->id,
            'type' => 'OUT',
        ]);
    }

    public function test_adjustment_sets_absolute_stock(): void
    {
        $this->actingAs($this->pharmacist());

        $medicine = Medicine::factory()->create(['stock' => 20]);

        $this->post(route('pharmacy.stock.adjust', $medicine), [
            'new_stock' => 17,
            'notes' => 'Stock opname',
        ])->assertRedirect();

        $this->assertSame(17, $medicine->fresh()->stock);

        $this->assertDatabaseHas('medicine_transactions', [
            'medicine_id' => $medicine->id,
            'type' => 'ADJUSTMENT',
            'stock_before' => 20,
            'stock_after' => 17,
        ]);
    }
}

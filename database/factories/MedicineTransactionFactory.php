<?php

namespace Database\Factories;

use App\Enums\TransactionType;
use App\Models\Medicine;
use App\Models\MedicineTransaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MedicineTransaction>
 */
class MedicineTransactionFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        $quantity = fake()->numberBetween(5, 50);
        $stockBefore = fake()->numberBetween(50, 200);

        return [
            'medicine_id' => Medicine::factory(),
            'type' => TransactionType::In->value,
            'quantity' => $quantity,
            'stock_before' => $stockBefore,
            'stock_after' => $stockBefore + $quantity,
            'reference_type' => 'manual',
            'reference_id' => null,
            'notes' => 'Barang masuk dari supplier.',
            'user_id' => User::factory()->pharmacist(),
            'created_at' => now()->subDays(fake()->numberBetween(0, 20)),
            'updated_at' => now()->subDays(fake()->numberBetween(0, 20)),
        ];
    }

    public function in(int $quantity, int $stockBefore = 0): static
    {
        return $this->state(fn () => [
            'type' => TransactionType::In->value,
            'quantity' => abs($quantity),
            'stock_before' => $stockBefore,
            'stock_after' => $stockBefore + abs($quantity),
            'notes' => 'Barang masuk.',
        ]);
    }

    public function out(int $quantity, int $stockBefore): static
    {
        return $this->state(fn () => [
            'type' => TransactionType::Out->value,
            'quantity' => -abs($quantity),
            'stock_before' => $stockBefore,
            'stock_after' => max(0, $stockBefore - abs($quantity)),
            'notes' => 'Barang keluar.',
        ]);
    }

    public function adjustment(int $from, int $to): static
    {
        return $this->state(fn () => [
            'type' => TransactionType::Adjustment->value,
            'quantity' => $to - $from,
            'stock_before' => $from,
            'stock_after' => $to,
            'notes' => 'Penyesuaian hasil opname.',
        ]);
    }
}

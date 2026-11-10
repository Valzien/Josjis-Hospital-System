<?php

namespace Database\Factories;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InvoiceItem>
 */
class InvoiceItemFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        $price = $this->faker->randomElement([15000, 25000, 75000, 100000]);
        $quantity = $this->faker->numberBetween(1, 3);

        return [
            'invoice_id' => Invoice::factory(),
            'type' => 'consultation',
            'description' => 'Biaya konsultasi dokter',
            'quantity' => $quantity,
            'unit_price' => $price,
            'subtotal' => $price * $quantity,
            'reference_type' => null,
            'reference_id' => null,
        ];
    }

    public function medicine(): static
    {
        return $this->state(fn () => ['type' => 'medicine', 'description' => 'Obat resep']);
    }
}

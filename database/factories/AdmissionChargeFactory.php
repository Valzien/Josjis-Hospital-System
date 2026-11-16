<?php

namespace Database\Factories;

use App\Enums\AdmissionChargeType;
use App\Models\Admission;
use App\Models\AdmissionCharge;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AdmissionCharge>
 */
class AdmissionChargeFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        $price = fake()->randomElement([35000, 45000, 85000, 120000]);
        $quantity = fake()->numberBetween(1, 3);

        return [
            'admission_id' => Admission::factory(),
            'type' => AdmissionChargeType::Service->value,
            'service_id' => null,
            'description' => fake()->randomElement(['Nebulisasi', 'Pemasangan Infus', 'Perawatan Luka']),
            'quantity' => $quantity,
            'unit_price' => $price,
            'subtotal' => $price * $quantity,
            'charged_at' => now(),
            'created_by' => null,
        ];
    }

    public function room(): static
    {
        return $this->state(fn () => ['type' => AdmissionChargeType::Room->value]);
    }
}

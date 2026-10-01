<?php

namespace Database\Factories;

use App\Models\Medicine;
use App\Models\Prescription;
use App\Models\PrescriptionDetail;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PrescriptionDetail>
 */
class PrescriptionDetailFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        $medicine = Medicine::factory();
        $price = fake()->randomElement([5000, 7500, 12000, 18500, 25000]);
        $quantity = fake()->numberBetween(1, 3);

        return [
            'prescription_id' => Prescription::factory(),
            'medicine_id' => $medicine,
            'quantity' => $quantity,
            'dosage' => fake()->randomElement([
                '500 mg',
                '400 mg',
                '250 mg',
                '10 mg',
                '5 mg',
                '1 tablet',
            ]),
            'instructions' => fake()->randomElement([
                'Diminum tiga kali sehari setelah makan.',
                'Diminum dua kali sehari sesudah makan.',
                'Diminum sekali sehari pada malam hari.',
                'Diminum saat gejala muncul.',
                'Diminum sebelum tidur.',
            ]),
            'price' => $price,
            'subtotal' => $price * $quantity,
        ];
    }
}

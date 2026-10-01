<?php

namespace Database\Factories;

use App\Enums\ActiveStatus;
use App\Models\Medicine;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Medicine>
 */
class MedicineFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        $name = fake()->randomElement([
            'Amoksisilin 500 mg',
            'Paracetamol 500 mg',
            'Ibuprofen 400 mg',
            'Metronidazol 500 mg',
            'Cetirizine 10 mg',
            'Amoxicillin Dry Syrup',
            'Isosorbid Mononitrat 5 mg',
            'Salbutamol Inhaler',
            'Omeprazol 20 mg',
            'Dexamethasone 0,5 mg',
            'Vitamin C 500 mg',
            'ORS Sachet',
            'Betadine 100 ml',
            'Cetirizine Sirup 60 ml',
            'Insulin Glargine 100 IU/ml',
        ]);

        return [
            'medicine_code' => $this->faker->unique()->numerify('M-####'),
            'name' => $name,
            'category' => fake()->randomElement([
                'Antibiotik',
                'Analgetik',
                'Antihistamin',
                'Antasid',
                'Inhaler',
                'Vitamin',
                'Antiseptik',
                'Insulin',
            ]),
            'unit' => fake()->randomElement(['Tablet', 'Kapsul', 'Sirup', 'Botol', 'Sachet', 'Inhaler', 'Vial']),
            'stock' => fake()->numberBetween(0, 500),
            'minimum_stock' => fake()->numberBetween(10, 50),
            'price' => fake()->randomElement([2500, 5000, 7500, 12000, 18500, 25000, 42000, 78000]),
            'description' => fake()->optional()->sentence(12),
            'status' => ActiveStatus::Active->value,
        ];
    }

    public function lowStock(int $minimum = 20): static
    {
        return $this->state(fn () => [
            'minimum_stock' => $minimum,
            'stock' => fake()->numberBetween(1, max(1, $minimum - 1)),
        ]);
    }

    public function outOfStock(): static
    {
        return $this->state(fn () => ['stock' => 0]);
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['status' => ActiveStatus::Inactive->value]);
    }
}

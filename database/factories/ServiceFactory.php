<?php

namespace Database\Factories;

use App\Enums\ActiveStatus;
use App\Models\Service;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Service>
 */
class ServiceFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'code' => 'SVC-'.$this->faker->unique()->numerify('####'),
            'name' => fake()->randomElement([
                'Pemeriksaan Umum',
                'Pemeriksaan Fisik',
                'Nebulisasi',
                'Pemasangan Infus',
                'Perawatan Luka Ringan',
                'Pemeriksaan Darah Lengkap',
                'Rontgen Thorax',
                'EKG',
                'USG Abdomen',
                'Suntik Intramuskular',
            ]),
            'category' => fake()->randomElement(['Tindakan Medis', 'Laboratorium', 'Radiologi', 'Keperawatan']),
            'unit' => fake()->randomElement(['Tindakan', 'Kali', 'Pemeriksaan']),
            'price' => fake()->randomElement([25000, 45000, 65000, 85000, 120000, 175000]),
            'status' => ActiveStatus::Active->value,
            'description' => fake()->optional()->sentence(8),
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['status' => ActiveStatus::Inactive->value]);
    }
}

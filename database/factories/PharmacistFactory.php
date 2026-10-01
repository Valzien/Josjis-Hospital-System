<?php

namespace Database\Factories;

use App\Enums\ActiveStatus;
use App\Models\Pharmacist;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Pharmacist>
 */
class PharmacistFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        $name = fake()->name();

        return [
            'user_id' => User::factory()->pharmacist(),
            'name' => $name,
            'phone' => fake()->numerify('08##########'),
            'license_number' => 'AP-'.fake()->unique()->numerify('#####'),
            'status' => ActiveStatus::Active->value,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['status' => ActiveStatus::Inactive->value]);
    }
}

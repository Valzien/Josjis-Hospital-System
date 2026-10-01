<?php

namespace Database\Factories;

use App\Enums\ActiveStatus;
use App\Models\Receptionist;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Receptionist>
 */
class ReceptionistFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        $name = fake()->name();

        return [
            'user_id' => User::factory()->receptionist(),
            'name' => $name,
            'phone' => fake()->numerify('08##########'),
            'shift' => fake()->randomElement(['Pagi', 'Siang', 'Sore', 'Malam']),
            'status' => ActiveStatus::Active->value,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['status' => ActiveStatus::Inactive->value]);
    }
}

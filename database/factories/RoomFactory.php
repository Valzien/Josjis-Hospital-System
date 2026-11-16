<?php

namespace Database\Factories;

use App\Enums\ActiveStatus;
use App\Enums\RoomClass;
use App\Models\Room;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Room>
 */
class RoomFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'code' => 'RM-'.$this->faker->unique()->numerify('####'),
            'name' => 'Ruang '.fake()->randomElement(['Melati', 'Mawar', 'Anggrek', 'Kenanga', 'Dahlia']),
            'class' => fake()->randomElement(RoomClass::cases())->value,
            'floor' => (string) fake()->numberBetween(1, 5),
            'rate_per_day' => fake()->randomElement([175000, 300000, 450000, 750000]),
            'status' => ActiveStatus::Active->value,
            'notes' => null,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['status' => ActiveStatus::Inactive->value]);
    }
}

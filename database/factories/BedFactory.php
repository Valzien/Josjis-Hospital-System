<?php

namespace Database\Factories;

use App\Enums\BedStatus;
use App\Models\Bed;
use App\Models\Room;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Bed>
 */
class BedFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'room_id' => Room::factory(),
            'code' => strtoupper($this->faker->unique()->bothify('?#')),
            'status' => BedStatus::Available->value,
            'notes' => null,
        ];
    }

    public function occupied(): static
    {
        return $this->state(fn () => ['status' => BedStatus::Occupied->value]);
    }

    public function maintenance(): static
    {
        return $this->state(fn () => ['status' => BedStatus::Maintenance->value]);
    }
}

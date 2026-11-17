<?php

namespace Database\Factories;

use App\Models\Medicine;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MedicineBatch>
 */
class MedicineBatchFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'medicine_id' => Medicine::factory(),
            'batch_number' => 'BATCH-'.fake()->unique()->numerify('####'),
            'quantity' => fake()->numberBetween(1, 60),
            'expiry_date' => fake()->boolean(80)
                ? fake()->dateTimeBetween('now', '+18 months')->format('Y-m-d')
                : null,
            'notes' => 'Batch penerimaan supplier.',
        ];
    }

    public function expiring(?int $days = 7): static
    {
        return $this->state(fn () => [
            'expiry_date' => now()->addDays($days ?? 7)->toDateString(),
        ]);
    }

    public function expired(?int $days = 7): static
    {
        return $this->state(fn () => [
            'expiry_date' => now()->subDays($days ?? 7)->toDateString(),
        ]);
    }
}

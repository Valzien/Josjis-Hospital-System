<?php

namespace Database\Factories;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AuditLog>
 */
class AuditLogFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'action' => fake()->randomElement(['created', 'updated', 'deleted']),
            'module' => fake()->randomElement(['patient', 'queue', 'prescription', 'pharmacy', 'user']),
            'description' => fake()->sentence(8),
            'ip_address' => fake()->ipv4(),
            'user_agent' => fake()->userAgent(),
            'properties' => null,
            'created_at' => now()->subDays(fake()->numberBetween(0, 30)),
            'updated_at' => now()->subDays(fake()->numberBetween(0, 30)),
        ];
    }
}

<?php

namespace Database\Factories;

use App\Enums\ActiveStatus;
use App\Models\Doctor;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Doctor>
 */
class DoctorFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        $name = fake()->name();

        return [
            'user_id' => null,
            'doctor_code' => $this->faker->unique()->numerify('DR-####'),
            'name' => $name,
            'specialization' => fake()->randomElement([
                'Penyakit Dalam',
                'Penyakit Anak',
                'Kandungan',
                'Saraf',
                'Gigi',
                'Kulit',
                'Mata',
                'ENT',
            ]),
            'phone' => fake()->numerify('08##########'),
            'email' => fake()->unique()->safeEmail(),
            'bio' => fake()->optional()->paragraph(),
            'status' => ActiveStatus::Active->value,
        ];
    }

    public function withAccount(?string $email = null): static
    {
        return $this->state(function (array $attributes) use ($email) {
            $user = User::factory()->doctor()->create([
                'name' => $attributes['name'],
                'email' => $email ?? 'doctor'.fake()->unique()->numberBetween(1, 999999).'@example.test',
            ]);

            return ['user_id' => $user->id];
        });
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['status' => ActiveStatus::Inactive->value]);
    }
}

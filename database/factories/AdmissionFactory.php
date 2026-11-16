<?php

namespace Database\Factories;

use App\Enums\AdmissionStatus;
use App\Models\Admission;
use App\Models\Bed;
use App\Models\Doctor;
use App\Models\Patient;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Admission>
 */
class AdmissionFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        $admittedAt = now()->subDays(fake()->numberBetween(1, 4));

        return [
            'code' => 'ADM-'.$this->faker->unique()->numerify('####'),
            'patient_id' => Patient::factory(),
            'doctor_id' => Doctor::factory(),
            'room_id' => null,
            'bed_id' => Bed::factory(),
            'status' => AdmissionStatus::Active->value,
            'admitted_at' => $admittedAt,
            'discharged_at' => null,
            'complaint' => fake()->optional()->sentence(10),
            'diagnosis' => fake()->optional()->sentence(6),
            'notes' => null,
            'discharge_notes' => null,
            'created_by' => null,
        ];
    }

    public function discharged(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => AdmissionStatus::Discharged->value,
            'discharged_at' => now(),
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn () => ['status' => AdmissionStatus::Cancelled->value]);
    }
}

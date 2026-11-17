<?php

namespace Database\Factories;

use App\Models\Doctor;
use App\Models\DoctorLeave;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DoctorLeave>
 */
class DoctorLeaveFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'doctor_id' => Doctor::factory(),
            'date' => now()->addDays(3)->startOfDay(),
            'reason' => fake()->optional()->randomElement([
                'Cuti tahunan',
                'Seminar',
                'Tugas pendidikan',
            ]),
            'created_by' => null,
        ];
    }

    public function forDoctor(Doctor $doctor): static
    {
        return $this->state(fn () => ['doctor_id' => $doctor->id]);
    }

    public function onDate(mixed $date): static
    {
        return $this->state(fn () => ['date' => $date]);
    }
}

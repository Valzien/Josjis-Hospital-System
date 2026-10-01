<?php

namespace Database\Factories;

use App\Enums\ActiveStatus;
use App\Enums\Day;
use App\Models\Doctor;
use App\Models\DoctorSchedule;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DoctorSchedule>
 */
class DoctorScheduleFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        $start = fake()->randomElement(['08:00', '09:00', '10:00', '13:00', '15:00']);
        $end = fake()->randomElement(['11:00', '12:00', '14:00', '17:00', '18:00']);

        return [
            'doctor_id' => Doctor::factory(),
            'day' => fake()->randomElement([
                Day::Monday->value,
                Day::Tuesday->value,
                Day::Wednesday->value,
                Day::Thursday->value,
                Day::Friday->value,
            ]),
            'start_time' => $start,
            'end_time' => $end,
            'room' => 'Ruang '.fake()->randomElement(['Konsultasi 1', 'Konsultasi 2', 'Konsultasi 3', 'Klinik Anak', 'IGD']),
            'quota' => fake()->randomElement([20, 25, 30, 40]),
            'status' => ActiveStatus::Active->value,
        ];
    }

    /** Jadwal untuk hari ini. */
    public function today(?int $dayOfWeek = null): static
    {
        return $this->state(fn () => [
            'day' => $dayOfWeek ?? now()->dayOfWeek,
        ]);
    }

    /** Jadwal dengan jam layanan mencakup seluruh hari ini (agar selalu bisa ambil antrean). */
    public function openAllDay(): static
    {
        return $this->state(fn () => [
            'day' => now()->dayOfWeek,
            'start_time' => '00:00:00',
            'end_time' => '23:59:59',
            'status' => ActiveStatus::Active->value,
        ]);
    }

    public function on(Day $day): static
    {
        return $this->state(fn () => ['day' => $day->value]);
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['status' => ActiveStatus::Inactive->value]);
    }
}

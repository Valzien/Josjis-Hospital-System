<?php

namespace Database\Factories;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\DoctorSchedule;
use App\Models\Patient;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Appointment>
 */
class AppointmentFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'code' => 'BK-'.$this->faker->unique()->numerify('########'),
            'patient_id' => Patient::factory(),
            'doctor_id' => Doctor::factory(),
            'doctor_schedule_id' => null,
            'appointment_date' => now()->addDay()->toDateString(),
            'appointment_time' => '09:00:00',
            'status' => AppointmentStatus::Booked->value,
            'complaint_note' => $this->faker->optional()->sentence(8),
            'queue_id' => null,
            'created_by' => null,
            'checked_in_at' => null,
            'cancelled_at' => null,
        ];
    }

    public function forSchedule(DoctorSchedule $schedule): static
    {
        return $this->state(fn () => [
            'doctor_id' => $schedule->doctor_id,
            'doctor_schedule_id' => $schedule->id,
        ]);
    }

    public function on(string $date, string $time = '09:00'): static
    {
        return $this->state(fn () => [
            'appointment_date' => $date,
            'appointment_time' => $time.':00',
        ]);
    }

    public function status(AppointmentStatus $status): static
    {
        return $this->state(fn () => ['status' => $status->value]);
    }

    public function checkedIn(): static
    {
        return $this->status(AppointmentStatus::CheckedIn)->state(fn () => [
            'checked_in_at' => now(),
        ]);
    }

    public function cancelled(): static
    {
        return $this->status(AppointmentStatus::Cancelled)->state(fn () => [
            'cancelled_at' => now(),
        ]);
    }
}

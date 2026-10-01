<?php

namespace Database\Factories;

use App\Enums\QueueStatus;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Queue;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Queue>
 */
class QueueFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'patient_id' => Patient::factory(),
            'doctor_id' => Doctor::factory(),
            'doctor_schedule_id' => null,
            'queue_number' => fake()->unique()->numerify('A-###'),
            'queue_date' => now()->toDateString(),
            'status' => QueueStatus::Waiting->value,
            'called_at' => null,
            'started_at' => null,
            'completed_at' => null,
            'complaint_note' => fake()->optional()->sentence(8),
            'created_by' => null,
        ];
    }

    public function today(): static
    {
        return $this->state(fn () => ['queue_date' => now()->toDateString()]);
    }

    public function forDate(string $date): static
    {
        return $this->state(fn () => ['queue_date' => $date]);
    }

    public function number(string $number): static
    {
        return $this->state(fn () => ['queue_number' => $number]);
    }

    public function status(QueueStatus $status): static
    {
        return $this->state(fn () => ['status' => $status->value]);
    }

    public function waiting(): static
    {
        return $this->status(QueueStatus::Waiting);
    }

    public function called(): static
    {
        return $this->status(QueueStatus::Called)->state(fn () => [
            'called_at' => now()->subMinutes(2),
        ]);
    }

    public function inExamination(): static
    {
        return $this->status(QueueStatus::InExamination)->state(fn () => [
            'called_at' => now()->subMinutes(10),
            'started_at' => now()->subMinutes(5),
        ]);
    }

    public function completed(): static
    {
        return $this->status(QueueStatus::Completed)->state(fn () => [
            'called_at' => now()->subMinutes(30),
            'started_at' => now()->subMinutes(25),
            'completed_at' => now()->subMinutes(10),
        ]);
    }

    public function cancelled(): static
    {
        return $this->status(QueueStatus::Cancelled);
    }
}

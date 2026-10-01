<?php

namespace Database\Factories;

use App\Enums\PrescriptionStatus;
use App\Models\Doctor;
use App\Models\MedicalRecord;
use App\Models\Patient;
use App\Models\Prescription;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Prescription>
 */
class PrescriptionFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'code' => 'RS-'.$this->faker->unique()->numerify('########'),
            'medical_record_id' => null,
            'patient_id' => Patient::factory(),
            'doctor_id' => Doctor::factory(),
            'status' => PrescriptionStatus::Pending->value,
            'notes' => fake()->optional()->sentence(8),
            'total_price' => 0,
            'processed_at' => null,
            'processed_by' => null,
        ];
    }

    public function forRecord(MedicalRecord $record, ?Doctor $doctor = null): static
    {
        return $this->state(fn () => [
            'medical_record_id' => $record->id,
            'patient_id' => $record->patient_id,
            'doctor_id' => $doctor?->id ?? $record->doctor_id,
        ]);
    }

    public function status(PrescriptionStatus $status): static
    {
        return $this->state(fn () => ['status' => $status->value]);
    }

    public function pending(): static
    {
        return $this->status(PrescriptionStatus::Pending);
    }

    public function processing(): static
    {
        return $this->status(PrescriptionStatus::Processing);
    }

    public function ready(): static
    {
        return $this->status(PrescriptionStatus::Ready);
    }

    public function completed(): static
    {
        return $this->status(PrescriptionStatus::Completed)->state(fn () => [
            'processed_at' => now()->subHours(fake()->numberBetween(1, 48)),
        ]);
    }

    public function cancelled(): static
    {
        return $this->status(PrescriptionStatus::Cancelled);
    }
}

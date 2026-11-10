<?php

namespace Database\Factories;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Models\Patient;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Invoice>
 */
class InvoiceFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        $total = $this->faker->randomElement([75000, 90000, 125000, 150000]);

        return [
            'code' => 'INV-'.$this->faker->unique()->numerify('########'),
            'patient_id' => Patient::factory(),
            'queue_id' => null,
            'medical_record_id' => null,
            'status' => InvoiceStatus::Unpaid->value,
            'subtotal' => $total,
            'discount' => 0,
            'total' => $total,
            'paid_amount' => 0,
            'notes' => null,
            'issued_by' => null,
            'paid_at' => null,
        ];
    }

    public function status(InvoiceStatus $status): static
    {
        return $this->state(fn () => ['status' => $status->value]);
    }

    public function partial(float $paid): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => InvoiceStatus::Partial->value,
            'paid_amount' => $paid,
        ]);
    }

    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => InvoiceStatus::Paid->value,
            'paid_amount' => $attributes['total'],
            'paid_at' => now(),
        ]);
    }
}

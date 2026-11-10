<?php

namespace Database\Factories;

use App\Enums\PaymentMethod;
use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'invoice_id' => Invoice::factory(),
            'code' => 'PAY-'.$this->faker->unique()->numerify('########'),
            'method' => PaymentMethod::Cash->value,
            'amount' => $this->faker->randomElement([25000, 50000, 75000]),
            'notes' => null,
            'received_by' => null,
            'paid_at' => now(),
        ];
    }

    public function method(PaymentMethod $method): static
    {
        return $this->state(fn () => ['method' => $method->value]);
    }
}

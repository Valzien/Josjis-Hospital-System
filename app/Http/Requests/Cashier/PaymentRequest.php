<?php

namespace App\Http\Requests\Cashier;

use App\Enums\PaymentMethod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isCashier() || ($this->user()?->isAdmin() ?? false);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'min:1', 'max:999999999'],
            'method' => ['required', Rule::in(PaymentMethod::values())],
            'notes' => ['nullable', 'string', 'max:200'],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'amount' => 'nominal pembayaran',
            'method' => 'metode pembayaran',
            'notes' => 'catatan',
        ];
    }
}

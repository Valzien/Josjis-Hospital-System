<?php

namespace App\Http\Requests\Pharmacy;

use Illuminate\Foundation\Http\FormRequest;

class DisposeBatchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isPharmacist() || $this->user()?->isAdmin();
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [];
    }
}

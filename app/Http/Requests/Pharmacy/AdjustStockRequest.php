<?php

namespace App\Http\Requests\Pharmacy;

use Illuminate\Foundation\Http\FormRequest;

class AdjustStockRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isPharmacist() ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'new_stock' => ['required', 'integer', 'min:0', 'max:1000000'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'new_stock.required' => 'Jumlah stok baru wajib diisi untuk penyesuaian.',
            'new_stock.min' => 'Stok tidak boleh negatif.',
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return ['new_stock' => 'stok baru'];
    }
}

<?php

namespace App\Http\Requests\Pharmacy;

use Illuminate\Foundation\Http\FormRequest;

class StockRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isPharmacist() || $this->user()?->isAdmin();
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'quantity' => ['required', 'integer', 'min:1', 'max:1000000'],
            'batch_number' => ['nullable', 'string', 'max:80', 'required_with:expiry_date'],
            'expiry_date' => ['nullable', 'date', 'required_with:batch_number'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'quantity.required' => 'Jumlah wajib diisi.',
            'quantity.min' => 'Jumlah minimal 1.',
            'batch_number.required_with' => 'Nomor batch wajib diisi bersama tanggal kedaluwarsa.',
            'expiry_date.required_with' => 'Tanggal kedaluwarsa wajib diisi bersama nomor batch.',
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'quantity' => 'jumlah',
            'new_stock' => 'stok baru',
            'batch_number' => 'nomor batch',
            'expiry_date' => 'tanggal kedaluwarsa',
            'notes' => 'catatan',
        ];
    }
}

<?php

namespace App\Http\Requests\Pharmacy;

use App\Enums\ActiveStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MedicineRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isPharmacist() || $this->user()?->isAdmin();
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $medicineId = $this->route('medicine')?->id;

        return [
            'medicine_code' => ['required', 'string', 'max:30', Rule::unique('medicines', 'medicine_code')->ignore($medicineId)],
            'name' => ['required', 'string', 'min:2', 'max:150'],
            'category' => ['required', 'string', 'max:60'],
            'unit' => ['required', 'string', 'max:20'],
            'stock' => ['nullable', 'integer', 'min:0', 'max:1000000'],
            'minimum_stock' => ['required', 'integer', 'min:0', 'max:1000000'],
            'price' => ['required', 'numeric', 'min:0', 'max:10000000'],
            'description' => ['nullable', 'string', 'max:1000'],
            'status' => ['required', Rule::in(array_keys(ActiveStatus::options()))],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'medicine_code.unique' => 'Kode obat ini sudah digunakan.',
            'minimum_stock.required' => 'Stok minimum wajib diisi untuk peringatan stok rendah.',
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'medicine_code' => 'kode obat',
            'minimum_stock' => 'stok minimum',
            'name' => 'nama obat',
        ];
    }

    /** @return array<string, mixed> */
    public function payload(): array
    {
        return [
            'medicine_code' => strtoupper($this->string('medicine_code')->trim()->value()),
            'name' => $this->string('name')->trim()->value(),
            'category' => $this->string('category')->trim()->value(),
            'unit' => $this->input('unit'),
            'stock' => (int) ($this->input('stock') ?? 0),
            'minimum_stock' => (int) $this->input('minimum_stock'),
            'price' => (float) $this->input('price'),
            'description' => $this->input('description') ?: null,
            'status' => $this->input('status'),
        ];
    }
}

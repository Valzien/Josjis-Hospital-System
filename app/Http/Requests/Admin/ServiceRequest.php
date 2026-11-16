<?php

namespace App\Http\Requests\Admin;

use App\Enums\ActiveStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ServiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $serviceId = $this->route('service')?->id;

        return [
            'code' => ['required', 'string', 'max:30', Rule::unique('services', 'code')->ignore($serviceId)],
            'name' => ['required', 'string', 'min:3', 'max:150'],
            'category' => ['required', 'string', 'max:50'],
            'unit' => ['required', 'string', 'max:30'],
            'price' => ['required', 'numeric', 'min:0', 'max:999999999'],
            'status' => ['required', Rule::in(array_keys(ActiveStatus::options()))],
            'description' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'code' => 'kode layanan',
            'name' => 'nama layanan',
            'category' => 'kategori',
            'unit' => 'satuan',
            'price' => 'tarif',
            'status' => 'status',
        ];
    }

    /** @return array<string, mixed> */
    public function payload(): array
    {
        return [
            'code' => strtoupper($this->string('code')->trim()->value()),
            'name' => $this->string('name')->trim()->value(),
            'category' => $this->string('category')->trim()->value(),
            'unit' => $this->string('unit')->trim()->value(),
            'price' => (float) $this->input('price'),
            'status' => $this->input('status'),
            'description' => $this->input('description') ?: null,
        ];
    }
}

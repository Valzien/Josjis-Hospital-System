<?php

namespace App\Http\Requests\Admin;

use App\Enums\ActiveStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StaffRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $staffId = $this->route($this->routeName())?->id;

        return [
            'name' => ['required', 'string', 'min:3', 'max:100'],
            'phone' => ['nullable', 'string', 'max:30'],
            'license_number' => ['nullable', 'string', 'max:40'],
            'shift' => ['nullable', 'string', 'max:30'],
            'status' => ['required', Rule::in(array_keys(ActiveStatus::options()))],
            'user_id' => ['nullable', 'exists:users,id', Rule::unique($this->table(), 'user_id')->ignore($staffId)],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return ['name' => 'nama', 'phone' => 'nomor telepon', 'license_number' => 'nomor STR/SIPA', 'shift' => 'shift'];
    }

    private function routeName(): string
    {
        return $this->isForPharmacist() ? 'pharmacist' : 'receptionist';
    }

    private function table(): string
    {
        return $this->isForPharmacist() ? 'pharmacists' : 'receptionists';
    }

    private function isForPharmacist(): bool
    {
        return $this->routeIs('admin.pharmacists.*') || str_contains($this->action(), 'pharmacist');
    }

    /** @return array<string, mixed> */
    public function payload(): array
    {
        $data = [
            'name' => $this->string('name')->trim()->value(),
            'phone' => $this->input('phone') ?: null,
            'status' => $this->input('status'),
            'user_id' => $this->input('user_id') ?: null,
        ];

        if ($this->isForPharmacist()) {
            $data['license_number'] = $this->input('license_number') ?: null;
        } else {
            $data['shift'] = $this->input('shift') ?: null;
        }

        return $data;
    }
}

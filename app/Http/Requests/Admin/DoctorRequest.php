<?php

namespace App\Http\Requests\Admin;

use App\Enums\ActiveStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DoctorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $doctorId = $this->route('doctor')?->id;

        return [
            'doctor_code' => ['required', 'string', 'max:30', Rule::unique('doctors', 'doctor_code')->ignore($doctorId)],
            'name' => ['required', 'string', 'min:3', 'max:100'],
            'specialization' => ['required', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('doctors', 'email')->ignore($doctorId)],
            'bio' => ['nullable', 'string', 'max:1000'],
            'status' => ['required', Rule::in(array_keys(ActiveStatus::options()))],
            'user_id' => ['nullable', 'exists:users,id', Rule::unique('doctors', 'user_id')->ignore($doctorId)],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'doctor_code' => 'kode dokter',
            'name' => 'nama dokter',
            'specialization' => 'spesialisasi',
            'bio' => 'profil singkat',
        ];
    }

    /** @return array<string, mixed> */
    public function payload(): array
    {
        return [
            'doctor_code' => strtoupper($this->string('doctor_code')->trim()->value()),
            'name' => $this->string('name')->trim()->value(),
            'specialization' => $this->string('specialization')->trim()->value(),
            'phone' => $this->input('phone') ?: null,
            'email' => $this->input('email') ?: null,
            'bio' => $this->input('bio') ?: null,
            'status' => $this->input('status'),
            'user_id' => $this->input('user_id') ?: null,
        ];
    }
}

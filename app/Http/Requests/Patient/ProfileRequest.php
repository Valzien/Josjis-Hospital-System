<?php

namespace App\Http\Requests\Patient;

use App\Enums\BloodType;
use App\Enums\Gender;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isPatient() ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:3', 'max:100'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->user()->id)],
            'nik' => ['nullable', 'string', 'max:20', Rule::unique('patients', 'nik')->ignore($this->user()->patient?->id)],
            'gender' => ['required', Rule::in(array_keys(Gender::options()))],
            'birth_date' => ['required', 'date', 'before:today', 'after:1900-01-01'],
            'phone' => ['required', 'string', 'max:30'],
            'address' => ['required', 'string', 'max:500'],
            'blood_type' => ['nullable', Rule::in(array_keys(BloodType::options()))],
            'emergency_contact_name' => ['nullable', 'string', 'max:100'],
            'emergency_contact_phone' => ['nullable', 'string', 'max:30'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'gender.required' => 'Jenis kelamin wajib dipilih.',
            'birth_date.required' => 'Tanggal lahir wajib diisi.',
            'phone.required' => 'Nomor telepon wajib diisi.',
            'address.required' => 'Alamat wajib diisi.',
            'nik.unique' => 'NIK ini sudah digunakan pasien lain.',
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'name' => 'nama lengkap',
            'birth_date' => 'tanggal lahir',
            'address' => 'alamat',
            'blood_type' => 'golongan darah',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('email')) {
            $this->merge(['email' => strtolower(trim($this->input('email')))]);
        }
    }

    /** @return array<string, mixed> */
    public function userPayload(): array
    {
        return [
            'name' => $this->string('name')->trim()->value(),
            'email' => $this->string('email')->value(),
            'phone' => $this->input('phone'),
        ];
    }

    /** @return array<string, mixed> */
    public function patientPayload(): array
    {
        return [
            'name' => $this->string('name')->trim()->value(),
            'nik' => $this->input('nik') ?: null,
            'gender' => $this->input('gender'),
            'birth_date' => $this->input('birth_date'),
            'phone' => $this->input('phone'),
            'address' => $this->input('address'),
            'blood_type' => $this->input('blood_type') ?: null,
            'emergency_contact_name' => $this->input('emergency_contact_name') ?: null,
            'emergency_contact_phone' => $this->input('emergency_contact_phone') ?: null,
        ];
    }
}

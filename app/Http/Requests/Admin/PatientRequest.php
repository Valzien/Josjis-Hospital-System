<?php

namespace App\Http\Requests\Admin;

use App\Enums\BloodType;
use App\Enums\Gender;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PatientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $patientId = $this->route('patient')?->id;

        return [
            'name' => ['required', 'string', 'min:3', 'max:100'],
            'nik' => ['nullable', 'string', 'max:20', Rule::unique('patients', 'nik')->ignore($patientId)],
            'gender' => ['required', Rule::in(array_keys(Gender::options()))],
            'birth_date' => ['required', 'date', 'before:today', 'after:1900-01-01'],
            'phone' => ['required', 'string', 'max:30'],
            'address' => ['required', 'string', 'max:500'],
            'blood_type' => ['nullable', Rule::in(array_keys(BloodType::options()))],
            'allergies' => ['nullable', 'string', 'max:500'],
            'emergency_contact_name' => ['nullable', 'string', 'max:100'],
            'emergency_contact_phone' => ['nullable', 'string', 'max:30'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'nik.unique' => 'NIK ini sudah terdaftar di sistem.',
            'birth_date.before' => 'Tanggal lahir tidak valid.',
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'name' => 'nama pasien',
            'nik' => 'NIK',
            'birth_date' => 'tanggal lahir',
            'address' => 'alamat',
            'blood_type' => 'golongan darah',
        ];
    }

    /** @return array<string, mixed> */
    public function payload(): array
    {
        return [
            'name' => $this->string('name')->trim()->value(),
            'nik' => $this->input('nik') ?: null,
            'gender' => $this->input('gender'),
            'birth_date' => $this->input('birth_date'),
            'phone' => $this->input('phone'),
            'address' => $this->input('address'),
            'blood_type' => $this->input('blood_type') ?: null,
            'allergies' => $this->input('allergies') ?: null,
            'emergency_contact_name' => $this->input('emergency_contact_name') ?: null,
            'emergency_contact_phone' => $this->input('emergency_contact_phone') ?: null,
        ];
    }
}

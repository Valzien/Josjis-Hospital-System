<?php

namespace App\Http\Requests\Reception;

use Illuminate\Foundation\Http\FormRequest;

class AdmissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isReceptionist() || $this->user()?->isAdmin();
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'patient_id' => ['required', 'exists:patients,id'],
            'doctor_id' => ['required', 'exists:doctors,id'],
            'bed_id' => ['required', 'exists:beds,id'],
            'admitted_at' => ['nullable', 'date'],
            'complaint' => ['nullable', 'string', 'max:1000'],
            'diagnosis' => ['nullable', 'string', 'max:1000'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'patient_id' => 'pasien',
            'doctor_id' => 'dokter',
            'bed_id' => 'tempat tidur',
            'admitted_at' => 'waktu masuk',
            'complaint' => 'keluhan',
            'diagnosis' => 'diagnosis',
            'notes' => 'catatan',
        ];
    }
}

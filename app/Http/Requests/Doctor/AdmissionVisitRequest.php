<?php

namespace App\Http\Requests\Doctor;

use Illuminate\Foundation\Http\FormRequest;

class AdmissionVisitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isDoctor() || $this->user()?->isAdmin();
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'visited_at' => ['nullable', 'date'],
            'notes' => ['required', 'string', 'min:3', 'max:2000'],
            'temperature' => ['nullable', 'numeric', 'min:30', 'max:45'],
            'blood_pressure' => ['nullable', 'numeric', 'min:50', 'max:250'],
            'pulse' => ['nullable', 'integer', 'min:20', 'max:250'],
            'respiratory_rate' => ['nullable', 'integer', 'min:5', 'max:80'],
            'oxygen_saturation' => ['nullable', 'integer', 'min:50', 'max:100'],
            'weight' => ['nullable', 'numeric', 'min:1', 'max:400'],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'visited_at' => 'waktu visite',
            'notes' => 'catatan visite',
            'temperature' => 'suhu',
            'blood_pressure' => 'tekanan darah',
            'pulse' => 'denyut nadi',
            'respiratory_rate' => 'laju pernapasan',
            'oxygen_saturation' => 'saturasi oksigen',
            'weight' => 'berat badan',
        ];
    }

    /** @return array<string, mixed> */
    public function vitals(): array
    {
        return [
            'temperature' => $this->input('temperature') !== null ? (float) $this->input('temperature') : null,
            'blood_pressure' => $this->input('blood_pressure') !== null ? (float) $this->input('blood_pressure') : null,
            'pulse' => $this->input('pulse') !== null ? (int) $this->input('pulse') : null,
            'respiratory_rate' => $this->input('respiratory_rate') !== null ? (int) $this->input('respiratory_rate') : null,
            'oxygen_saturation' => $this->input('oxygen_saturation') !== null ? (int) $this->input('oxygen_saturation') : null,
            'weight' => $this->input('weight') !== null ? (float) $this->input('weight') : null,
        ];
    }
}

<?php

namespace App\Http\Requests\Patient;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TakeQueueRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isPatient() ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'doctor_schedule_id' => [
                'required',
                Rule::exists('doctor_schedules', 'id')->where('status', 'active'),
            ],
            'complaint_note' => ['nullable', 'string', 'max:500'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'doctor_schedule_id.required' => 'Silakan pilih dokter terlebih dahulu.',
            'doctor_schedule_id.exists' => 'Jadwal yang dipilih tidak tersedia atau sudah tidak aktif.',
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return ['doctor_schedule_id' => 'jadwal dokter', 'complaint_note' => 'keluhan'];
    }
}

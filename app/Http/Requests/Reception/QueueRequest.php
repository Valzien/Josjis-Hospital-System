<?php

namespace App\Http\Requests\Reception;

use App\Models\DoctorSchedule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class QueueRequest extends FormRequest
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
            'doctor_schedule_id' => ['required', Rule::exists('doctor_schedules', 'id')->where('status', 'active')],
            'complaint_note' => ['nullable', 'string', 'max:500'],
            'queue_date' => ['nullable', 'date', 'after_or_equal:today', 'before_or_equal:today'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'doctor_schedule_id.required' => 'Pilih dokter dan jadwal yang tersedia.',
            'queue_date.before_or_equal' => 'Antrean hanya dapat dibuat untuk hari ini.',
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return ['patient_id' => 'pasien', 'doctor_schedule_id' => 'jadwal dokter', 'complaint_note' => 'keluhan'];
    }

    public function schedule(): DoctorSchedule
    {
        return DoctorSchedule::query()
            ->with('doctor')
            ->findOrFail($this->integer('doctor_schedule_id'));
    }
}

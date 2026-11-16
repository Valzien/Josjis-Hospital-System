<?php

namespace App\Http\Requests\Patient;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class BookAppointmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isPatient() ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $isToday = $this->dateIsToday();

        return [
            'doctor_schedule_id' => [
                'required',
                Rule::exists('doctor_schedules', 'id')->where('status', 'active'),
            ],
            'appointment_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'appointment_time' => [
                'nullable',
                'date_format:H:i',
                Rule::requiredIf(fn () => ! $isToday),
            ],
            'complaint_note' => ['nullable', 'string', 'max:500'],
        ];
    }

    /** Kunjungan hari ini memakai nomor antrean, jadi tidak perlu memilih jam. */
    public function dateIsToday(): bool
    {
        $date = (string) $this->input('appointment_date', '');

        if (! Carbon::hasFormat($date, 'Y-m-d')) {
            return false;
        }

        return Carbon::createFromFormat('Y-m-d', $date)->isToday();
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'doctor_schedule_id.required' => 'Silakan pilih jadwal dokter terlebih dahulu.',
            'doctor_schedule_id.exists' => 'Jadwal yang dipilih tidak tersedia atau sudah tidak aktif.',
            'appointment_date.after_or_equal' => 'Tanggal janji temu tidak boleh di masa lalu.',
            'appointment_time.required' => 'Silakan pilih slot jam yang tersedia.',
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'doctor_schedule_id' => 'jadwal dokter',
            'appointment_date' => 'tanggal janji temu',
            'appointment_time' => 'jam janji temu',
            'complaint_note' => 'keluhan',
        ];
    }
}

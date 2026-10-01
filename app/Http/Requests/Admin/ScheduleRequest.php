<?php

namespace App\Http\Requests\Admin;

use App\Enums\ActiveStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $scheduleId = $this->route('schedule')?->id;

        return [
            'doctor_id' => ['required', 'exists:doctors,id'],
            'day' => ['required', 'integer', 'between:0,6'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'room' => ['nullable', 'string', 'max:30'],
            'quota' => ['required', 'integer', 'min:1', 'max:200'],
            'status' => ['required', Rule::in(array_keys(ActiveStatus::options()))],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'end_time.after' => 'Jam selesai harus lebih besar dari jam mulai.',
            'doctor_id.required' => 'Pilih dokter terlebih dahulu.',
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'day' => 'hari',
            'start_time' => 'jam mulai',
            'end_time' => 'jam selesai',
            'quota' => 'kuota',
            'room' => 'ruangan',
        ];
    }

    /** @return array<string, mixed> */
    public function payload(): array
    {
        return [
            'doctor_id' => (int) $this->input('doctor_id'),
            'day' => (int) $this->input('day'),
            'start_time' => $this->input('start_time').':00',
            'end_time' => $this->input('end_time').':00',
            'room' => $this->input('room') ?: null,
            'quota' => (int) $this->input('quota'),
            'status' => $this->input('status'),
        ];
    }
}

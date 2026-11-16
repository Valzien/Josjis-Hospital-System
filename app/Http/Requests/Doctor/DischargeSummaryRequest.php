<?php

namespace App\Http\Requests\Doctor;

use Illuminate\Foundation\Http\FormRequest;

class DischargeSummaryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isDoctor() || $this->user()?->isAdmin();
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'discharge_condition' => ['nullable', 'string', 'max:2000'],
            'discharge_plan' => ['nullable', 'string', 'max:2000'],
            'follow_up_date' => ['nullable', 'date'],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'discharge_condition' => 'kondisi saat pulang',
            'discharge_plan' => 'rencana tindak lanjut',
            'follow_up_date' => 'tanggal kontrol',
        ];
    }

    /** @return array<string, mixed> */
    public function summary(): array
    {
        return [
            'discharge_condition' => $this->filled('discharge_condition') ? trim((string) $this->input('discharge_condition')) : null,
            'discharge_plan' => $this->filled('discharge_plan') ? trim((string) $this->input('discharge_plan')) : null,
            'follow_up_date' => $this->input('follow_up_date') ?: null,
        ];
    }
}

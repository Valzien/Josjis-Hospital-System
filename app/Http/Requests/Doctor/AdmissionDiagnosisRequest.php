<?php

namespace App\Http\Requests\Doctor;

use App\Enums\DiagnosisType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AdmissionDiagnosisRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isDoctor() || $this->user()?->isAdmin();
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'icd10_code_id' => ['required', 'integer', 'exists:icd10_codes,id'],
            'type' => ['required', Rule::enum(DiagnosisType::class)],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'icd10_code_id' => 'kode ICD-10',
            'type' => 'jenis diagnosis',
            'notes' => 'catatan diagnosis',
        ];
    }
}

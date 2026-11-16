<?php

namespace App\Http\Requests\Reception;

use Illuminate\Foundation\Http\FormRequest;

class AdmissionTransferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isReceptionist() || $this->user()?->isAdmin();
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'bed_id' => ['required', 'exists:beds,id'],
            'reason' => ['nullable', 'string', 'max:500'],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'bed_id' => 'tempat tidur tujuan',
            'reason' => 'alasan pindah',
        ];
    }
}

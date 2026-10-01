<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class SettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'settings' => ['required', 'array'],
            'settings.*' => ['nullable', 'string', 'max:2000'],
            'queue_prefix' => ['required', 'string', 'max:5', 'regex:/^[A-Za-z0-9\-]+$/'],
            'estimated_minutes' => ['required', 'integer', 'between:1,120'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'queue_prefix.regex' => 'Prefix nomor antrean hanya boleh berisi huruf, angka, atau strip.',
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return ['queue_prefix' => 'prefix nomor antrean', 'estimated_minutes' => 'estimasi durata'];
    }
}

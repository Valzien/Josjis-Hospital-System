<?php

namespace App\Http\Requests\Admin;

use App\Enums\BedStatus;
use App\Models\Bed;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BedRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        /** @var Bed|null $bed */
        $bed = $this->route('bed');
        $roomId = $this->route('room')?->id;

        return [
            'code' => [
                'required', 'string', 'max:20',
                Rule::unique('beds', 'code')
                    ->where(fn ($query) => $query->where('room_id', $roomId))
                    ->ignore($bed?->id),
            ],
            'status' => ['required', Rule::in(array_keys(BedStatus::manualOptions()))],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'code' => 'kode tempat tidur',
            'status' => 'status',
            'notes' => 'catatan',
        ];
    }

    /** @return array<string, mixed> */
    public function payload(): array
    {
        return [
            'code' => strtoupper($this->string('code')->trim()->value()),
            'status' => $this->input('status'),
            'notes' => $this->input('notes') ?: null,
        ];
    }
}

<?php

namespace App\Http\Requests\Admin;

use App\Enums\ActiveStatus;
use App\Enums\RoomClass;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RoomRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $roomId = $this->route('room')?->id;

        return [
            'code' => ['required', 'string', 'max:30', Rule::unique('rooms', 'code')->ignore($roomId)],
            'name' => ['required', 'string', 'min:3', 'max:150'],
            'class' => ['required', Rule::in(array_keys(RoomClass::options()))],
            'floor' => ['nullable', 'string', 'max:20'],
            'rate_per_day' => ['required', 'numeric', 'min:0', 'max:999999999'],
            'status' => ['required', Rule::in(array_keys(ActiveStatus::options()))],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'code' => 'kode kamar',
            'name' => 'nama kamar',
            'class' => 'kelas kamar',
            'floor' => 'lantai',
            'rate_per_day' => 'tarif harian',
            'status' => 'status',
        ];
    }

    /** @return array<string, mixed> */
    public function payload(): array
    {
        return [
            'code' => strtoupper($this->string('code')->trim()->value()),
            'name' => $this->string('name')->trim()->value(),
            'class' => $this->input('class'),
            'floor' => $this->input('floor') ?: null,
            'rate_per_day' => (float) $this->input('rate_per_day'),
            'status' => $this->input('status'),
            'notes' => $this->input('notes') ?: null,
        ];
    }
}

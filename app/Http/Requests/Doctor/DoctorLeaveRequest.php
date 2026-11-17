<?php

namespace App\Http\Requests\Doctor;

use App\Models\DoctorLeave;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;

class DoctorLeaveRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isDoctor() || $this->user()?->isAdmin();
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $doctorId = $this->user()?->doctor?->id ?? 0;

        return [
            'date' => [
                'required',
                'date',
                'after_or_equal:today',
                function (string $attribute, mixed $value, Closure $fail) use ($doctorId): void {
                    if (DoctorLeave::query()->forDoctor((int) $doctorId)->onDate($value)->exists()) {
                        $fail('Anda sudah menandai cuti pada tanggal tersebut.');
                    }
                },
            ],
            'reason' => ['nullable', 'string', 'max:255'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'date.after_or_equal' => 'Tanggal cuti tidak boleh di masa lalu.',
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'date' => 'tanggal cuti',
            'reason' => 'keterangan',
        ];
    }

    /** @return array{date:string, reason:?string} */
    public function payload(): array
    {
        return [
            'date' => Carbon::parse($this->string('date')->value())->toDateString(),
            'reason' => $this->filled('reason') ? $this->string('reason')->trim()->value() : null,
        ];
    }
}

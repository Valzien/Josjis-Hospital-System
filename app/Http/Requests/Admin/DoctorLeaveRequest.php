<?php

namespace App\Http\Requests\Admin;

use App\Models\DoctorLeave;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;

class DoctorLeaveRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $doctorId = $this->input('doctor_id');

        return [
            'doctor_id' => ['required', 'integer', 'exists:doctors,id'],
            'date' => [
                'required',
                'date',
                'after_or_equal:today',
                function (string $attribute, mixed $value, Closure $fail) use ($doctorId): void {
                    if (DoctorLeave::query()->forDoctor((int) $doctorId)->onDate($value)->exists()) {
                        $fail('Dokter sudah ditandai cuti pada tanggal tersebut.');
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
            'doctor_id' => 'dokter',
            'date' => 'tanggal cuti',
            'reason' => 'keterangan',
        ];
    }

    /** @return array{doctor_id:int, date:string, reason:?string} */
    public function payload(): array
    {
        return [
            'doctor_id' => (int) $this->input('doctor_id'),
            'date' => Carbon::parse($this->string('date')->value())->toDateString(),
            'reason' => $this->filled('reason') ? $this->string('reason')->trim()->value() : null,
        ];
    }
}

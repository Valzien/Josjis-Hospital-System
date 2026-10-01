<?php

namespace App\Http\Requests\Doctor;

use Illuminate\Foundation\Http\FormRequest;

class ExaminationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isDoctor() || $this->user()?->isAdmin();
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'complaint' => ['required', 'string', 'min:5', 'max:1000'],
            'examination_result' => ['required', 'string', 'min:5', 'max:2000'],
            'diagnosis' => ['required', 'string', 'min:3', 'max:1000'],
            'treatment' => ['nullable', 'string', 'max:1000'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'temperature' => ['nullable', 'numeric', 'min:30', 'max:45'],
            'blood_pressure' => ['nullable', 'numeric', 'min:50', 'max:250'],
            'weight' => ['nullable', 'integer', 'min:1', 'max:400'],
            'height' => ['nullable', 'numeric', 'min:30', 'max:250'],
            'examined_at' => ['nullable', 'date', 'before_or_equal:now'],

            'items' => ['nullable', 'array'],
            'items.*.medicine_id' => ['required_with:items', 'integer', 'exists:medicines,id'],
            'items.*.quantity' => ['required_with:items', 'integer', 'min:1', 'max:1000'],
            'items.*.dosage' => ['nullable', 'string', 'max:60'],
            'items.*.instructions' => ['nullable', 'string', 'max:500'],
            'prescription_notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'complaint.required' => 'Keluhan pasien wajib diisi.',
            'diagnosis.required' => 'Diagnosis/hasil pemeriksaan wajib diisi sebelum menyimpan.',
            'items.*.medicine_id.required_with' => 'Pilih obat untuk setiap baris resep.',
            'items.*.quantity.required_with' => 'Jumlah obat minimal 1.',
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'complaint' => 'keluhan',
            'examination_result' => 'hasil pemeriksaan',
            'diagnosis' => 'diagnosis',
            'items' => 'resep',
        ];
    }

    /** @return array<string, mixed> */
    public function payload(): array
    {
        return [
            'complaint' => $this->string('complaint')->trim()->value(),
            'examination_result' => $this->string('examination_result')->trim()->value(),
            'diagnosis' => $this->string('diagnosis')->trim()->value(),
            'treatment' => $this->input('treatment') ?: null,
            'notes' => $this->input('notes') ?: null,
            'temperature' => $this->input('temperature') !== null ? (float) $this->input('temperature') : null,
            'blood_pressure' => $this->input('blood_pressure') !== null ? (float) $this->input('blood_pressure') : null,
            'weight' => $this->input('weight') !== null ? (int) $this->input('weight') : null,
            'height' => $this->input('height') !== null ? (float) $this->input('height') : null,
            'examined_at' => $this->input('examined_at') ?: now(),
        ];
    }

    /**
     * Item resep yang valid (baris kosong diabaikan).
     *
     * @return array<int, array{medicine_id:int, quantity:int, dosage:string|null, instructions:string|null}>
     */
    public function prescriptionItems(): array
    {
        $items = $this->input('items', []);

        if (! is_array($items)) {
            return [];
        }

        return collect($items)
            ->filter(fn ($item) => is_array($item) && ! empty($item['medicine_id']) && ! empty($item['quantity']))
            ->map(fn (array $item) => [
                'medicine_id' => (int) $item['medicine_id'],
                'quantity' => (int) $item['quantity'],
                'dosage' => $item['dosage'] ?? null,
                'instructions' => $item['instructions'] ?? null,
            ])
            ->values()
            ->all();
    }

    public function wantsPrescription(): bool
    {
        return $this->boolean('with_prescription') && $this->prescriptionItems() !== [];
    }
}

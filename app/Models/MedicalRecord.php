<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'record_number', 'patient_id', 'doctor_id', 'queue_id', 'complaint',
    'examination_result', 'diagnosis', 'treatment', 'notes',
    'temperature', 'blood_pressure', 'weight', 'height', 'examined_at',
])]
class MedicalRecord extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'examined_at' => 'datetime',
            'temperature' => 'decimal:1',
            'blood_pressure' => 'decimal:1',
            'height' => 'decimal:1',
        ];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    public function queue(): BelongsTo
    {
        return $this->belongsTo(Queue::class);
    }

    public function prescription(): HasOne
    {
        return $this->hasOne(Prescription::class);
    }

    /** @param  Builder<MedicalRecord>  $query */
    #[Scope]
    protected function search(Builder $query, ?string $term): void
    {
        if (blank($term)) {
            return;
        }

        $like = '%'.str_replace(['%', '_'], ['\%', '\_'], $term).'%';

        $query->where(function (Builder $q) use ($like) {
            $q->where('record_number', 'like', $like)
                ->orWhere('diagnosis', 'like', $like)
                ->orWhere('complaint', 'like', $like)
                ->orWhereHas('patient', fn (Builder $p) => $p->where('name', 'like', $like));
        });
    }

    public function hasPrescription(): bool
    {
        return $this->prescription !== null;
    }

    /** Tanda vital diringkas untuk ditampilkan. */
    public function vitalSigns(): array
    {
        $signs = [];

        if ($this->temperature !== null) {
            $signs['Suhu'] = $this->temperature.' °C';
        }

        if ($this->blood_pressure !== null) {
            $signs['Tekanan Darah'] = $this->blood_pressure.' mmHg';
        }

        if ($this->weight !== null) {
            $signs['Berat'] = $this->weight.' kg';
        }

        if ($this->height !== null) {
            $signs['Tinggi'] = $this->height.' cm';
        }

        return $signs;
    }
}

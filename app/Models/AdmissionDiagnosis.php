<?php

namespace App\Models;

use App\Enums\DiagnosisType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'admission_id', 'icd10_code_id', 'code', 'name', 'type', 'notes',
    'doctor_id', 'diagnosed_at', 'created_by',
])]
class AdmissionDiagnosis extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'type' => DiagnosisType::class,
            'diagnosed_at' => 'datetime',
        ];
    }

    public function admission(): BelongsTo
    {
        return $this->belongsTo(Admission::class);
    }

    public function icd10Code(): BelongsTo
    {
        return $this->belongsTo(Icd10Code::class);
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function label(): string
    {
        return $this->code.' — '.$this->name;
    }
}

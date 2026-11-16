<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'admission_id', 'doctor_id', 'visited_at', 'notes',
    'temperature', 'blood_pressure', 'pulse', 'respiratory_rate',
    'oxygen_saturation', 'weight', 'created_by',
])]
class AdmissionVisit extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'visited_at' => 'datetime',
            'temperature' => 'decimal:1',
            'blood_pressure' => 'decimal:1',
            'pulse' => 'integer',
            'respiratory_rate' => 'integer',
            'oxygen_saturation' => 'integer',
            'weight' => 'decimal:1',
        ];
    }

    public function admission(): BelongsTo
    {
        return $this->belongsTo(Admission::class);
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
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

        if ($this->pulse !== null) {
            $signs['Nadi'] = $this->pulse.' x/menit';
        }

        if ($this->respiratory_rate !== null) {
            $signs['Pernapasan'] = $this->respiratory_rate.' x/menit';
        }

        if ($this->oxygen_saturation !== null) {
            $signs['SpO₂'] = $this->oxygen_saturation.' %';
        }

        if ($this->weight !== null) {
            $signs['Berat'] = $this->weight.' kg';
        }

        return $signs;
    }
}

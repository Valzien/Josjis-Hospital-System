<?php

namespace App\Models;

use App\Enums\AdmissionChargeType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'admission_id', 'type', 'service_id', 'description', 'quantity',
    'unit_price', 'subtotal', 'charged_at', 'created_by',
])]
class AdmissionCharge extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'type' => AdmissionChargeType::class,
            'quantity' => 'integer',
            'unit_price' => 'decimal:2',
            'subtotal' => 'decimal:2',
            'charged_at' => 'datetime',
        ];
    }

    public function admission(): BelongsTo
    {
        return $this->belongsTo(Admission::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}

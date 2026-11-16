<?php

namespace App\Models;

use App\Enums\AdmissionStatus;
use App\Enums\BedStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['room_id', 'code', 'status', 'notes'])]
class Bed extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'status' => BedStatus::class,
        ];
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function admissions(): HasMany
    {
        return $this->hasMany(Admission::class);
    }

    public function activeAdmission(): HasOne
    {
        return $this->hasOne(Admission::class)->where('status', AdmissionStatus::Active->value);
    }

    /** @param  Builder<Bed>  $query */
    #[Scope]
    protected function available(Builder $query): void
    {
        $query->where('status', BedStatus::Available->value);
    }

    public function isAvailable(): bool
    {
        return $this->status === BedStatus::Available;
    }

    public function statusLabel(): string
    {
        return $this->status?->label() ?? '-';
    }

    public function label(): string
    {
        return trim(($this->room?->name ?? 'Kamar').' / '.$this->code);
    }
}

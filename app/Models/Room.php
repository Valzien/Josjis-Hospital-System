<?php

namespace App\Models;

use App\Enums\ActiveStatus;
use App\Enums\AdmissionStatus;
use App\Enums\RoomClass;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'code', 'name', 'class', 'floor', 'rate_per_day', 'status', 'notes',
])]
class Room extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'class' => RoomClass::class,
            'status' => ActiveStatus::class,
            'rate_per_day' => 'decimal:2',
        ];
    }

    public function beds(): HasMany
    {
        return $this->hasMany(Bed::class);
    }

    public function admissions(): HasMany
    {
        return $this->hasMany(Admission::class);
    }

    /** @param  Builder<Room>  $query */
    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('status', ActiveStatus::Active->value);
    }

    /** @param  Builder<Room>  $query */
    #[Scope]
    protected function search(Builder $query, ?string $term): void
    {
        if (blank($term)) {
            return;
        }

        $like = '%'.str_replace(['%', '_'], ['\%', '\_'], $term).'%';

        $query->where(function (Builder $q) use ($like) {
            $q->where('name', 'like', $like)
                ->orWhere('code', 'like', $like)
                ->orWhere('floor', 'like', $like);
        });
    }

    public function classLabel(): string
    {
        return $this->class?->label() ?? '-';
    }

    public function rateFormatted(): string
    {
        return 'Rp '.number_format((float) $this->rate_per_day, 0, ',', '.');
    }

    public function hasActiveAdmission(): bool
    {
        return $this->admissions()->where('status', AdmissionStatus::Active->value)->exists();
    }
}

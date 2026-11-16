<?php

namespace App\Models;

use App\Enums\AdmissionStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

#[Fillable([
    'code', 'patient_id', 'doctor_id', 'room_id', 'bed_id', 'status',
    'admitted_at', 'discharged_at', 'complaint', 'diagnosis', 'notes',
    'discharge_notes', 'created_by',
])]
class Admission extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'status' => AdmissionStatus::class,
            'admitted_at' => 'datetime',
            'discharged_at' => 'datetime',
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

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function bed(): BelongsTo
    {
        return $this->belongsTo(Bed::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function movements(): HasMany
    {
        return $this->hasMany(BedMovement::class)->latest('moved_at');
    }

    public function visits(): HasMany
    {
        return $this->hasMany(AdmissionVisit::class)->latest('visited_at');
    }

    public function charges(): HasMany
    {
        return $this->hasMany(AdmissionCharge::class);
    }

    public function invoice(): HasOne
    {
        return $this->hasOne(Invoice::class);
    }

    /** @param  Builder<Admission>  $query */
    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('status', AdmissionStatus::Active->value);
    }

    /** @param  Builder<Admission>  $query */
    #[Scope]
    protected function forDoctor(Builder $query, int $doctorId): void
    {
        $query->where('doctor_id', $doctorId);
    }

    /** @param  Builder<Admission>  $query */
    #[Scope]
    protected function search(Builder $query, ?string $term): void
    {
        if (blank($term)) {
            return;
        }

        $like = '%'.str_replace(['%', '_'], ['\%', '\_'], $term).'%';

        $query->where(function (Builder $q) use ($like) {
            $q->where('code', 'like', $like)
                ->orWhereHas('patient', fn (Builder $p) => $p->where('name', 'like', $like)
                    ->orWhere('medical_record_number', 'like', $like));
        });
    }

    public function isActive(): bool
    {
        return $this->status === AdmissionStatus::Active;
    }

    public function statusLabel(): string
    {
        return $this->status?->label() ?? '-';
    }

    /** Lama inap dalam hari (inklusif, minimal 1 hari). */
    public function dayCount(?Carbon $until = null): int
    {
        $start = $this->admitted_at?->copy()->startOfDay() ?? now()->startOfDay();
        $end = ($until ?? $this->discharged_at ?? now())->copy()->startOfDay();

        return max(1, (int) $start->diffInDays($end) + 1);
    }

    public function chargesTotal(): float
    {
        return (float) $this->charges()->sum('subtotal');
    }
}

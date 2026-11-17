<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['medicine_id', 'batch_number', 'quantity', 'expiry_date', 'notes'])]
class MedicineBatch extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'expiry_date' => 'date',
        ];
    }

    public function medicine(): BelongsTo
    {
        return $this->belongsTo(Medicine::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(MedicineTransaction::class);
    }

    /** @param  Builder<MedicineBatch>  $query */
    #[Scope]
    protected function available(Builder $query): void
    {
        $query->where('quantity', '>', 0);
    }

    /**
     * Batch yang masih boleh dipakai: belum kedaluwarsa dan masih ada stok.
     *
     * @param  Builder<MedicineBatch>  $query
     */
    #[Scope]
    protected function usable(Builder $query): void
    {
        $query->where('quantity', '>', 0)
            ->where(function (Builder $q) {
                $q->whereNull('expiry_date')
                    ->orWhereDate('expiry_date', '>=', now()->toDateString());
            });
    }

    /** @param  Builder<MedicineBatch>  $query */
    #[Scope]
    protected function expired(Builder $query): void
    {
        $query->where('quantity', '>', 0)
            ->whereNotNull('expiry_date')
            ->whereDate('expiry_date', '<', now()->toDateString());
    }

    /** @param  Builder<MedicineBatch>  $query */
    #[Scope]
    protected function expiringSoon(Builder $query, int $days = 30): void
    {
        $query->where('quantity', '>', 0)
            ->whereNotNull('expiry_date')
            ->whereDate('expiry_date', '>=', now()->toDateString())
            ->whereDate('expiry_date', '<=', now()->addDays($days)->toDateString());
    }

    public function isExpired(): bool
    {
        return $this->expiry_date !== null && $this->expiry_date->isPast();
    }

    public function isExpiringSoon(int $days = 30): bool
    {
        return $this->expiry_date !== null
            && ! $this->isExpired()
            && $this->expiry_date->diffInDays(now()) <= $days;
    }

    public function expiryLabel(): string
    {
        return $this->expiry_date?->translatedFormat('d M Y') ?? '-';
    }

    public function statusLabel(): string
    {
        return match (true) {
            $this->quantity <= 0 => 'Habis',
            $this->isExpired() => 'Kedaluwarsa',
            $this->isExpiringSoon() => 'Mendekati Expired',
            default => 'Aman',
        };
    }

    public function statusColor(): string
    {
        return match (true) {
            $this->quantity <= 0 => 'secondary',
            $this->isExpired() => 'danger',
            $this->isExpiringSoon() => 'warning',
            default => 'success',
        };
    }
}

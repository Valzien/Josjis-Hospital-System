<?php

namespace App\Models;

use App\Enums\ActiveStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'medicine_code', 'name', 'category', 'unit', 'stock',
    'minimum_stock', 'price', 'expiry_date', 'description', 'status',
])]
class Medicine extends Model
{
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'status' => ActiveStatus::class,
            'price' => 'decimal:2',
            'expiry_date' => 'date',
        ];
    }

    public function prescriptionDetails(): HasMany
    {
        return $this->hasMany(PrescriptionDetail::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(MedicineTransaction::class);
    }

    public function batches(): HasMany
    {
        return $this->hasMany(MedicineBatch::class);
    }

    /** @param  Builder<Medicine>  $query */
    #[Scope]
    protected function search(Builder $query, ?string $term): void
    {
        if (blank($term)) {
            return;
        }

        $like = '%'.str_replace(['%', '_'], ['\%', '\_'], $term).'%';

        $query->where(function (Builder $q) use ($like) {
            $q->where('name', 'like', $like)
                ->orWhere('medicine_code', 'like', $like)
                ->orWhere('category', 'like', $like);
        });
    }

    /** @param  Builder<Medicine>  $query */
    #[Scope]
    protected function lowStock(Builder $query): void
    {
        $query->whereColumn('stock', '<=', 'minimum_stock');
    }

    /** @param  Builder<Medicine>  $query */
    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('status', ActiveStatus::Active->value);
    }

    /**
     * Obat yang memiliki stok kedaluwarsa, baik pada data lama maupun batch.
     *
     * @param  Builder<Medicine>  $query
     */
    #[Scope]
    protected function expired(Builder $query): void
    {
        $query->where(function (Builder $q) {
            $q->whereNotNull('expiry_date')
                ->whereDate('expiry_date', '<', now()->toDateString())
                ->orWhereHas('batches', fn (Builder $b) => $b->expired());
        })->distinct();
    }

    /** @param  Builder<Medicine>  $query */
    #[Scope]
    protected function expiringSoon(Builder $query, int $days = 30): void
    {
        $query->where(function (Builder $q) use ($days) {
            $q->whereNotNull('expiry_date')
                ->whereDate('expiry_date', '>=', now()->toDateString())
                ->whereDate('expiry_date', '<=', now()->addDays($days)->toDateString())
                ->orWhereHas('batches', fn (Builder $b) => $b->expiringSoon($days));
        })->distinct();
    }

    /**
     * Menyelaraskan tanggal kedaluwarsa ringkasan dengan batch yang masih ada.
     * Batch yang masih bisa dipakai lebih diutamakan daripada batch kedaluwarsa.
     */
    public function refreshExpiryFromBatches(): void
    {
        $this->loadMissing('batches');

        $remaining = $this->batches->where('quantity', '>', 0);
        $usable = $remaining->filter(fn (MedicineBatch $batch) => ! $batch->isExpired());
        $source = $usable->isNotEmpty() ? $usable : $remaining;

        $expiry = $source
            ->filter(fn (MedicineBatch $batch) => $batch->expiry_date !== null)
            ->sortBy(fn (MedicineBatch $batch) => $batch->expiry_date)
            ->first()?->expiry_date;

        if ($expiry !== null) {
            $this->update(['expiry_date' => $expiry->toDateString()]);
        }
    }

    public function isLowStock(): bool
    {
        return $this->stock <= $this->minimum_stock;
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

    public function isOutOfStock(): bool
    {
        return $this->stock <= 0;
    }

    public function stockBadge(): string
    {
        return match (true) {
            $this->isOutOfStock() => 'danger',
            $this->isLowStock() => 'warning',
            default => 'success',
        };
    }

    public function stockLabel(): string
    {
        return match (true) {
            $this->isOutOfStock() => 'Habis',
            $this->isLowStock() => 'Stok Rendah',
            default => 'Aman',
        };
    }

    /** @return array<string, string> */
    public static function categoryOptions(): array
    {
        return static::query()
            ->distinct()
            ->orderBy('category')
            ->pluck('category', 'category')
            ->all();
    }

    /** @return array<string, string> */
    public static function unitOptions(): array
    {
        return [
            'Tablet' => 'Tablet',
            'Kapsul' => 'Kapsul',
            'Kapsul Soft' => 'Kapsul Soft',
            'Sirup' => 'Sirup',
            'Suspensi' => 'Suspensi',
            'Injeksi' => 'Injeksi',
            'Salep' => 'Salep',
            'Krim' => 'Krim',
            'Tetes' => 'Tetes',
            'Povidon Iodine' => 'Povidon Iodine',
            'Perban' => 'Perban',
            'Masker' => 'Masker',
            'Box' => 'Box',
        ];
    }
}

<?php

namespace App\Models;

use App\Enums\TransactionType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[Fillable([
    'medicine_id', 'type', 'quantity', 'stock_before', 'stock_after',
    'reference_type', 'reference_id', 'notes', 'user_id',
])]
class MedicineTransaction extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'type' => TransactionType::class,
            'created_at' => 'datetime',
        ];
    }

    public function medicine(): BelongsTo
    {
        return $this->belongsTo(Medicine::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reference(): MorphTo
    {
        return $this->morphTo();
    }

    /** @param  Builder<MedicineTransaction>  $query */
    #[Scope]
    protected function type(Builder $query, TransactionType|array|string $type): void
    {
        $query->whereIn('type', is_array($type) ? $type : [$type]);
    }

    /** @param  Builder<MedicineTransaction>  $query */
    #[Scope]
    protected function forReference(Builder $query, string $type, int $id): void
    {
        $query->where('reference_type', $type)->where('reference_id', $id);
    }

    /** @param  Builder<MedicineTransaction>  $query */
    #[Scope]
    protected function search(Builder $query, ?string $term): void
    {
        if (blank($term)) {
            return;
        }

        $like = '%'.str_replace(['%', '_'], ['\%', '\_'], $term).'%';

        $query->where(function (Builder $q) use ($like) {
            $q->whereHas('medicine', fn (Builder $m) => $m->where('name', 'like', $like))
                ->orWhere('notes', 'like', $like);
        });
    }

    public function typeLabel(): string
    {
        return $this->type?->label() ?? '-';
    }

    public function referenceLabel(): string
    {
        return match ($this->reference_type) {
            Prescription::class => 'Resep '.$this->reference?->code,
            'manual' => 'Penyesuaian manual',
            default => 'Lainnya',
        };
    }
}

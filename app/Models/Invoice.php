<?php

namespace App\Models;

use App\Enums\InvoiceStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'code', 'patient_id', 'queue_id', 'medical_record_id', 'status', 'subtotal',
    'discount', 'total', 'paid_amount', 'notes', 'issued_by', 'paid_at',
])]
class Invoice extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'status' => InvoiceStatus::class,
            'subtotal' => 'decimal:2',
            'discount' => 'decimal:2',
            'total' => 'decimal:2',
            'paid_amount' => 'decimal:2',
            'paid_at' => 'datetime',
        ];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function queue(): BelongsTo
    {
        return $this->belongsTo(Queue::class);
    }

    public function medicalRecord(): BelongsTo
    {
        return $this->belongsTo(MedicalRecord::class);
    }

    public function issuedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class)->latest('paid_at');
    }

    /** @param  Builder<Invoice>  $query */
    #[Scope]
    protected function status(Builder $query, InvoiceStatus|string|array $status): void
    {
        $query->whereIn('status', is_array($status)
            ? array_map(fn ($s) => $s instanceof InvoiceStatus ? $s->value : $s, $status)
            : [$status instanceof InvoiceStatus ? $status->value : $status]);
    }

    /** @param  Builder<Invoice>  $query */
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

    public function statusLabel(): string
    {
        return $this->status?->label() ?? '-';
    }

    public function remainingAmount(): float
    {
        return max(0, (float) $this->total - (float) $this->paid_amount);
    }

    public function isPaid(): bool
    {
        return $this->status === InvoiceStatus::Paid;
    }

    public function isOpen(): bool
    {
        return $this->status?->isOpen() ?? false;
    }

    public function progressPercent(): int
    {
        if ((float) $this->total <= 0) {
            return 100;
        }

        return min(100, (int) round((float) $this->paid_amount / (float) $this->total * 100));
    }

    public function hasBillableRecord(): bool
    {
        return $this->medical_record_id !== null;
    }
}

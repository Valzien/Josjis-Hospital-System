<?php

namespace App\Models;

use App\Enums\PrescriptionStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'code', 'medical_record_id', 'patient_id', 'doctor_id',
    'status', 'notes', 'total_price', 'processed_at', 'processed_by',
])]
class Prescription extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'status' => PrescriptionStatus::class,
            'processed_at' => 'datetime',
            'total_price' => 'decimal:2',
        ];
    }

    public function medicalRecord(): BelongsTo
    {
        return $this->belongsTo(MedicalRecord::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    public function details(): HasMany
    {
        return $this->hasMany(PrescriptionDetail::class);
    }

    public function processedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    /** @param  Builder<Prescription>  $query */
    #[Scope]
    protected function status(Builder $query, PrescriptionStatus|array|string $status): void
    {
        $query->whereIn('status', is_array($status) ? $status : [$status]);
    }

    /** @param  Builder<Prescription>  $query */
    #[Scope]
    protected function search(Builder $query, ?string $term): void
    {
        if (blank($term)) {
            return;
        }

        $like = '%'.str_replace(['%', '_'], ['\%', '\_'], $term).'%';

        $query->where(function (Builder $q) use ($like) {
            $q->where('code', 'like', $like)
                ->orWhereHas('patient', fn (Builder $p) => $p->where('name', 'like', $like))
                ->orWhereHas('doctor', fn (Builder $d) => $d->where('name', 'like', $like));
        });
    }

    public function statusLabel(): string
    {
        return $this->status?->label() ?? '-';
    }

    public function isCompleted(): bool
    {
        return $this->status === PrescriptionStatus::Completed;
    }

    /** @return array<int, Medicine> */
    public function medicines(): array
    {
        return $this->details->map->medicine->filter()->values()->all();
    }

    /** Apakah seluruh item resep masih tersedia di stok. */
    public function isStockAvailable(): bool
    {
        return $this->details->every(
            fn (PrescriptionDetail $detail) => $detail->medicine !== null
                && $detail->medicine->stock >= $detail->quantity
        );
    }

    /** @return array<int, string> Nama obat yang stoknya tidak cukup. */
    public function insufficientMedicines(): array
    {
        return $this->details
            ->filter(fn (PrescriptionDetail $detail) => $detail->medicine === null || $detail->medicine->stock < $detail->quantity)
            ->map(fn (PrescriptionDetail $detail) => $detail->medicine?->name ?? 'Obat dihapus')
            ->values()
            ->all();
    }
}

<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Tanggal dokter tidak praktik (cuti).
 *
 * Satu dokter hanya boleh punya satu catatan cuti per tanggal. Keberadaan baris
 * pada tanggal tertentu menutup seluruh slot booking dan antrean dokter tersebut.
 */
#[Fillable(['doctor_id', 'date', 'reason', 'created_by'])]
class DoctorLeave extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'date' => 'date',
        ];
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** @param  Builder<DoctorLeave>  $query */
    #[Scope]
    protected function onDate(Builder $query, mixed $date): void
    {
        $query->whereDate('date', $date instanceof CarbonInterface ? $date->toDateString() : $date);
    }

    /** @param  Builder<DoctorLeave>  $query */
    #[Scope]
    protected function upcoming(Builder $query): void
    {
        $query->whereDate('date', '>=', now()->toDateString());
    }

    /** @param  Builder<DoctorLeave>  $query */
    #[Scope]
    protected function forDoctor(Builder $query, int $doctorId): void
    {
        $query->where('doctor_id', $doctorId);
    }

    public function isPast(): bool
    {
        return $this->date !== null && $this->date->lt(now()->startOfDay());
    }
}

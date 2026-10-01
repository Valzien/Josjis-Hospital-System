<?php

namespace App\Models;

use App\Enums\ActiveStatus;
use App\Enums\Day;
use App\Enums\QueueStatus;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['doctor_id', 'day', 'start_time', 'end_time', 'room', 'quota', 'status'])]
class DoctorSchedule extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'day' => Day::class,
            'status' => ActiveStatus::class,
        ];
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    public function queues(): HasMany
    {
        return $this->hasMany(Queue::class);
    }

    /** @param  Builder<DoctorSchedule>  $query */
    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('status', ActiveStatus::Active->value);
    }

    /** @param  Builder<DoctorSchedule>  $query */
    #[Scope]
    protected function forDay(Builder $query, int|CarbonInterface $day): void
    {
        $query->where('day', $day instanceof CarbonInterface ? $day->dayOfWeek : $day);
    }

    /** @param  Builder<DoctorSchedule>  $query */
    #[Scope]
    protected function forDoctor(Builder $query, int $doctorId): void
    {
        $query->where('doctor_id', $doctorId);
    }

    public function dayLabel(): string
    {
        return $this->day?->label() ?? '-';
    }

    public function timeRange(): string
    {
        return substr((string) $this->start_time, 0, 5).' - '.substr((string) $this->end_time, 0, 5);
    }

    /** Apakah dokter sedang melayani pada jam sekarang. */
    public function isOngoing(?CarbonInterface $at = null): bool
    {
        $at ??= now();
        $current = $at->format('H:i:s');

        return $this->status === ActiveStatus::Active
            && $this->day?->value === $at->dayOfWeek
            && $current >= substr((string) $this->start_time, 0, 8)
            && $current <= substr((string) $this->end_time, 0, 8);
    }

    public function isToday(): bool
    {
        return $this->day?->value === now()->dayOfWeek;
    }

    public function isActive(): bool
    {
        return $this->status === ActiveStatus::Active;
    }

    public function isOpen(?CarbonInterface $at = null): bool
    {
        $at ??= now();

        if (! $this->isActive() || ! $this->isToday()) {
            return false;
        }

        $current = $at->format('H:i:s');

        return $current >= substr((string) $this->start_time, 0, 8)
            && $current < substr((string) $this->end_time, 0, 8);
    }

    public function remainingQuota(): int
    {
        $used = $this->queues()
            ->whereDate('queue_date', now())
            ->whereNotIn('status', QueueStatus::cancelledValues())
            ->count();

        return max(0, (int) $this->quota - $used);
    }
}

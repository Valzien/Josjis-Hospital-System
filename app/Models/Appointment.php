<?php

namespace App\Models;

use App\Enums\AppointmentStatus;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

#[Fillable([
    'code', 'patient_id', 'doctor_id', 'doctor_schedule_id', 'appointment_date',
    'appointment_time', 'status', 'complaint_note', 'queue_id', 'created_by',
    'checked_in_at', 'cancelled_at',
])]
class Appointment extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'appointment_date' => 'date',
            'status' => AppointmentStatus::class,
            'checked_in_at' => 'datetime',
            'cancelled_at' => 'datetime',
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

    public function doctorSchedule(): BelongsTo
    {
        return $this->belongsTo(DoctorSchedule::class);
    }

    public function queue(): BelongsTo
    {
        return $this->belongsTo(Queue::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** @param  Builder<Appointment>  $query */
    #[Scope]
    protected function onDate(Builder $query, CarbonInterface|string $date): void
    {
        $query->whereDate('appointment_date', $date instanceof CarbonInterface ? $date->toDateString() : $date);
    }

    /** @param  Builder<Appointment>  $query */
    #[Scope]
    protected function status(Builder $query, AppointmentStatus|string $status): void
    {
        $query->where('status', $status instanceof AppointmentStatus ? $status->value : $status);
    }

    /** @param  Builder<Appointment>  $query */
    #[Scope]
    protected function upcoming(Builder $query): void
    {
        $query->whereDate('appointment_date', '>=', now()->toDateString())
            ->whereIn('status', [AppointmentStatus::Booked->value, AppointmentStatus::CheckedIn->value]);
    }

    /** @param  Builder<Appointment>  $query */
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

    public function slotLabel(): string
    {
        return substr((string) $this->appointment_time, 0, 5);
    }

    /** Tanggal + jam janji temu sebagai objek Carbon. */
    public function appointmentAt(): Carbon
    {
        return Carbon::parse(
            $this->appointment_date->toDateString().' '.$this->appointment_time
        );
    }

    public function isToday(): bool
    {
        return $this->appointment_date?->isToday() ?? false;
    }

    public function isBooked(): bool
    {
        return $this->status === AppointmentStatus::Booked;
    }

    public function canCheckIn(): bool
    {
        return $this->isBooked() && $this->isToday();
    }

    public function canCancel(): bool
    {
        return $this->status === AppointmentStatus::Booked
            && $this->appointment_date !== null
            && $this->appointment_date->gte(now()->startOfDay());
    }
}

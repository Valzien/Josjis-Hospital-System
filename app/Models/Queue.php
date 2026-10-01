<?php

namespace App\Models;

use App\Enums\QueueStatus;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\DB;

#[Fillable([
    'patient_id', 'doctor_id', 'doctor_schedule_id', 'queue_number', 'queue_date',
    'status', 'called_at', 'started_at', 'completed_at', 'complaint_note', 'created_by',
])]
class Queue extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'queue_date' => 'date',
            'called_at' => 'datetime',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'status' => QueueStatus::class,
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

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function medicalRecord(): HasOne
    {
        return $this->hasOne(MedicalRecord::class);
    }

    /** @param  Builder<Queue>  $query */
    #[Scope]
    protected function open(Builder $query): void
    {
        $query->whereIn('status', [
            QueueStatus::Waiting->value,
            QueueStatus::Called->value,
            QueueStatus::InExamination->value,
        ]);
    }

    /** @param  Builder<Queue>  $query */
    #[Scope]
    protected function onDate(Builder $query, CarbonInterface|string $date): void
    {
        $query->whereDate('queue_date', $date instanceof CarbonInterface ? $date->toDateString() : $date);
    }

    /** @param  Builder<Queue>  $query */
    #[Scope]
    protected function forDoctor(Builder $query, int $doctorId): void
    {
        $query->where('doctor_id', $doctorId);
    }

    public function statusLabel(): string
    {
        return $this->status?->label() ?? '-';
    }

    public function isOpen(): bool
    {
        return $this->status?->isOpen() ?? false;
    }

    public function isWaiting(): bool
    {
        return $this->status === QueueStatus::Waiting;
    }

    public function isCompleted(): bool
    {
        return $this->status === QueueStatus::Completed;
    }

    /**
     * Posisi pasien di antrean (1 = berikutnya dilayani).
     * Perhitungan berdasarkan antrean aktif dengan nomor lebih kecil pada dokter & tanggal sama.
     */
    public function position(): ?int
    {
        if (! $this->isOpen()) {
            return null;
        }

        return static::query()
            ->where('doctor_id', $this->doctor_id)
            ->whereDate('queue_date', $this->queue_date->toDateString())
            ->open()
            ->where('queue_number', '<', $this->queue_number)
            ->count() + 1;
    }

    /** Estimasi waktu giliran dipanggil (menit). */
    public function estimatedMinutes(): ?int
    {
        $position = $this->position();

        return $position === null ? null : ($position - 1) * 10;
    }

    /** Pasien yang sedang dipanggil pada dokter + tanggal ini. */
    public static function currentlyServing(?int $doctorId = null, ?CarbonInterface $date = null)
    {
        $date ??= now();

        return static::query()
            ->with(['patient', 'doctor'])
            ->onDate($date)
            ->whereIn('status', [QueueStatus::Called->value, QueueStatus::InExamination->value])
            ->when($doctorId, fn (Builder $q) => $q->where('doctor_id', $doctorId))
            ->orderByDesc('queue_number')
            ->first();
    }

    /** @return array<string, int> */
    public static function statusSummaryForToday(?int $doctorId = null): array
    {
        $rows = static::query()
            ->onDate(now())
            ->when($doctorId, fn (Builder $q) => $q->where('doctor_id', $doctorId))
            ->select('status', DB::raw('count(*) as aggregate'))
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $summary = [];

        foreach (QueueStatus::cases() as $case) {
            $summary[$case->value] = (int) ($rows[$case->value] ?? 0);
        }

        return $summary;
    }
}

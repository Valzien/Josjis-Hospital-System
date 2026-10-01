<?php

namespace App\Services;

use App\Enums\QueueStatus;
use App\Models\DoctorSchedule;
use App\Models\Patient;
use App\Models\Queue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Logika bisnis sistem antrean (brief §16).
 *
 * Semua perubahan nomor antrean & status harus lewat service ini agar
 * alur Registrasi -> Antrean -> Pemeriksaan tetap konsisten antar modul.
 */
class QueueService
{
    /**
     * Nomor antrean berikutnya untuk satu dokter pada tanggal tertentu.
     * Format: A-001, A-002, ... (diulang per dokter per hari).
     *
     * Nomor bersifat unik per (dokter, tanggal) sesuai index unik database,
     * sehingga tidak boleh difilter per jadwal although dokter punya
     * lebih dari satu sesi pada hari yang sama.
     */
    public function nextQueueNumber(int $doctorId, string $date, ?int $scheduleId = null): string
    {
        $lastNumber = Queue::query()
            ->where('doctor_id', $doctorId)
            ->whereDate('queue_date', $date)
            ->orderByDesc('queue_number')
            ->value('queue_number');

        $sequence = 0;

        if ($lastNumber !== null) {
            $sequence = (int) Str::afterLast((string) $lastNumber, '-');
        }

        return $this->formatNumber($sequence + 1);
    }

    public function formatNumber(int $sequence): string
    {
        return 'A-'.str_pad((string) $sequence, 3, '0', STR_PAD_LEFT);
    }

    /**
     * Membuat antrean baru. Menolak jika pasien sudah punya antrean aktif
     * pada hari yang sama (satu pasien satu antrean per hari).
     */
    public function takeQueue(
        Patient $patient,
        DoctorSchedule $schedule,
        ?string $complaintNote = null,
        ?int $createdBy = null,
    ): Queue {
        return DB::transaction(function () use ($patient, $schedule, $complaintNote, $createdBy) {
            $date = now()->toDateString();

            $existing = Queue::query()
                ->with('doctor')
                ->where('patient_id', $patient->id)
                ->whereDate('queue_date', $date)
                ->open()
                ->first();

            if ($existing) {
                throw new RuntimeException($existing->doctor_id === $schedule->doctor_id
                    ? "Pasien ini sudah memiliki antrean aktif ({$existing->queue_number}) pada dokter tersebut hari ini."
                    : "Pasien ini sudah memiliki antrean aktif ({$existing->queue_number}) pada {$existing->doctor->name} hari ini.");
            }

            $queue = Queue::create([
                'patient_id' => $patient->id,
                'doctor_id' => $schedule->doctor_id,
                'doctor_schedule_id' => $schedule->id,
                'queue_number' => $this->nextQueueNumber($schedule->doctor_id, $date),
                'queue_date' => $date,
                'status' => QueueStatus::Waiting,
                'complaint_note' => $complaintNote,
                'created_by' => $createdBy,
            ]);

            return $queue->load(['patient', 'doctor']);
        });
    }

    /**
     * Memanggil pasien berikutnya (nomor antrean terkecil yang masih WAITING).
     * Optionally langsung memindahkan ke IN_EXAMINATION.
     */
    public function callNext(int $doctorId, ?string $date = null, bool $startExamination = false): ?Queue
    {
        $date ??= now()->toDateString();

        return DB::transaction(function () use ($doctorId, $date, $startExamination) {
            $queue = Queue::query()
                ->where('doctor_id', $doctorId)
                ->whereDate('queue_date', $date)
                ->where('status', QueueStatus::Waiting->value)
                ->orderBy('queue_number')
                ->lockForUpdate()
                ->first();

            if (! $queue) {
                return null;
            }

            $queue->update([
                'status' => $startExamination ? QueueStatus::InExamination : QueueStatus::Called,
                'called_at' => now(),
                'started_at' => $startExamination ? now() : null,
            ]);

            return $queue->fresh(['patient', 'doctor']);
        });
    }

    /**
     * Ubah status antrean dengan validasi transisi.
     *
     * @param  array<string, mixed>  $extra  Kolom tambahan yang di-update bersamaan (mis. completed_at).
     */
    public function updateStatus(Queue $queue, QueueStatus $target, array $extra = []): Queue
    {
        $current = $queue->status;

        if ($current === $target) {
            return $queue;
        }

        if ($current !== null && ! $current->canTransitionTo($target)) {
            throw new RuntimeException("Antrean tidak dapat berubah dari status {$current->label()} menjadi {$target->label()}.");
        }

        $payload = array_merge(['status' => $target], $extra);

        match ($target) {
            QueueStatus::Called => $payload['called_at'] ??= now(),
            QueueStatus::InExamination => $payload['started_at'] ??= now(),
            QueueStatus::Completed => $payload['completed_at'] ??= now(),
            QueueStatus::Cancelled => $payload['completed_at'] ??= now(),
            default => null,
        };

        $queue->update($payload);

        return $queue->refresh();
    }

    /**
     * Memulihkan antrean yang dibatalkan kembali ke WAITING.
     */
    public function restoreToWaiting(Queue $queue): Queue
    {
        $queue->update([
            'status' => QueueStatus::Waiting,
            'called_at' => null,
            'started_at' => null,
            'completed_at' => null,
        ]);

        return $queue->refresh();
    }

    /**
     * Sisa antreanAhead = jumlah antrean aktif dengan nomor lebih kecil.
     *
     * @return array<int, Queue>
     */
    public function upcoming(int $doctorId, ?string $date = null, int $limit = 5): array
    {
        return Queue::query()
            ->with('patient')
            ->where('doctor_id', $doctorId)
            ->whereDate('queue_date', $date ?? now()->toDateString())
            ->whereIn('status', [QueueStatus::Waiting->value, QueueStatus::Called->value])
            ->orderBy('queue_number')
            ->limit($limit)
            ->get()
            ->all();
    }

    /**
     * Memastikan jadwal masih menerima antrean (kuota & jam operasional).
     */
    public function assertScheduleAcceptsQueue(DoctorSchedule $schedule): void
    {
        if (! $schedule->isActive()) {
            throw new RuntimeException('Jadwal dokter ini sedang tidak aktif.');
        }

        if ($schedule->day?->value !== now()->dayOfWeek) {
            throw new RuntimeException("Jadwal ini hanya berlaku pada hari {$schedule->dayLabel()}.");
        }

        $current = now()->format('H:i:s');
        $opensAt = substr((string) $schedule->start_time, 0, 8);
        $closesAt = substr((string) $schedule->end_time, 0, 8);

        if ($current < $opensAt) {
            throw new RuntimeException('Jam layanan belum dimulai. Jadwal buka pukul '.substr($opensAt, 0, 5).'.');
        }

        if ($current > $closesAt) {
            throw new RuntimeException("Jam layanan sudah selesai (batas akhir {$schedule->timeRange()}).");
        }

        $taken = Queue::query()
            ->where('doctor_schedule_id', $schedule->id)
            ->whereDate('queue_date', now()->toDateString())
            ->whereNotIn('status', QueueStatus::cancelledValues())
            ->count();

        if ($taken >= $schedule->quota) {
            throw new RuntimeException("Kuota antrean hari ini sudah penuh ({$schedule->quota}).");
        }
    }

    /**
     * Sisa kuota untuk hari ini.
     */
    public function remainingQuota(DoctorSchedule $schedule): int
    {
        $taken = Queue::query()
            ->where('doctor_schedule_id', $schedule->id)
            ->whereDate('queue_date', now()->toDateString())
            ->whereNotIn('status', QueueStatus::cancelledValues())
            ->count();

        return max(0, $schedule->quota - $taken);
    }
}

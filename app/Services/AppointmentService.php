<?php

namespace App\Services;

use App\Enums\ActiveStatus;
use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\DoctorSchedule;
use App\Models\Patient;
use App\Models\Queue;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Logika janji temu (booking) online.
 *
 * Janji temu disimpan terpisah dari antrean. Pada hari H, resepsionis
 * melakukan check-in sehingga booking berubah menjadi nomor antrean biasa
 * lewat QueueService — alur pelayanan tetap satu pintu.
 */
class AppointmentService
{
    public function __construct(
        private readonly NumberGenerator $numbers,
        private readonly QueueService $queues,
    ) {}

    public function bookingWindowDays(): int
    {
        return (int) config('jhs.booking.days_ahead', 30);
    }

    public function slotMinutes(): int
    {
        return max(5, (int) config('jhs.booking.slot_minutes', 30));
    }

    public function latestBookableDate(): Carbon
    {
        return now()->startOfDay()->addDays($this->bookingWindowDays());
    }

    /**
     * Slot waktu pada satu sesi jadwal untuk tanggal tertentu.
     *
     * @return array<int, array{time: string, available: bool, reason: string|null}>
     */
    public function availableSlots(DoctorSchedule $schedule, CarbonInterface|string $date): array
    {
        $day = $date instanceof CarbonInterface ? $date->copy()->startOfDay() : Carbon::parse($date)->startOfDay();

        if ($schedule->day?->value !== $day->dayOfWeek || $schedule->status !== ActiveStatus::Active) {
            return [];
        }

        $taken = Appointment::query()
            ->where('doctor_id', $schedule->doctor_id)
            ->whereDate('appointment_date', $day->toDateString())
            ->pluck('appointment_time')
            ->map(fn ($time) => substr((string) $time, 0, 5))
            ->all();

        $quotaFull = $this->remainingQuota($schedule, $day) <= 0;
        $slots = [];
        $cursor = Carbon::parse($day->toDateString().' '.$schedule->start_time);
        $end = Carbon::parse($day->toDateString().' '.$schedule->end_time);

        while ($cursor->lt($end)) {
            $time = $cursor->format('H:i');
            $reason = match (true) {
                in_array($time, $taken, true) => 'Sudah dibooking',
                $cursor->lt(now()) => 'Waktu sudah lewat',
                $quotaFull => 'Kuota sesi penuh',
                default => null,
            };

            $slots[] = [
                'time' => $time,
                'available' => $reason === null,
                'reason' => $reason,
            ];

            $cursor->addMinutes($this->slotMinutes());
        }

        return $slots;
    }

    public function remainingQuota(DoctorSchedule $schedule, CarbonInterface|string $date): int
    {
        $day = $date instanceof CarbonInterface ? $date->toDateString() : Carbon::parse($date)->toDateString();

        $used = Appointment::query()
            ->where('doctor_schedule_id', $schedule->id)
            ->whereDate('appointment_date', $day)
            ->whereIn('status', AppointmentStatus::occupyingValues())
            ->count();

        return max(0, (int) $schedule->quota - $used);
    }

    /**
     * @throws RuntimeException
     */
    public function create(
        Patient $patient,
        DoctorSchedule $schedule,
        string $date,
        string $time,
        ?string $complaintNote = null,
        ?int $createdBy = null,
    ): Appointment {
        $day = Carbon::parse($date)->startOfDay();
        $time = substr($time, 0, 5);

        $this->assertBookable($patient, $schedule, $day, $time);

        return DB::transaction(function () use ($patient, $schedule, $day, $time, $complaintNote, $createdBy) {
            $appointment = Appointment::create([
                'code' => $this->numbers->appointmentCode(),
                'patient_id' => $patient->id,
                'doctor_id' => $schedule->doctor_id,
                'doctor_schedule_id' => $schedule->id,
                'appointment_date' => $day->toDateString(),
                'appointment_time' => $time.':00',
                'status' => AppointmentStatus::Booked,
                'complaint_note' => $complaintNote,
                'created_by' => $createdBy,
            ]);

            return $appointment->load(['doctor', 'doctorSchedule', 'patient']);
        });
    }

    /**
     * @throws RuntimeException
     */
    public function assertBookable(Patient $patient, DoctorSchedule $schedule, CarbonInterface $day, string $time): void
    {
        if ($schedule->status !== ActiveStatus::Active) {
            throw new RuntimeException('Jadwal dokter ini sedang tidak aktif.');
        }

        if ($schedule->day?->value !== $day->dayOfWeek) {
            throw new RuntimeException("Jadwal ini hanya berlaku pada hari {$schedule->dayLabel()}.");
        }

        if ($day->lt(now()->startOfDay())) {
            throw new RuntimeException('Tanggal janji temu tidak boleh di masa lalu.');
        }

        if ($day->gt($this->latestBookableDate())) {
            throw new RuntimeException('Janji temu hanya dapat dibuat maksimal '.$this->bookingWindowDays().' hari ke depan.');
        }

        $slot = Carbon::parse($day->toDateString().' '.$time);

        if ($time < substr((string) $schedule->start_time, 0, 5) || $time >= substr((string) $schedule->end_time, 0, 5)) {
            throw new RuntimeException('Jam yang dipilih berada di luar jam praktik dokter.');
        }

        if ($slot->lt(now())) {
            throw new RuntimeException('Jam yang dipilih sudah lewat. Silakan pilih slot lain.');
        }

        $exists = Appointment::query()
            ->where('doctor_id', $schedule->doctor_id)
            ->whereDate('appointment_date', $day->toDateString())
            ->where('appointment_time', $time.':00')
            ->exists();

        if ($exists) {
            throw new RuntimeException('Slot ini sudah dibooking. Silakan pilih jam lain.');
        }

        $activeBooking = Appointment::query()
            ->where('patient_id', $patient->id)
            ->whereDate('appointment_date', $day->toDateString())
            ->whereIn('status', [AppointmentStatus::Booked->value, AppointmentStatus::CheckedIn->value])
            ->exists();

        if ($activeBooking) {
            throw new RuntimeException('Anda sudah memiliki janji temu aktif pada tanggal tersebut.');
        }

        if ($this->remainingQuota($schedule, $day) <= 0) {
            throw new RuntimeException('Kuota sesi dokter pada tanggal tersebut sudah penuh.');
        }
    }

    /**
     * Check-in booking hari ini lalu ubah menjadi antrean.
     *
     * @throws RuntimeException
     */
    public function checkIn(Appointment $appointment, ?int $userId = null): Appointment
    {
        if ($appointment->status !== AppointmentStatus::Booked) {
            throw new RuntimeException("Janji temu dengan status {$appointment->statusLabel()} tidak dapat di-check-in.");
        }

        if (! $appointment->isToday()) {
            throw new RuntimeException('Check-in hanya dapat dilakukan pada tanggal janji temu.');
        }

        $schedule = $appointment->doctorSchedule;

        if ($schedule === null) {
            throw new RuntimeException('Jadwal dokter untuk janji temu ini tidak tersedia.');
        }

        $this->queues->assertScheduleAcceptsQueue($schedule);

        return DB::transaction(function () use ($appointment, $schedule, $userId) {
            $queue = $this->queues->takeQueue(
                $appointment->patient,
                $schedule,
                $appointment->complaint_note,
                $userId,
            );

            $appointment->update([
                'status' => AppointmentStatus::CheckedIn,
                'queue_id' => $queue->id,
                'checked_in_at' => now(),
            ]);

            return $appointment->refresh()->load(['patient', 'doctor', 'queue']);
        });
    }

    public function cancel(Appointment $appointment): Appointment
    {
        if (! $appointment->canCancel()) {
            throw new RuntimeException('Janji temu ini tidak dapat dibatalkan.');
        }

        $appointment->update([
            'status' => AppointmentStatus::Cancelled,
            'cancelled_at' => now(),
        ]);

        return $appointment->refresh();
    }

    public function markNoShow(Appointment $appointment): Appointment
    {
        if ($appointment->status !== AppointmentStatus::Booked) {
            throw new RuntimeException('Hanya janji temu berstatus terjadwal yang dapat ditandai tidak hadir.');
        }

        $appointment->update([
            'status' => AppointmentStatus::NoShow,
            'cancelled_at' => now(),
        ]);

        return $appointment->refresh();
    }

    /**
     * Sinkronkan status booking ketika antrean terkait selesai.
     */
    public function completeForQueue(Queue $queue): void
    {
        $queue->appointment()
            ->where('status', AppointmentStatus::CheckedIn->value)
            ->update(['status' => AppointmentStatus::Completed->value]);
    }
}

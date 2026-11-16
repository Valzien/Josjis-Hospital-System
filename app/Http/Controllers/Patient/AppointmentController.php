<?php

namespace App\Http\Controllers\Patient;

use App\Enums\AppointmentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Patient\BookAppointmentRequest;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\DoctorSchedule;
use App\Models\Patient;
use App\Services\AppointmentService;
use App\Services\AuditLogger;
use App\Services\QueueService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;
use RuntimeException;

/**
 * Satu pintu pendaftaran pasien.
 *
 * Tanggal hari ini berarti pasien datang langsung dan mendapat nomor antrean;
 * tanggal ke depan berarti janji temu yang di-check-in resepsionis pada hari H.
 */
class AppointmentController extends Controller
{
    public function __construct(
        private readonly AppointmentService $appointments,
        private readonly QueueService $queues,
        private readonly AuditLogger $audit,
    ) {}

    public function index(): View
    {
        $patient = auth()->user()->patient;

        return view('patient.appointments.index', [
            'upcoming' => $patient->appointments()
                ->with(['doctor', 'doctorSchedule', 'queue'])
                ->upcoming()
                ->orderBy('appointment_date')
                ->orderBy('appointment_time')
                ->get(),
            'history' => $patient->appointments()
                ->with(['doctor', 'doctorSchedule', 'queue'])
                ->where(fn ($q) => $q->whereDate('appointment_date', '<', now()->toDateString())
                    ->orWhereNotIn('status', ['BOOKED', 'CHECKED_IN']))
                ->latest('appointment_date')
                ->latest('appointment_time')
                ->limit(10)
                ->get(),
        ]);
    }

    public function create(Request $request): View
    {
        $patient = auth()->user()->patient;

        $latest = $this->appointments->latestBookableDate();
        $date = $this->resolveDate($request->query('date'));
        $doctorId = $request->integer('doctor_id') ?: null;
        $isToday = $date->isToday();

        if ($isToday) {
            $schedules = DoctorSchedule::query()
                ->with('doctor')
                ->active()
                ->forDay($date)
                ->when($doctorId, fn ($q) => $q->where('doctor_id', $doctorId))
                ->orderBy('start_time')
                ->get();

            $doctors = Doctor::query()
                ->active()
                ->whereHas('schedules', fn ($q) => $q->active()->forDay($date))
                ->withCount(['schedules' => fn ($q) => $q->active()->forDay($date)])
                ->orderBy('name')
                ->get();
        } else {
            $schedules = DoctorSchedule::query()
                ->with('doctor')
                ->active()
                ->forDay($date)
                ->when($doctorId, fn ($q) => $q->where('doctor_id', $doctorId))
                ->orderBy('start_time')
                ->get()
                ->map(function (DoctorSchedule $schedule) use ($date) {
                    $schedule->slots = $this->appointments->availableSlots($schedule, $date);
                    $schedule->remaining_quota = $this->appointments->remainingQuota($schedule, $date);

                    return $schedule;
                })
                ->groupBy('doctor_id');

            $doctors = collect();
        }

        return view('patient.appointments.create', [
            'date' => $date,
            'latestDate' => $latest,
            'isToday' => $isToday,
            'schedules' => $schedules,
            'doctors' => $doctors,
            'selectedDoctor' => $doctorId,
            'slotMinutes' => $this->appointments->slotMinutes(),
            'isProfileComplete' => $patient->isProfileComplete(),
            'missingFields' => array_values(array_filter([
                blank($patient->birth_date) ? 'tanggal lahir' : null,
                blank($patient->phone) ? 'nomor telepon' : null,
                blank($patient->address) ? 'alamat' : null,
            ])),
            'existingQueue' => $isToday
                ? $patient->queues()->onDate(now())->open()->latest('id')->first()
                : null,
            'todayAppointment' => $isToday
                ? $patient->appointments()
                    ->onDate(now())
                    ->whereIn('status', AppointmentStatus::occupyingValues())
                    ->first()
                : null,
        ]);
    }

    public function store(BookAppointmentRequest $request): RedirectResponse
    {
        $patient = auth()->user()->patient;

        if (! $patient->isProfileComplete()) {
            return back()->withInput()->with('error', 'Lengkapi data profil Anda sebelum mendaftar berobat.');
        }

        $schedule = DoctorSchedule::query()->with('doctor')->findOrFail($request->integer('doctor_schedule_id'));
        $date = Carbon::parse($request->string('appointment_date')->value())->startOfDay();

        if ($date->isToday()) {
            return $this->takeToday($request, $patient, $schedule);
        }

        try {
            $appointment = $this->appointments->create(
                $patient,
                $schedule,
                $request->string('appointment_date')->value(),
                $request->string('appointment_time')->value(),
                $request->input('complaint_note'),
                $patient->user_id,
            );
        } catch (RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        $this->audit->created(
            'appointment',
            'Appointment',
            $appointment->id,
            "Pasien {$patient->name} membuat janji temu {$appointment->code} pada {$schedule->doctor->name} tanggal {$appointment->appointment_date->translatedFormat('d M Y')} pukul {$appointment->slotLabel()}."
        );

        return redirect()->route('patient.appointments.index')
            ->with('success', "Janji temu {$appointment->code} berhasil dibuat. Datang dan check-in pada tanggal {$appointment->appointment_date->translatedFormat('d M Y')} pukul {$appointment->slotLabel()}.");
    }

    public function cancel(Appointment $appointment): RedirectResponse
    {
        abort_if($appointment->patient_id !== auth()->user()->patient?->id, 403);

        try {
            $this->appointments->cancel($appointment);
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        $this->audit->updated('appointment', 'Appointment', $appointment->id, "Pasien membatalkan janji temu {$appointment->code}.");

        return redirect()->route('patient.appointments.index')
            ->with('success', "Janji temu {$appointment->code} telah dibatalkan.");
    }

    /** Kunjungan hari ini: booking tidak dipakai, langsung terbit nomor antrean. */
    private function takeToday(BookAppointmentRequest $request, Patient $patient, DoctorSchedule $schedule): RedirectResponse
    {
        if (! $schedule->isToday()) {
            return back()->withInput()->with('error', "Jadwal ini berlaku pada hari {$schedule->dayLabel()}, bukan hari ini.");
        }

        try {
            $this->queues->assertScheduleAcceptsQueue($schedule);
            $queue = $this->queues->takeQueue(
                $patient,
                $schedule,
                $request->input('complaint_note'),
                $patient->user_id,
            );
        } catch (RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        $this->audit->created(
            'queue',
            'Queue',
            $queue->id,
            "Pasien {$patient->name} mendaftar hari ini dan mendapat antrean {$queue->queue_number} pada {$schedule->doctor->name}."
        );

        return redirect()->route('patient.queue.show', $queue)
            ->with('success', "Berhasil! Nomor antrean Anda adalah {$queue->queue_number}.");
    }

    private function resolveDate(?string $value): Carbon
    {
        $date = $value ? Carbon::parse($value)->startOfDay() : now()->startOfDay();

        if ($date->lt(now()->startOfDay())) {
            return now()->startOfDay();
        }

        if ($date->gt($this->appointments->latestBookableDate())) {
            return $this->appointments->latestBookableDate();
        }

        return $date;
    }
}

<?php

namespace App\Http\Controllers\Reception;

use App\Enums\AppointmentStatus;
use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Services\AppointmentService;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

class AppointmentController extends Controller
{
    public function __construct(
        private readonly AppointmentService $appointments,
        private readonly AuditLogger $audit,
    ) {}

    public function index(Request $request): View
    {
        $date = $request->date('date') ?? now();

        $query = Appointment::query()
            ->with(['patient', 'doctor', 'doctorSchedule', 'queue'])
            ->onDate($date)
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')->value))
            ->when($request->filled('q'), fn ($q) => $q->search($request->string('q')->trim()->value()))
            ->orderBy('appointment_time');

        return view('reception.appointments.index', [
            'appointments' => $query->paginate(20)->withQueryString(),
            'date' => $date->toDateString(),
            'statuses' => AppointmentStatus::options(),
            'filters' => $request->only('status', 'q'),
            'summary' => $this->summaryForDate($date->toDateString()),
        ]);
    }

    public function checkIn(Appointment $appointment): RedirectResponse
    {
        try {
            $this->appointments->checkIn($appointment, auth()->id());
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        $appointment->refresh()->load('queue');

        $this->audit->updated(
            'appointment',
            'Appointment',
            $appointment->id,
            "Check-in janji temu {$appointment->code} menghasilkan antrean {$appointment->queue?->queue_number}."
        );

        return back()->with('success', "Check-in berhasil. Nomor antrean: {$appointment->queue?->queue_number}.");
    }

    public function cancel(Appointment $appointment): RedirectResponse
    {
        try {
            $this->appointments->cancel($appointment);
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        $this->audit->updated('appointment', 'Appointment', $appointment->id, "Resepsionis membatalkan janji temu {$appointment->code}.");

        return back()->with('success', "Janji temu {$appointment->code} dibatalkan.");
    }

    public function noShow(Appointment $appointment): RedirectResponse
    {
        try {
            $this->appointments->markNoShow($appointment);
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        $this->audit->updated('appointment', 'Appointment', $appointment->id, "Janji temu {$appointment->code} ditandai tidak hadir.");

        return back()->with('success', "Janji temu {$appointment->code} ditandai tidak hadir.");
    }

    /** @return array<string, int> */
    private function summaryForDate(string $date): array
    {
        $summary = [];

        foreach (AppointmentStatus::cases() as $case) {
            $summary[$case->value] = Appointment::query()
                ->onDate($date)
                ->where('status', $case->value)
                ->count();
        }

        return $summary;
    }
}

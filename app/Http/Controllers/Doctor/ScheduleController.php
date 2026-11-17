<?php

namespace App\Http\Controllers\Doctor;

use App\Enums\AppointmentStatus;
use App\Enums\QueueStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Doctor\DoctorLeaveRequest;
use App\Models\Appointment;
use App\Models\DoctorLeave;
use App\Models\DoctorSchedule;
use App\Models\Queue;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ScheduleController extends Controller
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function index(): View
    {
        $doctor = auth()->user()->doctor;
        $doctorId = $doctor?->id ?? 0;

        return view('doctor.schedules.index', [
            'doctor' => $doctor,
            'schedules' => DoctorSchedule::query()
                ->forDoctor($doctorId)
                ->orderBy('day')
                ->orderBy('start_time')
                ->get(),
            'leaves' => DoctorLeave::query()
                ->forDoctor($doctorId)
                ->upcoming()
                ->orderBy('date')
                ->get(),
            'bookedCounts' => Appointment::query()
                ->selectRaw('DATE(appointment_date) as leave_date, count(*) as total')
                ->where('doctor_id', $doctorId)
                ->whereIn('status', AppointmentStatus::occupyingValues())
                ->whereDate('appointment_date', '>=', now()->toDateString())
                ->groupBy('leave_date')
                ->pluck('total', 'leave_date'),
            'todayStats' => $doctor ? [
                'total' => Queue::query()->forDoctor($doctor->id)->onDate(now())->count(),
                'completed' => Queue::query()->forDoctor($doctor->id)->onDate(now())->where('status', QueueStatus::Completed->value)->count(),
            ] : ['total' => 0, 'completed' => 0],
        ]);
    }

    public function storeLeave(DoctorLeaveRequest $request): RedirectResponse
    {
        $doctor = auth()->user()->doctor;

        abort_if($doctor === null, 403, 'Akun Anda belum ditautkan ke profil dokter.');

        $leave = DoctorLeave::create($request->payload() + [
            'doctor_id' => $doctor->id,
            'created_by' => auth()->id(),
        ]);

        $this->audit->created(
            'doctor_leave',
            'DoctorLeave',
            $leave->id,
            "Dokter {$doctor->name} menandai cuti pada {$leave->date->translatedFormat('d F Y')}."
        );

        $booked = Appointment::query()
            ->where('doctor_id', $doctor->id)
            ->whereDate('appointment_date', $leave->date->toDateString())
            ->whereIn('status', AppointmentStatus::occupyingValues())
            ->count();

        $message = "Cuti pada {$leave->date->translatedFormat('d F Y')} berhasil disimpan. Slot booking pada tanggal tersebut ditutup.";

        if ($booked > 0) {
            $message .= " Perhatian: {$booked} janji temu pada tanggal tersebut perlu dijadwalkan ulang atau dibatalkan.";
        }

        return back()->with('success', $message);
    }

    public function destroyLeave(DoctorLeave $leave): RedirectResponse
    {
        $doctor = auth()->user()->doctor;

        abort_if($doctor === null || $leave->doctor_id !== $doctor->id, 403);

        $date = $leave->date?->translatedFormat('d F Y') ?? '-';
        $leaveId = $leave->id;
        $leave->delete();

        $this->audit->deleted('doctor_leave', 'DoctorLeave', $leaveId, "Dokter {$doctor->name} membatalkan cuti pada {$date}.");

        return back()->with('success', "Cuti pada {$date} telah dibatalkan.");
    }
}

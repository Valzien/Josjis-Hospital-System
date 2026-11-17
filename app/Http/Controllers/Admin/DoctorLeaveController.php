<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AppointmentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DoctorLeaveRequest;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\DoctorLeave;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DoctorLeaveController extends Controller
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function index(Request $request): View
    {
        $period = $request->string('period')->value() === 'past' ? 'past' : 'upcoming';

        $leaves = DoctorLeave::query()
            ->with(['doctor', 'createdBy'])
            ->when($request->filled('doctor_id'), fn ($q) => $q->forDoctor($request->integer('doctor_id')))
            ->when($period === 'upcoming', fn ($q) => $q->upcoming(), fn ($q) => $q->whereDate('date', '<', now()->toDateString()))
            ->orderBy('date', $period === 'upcoming' ? 'asc' : 'desc')
            ->paginate(20)
            ->withQueryString();

        return view('admin.leaves.index', [
            'leaves' => $leaves,
            'doctors' => Doctor::query()->active()->orderBy('name')->get(),
            'period' => $period,
            'filters' => $request->only('doctor_id', 'period'),
        ]);
    }

    public function store(DoctorLeaveRequest $request): RedirectResponse
    {
        $payload = $request->payload();
        $leave = DoctorLeave::create($payload + ['created_by' => auth()->id()]);
        $doctor = Doctor::query()->find($payload['doctor_id']);

        $this->audit->created(
            'doctor_leave',
            'DoctorLeave',
            $leave->id,
            "Menandai cuti {$doctor?->name} pada {$leave->date->translatedFormat('d F Y')}."
        );

        $booked = Appointment::query()
            ->where('doctor_id', $leave->doctor_id)
            ->whereDate('appointment_date', $leave->date->toDateString())
            ->whereIn('status', AppointmentStatus::occupyingValues())
            ->count();

        $message = "Cuti {$doctor?->name} pada {$leave->date->translatedFormat('d F Y')} berhasil disimpan.";

        if ($booked > 0) {
            $message .= " Perhatian: {$booked} janji temu pada tanggal tersebut perlu dijadwalkan ulang atau dibatalkan.";
        }

        return back()->with('success', $message);
    }

    public function destroy(DoctorLeave $leave): RedirectResponse
    {
        $doctor = $leave->doctor?->name ?? 'Dokter';
        $date = $leave->date?->translatedFormat('d F Y') ?? '-';
        $leaveId = $leave->id;

        $leave->delete();

        $this->audit->deleted('doctor_leave', 'DoctorLeave', $leaveId, "Menghapus cuti {$doctor} pada {$date}.");

        return back()->with('success', "Cuti {$doctor} pada {$date} telah dihapus.");
    }
}

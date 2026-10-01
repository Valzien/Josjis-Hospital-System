<?php

namespace App\Http\Controllers\Patient;

use App\Enums\QueueStatus;
use App\Http\Controllers\Controller;
use App\Models\Doctor;
use App\Models\Queue;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DoctorController extends Controller
{
    public function index(Request $request): View
    {
        $doctors = Doctor::query()
            ->active()
            ->withCount('schedules')
            ->with(['schedules' => fn ($q) => $q->active()->forDay($request->boolean('available_today'))])
            ->when($request->filled('q'), fn ($q) => $q->search($request->string('q')->trim()->value()))
            ->when($request->filled('specialization'), fn ($q) => $q->where('specialization', $request->string('specialization')->value))
            ->orderBy('name')
            ->paginate(9)
            ->withQueryString();

        return view('patient.doctors.index', [
            'doctors' => $doctors,
            'specializations' => Doctor::query()->active()->distinct()->orderBy('specialization')->pluck('specialization'),
            'filters' => $request->only('q', 'specialization', 'available_today'),
        ]);
    }

    public function show(Doctor $doctor): View
    {
        abort_if(! $doctor->isActive(), 404);

        $doctor->load(['schedules' => fn ($q) => $q->active()->orderBy('day')]);

        return view('patient.doctors.show', [
            'doctor' => $doctor,
            'queueCount' => $doctor->queues()->count(),
            'myHistory' => auth()->user()->patient?->medicalRecords()->where('doctor_id', $doctor->id)->count() ?? 0,
            'waitingToday' => Queue::query()->forDoctor($doctor->id)->onDate(now())->where('status', QueueStatus::Waiting->value)->count(),
        ]);
    }
}

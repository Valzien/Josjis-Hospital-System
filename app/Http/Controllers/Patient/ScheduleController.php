<?php

namespace App\Http\Controllers\Patient;

use App\Http\Controllers\Controller;
use App\Models\Doctor;
use App\Models\DoctorSchedule;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ScheduleController extends Controller
{
    public function index(Request $request): View
    {
        $date = $request->date('date') ?? now();

        $schedules = DoctorSchedule::query()
            ->with('doctor')
            ->active()
            ->withCount(['queues' => fn ($q) => $q->whereDate('queue_date', $date->toDateString())])
            ->when($request->filled('doctor_id'), fn ($q) => $q->where('doctor_id', $request->integer('doctor_id')))
            ->when($request->filled('specialization'), fn ($q) => $q->whereHas('doctor', fn ($d) => $d->where('specialization', $request->string('specialization')->value)))
            ->orderBy('day')
            ->orderBy('start_time')
            ->paginate(20)
            ->withQueryString();

        $grouped = $schedules->groupBy(fn (DoctorSchedule $s) => $s->dayLabel());

        return view('patient.schedules.index', [
            'schedules' => $schedules,
            'grouped' => $grouped,
            'doctors' => Doctor::query()->active()->orderBy('name')->get(),
            'specializations' => Doctor::query()->active()->distinct()->orderBy('specialization')->pluck('specialization'),
            'date' => $date->toDateString(),
            'filters' => $request->only('doctor_id', 'specialization'),
        ]);
    }
}

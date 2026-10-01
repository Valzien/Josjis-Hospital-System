<?php

namespace App\Http\Controllers\Doctor;

use App\Enums\QueueStatus;
use App\Http\Controllers\Controller;
use App\Models\DoctorSchedule;
use App\Models\Queue;
use Illuminate\View\View;

class ScheduleController extends Controller
{
    public function index(): View
    {
        $doctor = auth()->user()->doctor;

        return view('doctor.schedules.index', [
            'doctor' => $doctor,
            'schedules' => DoctorSchedule::query()
                ->forDoctor($doctor?->id ?? 0)
                ->orderBy('day')
                ->orderBy('start_time')
                ->get(),
            'todayStats' => $doctor ? [
                'total' => Queue::query()->forDoctor($doctor->id)->onDate(now())->count(),
                'completed' => Queue::query()->forDoctor($doctor->id)->onDate(now())->where('status', QueueStatus::Completed->value)->count(),
            ] : ['total' => 0, 'completed' => 0],
        ]);
    }
}

<?php

namespace App\Http\Controllers\Reception;

use App\Enums\Day;
use App\Http\Controllers\Controller;
use App\Models\Doctor;
use App\Models\DoctorSchedule;
use App\Services\QueueService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ScheduleController extends Controller
{
    public function index(Request $request, QueueService $queueService): View
    {
        $date = $request->date('date') ?? now();

        $schedules = DoctorSchedule::query()
            ->with('doctor')
            ->withCount(['queues' => fn ($q) => $q->whereDate('queue_date', $date->toDateString())])
            ->when($request->filled('doctor_id'), fn ($q) => $q->where('doctor_id', $request->integer('doctor_id')))
            ->when($request->filled('day'), fn ($q) => $q->forDay($request->integer('day')))
            ->orderBy('day')
            ->orderBy('start_time')
            ->paginate(20)
            ->withQueryString();

        return view('reception.schedules.index', [
            'schedules' => $schedules,
            'doctors' => Doctor::query()->active()->orderBy('name')->get(),
            'days' => Day::options(),
            'date' => $date->toDateString(),
            'filters' => $request->only('doctor_id', 'day'),
            'isToday' => $date->isToday(),
        ]);
    }
}

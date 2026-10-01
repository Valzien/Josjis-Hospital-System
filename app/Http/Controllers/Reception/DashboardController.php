<?php

namespace App\Http\Controllers\Reception;

use App\Enums\QueueStatus;
use App\Http\Controllers\Controller;
use App\Models\Doctor;
use App\Models\DoctorSchedule;
use App\Models\Patient;
use App\Models\Queue;
use App\Services\QueueService;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(QueueService $queueService): View
    {
        $today = now()->toDateString();
        $summary = Queue::statusSummaryForToday();

        $todaySchedules = DoctorSchedule::query()
            ->with('doctor')
            ->active()
            ->forDay(now())
            ->orderBy('start_time')
            ->get()
            ->map(fn (DoctorSchedule $s) => [
                'schedule' => $s,
                'taken' => Queue::query()->where('doctor_schedule_id', $s->id)->whereDate('queue_date', $today)->count(),
                'remaining' => $queueService->remainingQuota($s),
                'done' => Queue::query()->where('doctor_schedule_id', $s->id)->whereDate('queue_date', $today)
                    ->where('status', QueueStatus::Completed->value)->count(),
            ]);

        return view('reception.dashboard', [
            'summary' => $summary,
            'totalToday' => array_sum($summary),
            'waitingQueues' => Queue::query()
                ->with(['patient', 'doctor'])
                ->onDate($today)
                ->where('status', QueueStatus::Waiting->value)
                ->orderBy('queue_number')
                ->limit(10)
                ->get(),
            'nowServing' => Queue::query()
                ->with(['patient', 'doctor'])
                ->onDate($today)
                ->whereIn('status', [QueueStatus::Called->value, QueueStatus::InExamination->value])
                ->orderByDesc('queue_number')
                ->limit(6)
                ->get(),
            'todaySchedules' => $todaySchedules,
            'onDutyDoctors' => Doctor::query()->whereHas('schedules', fn ($q) => $q->active()->forDay(now()))->count(),
            'newPatientsToday' => Patient::query()->whereDate('created_at', $today)->count(),
            'totalPatients' => Patient::query()->count(),
            'completedToday' => $summary[QueueStatus::Completed->value] ?? 0,
        ]);
    }
}

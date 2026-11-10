<?php

namespace App\Http\Controllers\Reception;

use App\Enums\QueueStatus;
use App\Http\Controllers\Controller;
use App\Models\Doctor;
use App\Models\DoctorSchedule;
use App\Models\Patient;
use App\Models\Queue;
use App\Services\QueueService;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(QueueService $queueService): View
    {
        $today = now()->toDateString();
        $summary = Queue::statusSummaryForToday();

        $queueStats = Queue::query()
            ->onDate($today)
            ->whereNotNull('doctor_schedule_id')
            ->select('doctor_schedule_id', 'status', DB::raw('count(*) as aggregate'))
            ->groupBy('doctor_schedule_id', 'status')
            ->get()
            ->groupBy('doctor_schedule_id');

        $cancelled = QueueStatus::cancelledValues();

        $todaySchedules = DoctorSchedule::query()
            ->with('doctor')
            ->active()
            ->forDay(now())
            ->orderBy('start_time')
            ->get()
            ->map(function (DoctorSchedule $s) use ($queueStats, $cancelled) {
                $stats = $queueStats->get($s->id, collect());
                $taken = (int) $stats->sum('aggregate');
                $activeTaken = (int) $stats->whereNotIn('status', $cancelled)->sum('aggregate');
                $done = (int) $stats->where('status', QueueStatus::Completed->value)->sum('aggregate');
                $remaining = max(0, $s->quota - $activeTaken);

                return [
                    'schedule' => $s,
                    'taken' => $taken,
                    'remaining' => $remaining,
                    'done' => $done,
                ];
            });

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
            'staleCount' => Queue::query()->whereDate('queue_date', '<', $today)->whereIn('status', [QueueStatus::Waiting->value, QueueStatus::Called->value])->count(),
        ]);
    }
}

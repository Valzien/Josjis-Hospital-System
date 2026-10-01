<?php

namespace App\Http\Controllers\Doctor;

use App\Enums\QueueStatus;
use App\Http\Controllers\Controller;
use App\Models\DoctorSchedule;
use App\Models\MedicalRecord;
use App\Models\Prescription;
use App\Models\Queue;
use App\Services\QueueService;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(QueueService $queueService): View
    {
        $doctor = auth()->user()->doctor;
        $today = now()->toDateString();
        $summary = Queue::statusSummaryForToday($doctor?->id);

        return view('doctor.dashboard', [
            'doctor' => $doctor,
            'summary' => $summary,
            'waitingQueues' => Queue::query()
                ->with('patient')
                ->forDoctor($doctor?->id ?? 0)
                ->onDate($today)
                ->where('status', QueueStatus::Waiting->value)
                ->orderBy('queue_number')
                ->limit(8)
                ->get(),
            'currentQueue' => Queue::query()
                ->with(['patient', 'medicalRecord.prescription'])
                ->forDoctor($doctor?->id ?? 0)
                ->onDate($today)
                ->whereIn('status', [QueueStatus::Called->value, QueueStatus::InExamination->value])
                ->orderByDesc('queue_number')
                ->first(),
            'nextUp' => $queueService->upcoming($doctor?->id ?? 0, $today, 5),
            'todaySchedules' => DoctorSchedule::query()
                ->with('doctor')
                ->forDoctor($doctor?->id ?? 0)
                ->active()
                ->orderBy('day')
                ->get(),
            'isOnDutyToday' => DoctorSchedule::query()->forDoctor($doctor?->id ?? 0)->active()->forDay(now())->exists(),
            'todayRecords' => MedicalRecord::query()
                ->with(['patient', 'prescription'])
                ->where('doctor_id', $doctor?->id)
                ->whereDate('examined_at', $today)
                ->latest('examined_at')
                ->limit(5)
                ->get(),
            'monthlyCount' => MedicalRecord::query()
                ->where('doctor_id', $doctor?->id)
                ->where('examined_at', '>=', now()->startOfMonth())
                ->count(),
            'totalRecords' => MedicalRecord::query()->where('doctor_id', $doctor?->id)->count(),
            'totalPrescriptions' => Prescription::query()->where('doctor_id', $doctor?->id)->count(),
            'waitingCount' => $summary[QueueStatus::Waiting->value] ?? 0,
            'completedCount' => $summary[QueueStatus::Completed->value] ?? 0,
        ]);
    }
}

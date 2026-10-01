<?php

namespace App\Http\Controllers\Patient;

use App\Http\Controllers\Controller;
use App\Models\Doctor;
use App\Models\Queue;
use App\Services\QueueService;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(QueueService $queueService): View
    {
        $patient = auth()->user()->patient;
        $today = now()->toDateString();

        $myQueue = $patient?->queues()->with('doctor')->onDate($today)->latest('id')->first();
        $doctorsCount = Doctor::query()->active()->count();
        $recordsCount = $patient?->medicalRecords()->count() ?? 0;

        $doctorsToday = Doctor::query()
            ->whereHas('schedules', fn ($q) => $q->active()->forDay(now()))
            ->count();

        return view('patient.dashboard', [
            'patient' => $patient,
            'queue' => $myQueue,
            'position' => $myQueue?->position(),
            'estimatedMinutes' => $myQueue?->estimatedMinutes(),
            'servingNow' => $myQueue ? Queue::currentlyServing($myQueue->doctor_id, now()) : null,
            'nextUp' => $myQueue ? $queueService->upcoming($myQueue->doctor_id, $today, 4) : [],
            'latestRecord' => $patient?->latestMedicalRecord(),
            'latestPrescription' => $patient?->prescriptions()->with('doctor')->latest()->first(),
            'recentRecords' => $patient?->medicalRecords()->with('doctor')->latest('examined_at')->limit(4)->get() ?? collect(),
            'stats' => [
                'doctors' => $doctorsCount,
                'doctors_today' => $doctorsToday,
                'records' => $recordsCount,
                'queues' => $patient?->queues()->count() ?? 0,
            ],
            'isProfileComplete' => $patient?->isProfileComplete() ?? false,
            'todayQueueCount' => $patient?->queues()->onDate($today)->count() ?? 0,
        ]);
    }
}

<?php

namespace App\Http\Controllers\Patient;

use App\Enums\QueueStatus;
use App\Http\Controllers\Controller;
use App\Models\Queue;
use App\Services\AuditLogger;
use App\Services\QueueService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class QueueController extends Controller
{
    public function __construct(
        private readonly QueueService $queues,
        private readonly AuditLogger $audit,
    ) {}

    public function index(Request $request): View
    {
        $queues = auth()->user()->patient->queues()
            ->with(['doctor', 'doctorSchedule', 'medicalRecord'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')->value))
            ->latest('queue_date')
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return view('patient.queue.index', [
            'queues' => $queues,
            'statuses' => QueueStatus::options(),
            'filters' => $request->only('status'),
            'activeQueue' => auth()->user()->patient->queues()->onDate(now())->open()->latest('id')->first(),
        ]);
    }

    public function show(Queue $queue): View
    {
        abort_if($queue->patient_id !== auth()->user()->patient?->id, 403);

        $queue->load([
            'doctor',
            'doctorSchedule',
            'medicalRecord.doctor',
            'medicalRecord.prescription.details.medicine',
        ]);

        return view('patient.queue.show', [
            'queue' => $queue,
            'position' => $queue->position(),
            'estimatedMinutes' => $queue->estimatedMinutes(),
            'servingNow' => Queue::currentlyServing($queue->doctor_id, $queue->queue_date),
            'nextUp' => $this->queues->upcoming($queue->doctor_id, $queue->queue_date->toDateString(), 5),
        ]);
    }

    public function ticket(Queue $queue): View
    {
        abort_if($queue->patient_id !== auth()->user()->patient?->id, 403);
        $queue->load(['patient', 'doctor', 'doctorSchedule']);

        return view('reception.queues.ticket', [
            'queue' => $queue,
            'isPatient' => true,
        ]);
    }

    public function cancel(Queue $queue): RedirectResponse
    {
        abort_if($queue->patient_id !== auth()->user()->patient?->id, 403);

        if (! $queue->isOpen()) {
            return back()->with('error', 'Antrean ini tidak dapat dibatalkan.');
        }

        $this->queues->updateStatus($queue, QueueStatus::Cancelled);

        $this->audit->updated('queue', 'Queue', $queue->id, "Pasien membatalkan antrean {$queue->queue_number}.");

        return redirect()->route('patient.queue.index')->with('success', "Antrean {$queue->queue_number} telah dibatalkan.");
    }
}

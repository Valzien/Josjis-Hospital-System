<?php

namespace App\Http\Controllers\Doctor;

use App\Enums\QueueStatus;
use App\Http\Controllers\Controller;
use App\Models\Queue;
use App\Services\AuditLogger;
use App\Services\QueueService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

class QueueController extends Controller
{
    public function __construct(
        private readonly QueueService $queues,
        private readonly AuditLogger $audit,
    ) {}

    public function index(Request $request): View
    {
        $doctorId = $this->doctorId();
        $date = $request->date('date') ?? now();

        $queues = Queue::query()
            ->with(['patient', 'medicalRecord.prescription'])
            ->forDoctor($doctorId)
            ->onDate($date)
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')->value()))
            ->when($request->filled('q'), fn ($q) => $q->whereHas('patient', fn ($p) => $p->search($request->string('q')->trim()->value())))
            ->orderBy('queue_number')
            ->paginate(20)
            ->withQueryString();

        return view('doctor.queues.index', [
            'queues' => $queues,
            'statuses' => QueueStatus::options(),
            'date' => $date->toDateString(),
            'filters' => $request->only('status', 'q'),
            'summary' => Queue::statusSummaryForToday($doctorId),
        ]);
    }

    public function show(Queue $queue): View
    {
        $this->ensureOwnQueue($queue);

        $queue->load(['patient.user', 'doctorSchedule', 'medicalRecord.prescription.details.medicine']);

        return view('doctor.queues.show', [
            'queue' => $queue,
            'position' => $queue->position(),
            'history' => $queue->patient?->medicalRecords()->with('doctor')->latest('examined_at')->limit(5)->get(),
        ]);
    }

    public function start(Queue $queue): RedirectResponse
    {
        $this->ensureOwnQueue($queue);

        try {
            $this->queues->updateStatus($queue, QueueStatus::InExamination);
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        $this->audit->updated('examination', 'Queue', $queue->id, "Memulai pemeriksaan antrean {$queue->queue_number}.");

        return redirect()->route('doctor.examinations.create', $queue)
            ->with('success', "Pemeriksaan untuk {$queue->patient->name} dimulai.");
    }

    public function complete(Queue $queue): RedirectResponse
    {
        $this->ensureOwnQueue($queue);

        try {
            $this->queues->updateStatus($queue, QueueStatus::Completed);
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        $this->audit->updated('queue', 'Queue', $queue->id, "Menyelesaikan antrean {$queue->queue_number} tanpa resep.");

        return back()->with('success', "Antrean {$queue->queue_number} ditandai selesai.");
    }

    private function doctorId(): int
    {
        return auth()->user()->doctor?->id ?? 0;
    }

    /** Antrean hanya boleh diakses oleh dokter yangرويجinya. */
    private function ensureOwnQueue(Queue $queue): void
    {
        abort_if($queue->doctor_id !== $this->doctorId(), 403);
    }
}

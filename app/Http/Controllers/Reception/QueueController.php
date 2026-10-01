<?php

namespace App\Http\Controllers\Reception;

use App\Enums\QueueStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Reception\QueueRequest;
use App\Models\Doctor;
use App\Models\DoctorSchedule;
use App\Models\Patient;
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
        $date = $request->date('date') ?? now();

        $queues = Queue::query()
            ->with(['patient', 'doctor', 'createdBy'])
            ->onDate($date)
            ->when($request->filled('doctor_id'), fn ($q) => $q->where('doctor_id', $request->integer('doctor_id')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')->value()))
            ->when($request->filled('q'), fn ($q) => $q->whereHas('patient', fn ($p) => $p->search($request->string('q')->trim()->value())))
            ->orderBy('doctor_id')
            ->orderBy('queue_number')
            ->paginate(20)
            ->withQueryString();

        return view('reception.queues.index', [
            'queues' => $queues,
            'doctors' => Doctor::query()->active()->orderBy('name')->get(),
            'statuses' => QueueStatus::options(),
            'date' => $date->toDateString(),
            'filters' => $request->only('doctor_id', 'status', 'q'),
            'summary' => Queue::statusSummaryForToday($request->integer('doctor_id') ?: null),
        ]);
    }

    public function board(Request $request, QueueService $queueService): View
    {
        $date = $request->date('date') ?? now();
        $doctorId = $request->integer('doctor_id') ?: null;

        $schedules = DoctorSchedule::query()
            ->with('doctor')
            ->active()
            ->when($doctorId, fn ($q) => $q->where('doctor_id', $doctorId))
            ->orderBy('start_time')
            ->get();

        return view('reception.queues.board', [
            'date' => $date->toDateString(),
            'doctors' => Doctor::query()->active()->orderBy('name')->get(),
            'selectedDoctor' => $doctorId,
            'queues' => Queue::query()
                ->with(['patient', 'doctor'])
                ->onDate($date)
                ->when($doctorId, fn ($q) => $q->where('doctor_id', $doctorId))
                ->orderBy('queue_number')
                ->get(),
            'nowServing' => Queue::query()
                ->with(['patient', 'doctor'])
                ->onDate($date)
                ->when($doctorId, fn ($q) => $q->where('doctor_id', $doctorId))
                ->whereIn('status', [QueueStatus::Called->value, QueueStatus::InExamination->value])
                ->orderByDesc('queue_number')
                ->get(),
            'nextUp' => $queueService->upcoming($doctorId ?? 0, $date->toDateString(), 5),
            'summary' => Queue::statusSummaryForToday($doctorId),
        ]);
    }

    public function create(): View
    {
        return view('reception.queues.create', [
            'doctors' => Doctor::query()->active()->whereHas('schedules', fn ($q) => $q->active()->forDay(now()))->orderBy('name')->get(),
            'patients' => Patient::query()->orderBy('name')->limit(500)->get(),
        ]);
    }

    public function store(QueueRequest $request): RedirectResponse
    {
        try {
            $schedule = $request->schedule();
            $patient = Patient::findOrFail($request->integer('patient_id'));

            $this->queues->assertScheduleAcceptsQueue($schedule);

            $queue = $this->queues->takeQueue(
                $patient,
                $schedule,
                $request->input('complaint_note'),
                $request->user()->id,
            );
        } catch (RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        $this->audit->created('queue', 'Queue', $queue->id, "Membuat antrean {$queue->queue_number} untuk {$patient->name} di {$schedule->doctor->name}.");

        return redirect()->route('reception.queues.index')->with('success', "Antrean {$queue->queue_number} berhasil dibuat untuk {$patient->name}.");
    }

    public function show(Queue $queue): View
    {
        $queue->load(['patient.user', 'doctor', 'doctorSchedule', 'medicalRecord.prescription.details.medicine']);

        return view('reception.queues.show', [
            'queue' => $queue,
            'position' => $queue->position(),
        ]);
    }

    public function call(Queue $queue): RedirectResponse
    {
        $target = $queue->isWaiting() ? QueueStatus::Called : QueueStatus::InExamination;

        try {
            $this->queues->updateStatus($queue, $target);
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        $this->audit->updated('queue', 'Queue', $queue->id, "Memanggil antrean {$queue->queue_number} ({$queue->patient->name}).");

        return back()->with('success', "Antrean {$queue->queue_number} dipanggil.");
    }

    public function callNext(Request $request): RedirectResponse
    {
        $doctorId = (int) $request->input('doctor_id');

        $queue = $this->queues->callNext($doctorId, $request->date('queue_date')?->toDateString(), $request->boolean('start'));

        if ($queue === null) {
            return back()->with('warning', 'Tidak ada antrean yang menunggu untuk dipanggil.');
        }

        $this->audit->updated('queue', 'Queue', $queue->id, "Memanggil antrean berikutnya {$queue->queue_number} untuk {$queue->patient->name}.");

        return back()->with('success', "Antrean {$queue->queue_number} dipanggil.");
    }

    public function cancel(Queue $queue): RedirectResponse
    {
        try {
            $this->queues->updateStatus($queue, QueueStatus::Cancelled);
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        $this->audit->updated('queue', 'Queue', $queue->id, "Membatalkan antrean {$queue->queue_number}.");

        return back()->with('success', "Antrean {$queue->queue_number} dibatalkan.");
    }

    public function restore(Queue $queue): RedirectResponse
    {
        if ($queue->status !== QueueStatus::Cancelled) {
            return back()->with('error', 'Hanya antrean yang dibatalkan yang dapat dipulihkan.');
        }

        $this->queues->restoreToWaiting($queue);

        $this->audit->updated('queue', 'Queue', $queue->id, "Memulihkan antrean {$queue->queue_number} menjadi MENUNGGU.");

        return back()->with('success', "Antrean {$queue->queue_number} dipulihkan menjadi MENUNGGU.");
    }
}

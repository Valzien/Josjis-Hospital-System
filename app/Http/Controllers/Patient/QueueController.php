<?php

namespace App\Http\Controllers\Patient;

use App\Enums\QueueStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Patient\TakeQueueRequest;
use App\Models\Doctor;
use App\Models\DoctorSchedule;
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
            'activeQueue' => auth()->user()->patient->queues()->open()->latest('id')->first(),
        ]);
    }

    public function create(Request $request): View
    {
        $patient = auth()->user()->patient;

        $doctorId = $request->integer('doctor_id') ?: null;

        $schedules = DoctorSchedule::query()
            ->with('doctor')
            ->active()
            ->when($doctorId, fn ($q) => $q->where('doctor_id', $doctorId))
            ->orderBy('day')
            ->orderBy('start_time')
            ->get()
            ->filter(fn (DoctorSchedule $s) => $s->day?->value === now()->dayOfWeek)
            ->values();

        return view('patient.queue.create', [
            'doctors' => Doctor::query()
                ->active()
                ->whereHas('schedules', fn ($q) => $q->active()->forDay(now()))
                ->withCount(['schedules' => fn ($q) => $q->active()->forDay(now())])
                ->orderBy('name')
                ->get(),
            'schedules' => $schedules,
            'selectedDoctor' => $doctorId,
            'isProfileComplete' => $patient->isProfileComplete(),
            'missingFields' => array_values(array_filter([
                blank($patient->birth_date) ? 'tanggal lahir' : null,
                blank($patient->phone) ? 'nomor telepon' : null,
                blank($patient->address) ? 'alamat' : null,
            ])),
            'existingQueue' => $patient->queues()->open()->latest('id')->first(),
        ]);
    }

    public function store(TakeQueueRequest $request): RedirectResponse
    {
        $patient = auth()->user()->patient;

        if (! $patient->isProfileComplete()) {
            return back()->withInput()->with('error', 'Lengkapi data profil Anda sebelum mengambil antrean.');
        }

        $schedule = DoctorSchedule::query()->with('doctor')->findOrFail($request->integer('doctor_schedule_id'));

        if (! $schedule->isToday()) {
            return back()->with('error', "Jadwal ini berlaku pada hari {$schedule->dayLabel()}, bukan hari ini.");
        }

        try {
            $this->queues->assertScheduleAcceptsQueue($schedule);
            $queue = $this->queues->takeQueue($patient, $schedule, $request->input('complaint_note'), $patient->user_id);
        } catch (RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        $this->audit->created('queue', 'Queue', $queue->id, "Pasien {$patient->name} mengambil antrean {$queue->queue_number} pada {$schedule->doctor->name}.");

        return redirect()->route('patient.queue.show', $queue)
            ->with('success', "Berhasil! Nomor antrean Anda adalah {$queue->queue_number}.");
    }

    public function show(Queue $queue): View
    {
        abort_if($queue->patient_id !== auth()->user()->patient?->id, 403);

        $queue->load(['doctor', 'doctorSchedule', 'medicalRecord.prescription']);

        return view('patient.queue.show', [
            'queue' => $queue,
            'position' => $queue->position(),
            'estimatedMinutes' => $queue->estimatedMinutes(),
            'servingNow' => Queue::currentlyServing($queue->doctor_id, $queue->queue_date),
            'nextUp' => $this->queues->upcoming($queue->doctor_id, $queue->queue_date->toDateString(), 5),
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

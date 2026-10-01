<?php

namespace App\Http\Controllers\Doctor;

use App\Enums\QueueStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Doctor\ExaminationRequest;
use App\Models\MedicalRecord;
use App\Models\Medicine;
use App\Models\Queue;
use App\Services\AuditLogger;
use App\Services\NumberGenerator;
use App\Services\PrescriptionService;
use App\Services\QueueService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use RuntimeException;

class ExaminationController extends Controller
{
    public function __construct(
        private readonly QueueService $queues,
        private readonly PrescriptionService $prescriptions,
        private readonly NumberGenerator $numbers,
        private readonly AuditLogger $audit,
    ) {}

    public function index(Request $request): View
    {
        $doctorId = auth()->user()->doctor?->id ?? 0;

        $records = MedicalRecord::query()
            ->with(['patient', 'prescription'])
            ->where('doctor_id', $doctorId)
            ->when($request->filled('q'), fn ($q) => $q->search($request->string('q')->trim()->value()))
            ->when($request->filled('from'), fn ($q) => $q->whereDate('examined_at', '>=', $request->date('from')))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('examined_at', '<=', $request->date('to')))
            ->latest('examined_at')
            ->paginate(15)
            ->withQueryString();

        return view('doctor.examinations.index', [
            'records' => $records,
            'filters' => $request->only('q', 'from', 'to'),
            'monthlyCount' => MedicalRecord::query()->where('doctor_id', $doctorId)->where('examined_at', '>=', now()->startOfMonth())->count(),
            'prescriptionCount' => MedicalRecord::query()->where('doctor_id', $doctorId)->whereHas('prescription')->where('examined_at', '>=', now()->startOfMonth())->count(),
            'totalCount' => MedicalRecord::query()->where('doctor_id', $doctorId)->count(),
        ]);
    }

    public function create(Queue $queue): View
    {
        abort_if($queue->doctor_id !== auth()->user()->doctor?->id, 403);
        abort_if($queue->status === QueueStatus::Completed, 404, 'Pemeriksaan ini sudah selesai.');
        abort_if($queue->medicalRecord !== null, 422, 'Rekam medis untuk antrean ini sudah dibuat.');

        $queue->load(['patient', 'doctorSchedule', 'doctor']);

        if ($queue->status === QueueStatus::Waiting) {
            $this->queues->updateStatus($queue, QueueStatus::Called);
        }

        if ($queue->status === QueueStatus::Called) {
            $this->queues->updateStatus($queue, QueueStatus::InExamination);
        }

        return view('doctor.examinations.create', [
            'queue' => $queue,
            'patient' => $queue->patient,
            'medicines' => Medicine::query()->active()->orderBy('name')->get(),
            'recentRecords' => $queue->patient->medicalRecords()->with('doctor')->latest('examined_at')->limit(3)->get(),
            'recordNumberPreview' => $this->numbers->medicalRecordNumberPreview(),
        ]);
    }

    public function store(ExaminationRequest $request, Queue $queue): RedirectResponse
    {
        abort_if($queue->doctor_id !== auth()->user()->doctor?->id, 403);

        if ($queue->medicalRecord !== null) {
            return back()->with('error', 'Rekam medis untuk antrean ini sudah pernah dibuat.');
        }

        $doctor = auth()->user()->doctor;
        $patient = $queue->patient;

        try {
            $result = DB::transaction(function () use ($request, $queue, $doctor, $patient) {
                $record = MedicalRecord::create([
                    'record_number' => $this->numbers->medicalRecordNumber(),
                    'patient_id' => $patient->id,
                    'doctor_id' => $doctor->id,
                    'queue_id' => $queue->id,
                    ...$request->payload(),
                ]);

                $prescription = null;

                if ($request->wantsPrescription()) {
                    $prescription = $this->prescriptions->createFromMedicalRecord(
                        $record,
                        $request->prescriptionItems(),
                        $request->input('prescription_notes'),
                    );
                }

                $this->queues->updateStatus($queue, QueueStatus::Completed);

                return ['record' => $record, 'prescription' => $prescription];
            }, 3);
        } catch (RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        $this->audit->created('examination', 'MedicalRecord', $result['record']->id, "Menyimpan rekam medis {$result['record']->record_number} untuk {$patient->name}.");

        if ($result['prescription']) {
            $this->audit->created('prescription', 'Prescription', $result['prescription']->id, "Membuat resep {$result['prescription']->code} untuk {$patient->name}.");
        }

        return redirect()
            ->route('doctor.examinations.show', $result['record'])
            ->with('success', 'Pemeriksaan tersimpan. Rekam medis berhasil dibuat.'.($result['prescription'] ? " Resep {$result['prescription']->code} dikirim ke apoteker." : ''));
    }

    public function show(MedicalRecord $record): View
    {
        $record->load(['patient.user', 'doctor', 'queue', 'prescription.details.medicine']);

        return view('doctor.examinations.show', ['record' => $record]);
    }

    public function print(MedicalRecord $record): View
    {
        $record->load(['patient', 'doctor', 'prescription.details.medicine']);

        return view('doctor.examinations.print', ['record' => $record]);
    }
}

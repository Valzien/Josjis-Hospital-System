<?php

namespace App\Http\Controllers\Nurse;

use App\Enums\AdmissionStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Nurse\AdmissionRequest;
use App\Http\Requests\Nurse\AdmissionVisitRequest;
use App\Models\Admission;
use App\Models\Bed;
use App\Models\Doctor;
use App\Models\Patient;
use App\Services\AdmissionService;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

class AdmissionController extends Controller
{
    public function __construct(
        private readonly AdmissionService $admissions,
        private readonly AuditLogger $audit,
    ) {}

    public function index(Request $request): View
    {
        $admissions = Admission::query()
            ->with(['patient', 'doctor', 'room', 'bed', 'latestVisit'])
            ->when($request->filled('q'), fn ($q) => $q->search($request->string('q')->trim()->value()))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')->value))
            ->when($request->filled('doctor_id'), fn ($q) => $q->where('doctor_id', $request->integer('doctor_id')))
            ->when($request->filled('date'), fn ($q) => $q->whereDate('admitted_at', $request->date('date')))
            ->latest('admitted_at')
            ->paginate(15)
            ->withQueryString();

        return view('nurse.admissions.index', [
            'admissions' => $admissions,
            'statuses' => AdmissionStatus::options(),
            'doctors' => Doctor::query()->active()->orderBy('name')->get(),
            'filters' => $request->only('q', 'status', 'doctor_id', 'date'),
            'summary' => [
                'active' => Admission::query()->active()->count(),
                'today' => Admission::query()->whereDate('admitted_at', today())->count(),
                'available_beds' => Bed::query()->available()->count(),
            ],
        ]);
    }

    public function create(Request $request): View
    {
        return view('nurse.admissions.create', [
            'patients' => Patient::query()->orderBy('name')->limit(500)->get(),
            'doctors' => Doctor::query()->active()->orderBy('name')->get(),
            'beds' => Bed::query()->available()->with('room')->orderBy('room_id')->orderBy('code')->get(),
            'selectedPatientId' => $request->integer('patient_id') ?: null,
        ]);
    }

    public function store(AdmissionRequest $request): RedirectResponse
    {
        try {
            $admission = $this->admissions->admit($request->validated(), auth()->id());
        } catch (RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        $this->audit->created('admission', 'Admission', $admission->id, "Perawat mendaftarkan rawat inap {$admission->code} untuk {$admission->patient?->name}.");

        return redirect()->route('nurse.admissions.show', $admission)
            ->with('success', "Rawat inap {$admission->code} berhasil dibuat.");
    }

    public function show(Admission $admission): View
    {
        $admission->load([
            'patient', 'doctor', 'room', 'bed', 'createdBy',
            'visits.doctor', 'visits.createdBy', 'movements.fromBed.room', 'movements.toBed.room',
            'latestVisit',
        ]);

        return view('nurse.admissions.show', [
            'admission' => $admission,
            'dayCount' => $admission->dayCount(),
        ]);
    }

    public function visit(AdmissionVisitRequest $request, Admission $admission): RedirectResponse
    {
        try {
            $this->admissions->recordVisit($admission, [
                'doctor_id' => $admission->doctor_id,
                'visited_at' => $request->input('visited_at') ?: now(),
                'notes' => $request->string('notes')->trim()->value(),
            ] + $request->vitals(), auth()->id());
        } catch (RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        $this->audit->created('admission_visit', 'AdmissionVisit', $admission->id, "Perawat mencatat visite/TTV rawat inap {$admission->code}.");

        return back()->with('success', 'Catatan visite dan tanda vital berhasil disimpan.');
    }
}

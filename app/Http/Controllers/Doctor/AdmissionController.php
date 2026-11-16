<?php

namespace App\Http\Controllers\Doctor;

use App\Enums\AdmissionStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Doctor\AdmissionChargeRequest;
use App\Http\Requests\Doctor\AdmissionDiagnosisRequest;
use App\Http\Requests\Doctor\AdmissionVisitRequest;
use App\Http\Requests\Doctor\DischargeSummaryRequest;
use App\Models\Admission;
use App\Models\AdmissionDiagnosis;
use App\Models\Icd10Code;
use App\Models\Service;
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
        $user = auth()->user();

        $admissions = Admission::query()
            ->with(['patient', 'doctor', 'room', 'bed'])
            ->when(! $user->isAdmin(), fn ($q) => $q->forDoctor($this->doctorId()))
            ->when($request->filled('q'), fn ($q) => $q->search($request->string('q')->trim()->value()))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')->value()))
            ->latest('admitted_at')
            ->paginate(15)
            ->withQueryString();

        return view('doctor.admissions.index', [
            'admissions' => $admissions,
            'statuses' => AdmissionStatus::options(),
            'filters' => $request->only('q', 'status'),
            'summary' => [
                'active' => Admission::query()
                    ->when(! $user->isAdmin(), fn ($q) => $q->forDoctor($this->doctorId()))
                    ->active()
                    ->count(),
                'discharged' => Admission::query()
                    ->when(! $user->isAdmin(), fn ($q) => $q->forDoctor($this->doctorId()))
                    ->where('status', AdmissionStatus::Discharged->value)
                    ->count(),
            ],
        ]);
    }

    public function show(Admission $admission): View
    {
        $this->ensureOwnAdmission($admission);

        $admission->load([
            'patient', 'doctor', 'room', 'bed', 'invoice', 'createdBy',
            'visits.doctor', 'movements.fromBed.room', 'movements.toBed.room',
            'charges.service', 'diagnoses.icd10Code', 'diagnoses.doctor', 'primaryDiagnosis', 'latestVisit',
        ]);

        return view('doctor.admissions.show', [
            'admission' => $admission,
            'services' => Service::query()->active()->orderBy('category')->orderBy('name')->get(),
            'icd10Codes' => Icd10Code::query()->orderBy('category')->orderBy('code')->get()->groupBy('category'),
            'chargesTotal' => $admission->chargesTotal(),
            'dayCount' => $admission->dayCount(),
        ]);
    }

    public function visit(AdmissionVisitRequest $request, Admission $admission): RedirectResponse
    {
        $this->ensureOwnAdmission($admission);

        try {
            $this->admissions->recordVisit($admission, [
                'doctor_id' => $this->doctorId() ?: $admission->doctor_id,
                'visited_at' => $request->input('visited_at') ?: now(),
                'notes' => $request->string('notes')->trim()->value(),
            ] + $request->vitals(), auth()->id());
        } catch (RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        $this->audit->created('admission_visit', 'AdmissionVisit', $admission->id, "Mencatat visite pasien rawat inap {$admission->code}.");

        return back()->with('success', 'Catatan visite berhasil disimpan.');
    }

    public function diagnosis(AdmissionDiagnosisRequest $request, Admission $admission): RedirectResponse
    {
        $this->ensureOwnAdmission($admission);

        try {
            $this->admissions->addDiagnosis($admission, [
                'icd10_code_id' => $request->integer('icd10_code_id'),
                'type' => $request->string('type')->value(),
                'notes' => $request->filled('notes') ? $request->string('notes')->trim()->value() : null,
                'doctor_id' => $this->doctorId() ?: $admission->doctor_id,
            ], auth()->id());
        } catch (RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        $this->audit->created('admission_diagnosis', 'AdmissionDiagnosis', $admission->id, "Menambahkan diagnosis ICD-10 pada rawat inap {$admission->code}.");

        return back()->with('success', 'Diagnosis berhasil ditambahkan.');
    }

    public function destroyDiagnosis(Admission $admission, AdmissionDiagnosis $diagnosis): RedirectResponse
    {
        $this->ensureOwnAdmission($admission);

        try {
            $this->admissions->removeDiagnosis($admission, $diagnosis, auth()->id());
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        $this->audit->deleted('admission_diagnosis', 'AdmissionDiagnosis', $diagnosis->id, "Menghapus diagnosis {$diagnosis->code} pada rawat inap {$admission->code}.");

        return back()->with('success', 'Diagnosis berhasil dihapus.');
    }

    public function updateSummary(DischargeSummaryRequest $request, Admission $admission): RedirectResponse
    {
        $this->ensureOwnAdmission($admission);

        try {
            $this->admissions->updateDischargeSummary($admission, $request->summary());
        } catch (RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        $this->audit->updated('admission', 'Admission', $admission->id, "Menyimpan ringkasan pulang rawat inap {$admission->code}.");

        return back()->with('success', 'Ringkasan pulang berhasil disimpan.');
    }

    public function summary(Admission $admission): View
    {
        $this->ensureOwnAdmission($admission);

        return view('admissions.summary', $this->summaryData($admission));
    }

    public function charge(AdmissionChargeRequest $request, Admission $admission): RedirectResponse
    {
        $this->ensureOwnAdmission($admission);

        $service = Service::query()->findOrFail($request->integer('service_id'));

        try {
            $this->admissions->addServiceCharge(
                $admission,
                $service,
                $request->integer('quantity'),
                auth()->id(),
            );
        } catch (RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        $this->audit->created('admission_charge', 'AdmissionCharge', $admission->id, "Menambahkan tindakan {$service->name} pada rawat inap {$admission->code}.");

        return back()->with('success', "Tindakan {$service->name} berhasil ditambahkan ke tagihan rawat inap.");
    }

    private function doctorId(): int
    {
        return auth()->user()->doctor?->id ?? 0;
    }

    /** @return array<string, mixed> */
    private function summaryData(Admission $admission): array
    {
        $admission->load([
            'patient', 'doctor', 'room', 'bed',
            'diagnoses.icd10Code', 'visits.doctor', 'latestVisit',
        ]);

        return [
            'admission' => $admission,
            'dayCount' => $admission->dayCount(),
        ];
    }

    private function ensureOwnAdmission(Admission $admission): void
    {
        if (auth()->user()->isAdmin()) {
            return;
        }

        abort_if($admission->doctor_id !== $this->doctorId(), 403);
    }
}

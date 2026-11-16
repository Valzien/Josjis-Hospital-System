<?php

namespace App\Http\Controllers\Reception;

use App\Enums\AdmissionStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Reception\AdmissionRequest;
use App\Http\Requests\Reception\AdmissionTransferRequest;
use App\Http\Requests\Reception\DischargeRequest;
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
            ->with(['patient', 'doctor', 'room', 'bed'])
            ->when($request->filled('q'), fn ($q) => $q->search($request->string('q')->trim()->value()))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')->value()))
            ->when($request->filled('doctor_id'), fn ($q) => $q->where('doctor_id', $request->integer('doctor_id')))
            ->when($request->filled('date'), fn ($q) => $q->whereDate('admitted_at', $request->date('date')))
            ->latest('admitted_at')
            ->paginate(15)
            ->withQueryString();

        return view('reception.admissions.index', [
            'admissions' => $admissions,
            'statuses' => AdmissionStatus::options(),
            'doctors' => Doctor::query()->active()->orderBy('name')->get(),
            'filters' => $request->only('q', 'status', 'doctor_id', 'date'),
            'summary' => [
                'active' => Admission::query()->active()->count(),
                'today' => Admission::query()->whereDate('admitted_at', today())->count(),
                'available_beds' => Bed::query()->available()->count(),
                'discharged_today' => Admission::query()
                    ->where('status', AdmissionStatus::Discharged->value)
                    ->whereDate('discharged_at', today())
                    ->count(),
            ],
        ]);
    }

    public function create(Request $request): View
    {
        return view('reception.admissions.create', [
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

        $this->audit->created('admission', 'Admission', $admission->id, "Mendaftarkan rawat inap {$admission->code} untuk {$admission->patient?->name}.");

        return redirect()->route('reception.admissions.show', $admission)
            ->with('success', "Rawat inap {$admission->code} berhasil dibuat.");
    }

    public function show(Admission $admission): View
    {
        $admission->load([
            'patient', 'doctor', 'room', 'bed', 'invoice', 'createdBy',
            'visits.doctor', 'movements.fromBed.room', 'movements.toBed.room',
            'charges.service', 'diagnoses.icd10Code', 'primaryDiagnosis', 'latestVisit',
        ]);

        return view('reception.admissions.show', [
            'admission' => $admission,
            'availableBeds' => Bed::query()->available()
                ->with('room')
                ->whereKeyNot($admission->bed_id)
                ->orderBy('room_id')
                ->orderBy('code')
                ->get(),
            'chargesTotal' => $admission->chargesTotal(),
            'dayCount' => $admission->dayCount(),
        ]);
    }

    public function summary(Admission $admission): View
    {
        $admission->load([
            'patient', 'doctor', 'room', 'bed',
            'diagnoses.icd10Code', 'visits.doctor', 'latestVisit',
        ]);

        return view('admissions.summary', [
            'admission' => $admission,
            'dayCount' => $admission->dayCount(),
        ]);
    }

    public function transfer(AdmissionTransferRequest $request, Admission $admission): RedirectResponse
    {
        try {
            $this->admissions->transfer(
                $admission,
                $request->integer('bed_id'),
                $request->input('reason'),
                auth()->id(),
            );
        } catch (RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        $this->audit->updated('admission', 'Admission', $admission->id, "Memindahkan pasien rawat inap {$admission->code}.");

        return back()->with('success', 'Pasien berhasil dipindahkan ke tempat tidur baru.');
    }

    public function discharge(DischargeRequest $request, Admission $admission): RedirectResponse
    {
        try {
            $admission = $this->admissions->discharge(
                $admission,
                $request->input('discharge_notes'),
                $request->input('discharged_at') ?: null,
                auth()->id(),
            );
        } catch (RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        $this->audit->updated('admission', 'Admission', $admission->id, "Memulangkan pasien rawat inap {$admission->code}.");

        $message = "Pasien {$admission->patient?->name} telah dipulangkan.";

        if ($admission->invoice !== null) {
            $message .= " Tagihan {$admission->invoice->code} telah dibuat.";
        }

        return redirect()->route('reception.admissions.show', $admission)->with('success', $message);
    }

    public function cancel(Request $request, Admission $admission): RedirectResponse
    {
        try {
            $this->admissions->cancel($admission, $request->input('reason'), auth()->id());
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        $this->audit->updated('admission', 'Admission', $admission->id, "Membatalkan rawat inap {$admission->code}.");

        return back()->with('success', "Rawat inap {$admission->code} telah dibatalkan.");
    }
}

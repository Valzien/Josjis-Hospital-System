<?php

namespace App\Http\Controllers;

use App\Enums\PrescriptionStatus;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Pencarian global dari topbar. Setiap role hanya boleh mencari entitas
 * yang memang relevan untuknya (prinsip data minimization, brief §13).
 */
class GlobalSearchController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $term = trim((string) $request->query('q', ''));

        if (mb_strlen($term) < 2) {
            return response()->json(['data' => []]);
        }

        $user = $request->user();
        $data = [];

        if ($user->isPatient()) {
            $data[] = $this->doctors($term);
        } else {
            $data[] = $this->patients($term, $user);
            $data[] = $this->doctors($term);
            $data[] = $this->prescriptions($term, $user);
        }

        return response()->json(['data' => array_values(array_filter($data))]);
    }

    private function patients(string $term, User $user): array
    {
        $query = Patient::query()->search($term)->orderBy('name')->limit(5);

        if ($user->isDoctor()) {
            $query->whereHas('medicalRecords', fn ($q) => $q->where('doctor_id', $user->doctor?->id));
        }

        $items = $query->get()
            ->map(fn (Patient $p) => [
                'title' => $p->name,
                'subtitle' => $p->medical_record_number.($p->age() ? ' · '.$p->age().' tahun' : ''),
                'icon' => 'bi-person-vcard',
                'url' => $this->patientUrl($user, $p),
            ])->all();

        if ($items === []) {
            return [];
        }

        return ['label' => 'Pasien', 'items' => $items];
    }

    private function patientUrl(User $user, Patient $patient): string
    {
        if ($user->isDoctor()) {
            return route('doctor.patients.show', $patient);
        }

        if ($user->isPharmacist()) {
            return route('pharmacy.prescriptions.index').'?q='.urlencode($patient->name);
        }

        return route('reception.patients.show', $patient);
    }

    private function doctors(string $term): array
    {
        $items = Doctor::query()
            ->search($term)
            ->orderBy('name')
            ->limit(5)
            ->get()
            ->map(fn (Doctor $d) => [
                'title' => $d->name,
                'subtitle' => $d->specialization,
                'icon' => 'bi-heart-pulse',
                'url' => $this->doctorUrl($d),
            ])->all();

        if ($items === []) {
            return [];
        }

        return ['label' => 'Dokter', 'items' => $items];
    }

    private function doctorUrl(Doctor $doctor): string
    {
        $user = auth()->user();

        return match (true) {
            $user->isPatient() => route('patient.doctors.show', $doctor),
            $user->isDoctor() => route('doctor.schedules.index'),
            default => route('admin.doctors.show', $doctor),
        };
    }

    private function prescriptions(string $term, User $user): array
    {
        $query = Prescription::query()
            ->with('patient')
            ->search($term);

        if ($user->isDoctor()) {
            $query->where('doctor_id', $user->doctor?->id)->whereIn('status', [
                PrescriptionStatus::Pending->value,
                PrescriptionStatus::Processing->value,
            ]);
        } else {
            $query->whereIn('status', [
                PrescriptionStatus::Pending->value,
                PrescriptionStatus::Processing->value,
                PrescriptionStatus::Ready->value,
            ]);
        }

        $items = $query->orderByDesc('created_at')->limit(5)->get()
            ->map(fn (Prescription $p) => [
                'title' => $p->code.' · '.$p->patient?->name,
                'subtitle' => $p->statusLabel(),
                'icon' => 'bi-file-earmark-medical',
                'url' => $user->isDoctor()
                    ? route('doctor.prescriptions.show', $p)
                    : route('pharmacy.prescriptions.show', $p),
            ])->all();

        if ($items === []) {
            return [];
        }

        return ['label' => 'Resep', 'items' => $items];
    }
}

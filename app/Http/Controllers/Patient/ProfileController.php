<?php

namespace App\Http\Controllers\Patient;

use App\Http\Controllers\Controller;
use App\Http\Requests\Patient\PasswordUpdateRequest;
use App\Http\Requests\Patient\ProfileRequest;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function edit(): View
    {
        $patient = auth()->user()->patient;

        return view('patient.profile.edit', [
            'patient' => $patient,
            'missing' => $this->missingFields($patient),
        ]);
    }

    public function update(ProfileRequest $request): RedirectResponse
    {
        $user = $request->user();
        $patient = $user->patient;

        $user->update($request->userPayload());
        $patient->update($request->patientPayload());

        $this->audit->updated('patient', 'Patient', $patient->id, "Pasien {$patient->name} memperbarui profilnya sendiri.");

        return redirect()->route('patient.profile.edit')->with('success', 'Profil berhasil diperbarui.');
    }

    public function updatePassword(PasswordUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();
        $user->update(['password' => $request->string('password')->value()]);

        $this->audit->updated('user', 'User', $user->id, 'Pasien memperbarui kata sandinya.');

        return back()->with('success', 'Kata sandi berhasil diperbarui.');
    }

    /** @return array<int, string> */
    private function missingFields($patient): array
    {
        if ($patient === null) {
            return ['Data pasien belum tersedia.'];
        }

        $missing = [];

        foreach ([
            'gender' => 'jenis kelamin',
            'birth_date' => 'tanggal lahir',
            'phone' => 'nomor telepon',
            'address' => 'alamat',
            'nik' => 'NIK',
        ] as $field => $label) {
            if (blank($patient->{$field})) {
                $missing[] = $label;
            }
        }

        return $missing;
    }
}

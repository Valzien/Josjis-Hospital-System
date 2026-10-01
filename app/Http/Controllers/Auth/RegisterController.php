<?php

namespace App\Http\Controllers\Auth;

use App\Enums\Gender;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\Patient;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class RegisterController extends Controller
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function create(): View
    {
        return view('auth.register');
    }

    public function store(RegisterRequest $request): RedirectResponse
    {
        $data = $request->validatedData();

        $user = DB::transaction(function () use ($data, $request) {
            $user = User::create($data);

            Patient::create([
                'user_id' => $user->id,
                'medical_record_number' => $this->nextPatientNumber(),
                'nik' => null,
                'name' => $data['name'],
                'gender' => $request->input('gender') ? Gender::from($request->input('gender')) : null,
                'birth_date' => $request->input('birth_date') ?: null,
                'phone' => $data['phone'],
            ]);

            return $user;
        });

        event(new Registered($user));

        $this->audit->created('patient', 'Patient', $user->patient?->id, "Pasien baru mendaftar: {$user->name}");

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('patient.dashboard')
            ->with('success', 'Pendaftaran berhasil. Lengkapi profil Anda untuk dapat mengambil antrean.');
    }

    private function nextPatientNumber(): string
    {
        $period = now()->format('Ym');

        $last = Patient::query()
            ->withTrashed()
            ->where('medical_record_number', 'like', "P-$period-%")
            ->orderByDesc('medical_record_number')
            ->value('medical_record_number');

        $sequence = $last !== null ? ((int) substr((string) $last, -4)) + 1 : 1;

        return sprintf('P-%s-%04d', $period, $sequence);
    }
}

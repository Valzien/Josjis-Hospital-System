<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\Setting;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function create(): View
    {
        return view('auth.login');
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        $user = $request->user();
        $user->forceFill(['last_login_at' => now()])->saveQuietly();

        $this->audit->login("{$user->name} ({$user->roleLabel()}) berhasil login.", [
            'role' => $user->role?->value,
        ]);

        return redirect()->intended(route($user->dashboardRoute()))
            ->with('success', "Selamat datang kembali, {$user->displayName()}!");
    }

    public function destroy(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user) {
            $this->audit->logout("{$user->name} ({$user->roleLabel()}) logout.");
        }

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'Anda telah keluar dari sistem.');
    }

    public function demoAccounts(): array
    {
        return Setting::get('demo_accounts', []) ? json_decode(Setting::get('demo_accounts'), true) : [];
    }
}

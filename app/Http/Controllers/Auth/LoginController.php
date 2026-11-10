<?php

namespace App\Http\Controllers\Auth;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\Setting;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function create(Request $request): View
    {
        $portal = $this->portal($request);

        if ($portal === 'patient') {
            return view('auth.login');
        }

        $config = config("jhs.staff_portals.{$portal}");

        abort_if($config === null, 404);

        return view('auth.staff-login', [
            'portal' => $portal,
            'portalConfig' => $config,
            'demoAccount' => $this->demoAccount($config['demo'] ?? null),
        ]);
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $portal = $this->portal($request);

        $request->authenticate();

        $user = $request->user();
        $expected = UserRole::fromPortal($portal);

        if (! $user->isActive()) {
            $this->logoutSafely($request);

            throw ValidationException::withMessages([
                'email' => 'Akun Anda sedang dinonaktifkan. Silakan hubungi administrator.',
            ]);
        }

        if ($expected !== null && $user->role !== $expected) {
            $this->logoutSafely($request);

            throw ValidationException::withMessages([
                'email' => $portal === 'patient'
                    ? "Akun {$user->roleLabel()} tidak dapat masuk melalui halaman pasien. Silakan gunakan portal login staf."
                    : "Portal ini hanya untuk akun {$expected->label()}. Akun Anda terdaftar sebagai {$user->roleLabel()}.",
            ]);
        }

        $request->session()->regenerate();

        $user->forceFill(['last_login_at' => now()])->saveQuietly();

        $this->audit->login("{$user->name} ({$user->roleLabel()}) berhasil login.", [
            'role' => $user->role?->value,
            'portal' => $portal,
        ]);

        return redirect()->intended(route($user->dashboardRoute()))
            ->with('success', "Selamat datang kembali, {$user->displayName()}!");
    }

    public function destroy(Request $request): RedirectResponse
    {
        $user = $request->user();
        $loginRoute = $user?->role?->loginRoute() ?? 'login';

        if ($user) {
            $this->audit->logout("{$user->name} ({$user->roleLabel()}) logout.");
        }

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route($loginRoute)->with('success', 'Anda telah keluar dari sistem.');
    }

    private function portal(Request $request): string
    {
        $portal = (string) $request->route('portal', 'patient');

        return array_key_exists($portal, config('jhs.staff_portals', [])) || $portal === 'patient'
            ? $portal
            : 'patient';
    }

    /** Akun demo khusus portal yang sedang dibuka. */
    private function demoAccount(?string $roleKey): ?array
    {
        if (! config('jhs.demo_credentials_enabled') || blank($roleKey)) {
            return null;
        }

        $account = config('jhs.demo_accounts.'.$roleKey);

        if ($account === null) {
            return null;
        }

        return $account + ['role' => $roleKey];
    }

    private function logoutSafely(Request $request): void
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
    }

    public function demoAccounts(): array
    {
        return Setting::get('demo_accounts', []) ? json_decode(Setting::get('demo_accounts'), true) : [];
    }
}

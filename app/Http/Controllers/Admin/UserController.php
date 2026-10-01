<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ActiveStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UserRequest;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class UserController extends Controller
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function index(Request $request): View
    {
        $users = User::query()
            ->when($request->filled('q'), fn ($q) => $q->where(fn ($w) => $w
                ->where('name', 'like', '%'.$request->string('q')->trim().'%')
                ->orWhere('email', 'like', '%'.$request->string('q')->trim().'%')))
            ->when($request->filled('role'), fn ($q) => $q->where('role', $request->string('role')->value()))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')->value()))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('admin.users.index', [
            'users' => $users,
            'roles' => UserRole::options(),
            'statuses' => ActiveStatus::options(),
            'filters' => $request->only('q', 'role', 'status'),
        ]);
    }

    public function create(): View
    {
        return view('admin.users.form', ['user' => new User(['role' => UserRole::Patient, 'status' => ActiveStatus::Active])]);
    }

    public function store(UserRequest $request): RedirectResponse
    {
        $user = User::create($request->payload());

        $this->audit->created('user', 'User', $user->id, "Membuat pengguna {$user->name} dengan role {$user->roleLabel()}.");

        return redirect()->route('admin.users.index')->with('success', "Pengguna {$user->name} berhasil dibuat.");
    }

    public function show(User $user): RedirectResponse
    {
        return redirect()->route('admin.users.edit', $user);
    }

    public function edit(User $user): View
    {
        return view('admin.users.form', ['user' => $user]);
    }

    public function update(UserRequest $request, User $user): RedirectResponse
    {
        $data = $request->payload();

        if ($user->id === $request->user()->id) {
            unset($data['role']);
        }

        $user->update($data);

        $this->audit->updated('user', 'User', $user->id, "Memperbarui data pengguna {$user->name}.");

        return redirect()->route('admin.users.index')->with('success', "Data pengguna {$user->name} berhasil diperbarui.");
    }

    public function toggleStatus(Request $request, User $user): RedirectResponse
    {
        if ($user->id === $request->user()->id) {
            return back()->with('error', 'Anda tidak dapat menonaktifkan akun sendiri.');
        }

        $status = $user->isActive() ? ActiveStatus::Inactive : ActiveStatus::Active;
        $user->update(['status' => $status]);

        $this->audit->updated('user', 'User', $user->id, "Mengubah status {$user->name} menjadi {$status->label()}.");

        return back()->with('success', "Status {$user->name} berhasil diubah menjadi {$status->label()}.");
    }

    public function resetPassword(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
        ], [], ['password' => 'kata sandi baru']);

        $user->update(['password' => $data['password']]);

        $this->audit->updated('user', 'User', $user->id, "Mengatur ulang kata sandi pengguna {$user->name}.");

        return back()->with('success', "Kata sandi {$user->name} berhasil diatur ulang.");
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($user->id === $request->user()->id) {
            return back()->with('error', 'Anda tidak dapat menghapus akun sendiri.');
        }

        $name = $user->name;
        $user->delete();

        $this->audit->deleted('user', 'User', $user->id, "Menghapus pengguna {$name}.");

        return redirect()->route('admin.users.index')->with('success', "Pengguna {$name} telah dihapus.");
    }
}

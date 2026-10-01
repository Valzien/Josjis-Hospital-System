<?php

namespace Database\Seeders;

use App\Enums\ActiveStatus;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Membuat satu administrator awal untuk lingkungan non-demo.
 *
 * Seeder ini berjalan hanya ketika JHS_DEMO_CREDENTIALS=false, sehingga
 * `migrate --seed` di server produksi tidak pernah menghasilkan akun dengan
 * sandi "password". Bila JHS_ADMIN_PASSWORD belum diisi, sandi acak dibuat dan
 * ditampilkan satu kali di konsol.
 */
class InitialAdminSeeder extends Seeder
{
    public function run(): void
    {
        if (User::query()->where('role', UserRole::Admin->value)->exists()) {
            return;
        }

        $email = (string) config('jhs.admin.email');
        $configured = (string) config('jhs.admin.password');

        $generated = $configured === '';
        $password = $generated ? Str::password(24) : $configured;

        User::query()->create([
            'name' => 'Administrator JOSJIS',
            'email' => $email,
            'password' => Hash::make($password),
            'role' => UserRole::Admin->value,
            'status' => ActiveStatus::Active->value,
            'email_verified_at' => now(),
        ]);

        if ($generated) {
            $this->command?->warn('JHS_ADMIN_PASSWORD belum diisi, sandi acak dibuat.');
            $this->command?->info("  email    : {$email}");
            $this->command?->info("  password : {$password}");
            $this->command?->info('Simpan sekarang; sandi ini tidak ditampilkan lagi.');
        }
    }
}

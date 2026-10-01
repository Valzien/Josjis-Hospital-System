<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Patient;
use App\Models\Queue;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Penjaga akun demo.
 *
 * Memastikan `migrate --seed` di lingkungan non-demo tidak pernah menghasilkan
 * akun dengan sandi "password", karena itulah lubang keamanan yang paling
 * mudah terlewat saat sistem dideploy.
 */
class DemoAccountGuardTest extends TestCase
{
    use RefreshDatabase;

    private const DEMO_EMAILS = [
        'admin@josjis.test',
        'resepsionis@josjis.test',
        'dokter@josjis.test',
        'apoteker@josjis.test',
        'pasien@josjis.test',
    ];

    private const STRONG_PASSWORD = 'sandi-kuat-produksi-2026';

    protected function tearDown(): void
    {
        config(['jhs.demo_credentials_enabled' => false]);

        parent::tearDown();
    }

    public function test_seeding_tanpa_demo_membuat_hanya_administrator_awal(): void
    {
        $this->seedNonDemo();

        $users = User::query()->get();

        $this->assertCount(1, $users, 'Lingkungan non-demo hanya boleh membuat satu akun administrator.');
        $this->assertSame('admin@jhs.test', $users->first()->email);
        $this->assertTrue($users->first()->isAdmin());
        $this->assertTrue(Hash::check(self::STRONG_PASSWORD, $users->first()->password));
    }

    public function test_seeding_tanpa_demo_tidak_pernah_membuat_sandi_password(): void
    {
        $this->seedNonDemo();

        foreach (User::query()->get() as $user) {
            $this->assertFalse(
                Hash::check('password', $user->password),
                "Akun {$user->email} masih memakai sandi demo."
            );
        }
    }

    public function test_seeding_tanpa_demo_tidak_membuat_data_klinis_palsu(): void
    {
        $this->seedNonDemo();

        $this->assertSame(0, User::query()->whereIn('email', self::DEMO_EMAILS)->count());
        $this->assertSame(0, Patient::query()->count());
        $this->assertSame(0, Queue::query()->count());
    }

    public function test_administrator_awal_tidak_dibuat_dua_kali(): void
    {
        $this->seedNonDemo();
        $this->seed(DatabaseSeeder::class);

        $this->assertSame(1, User::query()->where('role', UserRole::Admin->value)->count());
    }

    public function test_halaman_login_menyembunyikan_blok_akun_demo(): void
    {
        config(['jhs.demo_credentials_enabled' => false]);

        $this->get(route('login'))
            ->assertOk()
            ->assertDontSee('jhs-demo-credentials', false)
            ->assertDontSee('resepsionis@josjis.test', false);
    }

    public function test_halaman_login_menampilkan_blok_akun_demo_saat_diaktifkan(): void
    {
        config(['jhs.demo_credentials_enabled' => true]);

        $this->get(route('login'))
            ->assertOk()
            ->assertSee('jhs-demo-credentials', false)
            ->assertSee('resepsionis@josjis.test', false);
    }

    public function test_perintah_demo_off_mematikan_akun_demo_yang_sudah_ada(): void
    {
        config(['jhs.demo_credentials_enabled' => true]);
        $this->seed(DatabaseSeeder::class);

        $this->assertSame(count(self::DEMO_EMAILS), User::query()->whereIn('email', self::DEMO_EMAILS)->count());

        $this->artisan('jhs:demo-off')
            ->expectsConfirmation('Nonaktifkan akun-akun di atas?', 'yes')
            ->assertSuccessful();

        $demoUsers = User::query()->whereIn('email', self::DEMO_EMAILS)->get();

        $this->assertCount(count(self::DEMO_EMAILS), $demoUsers);

        foreach ($demoUsers as $user) {
            $this->assertFalse($user->isActive(), "Akun {$user->email} masih aktif.");
            $this->assertFalse(Hash::check('password', $user->password), "Akun {$user->email} masih memakai sandi demo.");
        }
    }

    private function seedNonDemo(): void
    {
        config([
            'jhs.demo_credentials_enabled' => false,
            'jhs.admin.email' => 'admin@jhs.test',
            'jhs.admin.password' => self::STRONG_PASSWORD,
        ]);

        $this->seed(DatabaseSeeder::class);
    }
}

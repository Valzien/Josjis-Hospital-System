<?php

namespace Tests\Feature;

use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaffPortalLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_setiap_portal_staf_menampilkan_halaman_login_sendiri(): void
    {
        foreach (array_keys(config('jhs.staff_portals')) as $portal) {
            $this->get(route($portal.'.login'))
                ->assertOk()
                ->assertSee(config("jhs.staff_portals.{$portal}.title"));
        }

        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Masuk sebagai Pasien');
    }

    public function test_akun_staf_ditolak_saat_login_di_portal_pasien(): void
    {
        $cashier = User::factory()->cashier()->create();

        $this->post(route('login'), [
            'email' => $cashier->email,
            'password' => 'password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_akun_dengan_role_berbeda_ditolak_di_portal_staf(): void
    {
        $doctor = User::factory()->doctor()->create();

        $this->post(route('admin.login'), [
            'email' => $doctor->email,
            'password' => 'password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_staf_dapat_login_melalui_portal_yang_sesuai(): void
    {
        $cashier = User::factory()->cashier()->create();

        $this->post(route('cashier.login'), [
            'email' => $cashier->email,
            'password' => 'password',
        ])->assertRedirect(route('cashier.dashboard'));

        $this->assertAuthenticatedAs($cashier);
    }

    public function test_pasien_tetap_dapat_login_melalui_portal_pasien(): void
    {
        $user = User::factory()->patient()->create();
        Patient::factory()->create(['user_id' => $user->id]);

        $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect(route('patient.dashboard'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_akun_nonaktif_tidak_dapat_login(): void
    {
        $cashier = User::factory()->cashier()->inactive()->create();

        $this->post(route('cashier.login'), [
            'email' => $cashier->email,
            'password' => 'password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_logout_kembali_ke_portal_sesuai_role(): void
    {
        $this->actingAs(User::factory()->cashier()->create());

        $this->post(route('logout'))
            ->assertRedirect(route('cashier.login'));

        $this->assertGuest();
    }

    public function test_role_tidak_dapat_membuka_portal_role_lain(): void
    {
        $cashier = User::factory()->cashier()->create();
        $patientUser = User::factory()->patient()->create();
        Patient::factory()->create(['user_id' => $patientUser->id]);

        $this->actingAs($cashier)->get(route('reception.dashboard'))->assertForbidden();

        $this->actingAs($patientUser)->get(route('cashier.dashboard'))->assertForbidden();

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('cashier.dashboard'))
            ->assertOk();
    }
}

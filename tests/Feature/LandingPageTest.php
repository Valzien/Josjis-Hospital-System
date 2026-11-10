<?php

namespace Tests\Feature;

use App\Models\Doctor;
use App\Models\Medicine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LandingPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_landing_page_renders(): void
    {
        $this->get('/')->assertOk();
    }

    public function test_landing_page_shows_doctors_and_features(): void
    {
        $doctor = Doctor::factory()->create(['name' => 'dr. Budi Santoso']);

        $this->get('/')
            ->assertOk()
            ->assertSee('dr. Budi Santoso')
            ->assertSee('Layanan Unggulan');
    }

    public function test_guest_is_redirected_to_login_from_dashboard(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));
    }
}

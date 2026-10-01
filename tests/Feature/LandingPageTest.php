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

    public function test_landing_page_shows_public_counts(): void
    {
        Doctor::factory()->count(3)->create();
        Medicine::factory()->count(2)->create();

        $this->get('/')
            ->assertOk()
            ->assertSee('3')
            ->assertSee('2');
    }

    public function test_guest_is_redirected_to_login_from_dashboard(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));
    }
}

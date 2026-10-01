<?php

namespace Tests\Feature;

use App\Enums\PrescriptionStatus;
use App\Models\Pharmacist;
use App\Models\User;

class PharmacyPagesTest extends SmokeTestCase
{
    public function test_pharmacy_pages_render(): void
    {
        $pharmacist = User::factory()->pharmacist()->create();
        Pharmacist::factory()->create(['user_id' => $pharmacist->id]);

        $this->actingAs($pharmacist);

        $data = $this->seedScenario();

        $this->assertPagesRender([
            route('pharmacy.dashboard'),
            route('pharmacy.prescriptions.index'),
            route('pharmacy.prescriptions.show', $data['prescription']),
            route('pharmacy.medicines.index'),
            route('pharmacy.medicines.create'),
            route('pharmacy.medicines.show', $data['medicine']),
            route('pharmacy.medicines.edit', $data['medicine']),
            route('pharmacy.stock.index'),
            route('pharmacy.transactions.index'),
        ]);
    }

    public function test_ready_prescription_print_renders(): void
    {
        $pharmacist = User::factory()->pharmacist()->create();
        Pharmacist::factory()->create(['user_id' => $pharmacist->id]);

        $this->actingAs($pharmacist);

        $data = $this->seedScenario();
        $data['prescription']->update([
            'status' => PrescriptionStatus::Ready,
            'processed_by' => $pharmacist->id,
        ]);

        $this->get(route('pharmacy.prescriptions.print', $data['prescription']))->assertOk();
    }

    public function test_transactions_page_shows_daily_chart_data(): void
    {
        $pharmacist = User::factory()->pharmacist()->create();
        Pharmacist::factory()->create(['user_id' => $pharmacist->id]);

        $this->actingAs($pharmacist);

        $this->seedScenario();

        $response = $this->get(route('pharmacy.transactions.index'));
        $response->assertOk();
        $response->assertViewHas('dailyChart');
    }
}

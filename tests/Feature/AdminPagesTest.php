<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\User;
use App\Services\ReportService;

class AdminPagesTest extends SmokeTestCase
{
    public function test_admin_pages_render(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $this->assertPagesRender([
            route('admin.dashboard'),
            route('admin.users.index'),
            route('admin.users.create'),
            route('admin.doctors.index'),
            route('admin.doctors.create'),
            route('admin.pharmacists.index'),
            route('admin.receptionists.index'),
            route('admin.patients.index'),
            route('admin.patients.create'),
            route('admin.schedules.index'),
            route('admin.reports.index'),
            route('admin.audit-logs.index'),
            route('admin.settings.index'),
        ]);
    }

    public function test_admin_detail_pages_render(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $data = $this->seedScenario();

        $this->assertPagesRender([
            route('admin.doctors.show', $data['doctor']),
            route('admin.doctors.edit', $data['doctor']),
            route('admin.patients.show', $data['patient']),
            route('admin.patients.edit', $data['patient']),
            route('admin.audit-logs.show', AuditLog::factory()->create()),
        ]);

        // Jadwal tidak punya halaman detail tersendiri, hanya form ubah.
        $this->get(route('admin.schedules.show', $data['schedule']))
            ->assertRedirect(route('admin.schedules.edit', $data['schedule']));
    }

    public function test_every_report_page_and_csv_export_render(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $this->seedScenario();

        foreach (array_keys(ReportService::availableReports()) as $report) {
            $this->get(route('admin.reports.show', ['report' => $report]))->assertOk();

            $this->get(route('admin.reports.export', ['report' => $report, 'format' => 'csv']))
                ->assertOk();
        }
    }

    public function test_unknown_report_returns_404(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $this->get(route('admin.reports.show', ['report' => 'tidak-ada']))->assertNotFound();
    }
}

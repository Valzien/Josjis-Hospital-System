<?php

namespace Tests\Feature;

use App\Enums\ActiveStatus;
use App\Enums\QueueStatus;
use App\Models\Doctor;
use App\Models\MedicalRecord;
use App\Models\Patient;
use App\Models\Queue;
use App\Models\Service;
use App\Models\User;
use App\Services\BillingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_update_toggle_and_delete_service(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        $this->post(route('admin.services.store'), [
            'code' => 'svc-100',
            'name' => 'Nebulisasi',
            'category' => 'Tindakan Medis',
            'unit' => 'Tindakan',
            'price' => 85000,
            'status' => ActiveStatus::Active->value,
            'description' => 'Terapi uap untuk gangguan pernapasan.',
        ])->assertRedirect(route('admin.services.index'))
            ->assertSessionHas('success');

        $service = Service::query()->firstOrFail();
        $this->assertSame('SVC-100', $service->code);
        $this->assertSame(ActiveStatus::Active, $service->status);
        $this->assertEqualsWithDelta(85000, (float) $service->price, 0.01);

        $this->put(route('admin.services.update', $service), [
            'code' => 'SVC-100',
            'name' => 'Nebulisasi Dewasa',
            'category' => 'Tindakan Medis',
            'unit' => 'Tindakan',
            'price' => 95000,
            'status' => ActiveStatus::Active->value,
            'description' => null,
        ])->assertRedirect(route('admin.services.index'));

        $service->refresh();
        $this->assertSame('Nebulisasi Dewasa', $service->name);
        $this->assertEqualsWithDelta(95000, (float) $service->price, 0.01);

        $this->put(route('admin.services.status', $service))->assertSessionHas('success');
        $this->assertSame(ActiveStatus::Inactive, $service->fresh()->status);

        $this->delete(route('admin.services.destroy', $service))->assertRedirect(route('admin.services.index'));
        $this->assertSoftDeleted('services', ['id' => $service->id]);
    }

    public function test_doctor_selected_services_are_snapshotted_and_billed(): void
    {
        config(['jhs.billing.consultation_fee' => 75000]);

        $doctor = Doctor::factory()->withAccount()->create();
        $patient = Patient::factory()->create();

        $queue = Queue::factory()->inExamination()->create([
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'queue_number' => 'C-010',
            'queue_date' => now()->toDateString(),
        ]);

        $service = Service::factory()->create([
            'name' => 'Nebulisasi',
            'unit' => 'Tindakan',
            'price' => 85000,
        ]);

        $this->actingAs($doctor->user)
            ->post(route('doctor.examinations.store', $queue), [
                'complaint' => 'Sesak napas sejak pagi.',
                'examination_result' => 'Mengi pada kedua lapang paru.',
                'diagnosis' => 'Asma bronkial',
                'services' => [
                    ['service_id' => $service->id, 'quantity' => 2],
                ],
            ])
            ->assertRedirect();

        $record = MedicalRecord::query()->latest('id')->firstOrFail();
        $this->assertSame(QueueStatus::Completed, $queue->fresh()->status);

        $this->assertDatabaseCount('medical_record_services', 1);
        $snapshot = $record->services()->firstOrFail();
        $this->assertSame($service->id, $snapshot->service_id);
        $this->assertSame('Nebulisasi', $snapshot->name);
        $this->assertSame(2, $snapshot->quantity);
        $this->assertEqualsWithDelta(85000, (float) $snapshot->unit_price, 0.01);
        $this->assertEqualsWithDelta(170000, (float) $snapshot->subtotal, 0.01);

        $invoice = app(BillingService::class)->createForQueue($queue->fresh(), 0, null, $doctor->user_id);

        $this->assertEqualsWithDelta(75000 + 170000, (float) $invoice->total, 0.01);

        $item = $invoice->items->firstWhere('type', 'service');
        $this->assertNotNull($item);
        $this->assertSame('Nebulisasi', $item->description);
        $this->assertSame(2, (int) $item->quantity);
        $this->assertEqualsWithDelta(170000, (float) $item->subtotal, 0.01);
    }

    public function test_inactive_service_is_not_offered_on_examination_form(): void
    {
        $doctor = Doctor::factory()->withAccount()->create();
        $patient = Patient::factory()->create();

        $queue = Queue::factory()->inExamination()->create([
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
        ]);

        Service::factory()->create(['name' => 'Layanan Aktif']);
        Service::factory()->inactive()->create(['name' => 'Layanan Nonaktif']);

        $this->actingAs($doctor->user)
            ->get(route('doctor.examinations.create', $queue))
            ->assertOk()
            ->assertSee('Layanan Aktif')
            ->assertDontSee('Layanan Nonaktif');
    }
}

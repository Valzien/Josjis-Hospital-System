<?php

namespace Tests\Feature;

use App\Enums\ActiveStatus;
use App\Enums\AdmissionStatus;
use App\Enums\BedStatus;
use App\Enums\RoomClass;
use App\Models\Admission;
use App\Models\Bed;
use App\Models\Doctor;
use App\Models\Invoice;
use App\Models\Patient;
use App\Models\Room;
use App\Models\Service;
use App\Models\User;
use App\Services\AdmissionService;

class InpatientTest extends SmokeTestCase
{
    private function receptionist(): User
    {
        return User::factory()->receptionist()->create();
    }

    /** @return array{room: Room, bed: Bed} */
    private function availableBed(array $roomAttributes = []): array
    {
        $room = Room::factory()->create($roomAttributes + [
            'rate_per_day' => 150000,
            'status' => ActiveStatus::Active->value,
        ]);

        $bed = Bed::factory()->create([
            'room_id' => $room->id,
            'status' => BedStatus::Available->value,
        ]);

        return compact('room', 'bed');
    }

    public function test_admin_can_manage_rooms_and_beds(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        $this->post(route('admin.rooms.store'), [
            'code' => 'rm-100',
            'name' => 'Melati 1',
            'class' => RoomClass::Class1->value,
            'floor' => '2',
            'rate_per_day' => 450000,
            'status' => ActiveStatus::Active->value,
            'notes' => 'Kamar kelas 1.',
        ])->assertRedirect();

        $room = Room::query()->firstOrFail();
        $this->assertSame('RM-100', $room->code);
        $this->assertSame(RoomClass::Class1, $room->class);

        $this->post(route('admin.rooms.beds.store', $room), [
            'code' => 'a',
            'status' => BedStatus::Available->value,
            'notes' => 'Dekat jendela.',
        ])->assertRedirect();

        $bed = Bed::query()->firstOrFail();
        $this->assertSame('A', $bed->code);
        $this->assertSame($room->id, $bed->room_id);

        // Kamar yang masih memiliki tempat tidur tidak boleh dihapus.
        $this->delete(route('admin.rooms.destroy', $room))->assertSessionHas('error');
        $this->assertDatabaseHas('rooms', ['id' => $room->id]);

        $this->put(route('admin.rooms.beds.update', [$room, $bed]), [
            'code' => 'A',
            'status' => BedStatus::Maintenance->value,
        ])->assertRedirect();

        $this->assertSame(BedStatus::Maintenance, $bed->fresh()->status);

        $this->put(route('admin.rooms.status', $room))->assertSessionHas('success');
        $this->assertSame(ActiveStatus::Inactive, $room->fresh()->status);

        $this->delete(route('admin.rooms.beds.destroy', [$room, $bed]))->assertRedirect();
        $this->assertDatabaseMissing('beds', ['id' => $bed->id]);

        $this->delete(route('admin.rooms.destroy', $room))->assertRedirect(route('admin.rooms.index'));
        $this->assertDatabaseMissing('rooms', ['id' => $room->id]);
    }

    public function test_reception_can_admit_transfer_discharge_and_invoice_is_created(): void
    {
        $receptionist = $this->receptionist();
        $doctor = Doctor::factory()->withAccount()->create();
        $patient = Patient::factory()->create();
        ['room' => $room, 'bed' => $firstBed] = $this->availableBed();

        $secondBed = Bed::factory()->create([
            'room_id' => $room->id,
            'status' => BedStatus::Available->value,
        ]);

        $admittedAt = now()->subDays(2)->startOfMinute();

        $this->actingAs($receptionist)->post(route('reception.admissions.store'), [
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'bed_id' => $firstBed->id,
            'admitted_at' => $admittedAt->format('Y-m-d H:i:s'),
            'complaint' => 'Demam dan lemas.',
            'diagnosis' => 'Observasi demam.',
        ])->assertRedirect();

        $admission = Admission::query()->firstOrFail();
        $this->assertSame(AdmissionStatus::Active, $admission->status);
        $this->assertSame($patient->id, $admission->patient_id);
        $this->assertSame($room->id, $admission->room_id);
        $this->assertSame($firstBed->id, $admission->bed_id);
        $this->assertSame(BedStatus::Occupied, $firstBed->fresh()->status);
        $this->assertDatabaseCount('bed_movements', 1);

        // Pasien dipindahkan ke tempat tidur lain.
        $this->post(route('reception.admissions.transfer', $admission), [
            'bed_id' => $secondBed->id,
            'reason' => 'Pindah kamar.',
        ])->assertRedirect();

        $admission->refresh();
        $this->assertSame($secondBed->id, $admission->bed_id);
        $this->assertSame(BedStatus::Available, $firstBed->fresh()->status);
        $this->assertSame(BedStatus::Occupied, $secondBed->fresh()->status);
        $this->assertDatabaseCount('bed_movements', 2);

        // Dokter mencatat visite dan tindakan.
        $service = Service::factory()->create([
            'name' => 'Nebulisasi',
            'price' => 85000,
        ]);

        $this->actingAs($doctor->user)->post(route('doctor.admissions.visit', $admission), [
            'notes' => 'Sesak berkurang setelah nebulisasi.',
        ])->assertRedirect();

        $this->actingAs($doctor->user)->post(route('doctor.admissions.charge', $admission), [
            'service_id' => $service->id,
            'quantity' => 2,
        ])->assertRedirect();

        $this->assertDatabaseCount('admission_visits', 1);
        $this->assertDatabaseCount('admission_charges', 1);
        $this->assertEqualsWithDelta(170000, (float) $admission->charges()->sum('subtotal'), 0.01);

        // Pasien pulang: biaya kamar 3 hari + 2 tindakan nebulasic.
        $this->actingAs($receptionist)->post(route('reception.admissions.discharge', $admission), [
            'discharged_at' => now()->format('Y-m-d H:i:s'),
            'discharge_notes' => 'Pulang dengan perbaikan.',
        ])->assertRedirect();

        $admission->refresh();
        $this->assertSame(AdmissionStatus::Discharged, $admission->status);
        $this->assertSame(BedStatus::Available, $secondBed->fresh()->status);

        // Biaya kamar: 3 hari x 150.000 = 450.000, tindakan 170.000.
        $this->assertDatabaseHas('admission_charges', [
            'admission_id' => $admission->id,
            'type' => 'ROOM',
            'quantity' => 3,
        ]);

        $invoice = Invoice::query()->where('admission_id', $admission->id)->firstOrFail();
        $this->assertEqualsWithDelta(620000, (float) $invoice->total, 0.01);
        $this->assertNull($invoice->queue_id);
        $this->assertSame($patient->id, $invoice->patient_id);

        $items = $invoice->items()->get();
        $this->assertNotNull($items->firstWhere('type', 'room'));
        $this->assertNotNull($items->firstWhere('type', 'service'));
    }

    public function test_bed_cannot_be_used_by_two_active_admissions(): void
    {
        $doctor = Doctor::factory()->create();
        ['bed' => $bed] = $this->availableBed();

        $first = Patient::factory()->create();
        $second = Patient::factory()->create();

        app(AdmissionService::class)->admit([
            'patient_id' => $first->id,
            'doctor_id' => $doctor->id,
            'bed_id' => $bed->id,
        ]);

        $this->actingAs($this->receptionist())->post(route('reception.admissions.store'), [
            'patient_id' => $second->id,
            'doctor_id' => $doctor->id,
            'bed_id' => $bed->id,
        ])->assertSessionHas('error');

        $this->assertDatabaseCount('admissions', 1);
    }

    public function test_patient_with_active_admission_cannot_be_admitted_again(): void
    {
        $doctor = Doctor::factory()->create();
        ['bed' => $firstBed] = $this->availableBed();
        $secondBed = Bed::factory()->create(['status' => BedStatus::Available->value]);
        $patient = Patient::factory()->create();

        $service = app(AdmissionService::class);

        $service->admit([
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'bed_id' => $firstBed->id,
        ]);

        $this->actingAs($this->receptionist())->post(route('reception.admissions.store'), [
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'bed_id' => $secondBed->id,
        ])->assertSessionHas('error');

        $this->assertDatabaseCount('admissions', 1);
    }

    public function test_occupancy_and_access_are_protected(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        $doctor = Doctor::factory()->withAccount()->create();
        ['room' => $room, 'bed' => $bed] = $this->availableBed();

        $admission = app(AdmissionService::class)->admit([
            'patient_id' => Patient::factory()->create()->id,
            'doctor_id' => $doctor->id,
            'bed_id' => $bed->id,
        ]);

        // Status tempat tidur yang terisi tidak dapat diubah manual.
        $this->put(route('admin.rooms.beds.update', [$room, $bed]), [
            'code' => $bed->code,
            'status' => BedStatus::Maintenance->value,
        ])->assertSessionHas('error');

        $this->assertSame(BedStatus::Occupied, $bed->fresh()->status);

        // Resepsionis tidak boleh mengelola kamar.
        $this->actingAs($this->receptionist())
            ->get(route('admin.rooms.index'))
            ->assertForbidden();

        // Dokter lain tidak boleh membuka rawat inap milik dokter ini.
        $otherDoctor = Doctor::factory()->withAccount()->create();
        $this->actingAs($otherDoctor->user)
            ->get(route('doctor.admissions.show', $admission))
            ->assertForbidden();

        $this->actingAs($doctor->user)
            ->get(route('doctor.admissions.show', $admission))
            ->assertOk()
            ->assertSee($admission->code);
    }

    public function test_cashier_can_view_admission_invoice_and_receipt(): void
    {
        $doctor = Doctor::factory()->create(['name' => 'dr. Andi Prasetyo']);
        ['room' => $room, 'bed' => $bed] = $this->availableBed([
            'name' => 'Melati VIP',
            'rate_per_day' => 500000,
        ]);
        $patient = Patient::factory()->create(['name' => 'Dewi Lestari']);

        $admission = app(AdmissionService::class)->admit([
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'bed_id' => $bed->id,
            'admitted_at' => now()->subDay(),
        ]);

        $admission = app(AdmissionService::class)->discharge($admission);

        $this->actingAs(User::factory()->cashier()->create());

        $invoice = $admission->invoice;
        $this->assertNotNull($invoice);

        $this->get(route('cashier.invoices.show', $invoice))
            ->assertOk()
            ->assertSee($admission->code)
            ->assertSee('Rawat Inap')
            ->assertDontSee('Whoops', false);

        $this->get(route('cashier.invoices.receipt', $invoice))
            ->assertOk()
            ->assertSee($admission->code)
            ->assertDontSee('Whoops', false);

        $this->get(route('cashier.invoices.index'))
            ->assertOk()
            ->assertSee($invoice->code)
            ->assertSee('dr. Andi Prasetyo');
    }
}

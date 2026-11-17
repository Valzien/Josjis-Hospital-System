<?php

namespace Tests\Feature;

use App\Enums\ActiveStatus;
use App\Enums\BedStatus;
use App\Enums\UserRole;
use App\Models\Admission;
use App\Models\Bed;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Room;
use App\Models\User;
use App\Services\AdmissionService;

class NurseRoleTest extends SmokeTestCase
{
    private function nurse(): User
    {
        return User::factory()->nurse()->create(['name' => 'Nur Aisyah']);
    }

    /** @return array{room: Room, bed: Bed} */
    private function availableBed(): array
    {
        $room = Room::factory()->create([
            'rate_per_day' => 150000,
            'status' => ActiveStatus::Active->value,
        ]);

        $bed = Bed::factory()->create([
            'room_id' => $room->id,
            'status' => BedStatus::Available->value,
        ]);

        return compact('room', 'bed');
    }

    public function test_nurse_role_metadata_and_factory_are_complete(): void
    {
        $role = UserRole::Nurse;

        $this->assertSame('nurse', $role->value);
        $this->assertSame('Perawat', $role->label());
        $this->assertSame('Perawat', $role->shortLabel());
        $this->assertSame('nurse', $role->routePrefix());
        $this->assertSame('nurse.dashboard', $role->homeRoute());
        $this->assertSame('nurse.login', $role->loginRoute());
        $this->assertSame($role, UserRole::fromPortal('nurse'));
        $this->assertSame('nurse.login', UserRole::loginRouteForRouteName('nurse.dashboard'));
        $this->assertContains($role, UserRole::staff());

        $nurse = $this->nurse();
        $this->assertTrue($nurse->isNurse());
        $this->assertFalse($nurse->isDoctor());
        $this->assertSame('nurse.dashboard', $nurse->dashboardRoute());
        $this->assertSame('Perawat', $nurse->roleLabel());
    }

    public function test_nurse_pages_render_with_scoped_sidebar(): void
    {
        $this->actingAs($this->nurse());

        $this->assertPagesRender([
            route('nurse.dashboard'),
            route('nurse.admissions.index'),
            route('nurse.admissions.create'),
        ]);

        $this->get(route('nurse.dashboard'))
            ->assertOk()
            ->assertSee('Dashboard Perawat')
            ->assertSee('Pasien Dirawat')
            ->assertSee('Pasien Rawat Inap')
            ->assertDontSee('Registrasi Pasien')
            ->assertDontSee('Tarif & Tindakan')
            ->assertDontSee('Tagihan & Kasir', false);
    }

    public function test_nurse_can_admit_patient_and_record_vitals_and_ttv(): void
    {
        $nurse = $this->nurse();
        $doctor = Doctor::factory()->withAccount()->create(['name' => 'dr. Andi Prasetyo']);
        $patient = Patient::factory()->create(['name' => 'Dewi Lestari']);
        ['bed' => $bed] = $this->availableBed();

        $this->actingAs($nurse)->post(route('nurse.admissions.store'), [
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'bed_id' => $bed->id,
            'complaint' => 'Demam dan batuk.',
            'diagnosis' => 'Observasi demam.',
        ])->assertRedirect();

        $admission = Admission::query()->firstOrFail();
        $this->assertSame($patient->id, $admission->patient_id);
        $this->assertSame($nurse->id, $admission->created_by);

        $this->get(route('nurse.admissions.show', $admission))
            ->assertOk()
            ->assertSee($admission->code)
            ->assertSee('Riwayat Visite & Tanda Vital')
            ->assertSee('Catat Visite & Tanda Vital');

        $this->post(route('nurse.admissions.visit', $admission), [
            'visited_at' => now()->format('Y-m-d H:i:s'),
            'notes' => 'Demam menurun, pasien lebih tenang.',
            'temperature' => 37.8,
            'blood_pressure' => 120,
            'pulse' => 88,
            'respiratory_rate' => 20,
            'oxygen_saturation' => 98,
            'weight' => 58.5,
        ])->assertRedirect()->assertSessionHas('success');

        $visit = $admission->visits()->firstOrFail();
        $this->assertSame('37.8', (string) $visit->temperature);
        $this->assertSame(88, $visit->pulse);
        $this->assertSame($nurse->id, $visit->created_by);
        $this->assertSame($doctor->id, $visit->doctor_id);

        $this->get(route('nurse.admissions.show', $admission))
            ->assertOk()
            ->assertSee('37.8')
            ->assertSee('SpO₂')
            ->assertSee($nurse->name);

        $this->post(route('nurse.admissions.visit', $admission), [
            'notes' => 'Suhu tidak valid.',
            'temperature' => 60,
        ])->assertSessionHasErrors('temperature');
    }

    public function test_nurse_is_forbidden_from_reception_doctor_admin_and_cashier_actions(): void
    {
        $nurse = $this->nurse();
        $doctor = Doctor::factory()->withAccount()->create();
        ['bed' => $bed] = $this->availableBed();

        $admission = app(AdmissionService::class)->admit([
            'patient_id' => Patient::factory()->create()->id,
            'doctor_id' => $doctor->id,
            'bed_id' => $bed->id,
        ]);

        $this->actingAs($nurse);

        $this->get(route('reception.dashboard'))->assertForbidden();
        $this->get(route('doctor.dashboard'))->assertForbidden();
        $this->get(route('pharmacy.dashboard'))->assertForbidden();
        $this->get(route('cashier.dashboard'))->assertForbidden();
        $this->get(route('admin.dashboard'))->assertForbidden();

        $this->get(route('reception.admissions.index'))->assertForbidden();
        $this->get(route('doctor.admissions.show', $admission))->assertForbidden();
        $this->post(route('reception.admissions.discharge', $admission), [])->assertForbidden();
        $this->post(route('doctor.admissions.charge', $admission), [])->assertForbidden();
        $this->post(route('doctor.admissions.diagnosis', $admission), [])->assertForbidden();
        $this->post(route('admin.rooms.store'), [])->assertForbidden();
    }

    public function test_nurse_sees_all_active_admissions_and_admin_retains_cross_access(): void
    {
        $doctor = Doctor::factory()->withAccount()->create();
        ['bed' => $firstBed] = $this->availableBed();
        ['bed' => $secondBed] = $this->availableBed();

        $first = app(AdmissionService::class)->admit([
            'patient_id' => Patient::factory()->create(['name' => 'Pasien Satu'])->id,
            'doctor_id' => $doctor->id,
            'bed_id' => $firstBed->id,
        ]);
        $second = app(AdmissionService::class)->admit([
            'patient_id' => Patient::factory()->create(['name' => 'Pasien Dua'])->id,
            'doctor_id' => $doctor->id,
            'bed_id' => $secondBed->id,
        ]);

        $this->actingAs($this->nurse())
            ->get(route('nurse.admissions.index'))
            ->assertOk()
            ->assertSee($first->code)
            ->assertSee($second->code)
            ->assertSee('Pasien Satu')
            ->assertSee('Pasien Dua');

        $this->actingAs($this->nurse())
            ->get(route('nurse.admissions.show', $second))
            ->assertOk()
            ->assertSee($second->code);

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('nurse.dashboard'))
            ->assertOk();
    }

    public function test_visite_requires_an_active_admission(): void
    {
        $nurse = $this->nurse();
        $doctor = Doctor::factory()->withAccount()->create();
        ['bed' => $bed] = $this->availableBed();

        $admission = app(AdmissionService::class)->admit([
            'patient_id' => Patient::factory()->create()->id,
            'doctor_id' => $doctor->id,
            'bed_id' => $bed->id,
        ]);
        app(AdmissionService::class)->discharge($admission);

        $this->actingAs($nurse)
            ->post(route('nurse.admissions.visit', $admission), [
                'notes' => 'Visite setelah pulang.',
            ])
            ->assertSessionHas('error');

        $this->assertSame(0, $admission->visits()->count());
    }
}

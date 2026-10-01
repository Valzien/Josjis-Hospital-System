<?php

namespace Tests\Feature;

use App\Models\DoctorSchedule;
use App\Models\Patient;
use App\Models\User;

class PatientPagesTest extends SmokeTestCase
{
    private function actingPatient(): Patient
    {
        $user = User::factory()->patient()->create();

        $patient = Patient::factory()->withAccount()->create();
        $patient->user()->associate($user)->save();

        $this->actingAs($user);

        return $patient->fresh();
    }

    public function test_patient_pages_render(): void
    {
        $patient = $this->actingPatient();
        $data = $this->seedScenarioForPatient($patient);

        $this->assertPagesRender([
            route('patient.dashboard'),
            route('patient.profile.edit'),
            route('patient.doctors.index'),
            route('patient.doctors.show', $data['doctor']),
            route('patient.schedules.index'),
            route('patient.queue.index'),
            route('patient.queue.create'),
            route('patient.queue.show', $data['queue']),
            route('patient.history.index'),
            route('patient.history.show', $data['record']),
            route('patient.prescriptions.index'),
            route('patient.prescriptions.show', $data['prescription']),
            route('patient.medicines.index'),
        ]);
    }

    public function test_patient_cannot_open_another_patients_record(): void
    {
        $this->actingPatient();

        $data = $this->seedScenario();

        $this->get(route('patient.history.show', $data['record']))->assertForbidden();
        $this->get(route('patient.prescriptions.show', $data['prescription']))->assertForbidden();
        $this->get(route('patient.queue.show', $data['queue']))->assertForbidden();
    }

    public function test_incomplete_profile_cannot_take_a_queue(): void
    {
        $user = User::factory()->patient()->create();
        $patient = Patient::factory()->incompleteProfile()->create(['user_id' => $user->id]);

        $this->actingAs($user);

        $schedule = DoctorSchedule::factory()->openAllDay()->create();

        $this->post(route('patient.queue.store'), [
            'doctor_schedule_id' => $schedule->id,
        ])->assertSessionHas('error');

        $this->assertDatabaseCount('queues', 0);
    }
}

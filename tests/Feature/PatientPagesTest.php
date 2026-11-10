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

    public function test_patient_can_cancel_their_own_open_queue(): void
    {
        $patient = $this->actingPatient();
        $data = $this->seedScenarioForPatient($patient);
        $queue = $data['queue'];

        $this->post(route('patient.queue.cancel', $queue))
            ->assertRedirect(route('patient.queue.index'))
            ->assertSessionHas('success');

        $this->assertEquals(\App\Enums\QueueStatus::Cancelled, $queue->fresh()->status);
    }

    public function test_patient_cannot_cancel_another_patients_queue(): void
    {
        $this->actingPatient();
        $data = $this->seedScenario();

        $this->post(route('patient.queue.cancel', $data['queue']))
            ->assertForbidden();
    }
}

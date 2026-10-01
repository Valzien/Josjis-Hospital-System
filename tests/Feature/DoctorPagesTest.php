<?php

namespace Tests\Feature;

use App\Models\Doctor;
use App\Models\DoctorSchedule;
use App\Models\MedicalRecord;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\PrescriptionDetail;
use App\Models\Queue;
use App\Models\User;

class DoctorPagesTest extends SmokeTestCase
{
    /** Dokter yang login beserta profilnya. */
    private function actingDoctor(): array
    {
        $user = User::factory()->doctor()->create();
        $doctor = Doctor::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user);

        return [$user, $doctor];
    }

    public function test_doctor_pages_render(): void
    {
        [, $doctor] = $this->actingDoctor();

        $schedule = DoctorSchedule::factory()->openAllDay()->for($doctor)->create();
        $patient = Patient::factory()->create();

        $queue = Queue::factory()->create([
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'doctor_schedule_id' => $schedule->id,
            'queue_number' => 'A-001',
            'queue_date' => now()->toDateString(),
        ]);

        $record = MedicalRecord::factory()->create([
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'queue_id' => $queue->id,
        ]);

        $prescription = Prescription::factory()->create([
            'medical_record_id' => $record->id,
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
        ]);

        PrescriptionDetail::factory()->create(['prescription_id' => $prescription->id]);

        $this->assertPagesRender([
            route('doctor.dashboard'),
            route('doctor.queues.index'),
            route('doctor.queues.show', $queue),
            route('doctor.schedules.index'),
            route('doctor.patients.index'),
            route('doctor.patients.show', $patient),
            route('doctor.medical-records.index'),
            route('doctor.medical-records.show', $record),
            route('doctor.examinations.index'),
            route('doctor.examinations.show', $record),
            route('doctor.prescriptions.index'),
            route('doctor.prescriptions.show', $prescription),
        ]);
    }

    public function test_examination_form_is_blocked_once_record_exists(): void
    {
        [, $doctor] = $this->actingDoctor();

        $schedule = DoctorSchedule::factory()->openAllDay()->for($doctor)->create();

        $queue = Queue::factory()->called()->create([
            'patient_id' => Patient::factory()->create()->id,
            'doctor_id' => $doctor->id,
            'doctor_schedule_id' => $schedule->id,
            'queue_number' => 'A-001',
        ]);

        // Belum ada rekam medis, form pemeriksaan harus terbuka.
        $this->get(route('doctor.examinations.create', $queue))->assertOk();

        MedicalRecord::factory()->create([
            'queue_id' => $queue->id,
            'patient_id' => $queue->patient_id,
            'doctor_id' => $doctor->id,
        ]);

        $this->get(route('doctor.examinations.create', $queue))->assertStatus(422);
    }

    public function test_doctor_cannot_open_another_doctors_queue(): void
    {
        $this->actingDoctor();

        $queue = Queue::factory()->create();

        $this->get(route('doctor.queues.show', $queue))->assertForbidden();
    }

    public function test_doctor_cannot_start_another_doctors_queue(): void
    {
        $this->actingDoctor();

        $queue = Queue::factory()->called()->create();

        $this->post(route('doctor.queues.start', $queue))->assertForbidden();

        $this->assertDatabaseMissing('audit_logs', [
            'description' => 'Memulai pemeriksaan antrean '.$queue->queue_number.'.',
        ]);
    }

    public function test_examination_and_prescription_print_render(): void
    {
        [, $doctor] = $this->actingDoctor();

        $patient = Patient::factory()->create();

        $record = MedicalRecord::factory()->create([
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
        ]);

        $prescription = Prescription::factory()->create([
            'medical_record_id' => $record->id,
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
        ]);

        $this->get(route('doctor.examinations.print', $record))->assertOk();
        $this->get(route('doctor.prescriptions.print', $prescription))->assertOk();
    }
}

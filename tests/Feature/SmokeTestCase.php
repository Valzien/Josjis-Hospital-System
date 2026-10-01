<?php

namespace Tests\Feature;

use App\Models\Doctor;
use App\Models\DoctorSchedule;
use App\Models\MedicalRecord;
use App\Models\Medicine;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\PrescriptionDetail;
use App\Models\Queue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

abstract class SmokeTestCase extends TestCase
{
    use RefreshDatabase;

    /** Data minimal yang cukup untuk merender seluruh halaman. */
    protected function seedScenario(): array
    {
        $doctor = Doctor::factory()->withAccount()->create();
        $schedule = DoctorSchedule::factory()->openAllDay()->for($doctor)->create();

        $patient = Patient::factory()->withAccount()->create();
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

        $medicine = Medicine::factory()->create();

        $prescription = Prescription::factory()->create([
            'medical_record_id' => $record->id,
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
        ]);

        PrescriptionDetail::factory()->create([
            'prescription_id' => $prescription->id,
            'medicine_id' => $medicine->id,
        ]);

        return compact('doctor', 'schedule', 'patient', 'queue', 'record', 'medicine', 'prescription');
    }

    /** Data milik pasien tertentu, agar uji ownership bermakna. */
    protected function seedScenarioForPatient(Patient $patient, ?Doctor $doctor = null, ?DoctorSchedule $schedule = null): array
    {
        $doctor ??= Doctor::factory()->withAccount()->create();
        $schedule ??= DoctorSchedule::factory()->openAllDay()->for($doctor)->create();

        $queue = Queue::factory()->create([
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'doctor_schedule_id' => $schedule->id,
            'queue_number' => 'B-001',
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

        PrescriptionDetail::factory()->create([
            'prescription_id' => $prescription->id,
            'medicine_id' => Medicine::factory()->create()->id,
        ]);

        return compact('doctor', 'schedule', 'queue', 'record', 'prescription');
    }

    /** @param  array<int, string>  $routes */
    protected function assertPagesRender(array $routes): void
    {
        foreach ($routes as $route) {
            $response = $this->get($route);

            $response->assertOk();
            $response->assertDontSee('Whoops', false);
        }
    }
}

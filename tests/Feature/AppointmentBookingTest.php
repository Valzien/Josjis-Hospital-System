<?php

namespace Tests\Feature;

use App\Enums\AppointmentStatus;
use App\Enums\QueueStatus;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\DoctorSchedule;
use App\Models\Patient;
use App\Models\Queue;
use App\Models\Receptionist;
use App\Models\User;
use App\Services\QueueService;
use Illuminate\Support\Carbon;

class AppointmentBookingTest extends SmokeTestCase
{
    private function actingPatient(): Patient
    {
        $user = User::factory()->patient()->create();
        $patient = Patient::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user);

        return $patient->fresh();
    }

    private function receptionist(): User
    {
        $user = User::factory()->receptionist()->create();
        Receptionist::factory()->create(['user_id' => $user->id]);

        return $user;
    }

    /** Jadwal praktik pada tanggal tertentu, cukup lebar untuk satu slot uji. */
    private function scheduleOn(Carbon $date): DoctorSchedule
    {
        return DoctorSchedule::factory()
            ->for(Doctor::factory())
            ->create([
                'day' => $date->dayOfWeek,
                'start_time' => '08:00:00',
                'end_time' => '12:00:00',
                'quota' => 20,
            ]);
    }

    public function test_patient_booking_pages_render(): void
    {
        $patient = $this->actingPatient();
        $this->seedScenarioForPatient($patient);

        // Jadwal hari ini agar halaman pemilihan slot punya isi.
        DoctorSchedule::factory()->openAllDay()->for(Doctor::factory()->create())->create();

        $this->assertPagesRender([
            route('patient.appointments.index'),
            route('patient.appointments.create'),
        ]);

        $this->get(route('patient.appointments.create'))
            ->assertOk()
            ->assertSee('Buat Janji Temu');
    }

    public function test_patient_can_book_a_future_slot(): void
    {
        $patient = $this->actingPatient();
        $date = Carbon::tomorrow();
        $schedule = $this->scheduleOn($date);

        $response = $this->post(route('patient.appointments.store'), [
            'doctor_schedule_id' => $schedule->id,
            'appointment_date' => $date->toDateString(),
            'appointment_time' => '09:00',
            'complaint_note' => 'Nyeri dada sejak kemarin.',
        ]);

        $response->assertRedirect(route('patient.appointments.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('appointments', [
            'patient_id' => $patient->id,
            'doctor_id' => $schedule->doctor_id,
            'doctor_schedule_id' => $schedule->id,
            'status' => AppointmentStatus::Booked->value,
        ]);

        $appointment = Appointment::query()->firstOrFail();
        $this->assertSame($date->toDateString(), $appointment->appointment_date->toDateString());
        $this->assertSame('09:00', $appointment->slotLabel());
        $this->assertStringStartsWith('BK-', $appointment->code);
    }

    public function test_already_booked_slot_is_rejected(): void
    {
        $patient = $this->actingPatient();
        $date = Carbon::tomorrow();
        $schedule = $this->scheduleOn($date);

        Appointment::factory()
            ->forSchedule($schedule)
            ->on($date->toDateString(), '09:00')
            ->create();

        $response = $this->post(route('patient.appointments.store'), [
            'doctor_schedule_id' => $schedule->id,
            'appointment_date' => $date->toDateString(),
            'appointment_time' => '09:00',
        ]);

        $response->assertSessionHas('error');
        $this->assertDatabaseCount('appointments', 1);
        $this->assertSame(0, Appointment::query()->where('patient_id', $patient->id)->count());
    }

    public function test_incomplete_profile_cannot_book(): void
    {
        $user = User::factory()->patient()->create();
        Patient::factory()->incompleteProfile()->create(['user_id' => $user->id]);
        $this->actingAs($user);

        $date = Carbon::tomorrow();
        $schedule = $this->scheduleOn($date);

        $response = $this->post(route('patient.appointments.store'), [
            'doctor_schedule_id' => $schedule->id,
            'appointment_date' => $date->toDateString(),
            'appointment_time' => '09:00',
        ]);

        $response->assertSessionHas('error');
        $this->assertDatabaseCount('appointments', 0);
    }

    public function test_patient_can_cancel_own_appointment(): void
    {
        $patient = $this->actingPatient();
        $schedule = $this->scheduleOn(Carbon::tomorrow());

        $appointment = Appointment::factory()
            ->forSchedule($schedule)
            ->on(Carbon::tomorrow()->toDateString(), '09:00')
            ->create(['patient_id' => $patient->id]);

        $this->patch(route('patient.appointments.cancel', $appointment))
            ->assertRedirect(route('patient.appointments.index'))
            ->assertSessionHas('success');

        $appointment->refresh();
        $this->assertSame(AppointmentStatus::Cancelled, $appointment->status);
        $this->assertNotNull($appointment->cancelled_at);
    }

    public function test_patient_cannot_cancel_another_patients_appointment(): void
    {
        $this->actingPatient();
        $schedule = $this->scheduleOn(Carbon::tomorrow());

        $appointment = Appointment::factory()
            ->forSchedule($schedule)
            ->on(Carbon::tomorrow()->toDateString(), '09:00')
            ->create(['patient_id' => Patient::factory()->create()->id]);

        $this->patch(route('patient.appointments.cancel', $appointment))
            ->assertForbidden();

        $this->assertSame(AppointmentStatus::Booked, $appointment->fresh()->status);
    }

    public function test_receptionist_check_in_creates_queue(): void
    {
        $this->actingAs($this->receptionist());

        $schedule = DoctorSchedule::factory()->openAllDay()->for(Doctor::factory())->create();

        $appointment = Appointment::factory()
            ->forSchedule($schedule)
            ->on(now()->toDateString(), '09:00')
            ->create();

        $this->patch(route('reception.appointments.check-in', $appointment))
            ->assertRedirect()
            ->assertSessionHas('success');

        $appointment->refresh();

        $this->assertSame(AppointmentStatus::CheckedIn, $appointment->status);
        $this->assertNotNull($appointment->queue_id);
        $this->assertNotNull($appointment->checked_in_at);

        $queue = Queue::query()->findOrFail($appointment->queue_id);
        $this->assertSame($appointment->patient_id, $queue->patient_id);
        $this->assertSame($schedule->doctor_id, $queue->doctor_id);
        $this->assertTrue($queue->queue_date->isToday());
        $this->assertSame(QueueStatus::Waiting, $queue->status);
    }

    public function test_receptionist_cannot_check_in_a_future_appointment(): void
    {
        $this->actingAs($this->receptionist());

        $schedule = $this->scheduleOn(Carbon::tomorrow());
        $appointment = Appointment::factory()
            ->forSchedule($schedule)
            ->on(Carbon::tomorrow()->toDateString(), '09:00')
            ->create();

        $this->patch(route('reception.appointments.check-in', $appointment))
            ->assertSessionHas('error');

        $appointment->refresh();
        $this->assertSame(AppointmentStatus::Booked, $appointment->status);
        $this->assertNull($appointment->queue_id);
        $this->assertDatabaseCount('queues', 0);
    }

    public function test_completing_the_queue_marks_appointment_completed(): void
    {
        $schedule = DoctorSchedule::factory()->openAllDay()->for(Doctor::factory())->create();

        $queue = Queue::factory()->create([
            'doctor_id' => $schedule->doctor_id,
            'doctor_schedule_id' => $schedule->id,
            'status' => QueueStatus::InExamination->value,
        ]);

        $appointment = Appointment::factory()
            ->forSchedule($schedule)
            ->checkedIn()
            ->create([
                'patient_id' => $queue->patient_id,
                'queue_id' => $queue->id,
                'appointment_date' => now()->toDateString(),
            ]);

        app(QueueService::class)->updateStatus($queue, QueueStatus::Completed);

        $this->assertSame(AppointmentStatus::Completed, $appointment->fresh()->status);
        $this->assertSame(QueueStatus::Completed, $queue->fresh()->status);
    }

    public function test_reception_appointment_page_renders(): void
    {
        $this->actingAs($this->receptionist());

        $schedule = DoctorSchedule::factory()->openAllDay()->for(Doctor::factory())->create();
        Appointment::factory()->forSchedule($schedule)->on(now()->toDateString(), '09:00')->create();

        $this->get(route('reception.appointments.index'))
            ->assertOk()
            ->assertDontSee('Whoops', false)
            ->assertSee('Janji Temu');
    }
}

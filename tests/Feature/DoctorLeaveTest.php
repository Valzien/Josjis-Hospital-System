<?php

namespace Tests\Feature;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\AuditLog;
use App\Models\Doctor;
use App\Models\DoctorLeave;
use App\Models\DoctorSchedule;
use App\Models\Patient;
use App\Models\User;
use App\Services\AppointmentService;
use App\Services\QueueService;
use Illuminate\Support\Carbon;
use RuntimeException;

class DoctorLeaveTest extends SmokeTestCase
{
    private function admin(): User
    {
        $user = User::factory()->admin()->create();
        $this->actingAs($user);

        return $user;
    }

    private function doctorWithAccount(): Doctor
    {
        return Doctor::factory()->withAccount()->create();
    }

    private function actingPatient(): Patient
    {
        $user = User::factory()->patient()->create();
        $patient = Patient::factory()->create(['user_id' => $user->id]);
        $this->actingAs($user);

        return $patient->fresh();
    }

    private function scheduleOn(Carbon $date, ?Doctor $doctor = null): DoctorSchedule
    {
        return DoctorSchedule::factory()
            ->for($doctor ?? Doctor::factory())
            ->create([
                'day' => $date->dayOfWeek,
                'start_time' => '08:00:00',
                'end_time' => '12:00:00',
                'quota' => 20,
            ]);
    }

    public function test_admin_can_manage_doctor_leaves(): void
    {
        $admin = $this->admin();
        $doctor = $this->doctorWithAccount();
        $date = Carbon::tomorrow();

        $this->get(route('admin.leaves.index'))
            ->assertOk()
            ->assertSee('Cuti Dokter');

        $this->post(route('admin.leaves.store'), [
            'doctor_id' => $doctor->id,
            'date' => $date->toDateString(),
            'reason' => 'Cuti tahunan',
        ])->assertRedirect()->assertSessionHas('success');

        $leave = DoctorLeave::query()->firstOrFail();
        $this->assertSame($doctor->id, $leave->doctor_id);
        $this->assertTrue($leave->date->isSameDay($date));
        $this->assertSame('Cuti tahunan', $leave->reason);
        $this->assertSame($admin->id, $leave->created_by);

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $admin->id,
            'module' => 'doctor_leave',
            'action' => 'create',
        ]);

        $leaveId = $leave->id;
        $this->delete(route('admin.leaves.destroy', $leave))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('doctor_leaves', ['id' => $leaveId]);
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $admin->id,
            'module' => 'doctor_leave',
            'action' => 'delete',
        ]);
    }

    public function test_admin_leave_validation_rejects_duplicate_and_past_dates(): void
    {
        $this->admin();
        $date = Carbon::tomorrow();

        DoctorLeave::factory()
            ->forDoctor($this->doctorWithAccount())
            ->onDate($date)
            ->create();

        $this->post(route('admin.leaves.store'), [
            'doctor_id' => DoctorLeave::query()->firstOrFail()->doctor_id,
            'date' => $date->toDateString(),
        ])->assertSessionHasErrors('date');

        $this->post(route('admin.leaves.store'), [
            'doctor_id' => $this->doctorWithAccount()->id,
            'date' => Carbon::yesterday()->toDateString(),
        ])->assertSessionHasErrors('date');

        $this->assertSame(1, DoctorLeave::query()->count());
    }

    public function test_doctor_can_manage_only_own_leaves(): void
    {
        $doctor = $this->doctorWithAccount();
        $other = $this->doctorWithAccount();
        $this->actingAs($doctor->user);

        $this->get(route('doctor.schedules.index'))
            ->assertOk()
            ->assertSee('Tandai Cuti');

        $this->post(route('doctor.leaves.store'), [
            'date' => Carbon::tomorrow()->toDateString(),
            'reason' => 'Seminar',
        ])->assertRedirect()->assertSessionHas('success');

        $leave = DoctorLeave::query()->firstOrFail();
        $this->assertSame($doctor->id, $leave->doctor_id);
        $this->assertSame($doctor->user_id, $leave->created_by);

        $foreignLeave = DoctorLeave::factory()->forDoctor($other)->onDate(Carbon::tomorrow())->create();

        $this->delete(route('doctor.leaves.destroy', $foreignLeave))->assertForbidden();
        $this->assertDatabaseHas('doctor_leaves', ['id' => $foreignLeave->id]);

        $leaveId = $leave->id;
        $this->delete(route('doctor.leaves.destroy', $leave))
            ->assertRedirect()
            ->assertSessionHas('success');
        $this->assertDatabaseMissing('doctor_leaves', ['id' => $leaveId]);
    }

    public function test_leave_closes_future_slots_and_blocks_booking(): void
    {
        $this->actingPatient();
        $date = Carbon::tomorrow();
        $doctor = $this->doctorWithAccount();
        $schedule = $this->scheduleOn($date, $doctor);

        DoctorLeave::factory()->forDoctor($doctor)->onDate($date)->create();

        $slots = app(AppointmentService::class)->availableSlots($schedule, $date);
        $this->assertNotEmpty($slots);
        foreach ($slots as $slot) {
            $this->assertFalse($slot['available']);
            $this->assertSame('Dokter cuti', $slot['reason']);
        }

        $this->post(route('patient.appointments.store'), [
            'doctor_schedule_id' => $schedule->id,
            'appointment_date' => $date->toDateString(),
            'appointment_time' => '09:00',
        ])->assertSessionHas('error');

        $this->assertDatabaseCount('appointments', 0);
    }

    public function test_patient_booking_page_flags_and_filters_leave_doctors(): void
    {
        $this->actingPatient();
        $date = Carbon::tomorrow();
        $onLeaveDoctor = $this->doctorWithAccount();
        $availableDoctor = $this->doctorWithAccount();

        $this->scheduleOn($date, $onLeaveDoctor);
        $this->scheduleOn($date, $availableDoctor);
        DoctorLeave::factory()->forDoctor($onLeaveDoctor)->onDate($date)->create();

        $this->get(route('patient.appointments.create', ['date' => $date->toDateString()]))
            ->assertOk()
            ->assertSee('Dokter cuti')
            ->assertSee($availableDoctor->name);

        // Hari ini: dokter yang sedang cuti tidak ditawarkan sama sekali.
        $todayDoctor = $this->doctorWithAccount();
        DoctorSchedule::factory()->openAllDay()->for($todayDoctor)->create();
        DoctorLeave::factory()->forDoctor($todayDoctor)->onDate(now())->create();

        $this->get(route('patient.appointments.create'))
            ->assertOk()
            ->assertDontSee($todayDoctor->name);
    }

    public function test_leave_blocks_same_day_walk_in_queue(): void
    {
        $this->actingPatient();
        $schedule = DoctorSchedule::factory()->openAllDay()->for($this->doctorWithAccount())->create();

        DoctorLeave::factory()
            ->forDoctor($schedule->doctor)
            ->onDate(now())
            ->create();

        $this->post(route('patient.appointments.store'), [
            'doctor_schedule_id' => $schedule->id,
            'appointment_date' => now()->toDateString(),
            'complaint_note' => 'Demam.',
        ])->assertSessionHas('error');

        $this->assertDatabaseCount('queues', 0);

        try {
            app(QueueService::class)->assertScheduleAcceptsQueue($schedule);
            $this->fail('Antrean seharusnya ditolak saat dokter cuti.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('cuti', $e->getMessage());
        }
    }

    public function test_existing_appointments_are_kept_but_warned_when_leave_is_added(): void
    {
        $admin = $this->admin();
        $date = Carbon::tomorrow();
        $schedule = $this->scheduleOn($date);

        Appointment::factory()->forSchedule($schedule)->on($date->toDateString(), '09:00')->create();

        $this->post(route('admin.leaves.store'), [
            'doctor_id' => $schedule->doctor_id,
            'date' => $date->toDateString(),
        ])->assertSessionHas('success');

        $this->assertSame(1, Appointment::query()->where('status', AppointmentStatus::Booked->value)->count());
        $this->assertDatabaseCount('doctor_leaves', 1);

        $this->assertSame(1, AuditLog::query()->where('module', 'doctor_leave')->count());
        $this->assertSame($admin->id, DoctorLeave::query()->firstOrFail()->created_by);
    }

    public function test_doctor_on_duty_status_follows_leave(): void
    {
        $doctor = $this->doctorWithAccount();
        DoctorSchedule::factory()->openAllDay()->for($doctor)->create();

        $this->assertTrue($doctor->isOnDuty());

        $leave = DoctorLeave::factory()->forDoctor($doctor)->onDate(now())->create();

        $this->assertTrue($doctor->fresh()->isOnLeave());
        $this->assertFalse($doctor->fresh()->isOnDuty());

        $leave->delete();

        $this->assertTrue($doctor->fresh()->isOnDuty());
    }
}

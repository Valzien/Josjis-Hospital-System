<?php

namespace Tests\Feature;

use App\Enums\Day;
use App\Enums\QueueStatus;
use App\Models\AuditLog;
use App\Models\Doctor;
use App\Models\DoctorSchedule;
use App\Models\Patient;
use App\Models\Queue;
use App\Models\Receptionist;
use App\Models\User;
use App\Services\QueueService;
use Illuminate\Database\UniqueConstraintViolationException;
use RuntimeException;

class QueueWorkflowTest extends SmokeTestCase
{
    private function receptionist(): User
    {
        $user = User::factory()->receptionist()->create();
        Receptionist::factory()->create(['user_id' => $user->id]);

        return $user;
    }

    public function test_queue_number_is_unique_per_doctor_and_date(): void
    {
        $doctor = Doctor::factory()->create();
        $schedule = DoctorSchedule::factory()->openAllDay()->for($doctor)->create();

        Queue::factory()->create([
            'doctor_id' => $doctor->id,
            'doctor_schedule_id' => $schedule->id,
            'queue_date' => now()->toDateString(),
            'queue_number' => 'A-001',
        ]);

        $this->expectException(UniqueConstraintViolationException::class);

        Queue::create([
            'patient_id' => Patient::factory()->create()->id,
            'doctor_id' => $doctor->id,
            'doctor_schedule_id' => $schedule->id,
            'queue_date' => now()->toDateString(),
            'queue_number' => 'A-001',
        ]);
    }

    public function test_take_queue_assigns_sequential_number_for_today(): void
    {
        $schedule = DoctorSchedule::factory()->openAllDay()->create();
        $service = app(QueueService::class);

        $first = $service->takeQueue(Patient::factory()->create(), $schedule);
        $second = $service->takeQueue(Patient::factory()->create(), $schedule);

        $this->assertSame('A-001', $first->queue_number);
        $this->assertSame('A-002', $second->queue_number);
        $this->assertTrue($first->queue_date->isToday());
        $this->assertSame(QueueStatus::Waiting, $first->status);
    }

    public function test_patient_cannot_have_two_open_queues_today(): void
    {
        $schedule = DoctorSchedule::factory()->openAllDay()->create();
        $service = app(QueueService::class);
        $patient = Patient::factory()->create();

        $service->takeQueue($patient, $schedule);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('sudah memiliki antrean aktif');

        $service->takeQueue($patient, DoctorSchedule::factory()->openAllDay()->create());
    }

    public function test_schedule_for_another_day_is_rejected(): void
    {
        $tomorrow = ((int) now()->addDay()->format('N')) % 7;
        $schedule = DoctorSchedule::factory()->on(Day::from($tomorrow))->create();
        $schedule->forceFill(['start_time' => '00:00:00', 'end_time' => '23:59:59'])->save();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('hanya berlaku pada hari');

        app(QueueService::class)->assertScheduleAcceptsQueue($schedule);
    }

    public function test_schedule_outside_operating_hours_is_rejected(): void
    {
        $schedule = DoctorSchedule::factory()->create([
            'day' => now()->dayOfWeek,
            'start_time' => '23:59:00',
            'end_time' => '23:59:30',
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Jam layanan belum dimulai');

        app(QueueService::class)->assertScheduleAcceptsQueue($schedule);
    }

    public function test_full_quota_is_rejected(): void
    {
        $schedule = DoctorSchedule::factory()->create([
            'day' => now()->dayOfWeek,
            'start_time' => '00:00:00',
            'end_time' => '23:59:59',
            'quota' => 1,
        ]);

        Queue::factory()->create([
            'doctor_schedule_id' => $schedule->id,
            'queue_date' => now()->toDateString(),
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Kuota antrean hari ini sudah penuh');

        app(QueueService::class)->assertScheduleAcceptsQueue($schedule);
    }

    public function test_cancelled_queue_does_not_consume_quota(): void
    {
        $schedule = DoctorSchedule::factory()->create([
            'day' => now()->dayOfWeek,
            'start_time' => '00:00:00',
            'end_time' => '23:59:59',
            'quota' => 1,
        ]);

        Queue::factory()->cancelled()->create([
            'doctor_schedule_id' => $schedule->id,
            'queue_date' => now()->toDateString(),
        ]);

        app(QueueService::class)->assertScheduleAcceptsQueue($schedule);

        $this->assertSame(1, app(QueueService::class)->remainingQuota($schedule));
    }

    public function test_call_next_advances_to_next_waiting_queue(): void
    {
        $this->actingAs($this->receptionist());

        $schedule = DoctorSchedule::factory()->openAllDay()->create();
        $doctor = $schedule->doctor;

        $first = Queue::factory()->waiting()->create([
            'doctor_id' => $doctor->id,
            'doctor_schedule_id' => $schedule->id,
            'queue_number' => 'A-001',
        ]);

        $second = Queue::factory()->waiting()->create([
            'doctor_id' => $doctor->id,
            'doctor_schedule_id' => $schedule->id,
            'queue_number' => 'A-002',
        ]);

        $this->post(route('reception.queues.call-next'), ['doctor_id' => $doctor->id])
            ->assertRedirect();

        $this->assertSame(QueueStatus::Called, $first->fresh()->status);
        $this->assertSame(QueueStatus::Waiting, $second->fresh()->status);
    }

    public function test_status_changes_are_recorded_in_audit_log(): void
    {
        $user = $this->receptionist();
        $this->actingAs($user);

        $queue = Queue::factory()->waiting()->create();

        $this->post(route('reception.queues.call', $queue))->assertRedirect();

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'module' => 'queue',
            'action' => 'update',
        ]);

        $log = AuditLog::query()->latest('id')->first();

        $this->assertSame('Queue', $log->properties['entity'] ?? null);
        $this->assertSame($queue->id, $log->properties['entity_id'] ?? null);
    }
}

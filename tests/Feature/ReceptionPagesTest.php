<?php

namespace Tests\Feature;

use App\Enums\QueueStatus;
use App\Models\Queue;
use App\Models\User;

class ReceptionPagesTest extends SmokeTestCase
{
    public function test_reception_pages_render(): void
    {
        $this->actingAs(User::factory()->receptionist()->create());

        $data = $this->seedScenario();

        $this->assertPagesRender([
            route('reception.dashboard'),
            route('reception.queues.index'),
            route('reception.queues.board'),
            route('reception.queues.create'),
            route('reception.queues.show', $data['queue']),
            route('reception.schedules.index'),
            route('reception.patients.index'),
            route('reception.patients.show', $data['patient']),
            route('reception.registration.create'),
            route('reception.registration.history'),
            route('reception.registration.edit', $data['patient']),
        ]);
    }

    public function test_queue_board_shows_queue_number(): void
    {
        $this->actingAs(User::factory()->receptionist()->create());

        $data = $this->seedScenario();

        $this->get(route('reception.queues.board'))->assertOk()->assertSee($data['queue']->queue_number);
    }

    public function test_receptionist_can_call_a_waiting_queue(): void
    {
        $this->actingAs(User::factory()->receptionist()->create());

        $queue = Queue::factory()->waiting()->create();

        $this->patch(route('reception.queues.call', $queue))->assertRedirect();

        $this->assertSame(QueueStatus::Called, $queue->fresh()->status);
    }

    public function test_receptionist_can_recall_a_called_queue(): void
    {
        $this->actingAs(User::factory()->receptionist()->create());

        $queue = Queue::factory()->create([
            'status' => QueueStatus::Called,
            'called_at' => now()->subMinutes(10),
        ]);

        $this->patch(route('reception.queues.recall', $queue))->assertRedirect();

        $this->assertSame(QueueStatus::Called, $queue->fresh()->status);
        $this->assertTrue($queue->fresh()->called_at->greaterThan(now()->subSeconds(5)));
    }

    public function test_receptionist_can_close_stale_queues(): void
    {
        $this->actingAs(User::factory()->receptionist()->create());

        $staleWaiting = Queue::factory()->create([
            'queue_date' => now()->subDay()->toDateString(),
            'status' => QueueStatus::Waiting,
        ]);
        $staleCalled = Queue::factory()->create([
            'queue_date' => now()->subDays(2)->toDateString(),
            'status' => QueueStatus::Called,
        ]);
        $todayWaiting = Queue::factory()->create([
            'queue_date' => now()->toDateString(),
            'status' => QueueStatus::Waiting,
        ]);

        $this->post(route('reception.queues.close-stale'))->assertRedirect();

        $this->assertSame(QueueStatus::Cancelled, $staleWaiting->fresh()->status);
        $this->assertSame(QueueStatus::Cancelled, $staleCalled->fresh()->status);
        $this->assertSame(QueueStatus::Waiting, $todayWaiting->fresh()->status);
    }

    public function test_ticket_page_renders(): void
    {
        $this->actingAs(User::factory()->receptionist()->create());

        $queue = Queue::factory()->waiting()->create();

        $this->get(route('reception.queues.ticket', $queue))
            ->assertOk()
            ->assertSee($queue->queue_number);
    }
}

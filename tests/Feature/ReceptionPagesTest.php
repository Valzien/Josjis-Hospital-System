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

        $this->post(route('reception.queues.call', $queue))->assertRedirect();

        $this->assertSame(QueueStatus::Called, $queue->fresh()->status);
    }
}

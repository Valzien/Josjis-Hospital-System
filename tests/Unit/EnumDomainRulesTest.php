<?php

namespace Tests\Unit;

use App\Enums\PrescriptionStatus;
use App\Enums\QueueStatus;
use App\Enums\TransactionType;
use App\Enums\UserRole;
use PHPUnit\Framework\TestCase;

class EnumDomainRulesTest extends TestCase
{
    public function test_prescription_status_follows_a_strict_linear_flow(): void
    {
        $this->assertTrue(PrescriptionStatus::Pending->canTransitionTo(PrescriptionStatus::Processing));
        $this->assertTrue(PrescriptionStatus::Processing->canTransitionTo(PrescriptionStatus::Ready));
        $this->assertTrue(PrescriptionStatus::Ready->canTransitionTo(PrescriptionStatus::Completed));

        $this->assertFalse(PrescriptionStatus::Pending->canTransitionTo(PrescriptionStatus::Ready));
        $this->assertFalse(PrescriptionStatus::Pending->canTransitionTo(PrescriptionStatus::Completed));
        $this->assertFalse(PrescriptionStatus::Ready->canTransitionTo(PrescriptionStatus::Processing));
        $this->assertFalse(PrescriptionStatus::Completed->canTransitionTo(PrescriptionStatus::Ready));
    }

    public function test_prescription_status_can_always_be_cancelled_until_completed(): void
    {
        foreach ([PrescriptionStatus::Pending, PrescriptionStatus::Processing, PrescriptionStatus::Ready] as $status) {
            $this->assertTrue($status->canTransitionTo(PrescriptionStatus::Cancelled), "{$status->value} should be cancellable");
        }

        $this->assertFalse(PrescriptionStatus::Completed->canTransitionTo(PrescriptionStatus::Cancelled));
        $this->assertFalse(PrescriptionStatus::Cancelled->canTransitionTo(PrescriptionStatus::Cancelled));
    }

    public function test_prescription_status_exposes_presentation_metadata_for_every_case(): void
    {
        foreach (PrescriptionStatus::cases() as $case) {
            $this->assertNotSame('', $case->label());
            $this->assertNotSame('', $case->badge());
            $this->assertStringStartsWith('bi-', $case->icon());
        }
    }

    public function test_queue_status_allows_calling_and_returning_to_waiting(): void
    {
        $this->assertTrue(QueueStatus::Waiting->canTransitionTo(QueueStatus::Called));
        $this->assertTrue(QueueStatus::Called->canTransitionTo(QueueStatus::InExamination));
        $this->assertTrue(QueueStatus::InExamination->canTransitionTo(QueueStatus::Completed));

        $this->assertTrue(QueueStatus::Called->canTransitionTo(QueueStatus::Waiting));
    }

    public function test_queue_status_cannot_skip_examination_or_reopen_when_finished(): void
    {
        $this->assertFalse(QueueStatus::Waiting->canTransitionTo(QueueStatus::Completed));
        $this->assertFalse(QueueStatus::Called->canTransitionTo(QueueStatus::Completed));
        $this->assertFalse(QueueStatus::Completed->canTransitionTo(QueueStatus::Waiting));
        $this->assertFalse(QueueStatus::Cancelled->canTransitionTo(QueueStatus::Waiting));
        $this->assertSame([], QueueStatus::Completed->allowedTransitions());
        $this->assertSame([], QueueStatus::Cancelled->allowedTransitions());
    }

    public function test_queue_status_allows_walking_straight_into_examination_from_waiting(): void
    {
        $this->assertTrue(QueueStatus::Waiting->canTransitionTo(QueueStatus::InExamination));
    }

    public function test_queue_status_classifies_open_and_finished_states(): void
    {
        $this->assertTrue(QueueStatus::Waiting->isOpen());
        $this->assertTrue(QueueStatus::Called->isOpen());
        $this->assertTrue(QueueStatus::InExamination->isOpen());

        $this->assertFalse(QueueStatus::Completed->isOpen());
        $this->assertFalse(QueueStatus::Cancelled->isOpen());

        $this->assertTrue(QueueStatus::Completed->isFinished());
        $this->assertTrue(QueueStatus::Cancelled->isFinished());
        $this->assertFalse(QueueStatus::InExamination->isFinished());
    }

    public function test_queue_status_cancelled_values_match_the_cancelled_case(): void
    {
        $this->assertSame([QueueStatus::Cancelled->value], QueueStatus::cancelledValues());
    }

    public function test_queue_status_can_always_be_cancelled_before_completion(): void
    {
        foreach ([QueueStatus::Waiting, QueueStatus::Called, QueueStatus::InExamination] as $status) {
            $this->assertTrue($status->canTransitionTo(QueueStatus::Cancelled), "{$status->value} should be cancellable");
        }

        $this->assertFalse(QueueStatus::Completed->canTransitionTo(QueueStatus::Cancelled));
    }

    public function test_transaction_type_sign_indicates_stock_direction(): void
    {
        $this->assertSame(1, TransactionType::In->sign());
        $this->assertSame(-1, TransactionType::Out->sign());
        $this->assertSame(0, TransactionType::Adjustment->sign());
    }

    public function test_user_role_routes_to_a_distinct_home_for_each_role(): void
    {
        $homes = [];

        foreach (UserRole::cases() as $role) {
            $this->assertNotSame('', $role->homeRoute());
            $this->assertNotSame('', $role->routePrefix());
            $this->assertNotSame('', $role->shortLabel());

            $homes[$role->homeRoute()][] = $role->value;
        }

        foreach ($homes as $route => $roles) {
            $this->assertCount(1, $roles, "route {$route} is shared by: ".implode(', ', $roles));
        }
    }

    public function test_options_helpers_cover_every_case_for_all_enums(): void
    {
        $pairs = [
            [PrescriptionStatus::class, PrescriptionStatus::cases()],
            [QueueStatus::class, QueueStatus::cases()],
            [TransactionType::class, TransactionType::cases()],
            [UserRole::class, UserRole::cases()],
        ];

        foreach ($pairs as [$class, $cases]) {
            $options = $class::options();

            $this->assertCount(count($cases), $options, "{$class}::options() must cover every case");
        }
    }
}

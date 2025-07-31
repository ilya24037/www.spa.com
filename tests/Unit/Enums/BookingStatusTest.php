<?php

namespace Tests\Unit\Enums;

use Tests\TestCase;
use App\Enums\BookingStatus;

class BookingStatusTest extends TestCase
{
    /** @test */
    public function it_has_correct_values()
    {
        $this->assertEquals('pending', BookingStatus::PENDING->value);
        $this->assertEquals('confirmed', BookingStatus::CONFIRMED->value);
        $this->assertEquals('completed', BookingStatus::COMPLETED->value);
        $this->assertEquals('cancelled', BookingStatus::CANCELLED->value);
        $this->assertEquals('no_show', BookingStatus::NO_SHOW->value);
    }

    /** @test */
    public function it_returns_correct_labels()
    {
        $this->assertEquals('Ожидает подтверждения', BookingStatus::PENDING->label());
        $this->assertEquals('Подтверждено', BookingStatus::CONFIRMED->label());
        $this->assertEquals('Завершено', BookingStatus::COMPLETED->label());
        $this->assertEquals('Отменено', BookingStatus::CANCELLED->label());
        $this->assertEquals('Не явился', BookingStatus::NO_SHOW->label());
    }

    /** @test */
    public function it_returns_correct_colors()
    {
        $this->assertEquals('yellow', BookingStatus::PENDING->color());
        $this->assertEquals('blue', BookingStatus::CONFIRMED->color());
        $this->assertEquals('green', BookingStatus::COMPLETED->color());
        $this->assertEquals('red', BookingStatus::CANCELLED->color());
        $this->assertEquals('gray', BookingStatus::NO_SHOW->color());
    }

    /** @test */
    public function it_returns_correct_icons()
    {
        $this->assertEquals('clock', BookingStatus::PENDING->icon());
        $this->assertEquals('check-circle', BookingStatus::CONFIRMED->icon());
        $this->assertEquals('check-double', BookingStatus::COMPLETED->icon());
        $this->assertEquals('x-circle', BookingStatus::CANCELLED->icon());
        $this->assertEquals('user-x', BookingStatus::NO_SHOW->icon());
    }

    /** @test */
    public function it_determines_if_cancellable()
    {
        $this->assertTrue(BookingStatus::PENDING->canBeCancelled());
        $this->assertTrue(BookingStatus::CONFIRMED->canBeCancelled());
        $this->assertFalse(BookingStatus::COMPLETED->canBeCancelled());
        $this->assertFalse(BookingStatus::CANCELLED->canBeCancelled());
        $this->assertFalse(BookingStatus::NO_SHOW->canBeCancelled());
    }

    /** @test */
    public function it_determines_if_modifiable()
    {
        $this->assertTrue(BookingStatus::PENDING->canBeModified());
        $this->assertTrue(BookingStatus::CONFIRMED->canBeModified());
        $this->assertFalse(BookingStatus::COMPLETED->canBeModified());
        $this->assertFalse(BookingStatus::CANCELLED->canBeModified());
        $this->assertFalse(BookingStatus::NO_SHOW->canBeModified());
    }

    /** @test */
    public function it_checks_transition_rules()
    {
        // From PENDING
        $this->assertTrue(BookingStatus::PENDING->canTransitionTo(BookingStatus::CONFIRMED));
        $this->assertTrue(BookingStatus::PENDING->canTransitionTo(BookingStatus::CANCELLED));
        $this->assertFalse(BookingStatus::PENDING->canTransitionTo(BookingStatus::COMPLETED));

        // From CONFIRMED
        $this->assertTrue(BookingStatus::CONFIRMED->canTransitionTo(BookingStatus::COMPLETED));
        $this->assertTrue(BookingStatus::CONFIRMED->canTransitionTo(BookingStatus::CANCELLED));
        $this->assertTrue(BookingStatus::CONFIRMED->canTransitionTo(BookingStatus::NO_SHOW));
        $this->assertFalse(BookingStatus::CONFIRMED->canTransitionTo(BookingStatus::PENDING));

        // From COMPLETED
        $this->assertFalse(BookingStatus::COMPLETED->canTransitionTo(BookingStatus::PENDING));
        $this->assertFalse(BookingStatus::COMPLETED->canTransitionTo(BookingStatus::CONFIRMED));
        $this->assertFalse(BookingStatus::COMPLETED->canTransitionTo(BookingStatus::CANCELLED));

        // From CANCELLED
        $this->assertFalse(BookingStatus::CANCELLED->canTransitionTo(BookingStatus::PENDING));
        $this->assertFalse(BookingStatus::CANCELLED->canTransitionTo(BookingStatus::CONFIRMED));
        $this->assertFalse(BookingStatus::CANCELLED->canTransitionTo(BookingStatus::COMPLETED));
    }

    /** @test */
    public function it_returns_all_statuses()
    {
        $all = BookingStatus::all();
        
        $this->assertCount(5, $all);
        $this->assertArrayHasKey('pending', $all);
        $this->assertArrayHasKey('confirmed', $all);
        $this->assertArrayHasKey('completed', $all);
        $this->assertArrayHasKey('cancelled', $all);
        $this->assertArrayHasKey('no_show', $all);
    }

    /** @test */
    public function it_returns_active_statuses()
    {
        $active = BookingStatus::active();
        
        $this->assertCount(2, $active);
        $this->assertContains(BookingStatus::PENDING, $active);
        $this->assertContains(BookingStatus::CONFIRMED, $active);
    }

    /** @test */
    public function it_returns_inactive_statuses()
    {
        $inactive = BookingStatus::inactive();
        
        $this->assertCount(3, $inactive);
        $this->assertContains(BookingStatus::COMPLETED, $inactive);
        $this->assertContains(BookingStatus::CANCELLED, $inactive);
        $this->assertContains(BookingStatus::NO_SHOW, $inactive);
    }

    /** @test */
    public function it_returns_notifiable_statuses()
    {
        $notifiable = BookingStatus::notifiable();
        
        $this->assertCount(4, $notifiable);
        $this->assertContains(BookingStatus::CONFIRMED, $notifiable);
        $this->assertContains(BookingStatus::CANCELLED, $notifiable);
        $this->assertContains(BookingStatus::COMPLETED, $notifiable);
        $this->assertContains(BookingStatus::NO_SHOW, $notifiable);
    }

    /** @test */
    public function it_returns_description_for_status()
    {
        $pending = BookingStatus::PENDING->description();
        $this->assertStringContainsString('ожидает подтверждения', $pending);

        $confirmed = BookingStatus::CONFIRMED->description();
        $this->assertStringContainsString('подтверждено', $confirmed);
    }
}
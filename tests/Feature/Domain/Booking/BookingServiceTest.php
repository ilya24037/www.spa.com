<?php

namespace Tests\Feature\Domain\Booking;

use Tests\TestCase;
use App\Domain\Booking\Services\BookingService;
use App\Domain\Booking\DTOs\BookingData;
use App\Domain\User\Models\User;
use App\Domain\Master\Models\MasterProfile;
use App\Enums\BookingStatus;
use App\Enums\UserRole;
use App\Enums\MasterStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;

class BookingServiceTest extends TestCase
{
    use RefreshDatabase;

    private BookingService $bookingService;
    private User $client;
    private User $master;
    private MasterProfile $masterProfile;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->bookingService = app(BookingService::class);
        
        // Создаем клиента
        $this->client = User::factory()->create([
            'role' => UserRole::CLIENT
        ]);
        
        // Создаем мастера
        $this->master = User::factory()->create([
            'role' => UserRole::MASTER
        ]);
        
        // Создаем профиль мастера
        $this->masterProfile = MasterProfile::factory()->create([
            'user_id' => $this->master->id,
            'status' => MasterStatus::ACTIVE,
        ]);
    }

    public function test_can_create_booking()
    {
        $bookingData = BookingData::fromArray([
            'master_id' => $this->masterProfile->id,
            'client_id' => $this->client->id,
            'service_ids' => [1, 2],
            'date' => now()->addDays(1)->format('Y-m-d'),
            'time_start' => '14:00',
            'time_end' => '15:00',
            'total_price' => 1500,
            'client_name' => 'Test Client',
            'client_phone' => '+7 999 999 99 99',
        ]);

        $result = $this->bookingService->create($bookingData);

        $this->assertArrayHasKey('success', $result);
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('booking', $result);
        $this->assertEquals(BookingStatus::PENDING, $result['booking']->status);
    }

    public function test_cannot_create_booking_for_inactive_master()
    {
        // Деактивируем мастера
        $this->masterProfile->update(['status' => MasterStatus::INACTIVE]);

        $bookingData = BookingData::fromArray([
            'master_id' => $this->masterProfile->id,
            'client_id' => $this->client->id,
            'service_ids' => [1],
            'date' => now()->addDays(1)->format('Y-m-d'),
            'time_start' => '14:00',
            'time_end' => '15:00',
            'total_price' => 1000,
        ]);

        $result = $this->bookingService->create($bookingData);

        $this->assertFalse($result['success']);
        $this->assertEquals('Мастер недоступен для бронирования', $result['message']);
    }

    public function test_can_cancel_booking()
    {
        // Создаем бронирование
        $bookingData = BookingData::fromArray([
            'master_id' => $this->masterProfile->id,
            'client_id' => $this->client->id,
            'service_ids' => [1],
            'date' => now()->addDays(1)->format('Y-m-d'),
            'time_start' => '14:00',
            'time_end' => '15:00',
            'total_price' => 1000,
        ]);

        $createResult = $this->bookingService->create($bookingData);
        $booking = $createResult['booking'];

        // Отменяем бронирование
        $cancelResult = $this->bookingService->cancel(
            $booking->id,
            $this->client->id,
            'Изменились планы'
        );

        $this->assertTrue($cancelResult['success']);
        $this->assertEquals(BookingStatus::CANCELLED, $cancelResult['booking']->status);
        $this->assertEquals('Изменились планы', $cancelResult['booking']->cancellation_reason);
    }

    public function test_master_can_confirm_booking()
    {
        // Создаем бронирование
        $bookingData = BookingData::fromArray([
            'master_id' => $this->masterProfile->id,
            'client_id' => $this->client->id,
            'service_ids' => [1],
            'date' => now()->addDays(1)->format('Y-m-d'),
            'time_start' => '14:00',
            'time_end' => '15:00',
            'total_price' => 1000,
        ]);

        $createResult = $this->bookingService->create($bookingData);
        $booking = $createResult['booking'];

        // Подтверждаем бронирование
        $confirmResult = $this->bookingService->confirm(
            $booking->id,
            $this->master->id
        );

        $this->assertTrue($confirmResult['success']);
        $this->assertEquals(BookingStatus::CONFIRMED, $confirmResult['booking']->status);
        $this->assertNotNull($confirmResult['booking']->confirmed_at);
    }

    public function test_cannot_book_past_time()
    {
        $bookingData = BookingData::fromArray([
            'master_id' => $this->masterProfile->id,
            'client_id' => $this->client->id,
            'service_ids' => [1],
            'date' => now()->subDays(1)->format('Y-m-d'),
            'time_start' => '14:00',
            'time_end' => '15:00',
            'total_price' => 1000,
        ]);

        $result = $this->bookingService->create($bookingData);

        $this->assertFalse($result['success']);
        $this->assertEquals('Нельзя создать бронирование на прошедшее время', $result['message']);
    }

    public function test_can_get_bookings_for_date()
    {
        $date = now()->addDays(1)->format('Y-m-d');
        
        // Создаем несколько бронирований на одну дату
        for ($i = 0; $i < 3; $i++) {
            $bookingData = BookingData::fromArray([
                'master_id' => $this->masterProfile->id,
                'client_id' => $this->client->id,
                'service_ids' => [1],
                'date' => $date,
                'time_start' => sprintf('%02d:00', 10 + $i),
                'time_end' => sprintf('%02d:00', 11 + $i),
                'total_price' => 1000,
            ]);
            
            $this->bookingService->create($bookingData);
        }

        // Получаем бронирования на дату
        $bookings = $this->bookingService->getBookingsForDate(
            $this->masterProfile->id,
            $date
        );

        $this->assertCount(3, $bookings);
    }
}
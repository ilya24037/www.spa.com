<?php

namespace Tests\Unit\Domain\Booking\Services;

use Tests\TestCase;
use App\Domain\Booking\Services\BookingService;
use App\Domain\Booking\Services\AvailabilityService;
use App\Domain\Booking\Services\PricingService;
use App\Domain\Booking\Services\ValidationService;
use App\Domain\Booking\Services\BookingSlotService;
use App\Domain\Booking\Services\NotificationService;
use App\Domain\Booking\Actions\CreateBookingAction;
use App\Domain\Booking\Actions\ConfirmBookingAction;
use App\Domain\Booking\Actions\CancelBookingAction;
use App\Domain\Booking\Actions\CompleteBookingAction;
use App\Domain\Booking\Actions\RescheduleBookingAction;
use App\Domain\Booking\Models\Booking;
use App\Domain\Booking\DTOs\BookingData;
use App\Domain\Booking\Repositories\BookingRepository;
use App\Domain\Booking\Events\BookingCreated;
use App\Domain\User\Models\User;
use App\Enums\BookingType;
use App\Enums\BookingStatus;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Mockery;

class BookingServiceTest extends TestCase
{
    use RefreshDatabase;

    private BookingService $service;
    private BookingRepository $repository;
    private AvailabilityService $availabilityService;
    private PricingService $pricingService;
    private ValidationService $validationService;
    private BookingSlotService $slotService;
    private NotificationService $notificationService;
    private CreateBookingAction $createAction;
    private ConfirmBookingAction $confirmAction;
    private CancelBookingAction $cancelAction;
    private CompleteBookingAction $completeAction;
    private RescheduleBookingAction $rescheduleAction;
    
    private User $client;
    private User $master;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock dependencies
        $this->repository = Mockery::mock(BookingRepository::class);
        $this->availabilityService = Mockery::mock(AvailabilityService::class);
        $this->pricingService = Mockery::mock(PricingService::class);
        $this->validationService = Mockery::mock(ValidationService::class);
        $this->slotService = Mockery::mock(BookingSlotService::class);
        $this->notificationService = Mockery::mock(NotificationService::class);
        $this->createAction = Mockery::mock(CreateBookingAction::class);
        $this->confirmAction = Mockery::mock(ConfirmBookingAction::class);
        $this->cancelAction = Mockery::mock(CancelBookingAction::class);
        $this->completeAction = Mockery::mock(CompleteBookingAction::class);
        $this->rescheduleAction = Mockery::mock(RescheduleBookingAction::class);

        $this->service = new BookingService(
            $this->repository,
            $this->availabilityService,
            $this->pricingService,
            $this->validationService,
            $this->slotService,
            $this->notificationService,
            $this->createAction,
            $this->confirmAction,
            $this->cancelAction,
            $this->completeAction,
            $this->rescheduleAction
        );

        $this->client = User::factory()->create(['role' => 'client']);
        $this->master = User::factory()->create(['role' => 'master']);

        Event::fake();
        Log::shouldReceive('error')->andReturnNull();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Тест создания бронирования с новым форматом
     */
    public function test_create_booking_with_type()
    {
        // Arrange
        $data = [
            'master_id' => $this->master->id,
            'client_id' => $this->client->id,
            'service_id' => 1,
            'type' => BookingType::DEFAULT->value,
            'start_time' => now()->addDays(2)->setHour(14)->format('Y-m-d H:i:s'),
            'duration' => 60,
            'price' => 1000,
            'notes' => 'Test booking'
        ];

        $booking = new Booking(array_merge($data, [
            'id' => 1,
            'booking_number' => 'BK123456',
            'status' => BookingStatus::PENDING
        ]));

        $this->validationService->shouldReceive('validateBookingData')
            ->once()
            ->with($data);

        $this->availabilityService->shouldReceive('validateTimeSlotAvailability')
            ->once()
            ->with($data, BookingType::DEFAULT);

        $this->createAction->shouldReceive('execute')
            ->once()
            ->with(Mockery::on(function ($bookingData) {
                return $bookingData instanceof BookingData;
            }))
            ->andReturn($booking);

        // Act
        $result = $this->service->createBooking($data);

        // Assert
        $this->assertInstanceOf(Booking::class, $result);
        $this->assertEquals(1, $result->id);
        Event::assertDispatched(BookingCreated::class);
    }

    /**
     * Тест создания бронирования со старым форматом
     */
    public function test_create_booking_legacy_format()
    {
        // Arrange
        $data = [
            'master_profile_id' => 1,
            'client_id' => $this->client->id,
            'service_id' => 1,
            'booking_date' => '2024-12-25',
            'booking_time' => '14:00',
            'price' => 1000
        ];

        $booking = new Booking([
            'id' => 1,
            'booking_number' => 'BK123456',
            'status' => BookingStatus::PENDING
        ]);

        $this->validationService->shouldReceive('validateBookingData')
            ->once()
            ->with($data);

        $this->availabilityService->shouldReceive('validateTimeSlot')
            ->once()
            ->with(1, '2024-12-25', '14:00', 1);

        $this->createAction->shouldReceive('execute')
            ->once()
            ->andReturn($booking);

        // Act
        $result = $this->service->createBooking($data);

        // Assert
        $this->assertInstanceOf(Booking::class, $result);
        Event::assertDispatched(BookingCreated::class);
    }

    /**
     * Тест подтверждения бронирования
     */
    public function test_confirm_booking()
    {
        // Arrange
        $booking = new Booking([
            'id' => 1,
            'status' => BookingStatus::PENDING,
            'master_id' => $this->master->id
        ]);

        $confirmedBooking = new Booking([
            'id' => 1,
            'status' => BookingStatus::CONFIRMED,
            'confirmed_at' => now()
        ]);

        $this->validationService->shouldReceive('validateMasterPermission')
            ->once()
            ->with($booking, $this->master);

        $this->validationService->shouldReceive('validateConfirmationAbility')
            ->once()
            ->with($booking);

        $this->confirmAction->shouldReceive('execute')
            ->once()
            ->with($booking, $this->master)
            ->andReturn($confirmedBooking);

        $this->notificationService->shouldReceive('sendBookingConfirmation')
            ->once()
            ->with($confirmedBooking);

        // Act
        $result = $this->service->confirmBooking($booking, $this->master);

        // Assert
        $this->assertEquals(BookingStatus::CONFIRMED, $result->status);
        $this->assertNotNull($result->confirmed_at);
    }

    /**
     * Тест отмены бронирования
     */
    public function test_cancel_booking()
    {
        // Arrange
        $booking = new Booking([
            'id' => 1,
            'status' => BookingStatus::CONFIRMED,
            'client_id' => $this->client->id
        ]);

        $cancelledBooking = new Booking([
            'id' => 1,
            'status' => BookingStatus::CANCELLED,
            'cancelled_at' => now(),
            'cancelled_by' => $this->client->id,
            'cancellation_reason' => 'Изменились планы'
        ]);

        $this->validationService->shouldReceive('validateCancellationPermission')
            ->once()
            ->with($booking, $this->client);

        $this->availabilityService->shouldReceive('canCancelBooking')
            ->once()
            ->with($booking)
            ->andReturn(true);

        $this->cancelAction->shouldReceive('execute')
            ->once()
            ->with($booking, $this->client, 'Изменились планы')
            ->andReturn($cancelledBooking);

        $this->notificationService->shouldReceive('sendBookingCancellation')
            ->once()
            ->with($cancelledBooking);

        // Act
        $result = $this->service->cancelBooking($booking, $this->client, 'Изменились планы');

        // Assert
        $this->assertEquals(BookingStatus::CANCELLED, $result->status);
        $this->assertEquals('Изменились планы', $result->cancellation_reason);
    }

    /**
     * Тест отмены бронирования когда нельзя отменить
     */
    public function test_cancel_booking_when_not_allowed()
    {
        // Arrange
        $booking = new Booking(['id' => 1]);

        $this->validationService->shouldReceive('validateCancellationPermission')
            ->once();

        $this->availabilityService->shouldReceive('canCancelBooking')
            ->once()
            ->with($booking)
            ->andReturn(false);

        // Assert
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Это бронирование нельзя отменить');

        // Act
        $this->service->cancelBooking($booking, $this->client);
    }

    /**
     * Тест завершения бронирования
     */
    public function test_complete_booking()
    {
        // Arrange
        $booking = new Booking([
            'id' => 1,
            'status' => BookingStatus::CONFIRMED,
            'master_id' => $this->master->id
        ]);

        $completedBooking = new Booking([
            'id' => 1,
            'status' => BookingStatus::COMPLETED,
            'completed_at' => now()
        ]);

        $this->validationService->shouldReceive('validateMasterPermission')
            ->once()
            ->with($booking, $this->master);

        $this->validationService->shouldReceive('validateCompletionAbility')
            ->once()
            ->with($booking);

        $this->completeAction->shouldReceive('execute')
            ->once()
            ->with($booking, $this->master)
            ->andReturn($completedBooking);

        $this->notificationService->shouldReceive('sendBookingCompleted')
            ->once()
            ->with($completedBooking);

        // Act
        $result = $this->service->completeBooking($booking, $this->master);

        // Assert
        $this->assertEquals(BookingStatus::COMPLETED, $result->status);
        $this->assertNotNull($result->completed_at);
    }

    /**
     * Тест переноса бронирования
     */
    public function test_reschedule_booking()
    {
        // Arrange
        $booking = new Booking([
            'id' => 1,
            'master_id' => $this->master->id,
            'start_time' => Carbon::tomorrow()->setHour(10),
            'duration_minutes' => 60
        ]);

        $newStartTime = Carbon::tomorrow()->setHour(14);

        $rescheduledBooking = new Booking([
            'id' => 1,
            'start_time' => $newStartTime,
            'rescheduled_at' => now()
        ]);

        $this->availabilityService->shouldReceive('canRescheduleBooking')
            ->once()
            ->with($booking)
            ->andReturn(true);

        $this->availabilityService->shouldReceive('isMasterAvailable')
            ->once()
            ->with($this->master->id, $newStartTime, 60)
            ->andReturn(true);

        $this->rescheduleAction->shouldReceive('execute')
            ->once()
            ->with($booking, $newStartTime, null)
            ->andReturn($rescheduledBooking);

        $this->notificationService->shouldReceive('sendBookingRescheduled')
            ->once();

        // Act
        $result = $this->service->rescheduleBooking($booking, $newStartTime);

        // Assert
        $this->assertEquals($newStartTime->format('Y-m-d H:i:s'), $result->start_time->format('Y-m-d H:i:s'));
        $this->assertNotNull($result->rescheduled_at);
    }

    /**
     * Тест получения доступных слотов
     */
    public function test_get_available_slots()
    {
        // Arrange
        $masterProfileId = 1;
        $serviceId = 1;
        $days = 14;

        $slots = [
            '2024-12-20' => ['10:00', '11:00', '14:00'],
            '2024-12-21' => ['09:00', '15:00', '16:00']
        ];

        $this->availabilityService->shouldReceive('getAvailableSlots')
            ->once()
            ->with($masterProfileId, $serviceId, $days)
            ->andReturn($slots);

        // Act
        $result = $this->service->getAvailableSlots($masterProfileId, $serviceId, $days);

        // Assert
        $this->assertEquals($slots, $result);
        $this->assertArrayHasKey('2024-12-20', $result);
        $this->assertCount(3, $result['2024-12-20']);
    }

    /**
     * Тест поиска бронирования по номеру
     */
    public function test_find_by_number()
    {
        // Arrange
        $bookingNumber = 'BK123456';
        $booking = new Booking([
            'id' => 1,
            'booking_number' => $bookingNumber
        ]);

        $this->repository->shouldReceive('findByNumber')
            ->once()
            ->with($bookingNumber)
            ->andReturn($booking);

        // Act
        $result = $this->service->findByNumber($bookingNumber);

        // Assert
        $this->assertInstanceOf(Booking::class, $result);
        $this->assertEquals($bookingNumber, $result->booking_number);
    }

    /**
     * Тест получения бронирований пользователя (клиент)
     */
    public function test_get_user_bookings_for_client()
    {
        // Arrange
        $filters = ['status' => 'confirmed'];
        $bookings = collect([
            new Booking(['id' => 1, 'client_id' => $this->client->id]),
            new Booking(['id' => 2, 'client_id' => $this->client->id])
        ]);

        $query = Mockery::mock();
        $query->shouldReceive('where')->once()->with('client_id', $this->client->id)->andReturnSelf();
        $query->shouldReceive('where')->once()->with('status', 'confirmed')->andReturnSelf();
        $query->shouldReceive('with')->once()->with(['master', 'service'])->andReturnSelf();
        $query->shouldReceive('orderBy')->once()->with('start_time', 'desc')->andReturnSelf();
        $query->shouldReceive('get')->once()->andReturn($bookings);

        $this->repository->shouldReceive('query')->once()->andReturn($query);

        // Act
        $result = $this->service->getUserBookings($this->client, $filters);

        // Assert
        $this->assertCount(2, $result);
    }

    /**
     * Тест расчета стоимости бронирования
     */
    public function test_calculate_price()
    {
        // Arrange
        $serviceId = 1;
        $options = [
            'duration' => 90,
            'location' => 'outcall'
        ];

        $pricing = [
            'base_price' => 1000,
            'duration_price' => 500,
            'location_fee' => 200,
            'total' => 1700
        ];

        $service = Mockery::mock(\App\Domain\Service\Models\Service::class);
        $this->app->instance(\App\Domain\Service\Models\Service::class, $service);
        $service->shouldReceive('findOrFail')->once()->with($serviceId)->andReturn($service);

        $this->pricingService->shouldReceive('calculatePricing')
            ->once()
            ->with($service, $options)
            ->andReturn($pricing);

        // Act
        $result = $this->service->calculatePrice($serviceId, $options);

        // Assert
        $this->assertEquals($pricing, $result);
        $this->assertEquals(1700, $result['total']);
    }

    /**
     * Тест применения промокода
     */
    public function test_apply_promo_code()
    {
        // Arrange
        $totalPrice = 1000;
        $promoCode = 'DISCOUNT10';

        $result = [
            'discount' => 100,
            'final_price' => 900,
            'promo_applied' => true
        ];

        $this->pricingService->shouldReceive('applyPromoCode')
            ->once()
            ->with($totalPrice, $promoCode)
            ->andReturn($result);

        // Act
        $response = $this->service->applyPromoCode($totalPrice, $promoCode);

        // Assert
        $this->assertEquals($result, $response);
        $this->assertEquals(900, $response['final_price']);
        $this->assertTrue($response['promo_applied']);
    }

    /**
     * Тест отправки напоминания о бронировании
     */
    public function test_send_booking_reminder()
    {
        // Arrange
        $booking = new Booking(['id' => 1]);

        $this->notificationService->shouldReceive('sendBookingReminder')
            ->once()
            ->with($booking);

        // Act
        $this->service->sendBookingReminder($booking);

        // Assert
        $this->assertTrue(true); // No exception thrown
    }

    /**
     * Тест отправки напоминания с ошибкой
     */
    public function test_send_booking_reminder_with_error()
    {
        // Arrange
        $booking = new Booking(['id' => 1]);

        $this->notificationService->shouldReceive('sendBookingReminder')
            ->once()
            ->with($booking)
            ->andThrow(new \Exception('Notification service error'));

        // Assert
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Notification service error');

        // Act
        $this->service->sendBookingReminder($booking);
    }

    /**
     * Тест проверки доступности слота
     */
    public function test_check_slot_availability()
    {
        // Arrange
        $masterProfileId = 1;
        $date = '2024-12-20';
        $time = '14:00';
        $serviceId = 1;

        $this->availabilityService->shouldReceive('validateTimeSlot')
            ->once()
            ->with($masterProfileId, $date, $time, $serviceId)
            ->andReturn(true);

        // Act
        $result = $this->service->checkSlotAvailability($masterProfileId, $date, $time, $serviceId);

        // Assert
        $this->assertTrue($result);
    }

    /**
     * Тест получения статистики бронирований
     */
    public function test_get_booking_stats()
    {
        // Arrange
        $period = 'month';

        $query = Mockery::mock();
        $query->shouldReceive('where')->with('master_id', $this->master->id)->andReturnSelf();
        $query->shouldReceive('where')->with('created_at', '>=', Mockery::any())->andReturnSelf();
        $query->shouldReceive('where')->with('status', BookingStatus::COMPLETED->value)->andReturnSelf();
        $query->shouldReceive('where')->with('status', BookingStatus::CANCELLED->value)->andReturnSelf();
        $query->shouldReceive('count')->andReturn(10, 7, 2);
        $query->shouldReceive('sum')->with('total_price')->andReturn(15000);

        $this->repository->shouldReceive('query')->times(4)->andReturn($query);

        $this->master->shouldReceive('isMaster')->once()->andReturn(true);

        // Act
        $result = $this->service->getBookingStats($this->master, $period);

        // Assert
        $this->assertEquals(10, $result['total']);
        $this->assertEquals(7, $result['completed']);
        $this->assertEquals(2, $result['cancelled']);
        $this->assertEquals(15000, $result['revenue']);
    }

    /**
     * Тест поиска следующего доступного слота
     */
    public function test_find_next_available_slot()
    {
        // Arrange
        $masterId = 1;
        $serviceId = 1;
        $preferredTime = Carbon::tomorrow()->setHour(14);
        $type = BookingType::DEFAULT;

        $slot = [
            'date' => '2024-12-20',
            'time' => '15:00',
            'available' => true
        ];

        $this->availabilityService->shouldReceive('findNextAvailableSlot')
            ->once()
            ->with($masterId, $serviceId, $preferredTime, $type)
            ->andReturn($slot);

        // Act
        $result = $this->service->findNextAvailableSlot($masterId, $serviceId, $preferredTime, $type);

        // Assert
        $this->assertEquals($slot, $result);
        $this->assertTrue($result['available']);
    }
}
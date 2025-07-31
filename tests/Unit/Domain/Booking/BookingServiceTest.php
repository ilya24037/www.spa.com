<?php

namespace Tests\Unit\Domain\Booking;

use Tests\TestCase;
use App\Domain\Booking\Services\BookingService;
use App\Domain\Booking\Repositories\BookingRepository;
use App\Domain\Booking\Actions\CreateBookingAction;
use App\Domain\Booking\Actions\UpdateBookingAction;
use App\Domain\Booking\Actions\CancelBookingAction;
use App\Domain\Booking\Actions\ConfirmBookingAction;
use App\Domain\Booking\DTOs\CreateBookingDTO;
use App\Domain\Booking\DTOs\UpdateBookingDTO;
use App\Models\Booking;
use App\Models\User;
use App\Models\MasterProfile;
use App\Enums\BookingStatus;
use Mockery;
use Carbon\Carbon;

class BookingServiceTest extends TestCase
{
    private BookingService $service;
    private $mockRepository;
    private $mockCreateAction;
    private $mockUpdateAction;
    private $mockCancelAction;
    private $mockConfirmAction;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockRepository = Mockery::mock(BookingRepository::class);
        $this->mockCreateAction = Mockery::mock(CreateBookingAction::class);
        $this->mockUpdateAction = Mockery::mock(UpdateBookingAction::class);
        $this->mockCancelAction = Mockery::mock(CancelBookingAction::class);
        $this->mockConfirmAction = Mockery::mock(ConfirmBookingAction::class);

        $this->service = new BookingService(
            $this->mockRepository,
            $this->mockCreateAction,
            $this->mockUpdateAction,
            $this->mockCancelAction,
            $this->mockConfirmAction
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function it_can_create_booking()
    {
        $dto = new CreateBookingDTO(
            masterId: 1,
            clientId: 2,
            serviceId: 3,
            date: Carbon::tomorrow(),
            startTime: '10:00',
            endTime: '11:00',
            price: 5000,
            clientName: 'Test Client',
            clientPhone: '+79123456789'
        );

        $booking = new Booking([
            'id' => 1,
            'master_id' => 1,
            'client_id' => 2,
            'status' => BookingStatus::PENDING
        ]);

        $this->mockCreateAction
            ->shouldReceive('execute')
            ->once()
            ->with($dto)
            ->andReturn($booking);

        $result = $this->service->create($dto);

        $this->assertInstanceOf(Booking::class, $result);
        $this->assertEquals(1, $result->id);
        $this->assertEquals(BookingStatus::PENDING, $result->status);
    }

    /** @test */
    public function it_can_update_booking()
    {
        $bookingId = 1;
        $dto = new UpdateBookingDTO(
            date: Carbon::tomorrow(),
            startTime: '14:00',
            endTime: '15:00'
        );

        $booking = new Booking(['id' => $bookingId]);

        $this->mockUpdateAction
            ->shouldReceive('execute')
            ->once()
            ->with($bookingId, $dto)
            ->andReturn($booking);

        $result = $this->service->update($bookingId, $dto);

        $this->assertInstanceOf(Booking::class, $result);
    }

    /** @test */
    public function it_can_cancel_booking()
    {
        $bookingId = 1;
        $reason = 'Client request';

        $booking = new Booking([
            'id' => $bookingId,
            'status' => BookingStatus::CANCELLED
        ]);

        $this->mockCancelAction
            ->shouldReceive('execute')
            ->once()
            ->with($bookingId, $reason)
            ->andReturn($booking);

        $result = $this->service->cancel($bookingId, $reason);

        $this->assertEquals(BookingStatus::CANCELLED, $result->status);
    }

    /** @test */
    public function it_can_confirm_booking()
    {
        $bookingId = 1;

        $booking = new Booking([
            'id' => $bookingId,
            'status' => BookingStatus::CONFIRMED
        ]);

        $this->mockConfirmAction
            ->shouldReceive('execute')
            ->once()
            ->with($bookingId)
            ->andReturn($booking);

        $result = $this->service->confirm($bookingId);

        $this->assertEquals(BookingStatus::CONFIRMED, $result->status);
    }

    /** @test */
    public function it_can_find_booking_by_id()
    {
        $bookingId = 1;
        $booking = new Booking(['id' => $bookingId]);

        $this->mockRepository
            ->shouldReceive('findById')
            ->once()
            ->with($bookingId)
            ->andReturn($booking);

        $result = $this->service->findById($bookingId);

        $this->assertInstanceOf(Booking::class, $result);
        $this->assertEquals($bookingId, $result->id);
    }

    /** @test */
    public function it_can_get_master_bookings()
    {
        $masterId = 1;
        $bookings = collect([
            new Booking(['master_id' => $masterId]),
            new Booking(['master_id' => $masterId])
        ]);

        $this->mockRepository
            ->shouldReceive('getMasterBookings')
            ->once()
            ->with($masterId, [])
            ->andReturn($bookings);

        $result = $this->service->getMasterBookings($masterId);

        $this->assertCount(2, $result);
    }

    /** @test */
    public function it_can_get_client_bookings()
    {
        $clientId = 1;
        $bookings = collect([
            new Booking(['client_id' => $clientId])
        ]);

        $this->mockRepository
            ->shouldReceive('getClientBookings')
            ->once()
            ->with($clientId, [])
            ->andReturn($bookings);

        $result = $this->service->getClientBookings($clientId);

        $this->assertCount(1, $result);
    }

    /** @test */
    public function it_can_check_availability()
    {
        $masterId = 1;
        $date = Carbon::tomorrow();
        $startTime = '10:00';
        $endTime = '11:00';

        $this->mockRepository
            ->shouldReceive('isTimeSlotAvailable')
            ->once()
            ->with($masterId, $date, $startTime, $endTime, null)
            ->andReturn(true);

        $result = $this->service->isAvailable($masterId, $date, $startTime, $endTime);

        $this->assertTrue($result);
    }

    /** @test */
    public function it_can_get_available_slots()
    {
        $masterId = 1;
        $date = Carbon::tomorrow();
        $slots = ['10:00', '11:00', '14:00'];

        $this->mockRepository
            ->shouldReceive('getAvailableSlots')
            ->once()
            ->with($masterId, $date)
            ->andReturn($slots);

        $result = $this->service->getAvailableSlots($masterId, $date);

        $this->assertCount(3, $result);
        $this->assertContains('10:00', $result);
    }

    /** @test */
    public function it_can_get_bookings_for_date_range()
    {
        $masterId = 1;
        $startDate = Carbon::now();
        $endDate = Carbon::now()->addWeek();

        $bookings = collect([
            new Booking(['master_id' => $masterId])
        ]);

        $this->mockRepository
            ->shouldReceive('getBookingsForDateRange')
            ->once()
            ->with($masterId, $startDate, $endDate)
            ->andReturn($bookings);

        $result = $this->service->getBookingsForDateRange($masterId, $startDate, $endDate);

        $this->assertCount(1, $result);
    }
}
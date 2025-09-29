<?php

namespace Tests\Unit\Domain\Booking\Actions;

use Tests\TestCase;
use App\Domain\Booking\Actions\CreateBookingAction;
use App\Domain\Booking\Repositories\BookingRepository;
use App\Domain\Master\Repositories\MasterRepository;
use App\Domain\Booking\DTOs\BookingData;
use App\Domain\Booking\Models\Booking;
use App\Domain\Booking\Models\BookingHistory;
use App\Domain\Master\Models\MasterProfile;
use App\Domain\Booking\Enums\BookingStatus;
use Tests\Traits\SafeRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Mockery;

class CreateBookingActionTest extends TestCase
{
    use SafeRefreshDatabase;

    private CreateBookingAction $action;
    private BookingRepository $bookingRepository;
    private MasterRepository $masterRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->bookingRepository = Mockery::mock(BookingRepository::class);
        $this->masterRepository = Mockery::mock(MasterRepository::class);
        
        $this->action = new CreateBookingAction(
            $this->bookingRepository,
            $this->masterRepository
        );

        Log::shouldReceive('info', 'error')->andReturnNull();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Тест успешного создания бронирования
     */
    public function test_execute_creates_booking_successfully()
    {
        // Arrange
        $bookingData = new BookingData([
            'masterId' => 1,
            'clientId' => 2,
            'serviceIds' => [1, 2],
            'date' => '2024-12-25',
            'timeStart' => '14:00',
            'timeEnd' => '15:00',
            'price' => 1500,
            'duration' => 60,
            'notes' => 'Test booking'
        ]);

        $master = Mockery::mock(MasterProfile::class);
        $master->shouldReceive('isActive')->once()->andReturn(true);

        $booking = new Booking([
            'id' => 1,
            'master_id' => 1,
            'client_id' => 2,
            'booking_number' => 'BK123456',
            'status' => BookingStatus::PENDING
        ]);

        $this->masterRepository->shouldReceive('findById')
            ->once()
            ->with(1)
            ->andReturn($master);

        $this->bookingRepository->shouldReceive('hasTimeConflict')
            ->once()
            ->with(1, '2024-12-25', '14:00', '15:00')
            ->andReturn(false);

        $this->bookingRepository->shouldReceive('create')
            ->once()
            ->with($bookingData->toArray())
            ->andReturn($booking);

        $this->bookingRepository->shouldReceive('attachServices')
            ->once()
            ->with(1, [1, 2]);

        // Mock static method for BookingHistory
        BookingHistory::shouldReceive('logCreated')
            ->once()
            ->with($booking, 2);

        // Act
        $result = $this->action->execute($bookingData);

        // Assert
        $this->assertTrue($result['success']);
        $this->assertEquals('Бронирование успешно создано', $result['message']);
        $this->assertArrayHasKey('booking', $result);
        $this->assertInstanceOf(Booking::class, $result['booking']);
    }

    /**
     * Тест создания бронирования без услуг
     */
    public function test_execute_creates_booking_without_services()
    {
        // Arrange
        $bookingData = new BookingData([
            'masterId' => 1,
            'clientId' => 2,
            'serviceIds' => [], // Без услуг
            'date' => '2024-12-25',
            'timeStart' => '14:00',
            'timeEnd' => '15:00',
            'price' => 1500,
            'duration' => 60
        ]);

        $master = Mockery::mock(MasterProfile::class);
        $master->shouldReceive('isActive')->once()->andReturn(true);

        $booking = new Booking(['id' => 1]);

        $this->masterRepository->shouldReceive('findById')
            ->once()
            ->with(1)
            ->andReturn($master);

        $this->bookingRepository->shouldReceive('hasTimeConflict')
            ->once()
            ->andReturn(false);

        $this->bookingRepository->shouldReceive('create')
            ->once()
            ->andReturn($booking);

        // attachServices не должен вызываться при пустом массиве услуг
        $this->bookingRepository->shouldNotReceive('attachServices');

        BookingHistory::shouldReceive('logCreated')->once();

        // Act
        $result = $this->action->execute($bookingData);

        // Assert
        $this->assertTrue($result['success']);
    }

    /**
     * Тест неудачного создания когда мастер не найден
     */
    public function test_execute_fails_when_master_not_found()
    {
        // Arrange
        $bookingData = new BookingData([
            'masterId' => 999,
            'clientId' => 2,
            'date' => '2024-12-25',
            'timeStart' => '14:00',
            'timeEnd' => '15:00'
        ]);

        $this->masterRepository->shouldReceive('findById')
            ->once()
            ->with(999)
            ->andReturn(null);

        // Act
        $result = $this->action->execute($bookingData);

        // Assert
        $this->assertFalse($result['success']);
        $this->assertEquals('Мастер не найден или недоступен', $result['message']);
    }

    /**
     * Тест неудачного создания когда мастер неактивен
     */
    public function test_execute_fails_when_master_is_inactive()
    {
        // Arrange
        $bookingData = new BookingData([
            'masterId' => 1,
            'clientId' => 2,
            'date' => '2024-12-25',
            'timeStart' => '14:00',
            'timeEnd' => '15:00'
        ]);

        $master = Mockery::mock(MasterProfile::class);
        $master->shouldReceive('isActive')->once()->andReturn(false);

        $this->masterRepository->shouldReceive('findById')
            ->once()
            ->with(1)
            ->andReturn($master);

        // Act
        $result = $this->action->execute($bookingData);

        // Assert
        $this->assertFalse($result['success']);
        $this->assertEquals('Мастер не найден или недоступен', $result['message']);
    }

    /**
     * Тест неудачного создания при конфликте времени
     */
    public function test_execute_fails_when_time_slot_has_conflict()
    {
        // Arrange
        $bookingData = new BookingData([
            'masterId' => 1,
            'clientId' => 2,
            'date' => '2024-12-25',
            'timeStart' => '14:00',
            'timeEnd' => '15:00'
        ]);

        $master = Mockery::mock(MasterProfile::class);
        $master->shouldReceive('isActive')->once()->andReturn(true);

        $this->masterRepository->shouldReceive('findById')
            ->once()
            ->with(1)
            ->andReturn($master);

        $this->bookingRepository->shouldReceive('hasTimeConflict')
            ->once()
            ->with(1, '2024-12-25', '14:00', '15:00')
            ->andReturn(true); // Есть конфликт

        // Act
        $result = $this->action->execute($bookingData);

        // Assert
        $this->assertFalse($result['success']);
        $this->assertEquals('Выбранное время недоступно', $result['message']);
    }

    /**
     * Тест обработки исключения при создании
     */
    public function test_execute_handles_exception()
    {
        // Arrange
        $bookingData = new BookingData([
            'masterId' => 1,
            'clientId' => 2,
            'date' => '2024-12-25',
            'timeStart' => '14:00',
            'timeEnd' => '15:00'
        ]);

        $master = Mockery::mock(MasterProfile::class);
        $master->shouldReceive('isActive')->once()->andReturn(true);

        $this->masterRepository->shouldReceive('findById')
            ->once()
            ->andReturn($master);

        $this->bookingRepository->shouldReceive('hasTimeConflict')
            ->once()
            ->andReturn(false);

        $this->bookingRepository->shouldReceive('create')
            ->once()
            ->andThrow(new \Exception('Database error'));

        // Act
        $result = $this->action->execute($bookingData);

        // Assert
        $this->assertFalse($result['success']);
        $this->assertEquals('Ошибка при создании бронирования', $result['message']);
    }

    /**
     * Тест транзакционности операции
     */
    public function test_execute_uses_transaction()
    {
        // Arrange
        $bookingData = new BookingData([
            'masterId' => 1,
            'clientId' => 2,
            'serviceIds' => [1],
            'date' => '2024-12-25',
            'timeStart' => '14:00',
            'timeEnd' => '15:00'
        ]);

        $master = Mockery::mock(MasterProfile::class);
        $master->shouldReceive('isActive')->once()->andReturn(true);

        $booking = new Booking(['id' => 1]);

        $this->masterRepository->shouldReceive('findById')
            ->once()
            ->andReturn($master);

        $this->bookingRepository->shouldReceive('hasTimeConflict')
            ->once()
            ->andReturn(false);

        $this->bookingRepository->shouldReceive('create')
            ->once()
            ->andReturn($booking);

        // Симулируем ошибку при прикреплении услуг
        $this->bookingRepository->shouldReceive('attachServices')
            ->once()
            ->andThrow(new \Exception('Failed to attach services'));

        // Act
        $result = $this->action->execute($bookingData);

        // Assert
        $this->assertFalse($result['success']);
        // Если бы транзакция не работала, бронирование могло бы остаться в БД
    }

    /**
     * Тест корректности передаваемых данных в репозиторий
     */
    public function test_execute_passes_correct_data_to_repository()
    {
        // Arrange
        $bookingData = new BookingData([
            'masterId' => 1,
            'clientId' => 2,
            'serviceIds' => [1, 2, 3],
            'date' => '2024-12-25',
            'timeStart' => '14:00',
            'timeEnd' => '15:30',
            'price' => 2500,
            'duration' => 90,
            'notes' => 'Special request',
            'contactPhone' => '+7 999 123-45-67',
            'contactName' => 'John Doe'
        ]);

        $master = Mockery::mock(MasterProfile::class);
        $master->shouldReceive('isActive')->once()->andReturn(true);

        $this->masterRepository->shouldReceive('findById')
            ->once()
            ->andReturn($master);

        $this->bookingRepository->shouldReceive('hasTimeConflict')
            ->once()
            ->andReturn(false);

        $this->bookingRepository->shouldReceive('create')
            ->once()
            ->with(Mockery::on(function ($data) {
                return $data['masterId'] === 1
                    && $data['clientId'] === 2
                    && $data['date'] === '2024-12-25'
                    && $data['timeStart'] === '14:00'
                    && $data['timeEnd'] === '15:30'
                    && $data['price'] === 2500
                    && $data['duration'] === 90
                    && $data['notes'] === 'Special request';
            }))
            ->andReturn(new Booking(['id' => 1]));

        $this->bookingRepository->shouldReceive('attachServices')
            ->once()
            ->with(1, [1, 2, 3]);

        BookingHistory::shouldReceive('logCreated')->once();

        // Act
        $result = $this->action->execute($bookingData);

        // Assert
        $this->assertTrue($result['success']);
    }

    /**
     * Тест логирования истории бронирования
     */
    public function test_execute_logs_booking_history()
    {
        // Arrange
        $bookingData = new BookingData([
            'masterId' => 1,
            'clientId' => 2,
            'date' => '2024-12-25',
            'timeStart' => '14:00',
            'timeEnd' => '15:00'
        ]);

        $master = Mockery::mock(MasterProfile::class);
        $master->shouldReceive('isActive')->once()->andReturn(true);

        $booking = new Booking([
            'id' => 1,
            'master_id' => 1,
            'client_id' => 2
        ]);

        $this->masterRepository->shouldReceive('findById')
            ->once()
            ->andReturn($master);

        $this->bookingRepository->shouldReceive('hasTimeConflict')
            ->once()
            ->andReturn(false);

        $this->bookingRepository->shouldReceive('create')
            ->once()
            ->andReturn($booking);

        // Проверяем что BookingHistory::logCreated вызывается с правильными параметрами
        BookingHistory::shouldReceive('logCreated')
            ->once()
            ->with($booking, 2);

        // Act
        $result = $this->action->execute($bookingData);

        // Assert
        $this->assertTrue($result['success']);
    }
}
<?php

namespace Tests\Unit\Domain\Payment\Services;

use Tests\TestCase;
use App\Domain\Payment\Services\PaymentService;
use App\Domain\Payment\Services\PaymentGatewayFactory;
use App\Domain\Payment\Repositories\PaymentRepository;
use App\Domain\Payment\Models\Payment;
use App\Domain\Payment\DTOs\CheckoutDTO;
use App\Domain\Payment\Enums\PaymentStatus;
use App\Domain\Payment\Enums\PaymentMethod;
use App\Domain\Payment\Enums\PaymentType;
use App\Domain\Payment\Contracts\PaymentProcessorInterface;
use App\Domain\User\Models\User;
use App\Domain\Ad\Models\Ad;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Mockery;

class PaymentServiceTest extends TestCase
{
    use RefreshDatabase;

    private PaymentService $service;
    private PaymentRepository $repository;
    private PaymentGatewayFactory $gatewayFactory;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = Mockery::mock(PaymentRepository::class);
        $this->gatewayFactory = Mockery::mock(PaymentGatewayFactory::class);
        
        $this->service = new PaymentService(
            $this->repository,
            $this->gatewayFactory
        );

        $this->user = User::factory()->create();
        
        Event::fake();
        Log::shouldReceive('info', 'warning', 'error')->andReturnNull();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Тест создания платежа
     */
    public function test_create_payment_successfully()
    {
        // Arrange
        $data = [
            'user_id' => $this->user->id,
            'amount' => 1000,
            'method' => PaymentMethod::CARD->value,
            'type' => PaymentType::SERVICE_PAYMENT->value,
            'description' => 'Test payment'
        ];

        $payment = new Payment(array_merge($data, [
            'id' => 1,
            'payment_number' => 'PAY123456',
            'status' => PaymentStatus::PENDING,
            'fee' => 30,
            'total_amount' => 1030,
            'currency' => 'RUB'
        ]));

        $this->repository->shouldReceive('create')
            ->once()
            ->andReturn($payment);

        // Act
        $result = $this->service->createPayment($data);

        // Assert
        $this->assertInstanceOf(Payment::class, $result);
        $this->assertEquals(1000, $result->amount);
        $this->assertEquals(PaymentStatus::PENDING, $result->status);
        Event::assertDispatched('payment.created');
    }

    /**
     * Тест обработки платежа - успешно
     */
    public function test_process_payment_successfully()
    {
        // Arrange
        $payment = Mockery::mock(Payment::class)->makePartial();
        $payment->id = 1;
        $payment->method = PaymentMethod::CARD;
        $payment->status = Mockery::mock(PaymentStatus::class);
        
        $payment->status->shouldReceive('canTransitionTo')
            ->with(PaymentStatus::PROCESSING)
            ->andReturn(true);
        
        $payment->shouldReceive('startProcessing')->once();

        $processor = Mockery::mock(PaymentProcessorInterface::class);
        $processor->shouldReceive('processPayment')
            ->once()
            ->with($payment)
            ->andReturn([
                'success' => true,
                'transaction_id' => 'TXN123'
            ]);

        $this->gatewayFactory->shouldReceive('getProcessor')
            ->once()
            ->with($payment->method)
            ->andReturn($processor);

        $this->repository->shouldReceive('update')
            ->once()
            ->andReturn(true);

        // Act
        $result = $this->service->processPayment($payment);

        // Assert
        $this->assertTrue($result);
        Event::assertDispatched('payment.confirmed');
    }

    /**
     * Тест обработки платежа - неудача
     */
    public function test_process_payment_failure()
    {
        // Arrange
        $payment = Mockery::mock(Payment::class)->makePartial();
        $payment->id = 1;
        $payment->method = PaymentMethod::CARD;
        $payment->status = Mockery::mock(PaymentStatus::class);
        
        $payment->status->shouldReceive('canTransitionTo')
            ->with(PaymentStatus::PROCESSING)
            ->andReturn(true);
        
        $payment->shouldReceive('startProcessing')->once();

        $processor = Mockery::mock(PaymentProcessorInterface::class);
        $processor->shouldReceive('processPayment')
            ->once()
            ->with($payment)
            ->andReturn([
                'success' => false,
                'error' => 'Card declined'
            ]);

        $this->gatewayFactory->shouldReceive('getProcessor')
            ->once()
            ->with($payment->method)
            ->andReturn($processor);

        $this->repository->shouldReceive('update')
            ->once()
            ->andReturn(true);

        // Act
        $result = $this->service->processPayment($payment);

        // Assert
        $this->assertFalse($result);
        Event::assertDispatched('payment.failed');
    }

    /**
     * Тест подтверждения платежа
     */
    public function test_confirm_payment()
    {
        // Arrange
        $payment = new Payment([
            'id' => 1,
            'type' => PaymentType::SERVICE_PAYMENT,
            'payable_type' => 'App\Domain\Booking\Models\Booking',
            'payable_id' => 1
        ]);

        $gatewayData = [
            'transaction_id' => 'TXN123',
            'status' => 'completed'
        ];

        $this->repository->shouldReceive('update')
            ->once()
            ->with($payment, Mockery::on(function ($data) {
                return $data['status'] === PaymentStatus::COMPLETED
                    && isset($data['confirmed_at'])
                    && isset($data['gateway_response'])
                    && $data['external_id'] === 'TXN123';
            }))
            ->andReturn(true);

        // Mock payable relation
        $booking = Mockery::mock();
        $booking->shouldReceive('update')
            ->once()
            ->with(['payment_status' => 'paid']);
        
        $payment->setRelation('payable', $booking);

        // Act
        $result = $this->service->confirmPayment($payment, $gatewayData);

        // Assert
        $this->assertTrue($result);
        Event::assertDispatched('payment.confirmed');
    }

    /**
     * Тест отклонения платежа
     */
    public function test_fail_payment()
    {
        // Arrange
        $payment = new Payment(['id' => 1]);
        $reason = 'Insufficient funds';

        $this->repository->shouldReceive('update')
            ->once()
            ->with($payment, Mockery::on(function ($data) use ($reason) {
                return $data['status'] === PaymentStatus::FAILED
                    && isset($data['failed_at'])
                    && $data['notes'] === $reason;
            }))
            ->andReturn(true);

        // Act
        $result = $this->service->failPayment($payment, $reason);

        // Assert
        $this->assertTrue($result);
        Event::assertDispatched('payment.failed');
    }

    /**
     * Тест отмены платежа
     */
    public function test_cancel_payment_when_cancellable()
    {
        // Arrange
        $payment = Mockery::mock(Payment::class)->makePartial();
        $payment->id = 1;
        $payment->shouldReceive('isCancellable')->once()->andReturn(true);

        $reason = 'User cancelled';

        $this->repository->shouldReceive('update')
            ->once()
            ->with($payment, Mockery::on(function ($data) use ($reason) {
                return $data['status'] === PaymentStatus::CANCELLED
                    && $data['notes'] === $reason;
            }))
            ->andReturn(true);

        // Act
        $result = $this->service->cancelPayment($payment, $reason);

        // Assert
        $this->assertTrue($result);
        Event::assertDispatched('payment.cancelled');
    }

    /**
     * Тест отмены платежа когда нельзя отменить
     */
    public function test_cancel_payment_when_not_cancellable()
    {
        // Arrange
        $payment = Mockery::mock(Payment::class)->makePartial();
        $payment->shouldReceive('isCancellable')->once()->andReturn(false);

        // Act
        $result = $this->service->cancelPayment($payment);

        // Assert
        $this->assertFalse($result);
        Event::assertNotDispatched('payment.cancelled');
    }

    /**
     * Тест создания возврата
     */
    public function test_create_refund_successfully()
    {
        // Arrange
        $originalPayment = Mockery::mock(Payment::class)->makePartial();
        $originalPayment->id = 1;
        $originalPayment->user_id = $this->user->id;
        $originalPayment->payment_number = 'PAY123';
        $originalPayment->method = PaymentMethod::CARD;
        $originalPayment->currency = 'RUB';
        $originalPayment->gateway = 'yookassa';
        $originalPayment->amount = 1000;
        
        $originalPayment->shouldReceive('isRefundable')->once()->andReturn(true);
        $originalPayment->shouldReceive('getRemainingRefundAmount')->once()->andReturn(1000);
        $originalPayment->shouldReceive('refresh')->once();
        $originalPayment->shouldReceive('isFullyRefunded')->once()->andReturn(false);
        $originalPayment->shouldReceive('isPartiallyRefunded')->once()->andReturn(true);

        $refundAmount = 500;
        $reason = 'Customer request';

        $refund = new Payment([
            'id' => 2,
            'payment_number' => 'REF123',
            'parent_payment_id' => 1,
            'amount' => $refundAmount,
            'type' => PaymentType::REFUND,
            'status' => PaymentStatus::PENDING
        ]);

        // Mock parent payment relation
        $refund->setRelation('parentPayment', $originalPayment);

        $this->repository->shouldReceive('create')
            ->once()
            ->andReturn($refund);

        $this->repository->shouldReceive('update')
            ->twice() // Once for refund status, once for original payment status
            ->andReturn(true);

        // Mock processor
        $processor = Mockery::mock(PaymentProcessorInterface::class);
        $processor->shouldReceive('processRefund')
            ->once()
            ->andReturn([
                'success' => true,
                'refund_id' => 'REF_EXT_123'
            ]);

        $this->gatewayFactory->shouldReceive('getProcessor')
            ->once()
            ->andReturn($processor);

        // Act
        $result = $this->service->createRefund($originalPayment, $refundAmount, $reason);

        // Assert
        $this->assertInstanceOf(Payment::class, $result);
        $this->assertEquals($refundAmount, $result->amount);
        $this->assertEquals(PaymentType::REFUND, $result->type);
        Event::assertDispatched('payment.refund_created');
    }

    /**
     * Тест создания возврата когда платеж не подлежит возврату
     */
    public function test_create_refund_when_not_refundable()
    {
        // Arrange
        $payment = Mockery::mock(Payment::class)->makePartial();
        $payment->shouldReceive('isRefundable')->once()->andReturn(false);

        // Act
        $result = $this->service->createRefund($payment, 100);

        // Assert
        $this->assertNull($result);
    }

    /**
     * Тест заморозки платежа
     */
    public function test_hold_payment()
    {
        // Arrange
        $payment = new Payment(['id' => 1]);
        $reason = 'Fraud check';

        $this->repository->shouldReceive('update')
            ->once()
            ->with($payment, [
                'status' => PaymentStatus::HELD,
                'notes' => $reason
            ])
            ->andReturn(true);

        // Act
        $result = $this->service->holdPayment($payment, $reason);

        // Assert
        $this->assertTrue($result);
    }

    /**
     * Тест разморозки платежа
     */
    public function test_unhold_payment()
    {
        // Arrange
        $payment = new Payment([
            'id' => 1,
            'status' => PaymentStatus::HELD
        ]);

        $this->repository->shouldReceive('update')
            ->once()
            ->with($payment, [
                'status' => PaymentStatus::PENDING,
                'notes' => null
            ])
            ->andReturn(true);

        // Act
        $result = $this->service->unholdPayment($payment);

        // Assert
        $this->assertTrue($result);
    }

    /**
     * Тест расчета комиссии
     */
    public function test_calculate_fee()
    {
        // Arrange
        $amount = 1000;
        $method = PaymentMethod::CARD;

        // Act
        $fee = $this->service->calculateFee($amount, $method);

        // Assert
        $this->assertIsFloat($fee);
        $this->assertGreaterThanOrEqual(0, $fee);
    }

    /**
     * Тест расчета общей суммы с комиссией
     */
    public function test_calculate_total_with_fee()
    {
        // Arrange
        $amount = 1000;
        $method = PaymentMethod::CARD;

        // Act
        $total = $this->service->calculateTotalWithFee($amount, $method);

        // Assert
        $this->assertIsFloat($total);
        $this->assertGreaterThanOrEqual($amount, $total);
    }

    /**
     * Тест создания платежа для оплаты объявления
     */
    public function test_create_checkout_payment()
    {
        // Arrange
        $dto = new CheckoutDTO(
            userId: $this->user->id,
            adId: 1,
            planId: 1,
            paymentId: 'PAY123',
            amount: 500,
            currency: 'RUB',
            description: 'Ad payment',
            metadata: ['test' => 'data']
        );

        // Act
        $result = $this->service->createCheckoutPayment($dto);

        // Assert
        $this->assertInstanceOf(Payment::class, $result);
        $this->assertEquals($dto->userId, $result->user_id);
        $this->assertEquals($dto->amount, $result->amount);
        $this->assertEquals('pending', $result->status);
    }

    /**
     * Тест активации объявления после оплаты
     */
    public function test_activate_ad_after_payment()
    {
        // Arrange
        $ad = Mockery::mock(Ad::class);
        $ad->id = 1;
        $ad->shouldReceive('update')
            ->once()
            ->with(Mockery::on(function ($data) {
                return $data['status'] === Ad::STATUS_ACTIVE
                    && $data['is_paid'] === true
                    && isset($data['paid_at'])
                    && isset($data['expires_at']);
            }));

        $adPlan = Mockery::mock();
        $adPlan->id = 1;
        $adPlan->days = 30;

        $payment = Mockery::mock(Payment::class)->makePartial();
        $payment->id = 1;
        $payment->shouldReceive('update')
            ->once()
            ->with(Mockery::on(function ($data) {
                return $data['status'] === 'completed'
                    && isset($data['paid_at']);
            }));
        
        $payment->shouldReceive('getAttribute')
            ->with('ad')
            ->andReturn($ad);
        
        $payment->shouldReceive('getAttribute')
            ->with('adPlan')
            ->andReturn($adPlan);

        // Act
        $result = $this->service->activateAdAfterPayment($payment);

        // Assert
        $this->assertTrue($result);
    }

    /**
     * Тест создания платежа для пополнения баланса
     */
    public function test_create_top_up_payment()
    {
        // Arrange
        $amount = 5000;
        $paymentMethod = 'card';

        $balance = Mockery::mock();
        $balance->shouldReceive('getDiscountForAmount')
            ->once()
            ->with($amount)
            ->andReturn([
                'amount' => 500,
                'percent' => 10,
                'final_amount' => 4500
            ]);

        $user = Mockery::mock(User::class);
        $user->shouldReceive('getBalance')
            ->once()
            ->andReturn($balance);

        $userRepository = Mockery::mock();
        $userRepository->shouldReceive('findOrFail')
            ->once()
            ->with($this->user->id)
            ->andReturn($user);

        $this->app->instance(\App\Domain\User\Repositories\UserRepository::class, $userRepository);

        // Act
        $result = $this->service->createTopUpPayment($this->user->id, $amount, $paymentMethod);

        // Assert
        $this->assertInstanceOf(Payment::class, $result);
        $this->assertEquals($amount, $result->amount);
        $this->assertEquals(500, $result->discount_amount);
        $this->assertEquals(10, $result->discount_percent);
        $this->assertEquals(4500, $result->final_amount);
        $this->assertEquals('balance_top_up', $result->purchase_type);
    }

    /**
     * Тест проверки возможности оплаты объявления
     */
    public function test_can_pay_for_ad()
    {
        // Arrange
        $ad = new Ad(['status' => 'waiting_payment']);

        // Act
        $result = $this->service->canPayForAd($ad);

        // Assert
        $this->assertTrue($result);
    }

    /**
     * Тест проверки невозможности оплаты объявления
     */
    public function test_cannot_pay_for_ad_with_wrong_status()
    {
        // Arrange
        $ad = new Ad(['status' => 'active']);

        // Act
        $result = $this->service->canPayForAd($ad);

        // Assert
        $this->assertFalse($result);
    }

    /**
     * Тест получения доступных планов
     */
    public function test_get_available_plans()
    {
        // Arrange
        $plans = collect([
            (object)[
                'id' => 1,
                'name' => 'Basic',
                'days' => 7,
                'price' => 100,
                'formatted_price' => '100 ₽',
                'description' => 'Basic plan',
                'is_popular' => false
            ],
            (object)[
                'id' => 2,
                'name' => 'Premium',
                'days' => 30,
                'price' => 300,
                'formatted_price' => '300 ₽',
                'description' => 'Premium plan',
                'is_popular' => true
            ]
        ]);

        $planRepository = Mockery::mock();
        $planRepository->shouldReceive('getOrderedPlans')
            ->once()
            ->andReturn($plans);

        $this->app->instance(\App\Domain\Ad\Repositories\AdPlanRepository::class, $planRepository);

        // Act
        $result = $this->service->getAvailablePlans();

        // Assert
        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertEquals('Basic', $result[0]['name']);
        $this->assertEquals(300, $result[1]['price']);
        $this->assertTrue($result[1]['is_popular']);
    }

    /**
     * Тест очистки истекших платежей
     */
    public function test_cleanup_expired_payments()
    {
        // Arrange
        $deletedCount = 10;
        
        $this->repository->shouldReceive('cleanupExpired')
            ->once()
            ->andReturn($deletedCount);

        // Act
        $result = $this->service->cleanupExpiredPayments();

        // Assert
        $this->assertEquals($deletedCount, $result);
    }

    /**
     * Тест получения статистики
     */
    public function test_get_statistics()
    {
        // Arrange
        $filters = ['date_from' => '2024-01-01'];
        $stats = [
            'total_amount' => 100000,
            'total_count' => 150,
            'average_amount' => 666.67
        ];

        $this->repository->shouldReceive('getStatistics')
            ->once()
            ->with($filters)
            ->andReturn($stats);

        // Act
        $result = $this->service->getStatistics($filters);

        // Assert
        $this->assertEquals($stats, $result);
    }
}
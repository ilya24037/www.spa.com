<?php

namespace App\Console\Commands;

use App\Models\Ad;
use App\Models\AdPlan;
use App\Models\Payment;
use App\Models\User;
use App\Services\PaymentGatewayService;
use Illuminate\Console\Command;

class TestPaymentSystem extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'payment:test {--gateway=yookassa}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Тестирование системы платежей';

    protected PaymentGatewayService $paymentGateway;

    public function __construct(PaymentGatewayService $paymentGateway)
    {
        parent::__construct();
        $this->paymentGateway = $paymentGateway;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🧪 Тестирование системы платежей...');
        
        $gateway = $this->option('gateway');
        $this->info("💳 Тестируем шлюз: {$gateway}");

        // Создаем тестового пользователя
        $user = $this->createTestUser();
        
        // Создаем тестовое объявление
        $ad = $this->createTestAd($user);
        
        // Создаем тестовый платеж
        $payment = $this->createTestPayment($user, $ad, $gateway);
        
        // Тестируем создание платежа
        $this->testPaymentCreation($payment);
        
        // Тестируем webhook
        $this->testWebhook($payment);
        
        $this->info('🎉 ТЕСТИРОВАНИЕ ЗАВЕРШЕНО!');
    }

    private function createTestUser(): User
    {
        $user = User::firstOrCreate(
            ['email' => 'payment-test@example.com'],
            [
                'name' => 'Payment Test User',
                'password' => bcrypt('password'),
                'role' => 'client'
            ]
        );

        $this->info("👤 Пользователь: {$user->name} (ID: {$user->id})");
        return $user;
    }

    private function createTestAd(User $user): Ad
    {
        $ad = Ad::create([
            'user_id' => $user->id,
            'title' => 'Тестовое объявление для оплаты',
            'description' => 'Тестовое описание',
            'price' => 2500,
            'category_id' => 1,
            'district_id' => 1,
            'is_active' => false,
            'status' => 'waiting_payment'
        ]);

        $this->info("📋 Объявление: {$ad->title} (ID: {$ad->id})");
        return $ad;
    }

    private function createTestPayment(User $user, Ad $ad, string $gateway): Payment
    {
        $plan = AdPlan::first();
        
        if (!$plan) {
            $this->error('❌ Не найдены тарифные планы');
            exit(1);
        }

        $payment = Payment::create([
            'user_id' => $user->id,
            'ad_id' => $ad->id,
            'ad_plan_id' => $plan->id,
            'payment_id' => Payment::generatePaymentId(),
            'amount' => $plan->price,
            'currency' => 'RUB',
            'status' => 'pending',
            'payment_method' => 'card', // используем валидное enum значение
            'description' => 'Размещение объявления',
            'metadata' => [
                'test' => true,
                'created_by' => 'test_command',
                'final_amount' => $plan->price,
                'purchase_type' => 'ad_placement',
                'gateway' => $gateway
            ]
        ]);

        $this->info("💰 Платеж: #{$payment->payment_id} на сумму {$payment->final_amount} ₽");
        return $payment;
    }

    private function testPaymentCreation(Payment $payment): void
    {
        $this->info('🔄 Тестируем создание платежа...');

        try {
            $result = $this->paymentGateway->createPayment($payment);
            
            if ($result['success']) {
                $this->info('✅ Платеж создан успешно');
                
                if (isset($result['redirect_url'])) {
                    $this->info("🔗 URL для оплаты: {$result['redirect_url']}");
                }
                
                if (isset($result['qr_code'])) {
                    $this->info("📱 QR-код: {$result['qr_code']}");
                }
            } else {
                $this->error("❌ Ошибка создания платежа: " . ($result['error'] ?? 'Unknown error'));
            }
        } catch (\Exception $e) {
            $this->error("❌ Исключение при создании платежа: " . $e->getMessage());
        }
    }

    private function testWebhook(Payment $payment): void
    {
        $this->info('🔄 Тестируем webhook...');

        // Подготавливаем тестовые данные webhook
        $webhookData = [
            'object' => [
                'id' => 'test_' . time(),
                'status' => 'succeeded',
                'metadata' => [
                    'payment_id' => $payment->payment_id,
                    'user_id' => $payment->user_id
                ],
                'amount' => [
                    'value' => number_format($payment->final_amount, 2, '.', ''),
                    'currency' => 'RUB'
                ],
                'created_at' => now()->toISOString(),
                'paid' => true
            ],
            'event' => 'payment.succeeded'
        ];

        try {
            $success = $this->paymentGateway->handleWebhook('yookassa', $webhookData);
            
            if ($success) {
                $this->info('✅ Webhook обработан успешно');
                
                // Проверяем, что платеж обновился
                $payment->refresh();
                
                if ($payment->isPaid()) {
                    $this->info("✅ Статус платежа: {$payment->status}");
                    $this->info("📅 Дата оплаты: {$payment->paid_at}");
                    
                    // Проверяем, что объявление активировалось
                    $payment->ad->refresh();
                    if ($payment->ad->status === 'active') {
                        $this->info('✅ Объявление активировано');
                    } else {
                        $this->warn("⚠️ Объявление не активировалось: {$payment->ad->status}");
                    }
                } else {
                    $this->warn('⚠️ Платеж не отмечен как оплаченный');
                }
            } else {
                $this->error('❌ Ошибка обработки webhook');
            }
        } catch (\Exception $e) {
            $this->error("❌ Исключение при обработке webhook: " . $e->getMessage());
        }
    }
}

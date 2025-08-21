<?php

namespace App\Application\Http\Controllers;

use App\Domain\Payment\Services\PaymentGatewayService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Контроллер для обработки webhook от платежных систем
 */
class WebhookController extends Controller
{
    protected PaymentGatewayService $paymentGateway;

    public function __construct(PaymentGatewayService $paymentGateway)
    {
        $this->paymentGateway = $paymentGateway;
    }

    /**
     * Webhook для YooKassa
     */
    public function yookassa(Request $request): Response
    {
        // Проверяем IP адрес
        if (!$this->isAllowedIp($request, 'yookassa')) {
            return response('Forbidden', 403);
        }

        // Проверяем подпись webhook
        if (!$this->verifyYookassaSignature($request)) {
            return response('Invalid signature', 400);
        }

        $data = $request->json()->all();

        $success = $this->paymentGateway->handleWebhook('yookassa', $data);

        return response($success ? 'OK' : 'ERROR', $success ? 200 : 400);
    }

    /**
     * Webhook для СБП
     */
    public function sbp(Request $request): Response
    {
        $data = $request->all();

        $success = $this->paymentGateway->handleWebhook('sbp', $data);

        return response($success ? 'OK' : 'ERROR', $success ? 200 : 400);
    }

    /**
     * Webhook для WebMoney
     */
    public function webmoney(Request $request): Response
    {
        // Проверяем IP адрес
        if (!$this->isAllowedIp($request, 'webmoney')) {
            return response('NO', 403);
        }

        $data = $request->all();

        $success = $this->paymentGateway->handleWebhook('webmoney', $data);

        return response($success ? 'YES' : 'NO', $success ? 200 : 400);
    }

    /**
     * Webhook для Робокассы
     */
    public function robokassa(Request $request): Response
    {
        $data = $request->all();

        $success = $this->paymentGateway->handleWebhook('robokassa', $data);

        return response($success ? 'OK' : 'ERROR', $success ? 200 : 400);
    }

    /**
     * Универсальный webhook обработчик
     */
    public function handle(Request $request, string $gateway): Response
    {
        $allowedGateways = ['yookassa', 'sbp', 'webmoney', 'robokassa', 'paypal'];

        if (!in_array($gateway, $allowedGateways)) {
            return response('Unknown gateway', 404);
        }

        // Перенаправляем на соответствующий метод
        switch ($gateway) {
            case 'yookassa':
                return $this->yookassa($request);
            case 'sbp':
                return $this->sbp($request);
            case 'webmoney':
                return $this->webmoney($request);
            case 'robokassa':
                return $this->robokassa($request);
            default:
                return response('Not implemented', 501);
        }
    }

    /**
     * Проверить IP адрес
     */
    private function isAllowedIp(Request $request, string $gateway): bool
    {
        $allowedIps = config("payments.security.allowed_ips.{$gateway}", []);
        
        if (empty($allowedIps)) {
            return true; // Если IP не настроены, разрешаем всем
        }

        $clientIp = $request->ip();

        foreach ($allowedIps as $allowedIp) {
            if ($this->ipInRange($clientIp, $allowedIp)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Проверить, входит ли IP в диапазон
     */
    private function ipInRange(string $ip, string $range): bool
    {
        if (strpos($range, '/') === false) {
            return $ip === $range;
        }

        list($subnet, $mask) = explode('/', $range);
        
        if ((ip2long($ip) & ~((1 << (32 - $mask)) - 1)) === ip2long($subnet)) {
            return true;
        }

        return false;
    }

    /**
     * Проверить подпись YooKassa
     */
    private function verifyYookassaSignature(Request $request): bool
    {
        $webhookSecret = config('payments.yookassa.webhook_secret');
        
        if (!$webhookSecret) {
            return true; // Если секрет не настроен, пропускаем проверку
        }

        $body = $request->getContent();
        $signature = $request->header('HTTP_X_YOOKASSA_SIGNATURE');

        if (!$signature) {
            return false;
        }

        $expectedSignature = hash_hmac('sha256', $body, $webhookSecret);

        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Тестовый webhook для разработки
     */
    public function test(Request $request): Response
    {
        if (!config('app.debug')) {
            return response('Test endpoint disabled', 403);
        }

        $data = $request->all();

        // Эмуляция успешного платежа
        if (isset($data['payment_id'])) {
            $testData = [
                'object' => [
                    'id' => 'test_' . time(),
                    'status' => 'succeeded',
                    'metadata' => [
                        'payment_id' => $data['payment_id']
                    ],
                    'amount' => [
                        'value' => $data['amount'] ?? '1000.00',
                        'currency' => 'RUB'
                    ]
                ],
                'event' => 'payment.succeeded'
            ];

            $success = $this->paymentGateway->handleWebhook('yookassa', $testData);
            
            return response()->json([
                'success' => $success,
                'message' => $success ? 'Payment processed' : 'Payment failed',
                'test_data' => $testData
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'payment_id required for test webhook'
        ], 400);
    }
} 
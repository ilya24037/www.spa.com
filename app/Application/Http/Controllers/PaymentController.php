<?php

namespace App\Application\Http\Controllers;

use App\Domain\Ad\Models\Ad;
use App\Domain\Ad\Models\AdPlan;
use App\Domain\Payment\Models\Payment;
use App\Domain\Payment\Services\PaymentService;
use App\Domain\Payment\DTOs\CheckoutDTO;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PaymentController extends Controller
{
    protected PaymentService $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }
    /**
     * Страница выбора тарифа для объявления
     */
    public function selectPlan(Ad $ad)
    {
        $this->authorize('update', $ad);

        // Проверяем, что объявление ждет оплаты
        if ($ad->status !== Ad::STATUS_WAITING_PAYMENT) {
            return redirect()->route('my-ads.index')
                ->with('error', 'Это объявление не требует оплаты');
        }

        // Получаем доступные тарифные планы
        $plans = AdPlan::orderBy('sort_order')->get();

        return Inertia::render('Payment/SelectPlan', [
            'ad' => [
                'id' => $ad->id,
                'title' => $ad->title,
                'price' => $ad->price,
                'formatted_price' => $ad->formatted_price,
                'address' => $ad->address,
                'created_at' => $ad->created_at->format('d.m.Y')
            ],
            'plans' => $plans->map(function ($plan) {
                return [
                    'id' => $plan->id,
                    'name' => $plan->name,
                    'days' => $plan->days,
                    'price' => $plan->price,
                    'formatted_price' => $plan->formatted_price,
                    'description' => $plan->description,
                    'is_popular' => $plan->is_popular
                ];
            })
        ]);
    }

    /**
     * Страница оплаты
     */
    public function checkout(Request $request, Ad $ad)
    {
        $this->authorize('update', $ad);

        $validated = $request->validate([
            'plan_id' => 'required|exists:ad_plans,id'
        ]);

        $plan = AdPlan::findOrFail($validated['plan_id']);

        // Используем сервис для создания платежа
        $payment = $this->paymentService->processAdPlanPayment(
            $ad, 
            $plan, 
            auth()->id()
        );

        return Inertia::render('Payment/Checkout', [
            'payment' => [
                'id' => $payment->id,
                'payment_id' => $payment->payment_id,
                'amount' => $payment->amount,
                'formatted_amount' => $payment->formatted_amount,
                'description' => $payment->description
            ],
            'ad' => [
                'id' => $ad->id,
                'title' => $ad->title
            ],
            'plan' => [
                'id' => $plan->id,
                'name' => $plan->name,
                'days' => $plan->days
            ]
        ]);
    }

    /**
     * Обработка платежа
     */
    public function process(Request $request, Payment $payment)
    {
        // Проверяем, что платеж принадлежит текущему пользователю
        if ($payment->user_id !== auth()->id()) {
            abort(403);
        }

        // Проверяем, что платеж еще не оплачен
        if ($payment->isPaid()) {
            return redirect()->route('payment.success', $payment);
        }

        $validated = $request->validate([
            'payment_method' => 'required|in:sbp,wallet,card'
        ]);

        // Обновляем метод оплаты
        $payment->update([
            'payment_method' => $validated['payment_method']
        ]);

        // Если выбран СБП, перенаправляем на страницу QR-кода
        if ($validated['payment_method'] === 'sbp') {
            return redirect()->route('payment.sbp-qr', [
                'payment' => $payment->id,
                'ad' => $payment->ad->id
            ]);
        }

        // Для других методов - симуляция мгновенной оплаты и активация объявления
        $this->paymentService->activateAdAfterPayment($payment);

        return redirect()->route('payment.success', $payment);
    }

    /**
     * Страница QR-кода для СБП
     */
    public function sbpQr(Payment $payment)
    {
        // Проверяем, что платеж принадлежит текущему пользователю
        if ($payment->user_id !== auth()->id()) {
            abort(403);
        }

        if ($payment->payment_method !== 'sbp') {
            return redirect()->route('payment.checkout', ['ad' => $payment->ad->id]);
        }

        // Генерируем QR-код для СБП (в реальном приложении здесь будет интеграция с банком)
        $qrCode = $this->paymentService->generateSbpQrCode($payment);

        return Inertia::render('Payment/SbpQr', [
            'payment' => [
                'id' => $payment->id,
                'amount' => $payment->amount,
                'formatted_amount' => $payment->formatted_amount,
                'status' => $payment->status
            ],
            'ad' => [
                'id' => $payment->ad->id,
                'title' => $payment->ad->title
            ],
            'qrCode' => $qrCode
        ]);
    }

    /**
     * Проверка статуса платежа (для СБП)
     */
    public function checkStatus(Payment $payment)
    {
        // Проверяем, что платеж принадлежит текущему пользователю
        if ($payment->user_id !== auth()->id()) {
            abort(403);
        }

        // В реальном приложении здесь будет проверка статуса в банке
        // Пока симулируем случайную успешную оплату
        if (rand(1, 10) === 1) { // 10% шанс успешной оплаты
            // Используем сервис для активации объявления
            $this->paymentService->activateAdAfterPayment($payment);
            
            return response()->json(['status' => 'completed']);
        }

        return response()->json(['status' => 'pending']);
    }


    /**
     * Страница успешной оплаты
     */
    public function success(Payment $payment)
    {
        // Проверяем, что платеж принадлежит текущему пользователю
        if ($payment->user_id !== auth()->id()) {
            abort(403);
        }

        return Inertia::render('Payment/Success', [
            'payment' => [
                'id' => $payment->id,
                'payment_id' => $payment->payment_id,
                'amount' => $payment->amount,
                'formatted_amount' => $payment->formatted_amount,
                'paid_at' => $payment->paid_at->format('d.m.Y H:i')
            ],
            'ad' => [
                'id' => $payment->ad->id,
                'title' => $payment->ad->title,
                'expires_at' => $payment->ad->expires_at->format('d.m.Y')
            ]
        ]);
    }

    /**
     * История платежей пользователя
     */
    public function history(Request $request)
    {
        $payments = Payment::where('user_id', auth()->id())
            ->with(['ad', 'adPlan'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return Inertia::render('Payment/History', [
            'payments' => $payments
        ]);
    }

    // ====== DigiSeller/WebMoney Integration ======

    /**
     * Страница пополнения баланса (как в DigiSeller)
     */
    public function topUpBalance()
    {
        $user = auth()->user();
        $balance = $user->getBalance();

        // Тарифы пополнения (как в DigiSeller)
        $topUpPlans = [
            ['amount' => 1000, 'price' => 950, 'discount' => 5],
            ['amount' => 2000, 'price' => 1750, 'discount' => 12.5],
            ['amount' => 5000, 'price' => 3750, 'discount' => 25],
            ['amount' => 10000, 'price' => 7500, 'discount' => 25],
            ['amount' => 15000, 'price' => 11000, 'discount' => 27]
        ];

        return Inertia::render('Payment/TopUpBalance', [
            'balance' => [
                'rub_balance' => $balance->rub_balance,
                'formatted_balance' => $balance->formatted_balance,
                'loyalty_level' => $balance->loyalty_level,
                'loyalty_discount_percent' => $balance->loyalty_discount_percent
            ],
            'topUpPlans' => $topUpPlans
        ]);
    }

    /**
     * Создать платеж для пополнения баланса
     */
    public function createTopUpPayment(Request $request)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:100|max:50000',
            'payment_method' => 'required|in:webmoney,bank_card,bitcoin,ethereum,qiwi,yandex_money'
        ]);

        // Используем сервис для создания платежа пополнения баланса
        $payment = $this->paymentService->createTopUpPayment(
            auth()->id(),
            $validated['amount'],
            $validated['payment_method']
        );

        // Перенаправляем на соответствующий платёжный шлюз
        return $this->paymentService->redirectToPaymentGateway($payment);
    }

    /**
     * Обработка активационного кода (как в DigiSeller)
     */
    public function activateCode(Request $request)
    {
        $validated = $request->validate([
            'activation_code' => 'required|string|min:10|max:50'
        ]);

        try {
            $result = $this->paymentService->activateCode(
                $validated['activation_code'],
                auth()->id()
            );

            return response()->json([
                'success' => true,
                'message' => 'Баланс пополнен на ' . number_format($result['amount'], 0, ',', ' ') . ' ₽',
                'new_balance' => $result['new_balance']
            ]);
            
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при активации кода'
            ], 500);
        }
    }

    /**
     * WebMoney callback обработчик
     */
    public function webmoneyCallback(Request $request)
    {
        try {
            $this->paymentService->handleWebMoneyCallback($request->all());
            return response('YES', 200);
            
        } catch (\InvalidArgumentException $e) {
            \Log::warning('WebMoney callback: ' . $e->getMessage(), $request->all());
            return response('NO', 400);
            
        } catch (\Exception $e) {
            \Log::error('WebMoney callback error: ' . $e->getMessage(), $request->all());
            return response('NO', 500);
        }
    }


}

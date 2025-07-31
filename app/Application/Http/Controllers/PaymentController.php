<?php

namespace App\Application\Http\Controllers;

use App\Models\Ad;
use App\Models\AdPlan;
use App\Models\Payment;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PaymentController extends Controller
{
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

        // Создаем платеж
        $payment = Payment::create([
            'user_id' => auth()->id(),
            'ad_id' => $ad->id,
            'ad_plan_id' => $plan->id,
            'payment_id' => Payment::generatePaymentId(),
            'amount' => $plan->price,
            'currency' => 'RUB',
            'status' => 'pending',
            'description' => 'Публикация объявления на ' . $plan->days . ' дней',
            'metadata' => [
                'ad_title' => $ad->title,
                'plan_name' => $plan->name
            ]
        ]);

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

        // Для других методов - симуляция мгновенной оплаты
        $payment->update([
            'status' => 'completed',
            'paid_at' => now()
        ]);

        // Активируем объявление
        $ad = $payment->ad;
        $plan = $payment->adPlan;
        
        $ad->update([
            'status' => Ad::STATUS_ACTIVE,
            'is_paid' => true,
            'paid_at' => now(),
            'expires_at' => now()->addDays($plan->days)
        ]);

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
        $qrCode = $this->generateSbpQrCode($payment);

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
            $payment->update([
                'status' => 'completed',
                'paid_at' => now()
            ]);

            // Активируем объявление
            $ad = $payment->ad;
            $plan = $payment->adPlan;
            
            $ad->update([
                'status' => Ad::STATUS_ACTIVE,
                'is_paid' => true,
                'paid_at' => now(),
                'expires_at' => now()->addDays($plan->days)
            ]);

            return response()->json(['status' => 'completed']);
        }

        return response()->json(['status' => 'pending']);
    }

    /**
     * Генерация QR-кода для СБП
     */
    private function generateSbpQrCode(Payment $payment)
    {
        // В реальном приложении здесь будет генерация настоящего QR-кода для СБП
        // Пока возвращаем заглушку
        return 'sbp://payment?amount=' . $payment->amount . '&id=' . $payment->payment_id;
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

        $user = auth()->user();
        $balance = $user->getBalance();

        // Рассчитываем скидку
        $discount = $balance->getDiscountForAmount($validated['amount']);

        $payment = Payment::create([
            'user_id' => $user->id,
            'payment_id' => Payment::generatePaymentId(),
            'amount' => $validated['amount'],
            'discount_amount' => $discount['amount'],
            'discount_percent' => $discount['percent'],
            'final_amount' => $discount['final_amount'],
            'currency' => 'RUB',
            'status' => 'pending',
            'payment_method' => $validated['payment_method'],
            'purchase_type' => 'balance_top_up',
            'description' => 'Пополнение баланса',
            'metadata' => [
                'original_amount' => $validated['amount'],
                'discount_applied' => $discount['percent']
            ]
        ]);

        // Перенаправляем на соответствующий платёжный шлюз
        return $this->redirectToPaymentGateway($payment);
    }

    /**
     * Обработка активационного кода (как в DigiSeller)
     */
    public function activateCode(Request $request)
    {
        $validated = $request->validate([
            'activation_code' => 'required|string|min:10|max:50'
        ]);

        $payment = Payment::where('activation_code', $validated['activation_code'])
            ->where('activation_code_used', false)
            ->where('status', 'completed')
            ->first();

        if (!$payment) {
            return response()->json([
                'success' => false,
                'message' => 'Неверный или уже использованный код активации'
            ], 422);
        }

        // Активируем код
        $payment->update(['activation_code_used' => true]);

        // Пополняем баланс пользователя
        $user = auth()->user();
        $balance = $user->getBalance();
        $balance->addFunds($payment->final_amount);

        return response()->json([
            'success' => true,
            'message' => 'Баланс пополнен на ' . number_format($payment->final_amount, 0, ',', ' ') . ' ₽',
            'new_balance' => $balance->formatted_balance
        ]);
    }

    /**
     * WebMoney callback обработчик
     */
    public function webmoneyCallback(Request $request)
    {
        // Проверяем подпись WebMoney
        if (!$this->verifyWebMoneySignature($request)) {
            \Log::warning('WebMoney callback: Invalid signature', $request->all());
            return response('NO', 400);
        }

        $paymentId = $request->input('LMI_PAYMENT_NO');
        $amount = $request->input('LMI_PAYMENT_AMOUNT');
        $mode = $request->input('LMI_MODE'); // 0 - реальный, 1 - тест

        $payment = Payment::where('payment_id', $paymentId)->first();

        if (!$payment) {
            \Log::error('WebMoney callback: Payment not found', ['payment_id' => $paymentId]);
            return response('NO', 404);
        }

        // Проверяем сумму
        if (abs($payment->final_amount - $amount) > 0.01) {
            \Log::error('WebMoney callback: Amount mismatch', [
                'expected' => $payment->final_amount,
                'received' => $amount
            ]);
            return response('NO', 400);
        }

        // Обновляем статус платежа
        $payment->update([
            'status' => 'completed',
            'paid_at' => now(),
            'external_payment_id' => $request->input('LMI_SYS_PAYMENT_ID'),
            'metadata' => array_merge($payment->metadata ?? [], [
                'webmoney_data' => $request->except(['LMI_HASH'])
            ])
        ]);

        // Обрабатываем платеж в зависимости от типа
        $this->processCompletedPayment($payment);

        return response('YES', 200);
    }

    /**
     * Перенаправление на платёжный шлюз
     */
    private function redirectToPaymentGateway(Payment $payment)
    {
        switch ($payment->payment_method) {
            case 'webmoney':
                return $this->redirectToWebMoney($payment);
            case 'bank_card':
                return $this->redirectToCardPayment($payment);
            default:
                return redirect()->route('payment.checkout', ['payment' => $payment->id]);
        }
    }

    /**
     * Редирект на WebMoney
     */
    private function redirectToWebMoney(Payment $payment)
    {
        $webmoneyParams = [
            'LMI_PAYEE_PURSE' => config('payments.webmoney.purse'),
            'LMI_PAYMENT_AMOUNT' => $payment->final_amount,
            'LMI_PAYMENT_NO' => $payment->payment_id,
            'LMI_PAYMENT_DESC' => $payment->description,
            'LMI_SUCCESS_URL' => route('payment.success', $payment),
            'LMI_FAIL_URL' => route('payment.fail', $payment),
            'LMI_RESULT_URL' => route('payment.webmoney.callback')
        ];

        return Inertia::render('Payment/WebMoney', [
            'payment' => $payment,
            'webmoneyParams' => $webmoneyParams,
            'webmoneyAction' => 'https://merchant.webmoney.com/lmi/payment_utf.asp'
        ]);
    }

    /**
     * Проверка подписи WebMoney
     */
    private function verifyWebMoneySignature(Request $request): bool
    {
        $secretKey = config('payments.webmoney.secret_key');
        
        $signString = $request->input('LMI_PAYEE_PURSE') . 
                     $request->input('LMI_PAYMENT_AMOUNT') . 
                     $request->input('LMI_PAYMENT_NO') . 
                     $request->input('LMI_MODE') . 
                     $request->input('LMI_SYS_INVS_NO') . 
                     $request->input('LMI_SYS_TRANS_NO') . 
                     $request->input('LMI_SYS_TRANS_DATE') . 
                     $secretKey . 
                     $request->input('LMI_PAYER_PURSE') . 
                     $request->input('LMI_PAYER_WM');

        $expectedHash = strtoupper(hash('sha256', $signString));
        $receivedHash = strtoupper($request->input('LMI_HASH'));

        return hash_equals($expectedHash, $receivedHash);
    }

    /**
     * Обработка завершенного платежа
     */
    private function processCompletedPayment(Payment $payment)
    {
        switch ($payment->purchase_type) {
            case 'balance_top_up':
                // Пополняем баланс
                $balance = $payment->user->getBalance();
                $balance->addFunds($payment->final_amount);
                break;

            case 'ad_placement':
                // Активируем объявление
                if ($payment->ad) {
                    $plan = $payment->adPlan;
                    $payment->ad->update([
                        'status' => Ad::STATUS_ACTIVE,
                        'is_paid' => true,
                        'paid_at' => now(),
                        'expires_at' => now()->addDays($plan->days)
                    ]);
                }
                break;

            case 'ad_promotion':
                // Логика продвижения объявления
                break;
        }
    }
}

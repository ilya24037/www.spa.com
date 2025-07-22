<?php

namespace App\Http\Controllers;

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
}

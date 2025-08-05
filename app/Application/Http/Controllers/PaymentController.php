<?php

namespace App\Application\Http\Controllers;

use App\Domain\Ad\Models\Ad;
use App\Domain\Ad\Services\AdService;
use App\Domain\Payment\Models\Payment;
use App\Domain\Payment\Services\PaymentService;
use App\Domain\Payment\Services\PaymentAuthorizationService;
use App\Domain\Payment\Services\PaymentDataProvider;
use App\Domain\Payment\Services\PaymentCallbackService;
use Illuminate\Http\Request;
use Inertia\Inertia;

/**
 * Контроллер платежных операций
 * Рефакторинг: 312 → ≤200 строк CLAUDE.md
 */
class PaymentController extends Controller
{
    public function __construct(
        private PaymentService $paymentService,
        private AdService $adService,
        private PaymentAuthorizationService $authService,
        private PaymentDataProvider $dataProvider,
        private PaymentCallbackService $callbackService
    ) {}

    /** Страница выбора тарифа для объявления */
    public function selectPlan(Ad $ad)
    {
        $this->authService->authorizeAdUpdate($ad, auth()->id());

        if (!$this->paymentService->canPayForAd($ad)) {
            return redirect()->route('my-ads.index')
                ->with('error', 'Это объявление не требует оплаты');
        }

        return Inertia::render('Payment/SelectPlan', [
            'ad' => $this->dataProvider->prepareAdData($ad),
            'plans' => $this->paymentService->getAvailablePlans()
        ]);
    }

    /** Страница оплаты */
    public function checkout(Request $request, Ad $ad)
    {
        $this->authService->authorizeAdUpdate($ad, auth()->id());

        $validated = $request->validate([
            'plan_id' => 'required|exists:ad_plans,id'
        ]);

        $paymentData = $this->paymentService->processAdPlanPayment(
            $ad, 
            $validated['plan_id'], 
            $request->user()->id
        );

        return Inertia::render('Payment/Checkout', $paymentData);
    }

    /** Обработка платежа */
    public function process(Request $request, Payment $payment)
    {
        $this->authService->authorizePaymentOwnership($payment, auth()->id());

        if (!$this->authService->validatePaymentForProcessing($payment)) {
            return redirect()->route('payment.success', $payment);
        }

        $validated = $request->validate([
            'payment_method' => 'required|in:sbp,wallet,card'
        ]);

        $payment->update(['payment_method' => $validated['payment_method']]);

        if ($validated['payment_method'] === 'sbp') {
            return redirect()->route('payment.sbp-qr', [
                'payment' => $payment->id,
                'ad' => $payment->ad->id
            ]);
        }

        $this->paymentService->activateAdAfterPayment($payment);
        return redirect()->route('payment.success', $payment);
    }

    /** Страница QR-кода для СБП */
    public function sbpQr(Payment $payment)
    {
        $this->authService->authorizePaymentOwnership($payment, auth()->id());

        if (!$this->authService->validateSbpPayment($payment)) {
            return redirect()->route('payment.checkout', ['ad' => $payment->ad->id]);
        }

        $qrCode = $this->paymentService->generateSbpQrCode($payment);

        return Inertia::render('Payment/SbpQr', [
            'payment' => $this->dataProvider->preparePaymentData($payment),
            'ad' => $this->dataProvider->prepareAdData($payment->ad),
            'qrCode' => $qrCode
        ]);
    }

    /** Проверка статуса платежа (для СБП) */
    public function checkStatus(Payment $payment)
    {
        $this->authService->authorizePaymentOwnership($payment, auth()->id());
        
        $status = $this->callbackService->checkSbpPaymentStatus($payment);
        return response()->json($status);
    }

    /** Страница успешной оплаты */
    public function success(Payment $payment)
    {
        $this->authService->authorizePaymentOwnership($payment, auth()->id());

        return Inertia::render('Payment/Success', [
            'payment' => $this->dataProvider->preparePaymentData($payment),
            'ad' => $this->dataProvider->prepareAdData($payment->ad)
        ]);
    }

    /** История платежей пользователя */
    public function history(Request $request)
    {
        $payments = Payment::where('user_id', auth()->id())
            ->with(['ad', 'adPlan'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return Inertia::render('Payment/History', ['payments' => $payments]);
    }

    /** Страница пополнения баланса */
    public function topUpBalance()
    {
        $balance = auth()->user()->getBalance();

        return Inertia::render('Payment/TopUpBalance', [
            'balance' => $this->dataProvider->prepareBalanceData($balance),
            'topUpPlans' => $this->dataProvider->getTopUpPlans()
        ]);
    }

    /** Создать платеж для пополнения баланса */
    public function createTopUpPayment(Request $request)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:100|max:50000',
            'payment_method' => 'required|in:webmoney,bank_card,bitcoin,ethereum,qiwi,yandex_money'
        ]);

        $payment = $this->paymentService->createTopUpPayment(
            auth()->id(),
            $validated['amount'],
            $validated['payment_method']
        );

        return $this->paymentService->redirectToPaymentGateway($payment);
    }

    /** Обработка активационного кода */
    public function activateCode(Request $request)
    {
        $validated = $request->validate([
            'activation_code' => 'required|string|min:10|max:50'
        ]);

        $result = $this->callbackService->processActivationCode(
            $validated['activation_code'],
            auth()->id()
        );

        $statusCode = $result['status_code'] ?? 200;
        unset($result['status_code']);

        return response()->json($result, $statusCode);
    }

    /** WebMoney callback обработчик */
    public function webmoneyCallback(Request $request)
    {
        try {
            $response = $this->callbackService->handleWebMoneyCallback($request->all());
            return response($response, 200);
        } catch (\Exception $e) {
            return response('NO', $e instanceof \InvalidArgumentException ? 400 : 500);
        }
    }
}
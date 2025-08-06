<?php

namespace App\Domain\Payment\Services;

/**
 * Калькулятор комиссий Stripe
 */
class StripeFeeCalculator
{
    /**
     * Базовый процент комиссии Stripe
     */
    private const BASE_FEE_PERCENT = 0.029; // 2.9%

    /**
     * Фиксированные комиссии по валютам
     */
    private const FIXED_FEES = [
        'USD' => 0.30,
        'EUR' => 0.25,
        'GBP' => 0.20,
        'RUB' => 15.0,
        'CAD' => 0.30,
        'AUD' => 0.30,
        'JPY' => 35.0,
        'CHF' => 0.30,
        'SEK' => 3.0,
        'NOK' => 3.0,
        'DKK' => 2.0,
    ];

    /**
     * Специальные проценты для некоторых валют
     */
    private const SPECIAL_PERCENTS = [
        'JPY' => 0.036, // 3.6% для японской йены
        'RUB' => 0.035, // 3.5% для российского рубля
    ];

    /**
     * Рассчитать комиссию за платеж
     */
    public function calculatePaymentFee(float $amount, string $currency = 'USD'): float
    {
        $currency = strtoupper($currency);
        
        $feePercent = self::SPECIAL_PERCENTS[$currency] ?? self::BASE_FEE_PERCENT;
        $fixedFee = self::FIXED_FEES[$currency] ?? self::FIXED_FEES['USD'];
        
        return round($amount * $feePercent + $fixedFee, 2);
    }

    /**
     * Рассчитать комиссию за международный платеж
     */
    public function calculateInternationalFee(float $amount, string $currency = 'USD'): float
    {
        $baseFee = $this->calculatePaymentFee($amount, $currency);
        $internationalSurcharge = $amount * 0.01; // Дополнительно 1% за международные платежи
        
        return round($baseFee + $internationalSurcharge, 2);
    }

    /**
     * Рассчитать комиссию за возврат
     */
    public function calculateRefundFee(float $amount, string $currency = 'USD'): float
    {
        // Stripe не взимает комиссию за возвраты, но не возвращает процессинговые комиссии
        return 0;
    }

    /**
     * Рассчитать комиссию за диспут
     */
    public function calculateDisputeFee(string $currency = 'USD'): float
    {
        return match (strtoupper($currency)) {
            'USD' => 15.00,
            'EUR' => 15.00,
            'GBP' => 15.00,
            'RUB' => 1100.0,
            'JPY' => 1500.0,
            default => 15.00
        };
    }

    /**
     * Рассчитать сумму которую получит получатель после комиссии
     */
    public function calculateNetAmount(float $grossAmount, string $currency = 'USD'): float
    {
        $fee = $this->calculatePaymentFee($grossAmount, $currency);
        return round($grossAmount - $fee, 2);
    }

    /**
     * Рассчитать сумму которую нужно заплатить чтобы получить желаемую сумму
     */
    public function calculateGrossAmount(float $netAmount, string $currency = 'USD'): float
    {
        $currency = strtoupper($currency);
        
        $feePercent = self::SPECIAL_PERCENTS[$currency] ?? self::BASE_FEE_PERCENT;
        $fixedFee = self::FIXED_FEES[$currency] ?? self::FIXED_FEES['USD'];
        
        // Формула: grossAmount = (netAmount + fixedFee) / (1 - feePercent)
        $grossAmount = ($netAmount + $fixedFee) / (1 - $feePercent);
        
        return round($grossAmount, 2);
    }

    /**
     * Получить структуру комиссий для валюты
     */
    public function getFeeStructure(string $currency = 'USD'): array
    {
        $currency = strtoupper($currency);
        
        return [
            'currency' => $currency,
            'percentage' => (self::SPECIAL_PERCENTS[$currency] ?? self::BASE_FEE_PERCENT) * 100,
            'fixed_fee' => self::FIXED_FEES[$currency] ?? self::FIXED_FEES['USD'],
            'dispute_fee' => $this->calculateDisputeFee($currency),
            'supports_international_cards' => true,
            'international_surcharge_percent' => 1.0,
        ];
    }

    /**
     * Получить все поддерживаемые валюты и их комиссии
     */
    public function getAllFeeStructures(): array
    {
        $structures = [];
        
        foreach (array_keys(self::FIXED_FEES) as $currency) {
            $structures[$currency] = $this->getFeeStructure($currency);
        }
        
        return $structures;
    }

    /**
     * Проверить поддерживается ли валюта
     */
    public function isCurrencySupported(string $currency): bool
    {
        return isset(self::FIXED_FEES[strtoupper($currency)]);
    }

    /**
     * Получить минимальную сумму для валюты
     */
    public function getMinimumAmount(string $currency = 'USD'): float
    {
        return match (strtoupper($currency)) {
            'USD', 'EUR', 'GBP', 'CAD', 'AUD', 'CHF' => 0.50,
            'RUB' => 30.0,
            'JPY' => 50.0,
            'SEK', 'NOK' => 3.0,
            'DKK' => 2.5,
            default => 0.50
        };
    }

    /**
     * Получить максимальную сумму для валюты
     */
    public function getMaximumAmount(string $currency = 'USD'): float
    {
        return match (strtoupper($currency)) {
            'USD', 'EUR', 'GBP', 'CAD', 'AUD', 'CHF' => 999999.99,
            'RUB' => 73000000.0, // ~1M USD
            'JPY' => 110000000.0, // ~1M USD
            'SEK', 'NOK' => 9000000.0, // ~1M USD
            'DKK' => 6700000.0, // ~1M USD
            default => 999999.99
        };
    }

    /**
     * Рассчитать общую стоимость обработки платежей за период
     */
    public function calculatePeriodFees(array $payments): array
    {
        $totalFees = 0;
        $totalAmount = 0;
        $feesBycurrency = [];
        $paymentCount = 0;
        
        foreach ($payments as $payment) {
            $amount = $payment['amount'] ?? 0;
            $currency = $payment['currency'] ?? 'USD';
            
            $fee = $this->calculatePaymentFee($amount, $currency);
            
            $totalFees += $fee;
            $totalAmount += $amount;
            $paymentCount++;
            
            if (!isset($feesByurrency[$currency])) {
                $feesByurrency[$currency] = [
                    'count' => 0,
                    'total_amount' => 0,
                    'total_fees' => 0,
                ];
            }
            
            $feesByurrency[$currency]['count']++;
            $feesByurrency[$currency]['total_amount'] += $amount;
            $feesByurrency[$currency]['total_fees'] += $fee;
        }
        
        return [
            'total_payments' => $paymentCount,
            'total_amount' => round($totalAmount, 2),
            'total_fees' => round($totalFees, 2),
            'average_fee_rate' => $totalAmount > 0 ? round(($totalFees / $totalAmount) * 100, 2) : 0,
            'fees_by_currency' => $feesByurrency,
        ];
    }
}
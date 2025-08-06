<?php

namespace App\Domain\Payment\Services;

/**
 * Сервис расчета комиссий YooKassa
 */
class YooKassaFeeCalculator
{
    /**
     * Рассчитать комиссию
     */
    public function calculateFee(float $amount, array $config = []): float
    {
        // Комиссия YooKassa для самозанятых: 6% + 3 руб
        if ($config['self_employed_mode'] ?? false) {
            return round($amount * 0.06 + 3, 2);
        }
        
        // Комиссия для юридических лиц
        if ($config['legal_entity_mode'] ?? false) {
            return round($amount * 0.035, 2); // 3.5%
        }
        
        // Стандартная комиссия: 2.8%
        return round($amount * 0.028, 2);
    }

    /**
     * Получить информацию о тарифах
     */
    public function getTariffInfo(array $config = []): array
    {
        if ($config['self_employed_mode'] ?? false) {
            return [
                'type' => 'self_employed',
                'percent' => 6.0,
                'fixed_fee' => 3.0,
                'description' => 'Тариф для самозанятых: 6% + 3 руб.'
            ];
        }
        
        if ($config['legal_entity_mode'] ?? false) {
            return [
                'type' => 'legal_entity',
                'percent' => 3.5,
                'fixed_fee' => 0.0,
                'description' => 'Тариф для юридических лиц: 3.5%'
            ];
        }
        
        return [
            'type' => 'standard',
            'percent' => 2.8,
            'fixed_fee' => 0.0,
            'description' => 'Стандартный тариф: 2.8%'
        ];
    }

    /**
     * Рассчитать итоговую сумму с учетом комиссии
     */
    public function calculateTotalWithFee(float $amount, array $config = []): array
    {
        $fee = $this->calculateFee($amount, $config);
        $total = $amount + $fee;
        
        return [
            'base_amount' => $amount,
            'fee' => $fee,
            'total' => $total,
            'tariff' => $this->getTariffInfo($config)
        ];
    }
}
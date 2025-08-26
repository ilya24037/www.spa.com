<?php

namespace App\Infrastructure\Listeners\Master\Processors;

/**
 * Анализатор изменений профиля мастера
 */
class ProfileChangeAnalyzer
{
    private array $criticalFields = ['phone', 'location_type', 'city', 'certifications'];
    private array $importantFields = ['display_name', 'bio', 'experience_years', 'working_hours'];
    private array $mediaFields = ['avatar', 'portfolio_photos', 'certificate_photos'];
    private array $contactFields = ['phone', 'email', 'languages'];
    private array $searchableFields = ['display_name', 'city', 'bio', 'services', 'location_type'];

    /**
     * Анализировать изменения
     */
    public function analyzeChanges($masterProfile, array $updatedData, array $changedFields): array
    {
        $changes = [
            'critical' => [],
            'important' => [],
            'media' => [],
            'services' => [],
            'contact' => [],
            'pricing' => [],
        ];

        foreach ($changedFields as $field) {
            $this->categorizeChange($field, $changes, $masterProfile, $updatedData);
        }

        return $changes;
    }

    /**
     * Категоризировать изменение
     */
    private function categorizeChange(string $field, array &$changes, $masterProfile, array $updatedData): void
    {
        if (in_array($field, $this->criticalFields)) {
            $changes['critical'][] = $field;
            return;
        }

        if (in_array($field, $this->importantFields)) {
            $changes['important'][] = $field;
            return;
        }

        if (in_array($field, $this->mediaFields)) {
            $changes['media'][] = $field;
            return;
        }

        if ($field === 'services') {
            $changes['services'][] = $field;
            $this->analyzeServiceChanges($changes, $masterProfile, $updatedData);
            return;
        }

        if (in_array($field, $this->contactFields)) {
            $changes['contact'][] = $field;
        }
    }

    /**
     * Анализировать изменения услуг
     */
    private function analyzeServiceChanges(array &$changes, $masterProfile, array $updatedData): void
    {
        if (!isset($updatedData['services'])) {
            return;
        }

        $currentServices = $masterProfile->services->keyBy('id')->toArray();
        $updatedServices = collect($updatedData['services'])->keyBy('id');

        foreach ($updatedServices as $serviceId => $serviceData) {
            if (isset($currentServices[$serviceId])) {
                $currentPrice = $currentServices[$serviceId]['price'];
                $newPrice = $serviceData['price'] ?? $currentPrice;
                
                if ($currentPrice != $newPrice) {
                    $changes['pricing'][] = "service_{$serviceId}_price";
                }
            }
        }
    }

    /**
     * Проверить нужна ли переиндексация
     */
    public function needsReindex(array $changes): bool
    {
        $affectedFields = array_merge(
            $changes['critical'], 
            $changes['important'], 
            $changes['services']
        );

        return !empty(array_intersect($this->searchableFields, $affectedFields));
    }
}
<?php

namespace App\Domain\Search\Engines\Handlers;

/**
 * Форматировщик результатов поиска мастеров
 */
class MasterResultFormatter
{
    /**
     * Форматировать результат быстрого поиска
     */
    public function formatQuickResult($item): array
    {
        return [
            'id' => $item->id,
            'name' => $item->name,
            'specialty' => $item->specialty,
            'city' => $item->city,
            'rating' => $item->rating,
            'reviews_count' => $item->reviews_count,
            'min_price' => $item->min_price,
            'avatar' => $this->getAvatarUrl($item),
            'is_online' => $this->isOnline($item),
            'url' => route('masters.show', $item->id),
        ];
    }

    /**
     * Форматировать результат поиска похожих
     */
    public function formatSimilarResult($item): array
    {
        return [
            'id' => $item->id,
            'name' => $item->name,
            'specialty' => $item->specialty,
            'city' => $item->city,
            'rating' => $item->rating,
            'reviews_count' => $item->reviews_count,
            'experience_years' => $item->experience_years,
            'min_price' => $item->min_price,
            'similarity_score' => $item->similarity_score ?? 0,
            'avatar' => $this->getAvatarUrl($item),
            'url' => route('masters.show', $item->id),
        ];
    }

    /**
     * Форматировать результат геопоиска
     */
    public function formatGeoResult($item): array
    {
        return [
            'id' => $item->id,
            'name' => $item->name,
            'specialty' => $item->specialty,
            'city' => $item->city,
            'address' => $item->address,
            'latitude' => $item->latitude,
            'longitude' => $item->longitude,
            'distance' => round($item->distance ?? 0, 2),
            'rating' => $item->rating,
            'min_price' => $item->min_price,
            'url' => route('masters.show', $item->id),
        ];
    }

    /**
     * Получить заголовки для CSV экспорта
     */
    public function getCsvHeaders(): array
    {
        return [
            'ID',
            'Имя',
            'Специализация',
            'Город',
            'Рейтинг',
            'Количество отзывов',
            'Опыт (лет)',
            'Минимальная цена',
            'Проверен',
            'Дата регистрации',
            'URL'
        ];
    }

    /**
     * Форматировать строку CSV
     */
    public function formatCsvRow($item): array
    {
        return [
            $item->id,
            $item->name,
            $item->specialty,
            $item->city,
            $item->rating,
            $item->reviews_count,
            $item->experience_years,
            $item->min_price,
            $item->is_verified ? 'Да' : 'Нет',
            $item->created_at->format('d.m.Y'),
            route('masters.show', $item->id)
        ];
    }

    /**
     * Получить URL аватара мастера
     */
    private function getAvatarUrl($item): ?string
    {
        return $item->media->where('type', 'avatar')->first()?->url;
    }

    /**
     * Проверить онлайн статус мастера
     */
    private function isOnline($item): bool
    {
        return $item->last_activity_at >= now()->subMinutes(15);
    }
}
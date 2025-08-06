<?php

namespace App\Domain\Search\Engines\Formatters;

/**
 * Форматировщик результатов поиска объявлений
 */
class AdResultFormatter
{
    /**
     * Форматировать быстрый результат
     */
    public function formatQuickResult($item): array
    {
        return [
            'id' => $item->id,
            'title' => $item->title,
            'price' => $item->price,
            'city' => $item->city,
            'master_name' => $item->user->name,
            'master_rating' => $item->user->rating,
            'image' => $item->media->first()?->url,
            'url' => route('ads.show', $item->id),
        ];
    }

    /**
     * Форматировать похожий результат
     */
    public function formatSimilarResult($item): array
    {
        return [
            'id' => $item->id,
            'title' => $item->title,
            'price' => $item->price,
            'city' => $item->city,
            'specialty' => $item->specialty,
            'master_name' => $item->user->name,
            'master_rating' => $item->user->rating,
            'similarity_score' => $item->similarity_score ?? 0,
            'image' => $item->media->first()?->url,
            'url' => route('ads.show', $item->id),
        ];
    }

    /**
     * Форматировать гео результат
     */
    public function formatGeoResult($item): array
    {
        return [
            'id' => $item->id,
            'title' => $item->title,
            'price' => $item->price,
            'city' => $item->city,
            'address' => $item->address,
            'latitude' => $item->latitude,
            'longitude' => $item->longitude,
            'distance' => round($item->distance ?? 0, 2),
            'master_name' => $item->user->name,
            'url' => route('ads.show', $item->id),
        ];
    }

    /**
     * Получить CSV заголовки
     */
    public function getCsvHeaders(): array
    {
        return [
            'ID',
            'Заголовок',
            'Описание',
            'Цена',
            'Город',
            'Специализация',
            'Мастер',
            'Рейтинг мастера',
            'Количество отзывов',
            'Дата создания',
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
            $item->title,
            strip_tags($item->description),
            $item->price,
            $item->city,
            $item->specialty,
            $item->user->name,
            $item->user->rating,
            $item->user->reviews_count,
            $item->created_at->format('d.m.Y H:i'),
            route('ads.show', $item->id)
        ];
    }
}
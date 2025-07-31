<?php

namespace App\Domain\Master\Traits;

/**
 * Трейт для генерации мета-тегов
 * Автоматически создаёт SEO-оптимизированные заголовки и описания
 */
trait GeneratesMetaTags
{
    /**
     * Генерация мета-тегов для профиля
     */
    public function generateMetaTags(): self
    {
        if (empty($this->meta_title)) {
            $this->meta_title = $this->generateMetaTitle();
        }

        if (empty($this->meta_description)) {
            $this->meta_description = $this->generateMetaDescription();
        }

        return $this;
    }

    /**
     * Генерация мета-заголовка
     */
    protected function generateMetaTitle(): string
    {
        $parts = [$this->display_name];

        // Получаем основную услугу
        $mainService = $this->services()
            ->orderBy('bookings_count', 'desc')
            ->value('name') ?: 'Массажист';

        $parts[] = $mainService;

        // Добавляем локацию
        $location = $this->district
            ? "{$this->district}, {$this->city}"
            : $this->city;
        $parts[] = $location;

        return implode(' • ', $parts);
    }

    /**
     * Генерация мета-описания
     */
    protected function generateMetaDescription(): string
    {
        $desc = [];

        // Основная информация
        $desc[] = $this->is_verified
            ? "✓ Верифицированный массажист {$this->display_name}"
            : "Массажист {$this->display_name}";

        // Услуги
        $services = $this->services()
            ->where('status', 'active')
            ->orderBy('bookings_count', 'desc')
            ->take(3)
            ->pluck('name')
            ->implode(', ');
            
        if ($services) {
            $desc[] = "Услуги: {$services}";
        }

        // Опыт работы
        if ($this->experience_years > 0) {
            $yearWord = $this->getYearWord($this->experience_years);
            $desc[] = "Опыт {$this->experience_years} {$yearWord}";
        }

        // Рейтинг
        if ($this->rating > 0 && $this->reviews_count > 0) {
            $stars = str_repeat('★', round($this->rating));
            $desc[] = "Рейтинг {$this->rating} {$stars} ({$this->reviews_count} отзывов)";
        }

        // Минимальная цена
        $minPrice = $this->services()
            ->where('status', 'active')
            ->min('price');
            
        if ($minPrice) {
            $desc[] = "Цены от " . number_format($minPrice, 0, '', ' ') . " ₽";
        }

        $fullDescription = implode('. ', $desc) . '.';
        
        // Обрезаем до 160 символов если необходимо
        return mb_strlen($fullDescription) > 160
            ? mb_substr($fullDescription, 0, 157) . '...'
            : $fullDescription;
    }

    /**
     * Склонение слова "год"
     */
    protected function getYearWord(int $years): string
    {
        $lastTwo = $years % 100;
        
        if ($lastTwo >= 11 && $lastTwo <= 14) {
            return 'лет';
        }

        return match ($years % 10) {
            1       => 'год',
            2, 3, 4 => 'года',
            default => 'лет',
        };
    }

    /**
     * Генерация структурированных данных для поисковиков
     */
    public function getStructuredData(): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'LocalBusiness',
            'name' => $this->display_name,
            'description' => $this->meta_description,
            'address' => [
                '@type' => 'PostalAddress',
                'addressLocality' => $this->city,
                'addressRegion' => $this->district,
            ],
            'aggregateRating' => $this->rating > 0 ? [
                '@type' => 'AggregateRating',
                'ratingValue' => $this->rating,
                'reviewCount' => $this->reviews_count,
            ] : null,
            'priceRange' => $this->getPriceRange(),
        ];
    }

    /**
     * Получить диапазон цен
     */
    protected function getPriceRange(): string
    {
        $prices = $this->services()
            ->where('status', 'active')
            ->pluck('price');
            
        if ($prices->isEmpty()) {
            return '₽₽';
        }

        $min = $prices->min();
        $max = $prices->max();
        
        // Определяем ценовую категорию
        if ($max < 3000) {
            return '₽';
        } elseif ($max < 5000) {
            return '₽₽';
        } elseif ($max < 10000) {
            return '₽₽₽';
        } else {
            return '₽₽₽₽';
        }
    }
}
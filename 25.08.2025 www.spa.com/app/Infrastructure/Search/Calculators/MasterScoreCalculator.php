<?php

namespace App\Infrastructure\Search\Calculators;

use App\Domain\Master\Models\MasterProfile;

/**
 * Калькулятор различных скорингов для мастера
 */
class MasterScoreCalculator
{
    /**
     * Рассчитать полноту профиля (0-100%)
     */
    public function calculateProfileCompleteness(MasterProfile $master): int
    {
        $score = 0;
        $maxScore = 100;
        
        // Основная информация (30%)
        if (!empty($master->user->name)) $score += 5;
        if (!empty($master->about)) $score += 10;
        if (!empty($master->specialty)) $score += 5;
        if (!empty($master->user->avatar_url)) $score += 10;
        
        // Контакты и локация (20%)
        if (!empty($master->user->phone)) $score += 5;
        if ($master->latitude && $master->longitude) $score += 10;
        if (!empty($master->address)) $score += 5;
        
        // Услуги и цены (20%)
        if ($master->services && $master->services->count() > 0) $score += 15;
        if ($master->services && $master->services->where('price', '>', 0)->count() > 0) $score += 5;
        
        // Портфолио (15%)
        if ($master->media && $master->media->count() >= 3) $score += 10;
        if ($master->media && $master->media->count() >= 10) $score += 5;
        
        // Расписание (10%)
        if (!empty($master->working_hours)) $score += 10;
        
        // Образование и сертификаты (5%)
        if (!empty($master->education)) $score += 3;
        if (!empty($master->certificates)) $score += 2;
        
        return min($score, $maxScore);
    }

    /**
     * Рассчитать активность мастера (0-100)
     */
    public function calculateActivityScore(MasterProfile $master): int
    {
        $score = 0;
        
        // Последняя активность
        if ($master->last_active_at) {
            $hoursAgo = $master->last_active_at->diffInHours(now());
            
            if ($hoursAgo <= 1) {
                $score += 40; // Онлайн сейчас
            } elseif ($hoursAgo <= 24) {
                $score += 30; // Был сегодня
            } elseif ($hoursAgo <= 168) { // 7 дней
                $score += 20; // Был на неделе
            } elseif ($hoursAgo <= 720) { // 30 дней
                $score += 10; // Был в месяце
            }
        }
        
        // Обновления профиля
        if ($master->updated_at && $master->updated_at->diffInDays(now()) <= 30) {
            $score += 20;
        }
        
        // Количество заказов за месяц
        $recentOrders = $master->bookings()
            ->where('created_at', '>=', now()->subMonth())
            ->count();
            
        if ($recentOrders >= 10) {
            $score += 20;
        } elseif ($recentOrders >= 5) {
            $score += 15;
        } elseif ($recentOrders >= 1) {
            $score += 10;
        }
        
        // Ответы на сообщения (если есть такая статистика)
        if ($master->response_rate && $master->response_rate >= 80) {
            $score += 10;
        }
        
        // Быстрота ответа
        if ($master->avg_response_time && $master->avg_response_time <= 60) { // минуты
            $score += 10;
        }
        
        return min($score, 100);
    }

    /**
     * Рассчитать качество услуг (0-100)
     */
    public function calculateQualityScore(MasterProfile $master): int
    {
        $score = 0;
        
        // Рейтинг
        if ($master->rating) {
            $score += ($master->rating / 5) * 40; // Максимум 40 баллов за рейтинг
        }
        
        // Количество отзывов
        $reviewsCount = $master->reviews_count ?? 0;
        if ($reviewsCount >= 50) {
            $score += 20;
        } elseif ($reviewsCount >= 20) {
            $score += 15;
        } elseif ($reviewsCount >= 10) {
            $score += 10;
        } elseif ($reviewsCount >= 5) {
            $score += 5;
        }
        
        // Процент повторных клиентов
        if ($master->repeat_clients_percent) {
            if ($master->repeat_clients_percent >= 70) {
                $score += 15;
            } elseif ($master->repeat_clients_percent >= 50) {
                $score += 10;
            } elseif ($master->repeat_clients_percent >= 30) {
                $score += 5;
            }
        }
        
        // Опыт работы
        if ($master->experience_years) {
            if ($master->experience_years >= 10) {
                $score += 15;
            } elseif ($master->experience_years >= 5) {
                $score += 10;
            } elseif ($master->experience_years >= 2) {
                $score += 5;
            }
        }
        
        // Количество выполненных заказов
        $completedOrders = $master->completed_orders ?? 0;
        if ($completedOrders >= 500) {
            $score += 10;
        } elseif ($completedOrders >= 100) {
            $score += 8;
        } elseif ($completedOrders >= 50) {
            $score += 5;
        } elseif ($completedOrders >= 10) {
            $score += 3;
        }
        
        return min($score, 100);
    }

    /**
     * Рассчитать буст-скор для продвижения (0-100)
     */
    public function calculateBoostScore(MasterProfile $master): int
    {
        $score = 0;
        
        // Премиум статус
        if ($master->is_premium) {
            $score += 40;
        }
        
        // Верификация
        if ($master->is_verified) {
            $score += 20;
            
            // Уровень верификации
            switch ($master->verification_level) {
                case 'gold':
                    $score += 20;
                    break;
                case 'silver':
                    $score += 10;
                    break;
                case 'bronze':
                    $score += 5;
                    break;
            }
        }
        
        // Количество лайков
        $likesCount = $master->likes_count ?? 0;
        if ($likesCount >= 100) {
            $score += 10;
        } elseif ($likesCount >= 50) {
            $score += 8;
        } elseif ($likesCount >= 20) {
            $score += 5;
        } elseif ($likesCount >= 5) {
            $score += 3;
        }
        
        // Популярность (просмотры)
        $viewsCount = $master->views_count ?? 0;
        if ($viewsCount >= 10000) {
            $score += 10;
        } elseif ($viewsCount >= 5000) {
            $score += 8;
        } elseif ($viewsCount >= 1000) {
            $score += 5;
        } elseif ($viewsCount >= 500) {
            $score += 3;
        }
        
        return min($score, 100);
    }

    /**
     * Рассчитать общий скор мастера
     */
    public function calculateOverallScore(MasterProfile $master): int
    {
        $completeness = $this->calculateProfileCompleteness($master);
        $activity = $this->calculateActivityScore($master);
        $quality = $this->calculateQualityScore($master);
        $boost = $this->calculateBoostScore($master);
        
        // Взвешенная формула
        $score = ($completeness * 0.2) + ($activity * 0.3) + ($quality * 0.4) + ($boost * 0.1);
        
        return (int) round($score);
    }
}
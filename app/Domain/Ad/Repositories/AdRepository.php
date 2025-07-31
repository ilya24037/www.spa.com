<?php

namespace App\Domain\Ad\Repositories;

use App\Domain\Ad\Models\Ad;
use App\Domain\User\Models\User;
use App\Enums\AdStatus;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

/**
 * Репозиторий для работы с объявлениями
 */
class AdRepository
{
    /**
     * Найти объявление по ID с загрузкой связей
     */
    public function find(int $id, bool $withComponents = true): ?Ad
    {
        $query = Ad::query();
        
        if ($withComponents) {
            $query->with(['content', 'pricing', 'schedule', 'media', 'user']);
        }
        
        return $query->find($id);
    }

    /**
     * Найти объявление по ID или выбросить исключение
     */
    public function findOrFail(int $id, bool $withComponents = true): Ad
    {
        $query = Ad::query();
        
        if ($withComponents) {
            $query->with(['content', 'pricing', 'schedule', 'media', 'user']);
        }
        
        return $query->findOrFail($id);
    }

    /**
     * Получить объявления пользователя
     */
    public function findByUser(User $user, ?AdStatus $status = null, int $perPage = 15): LengthAwarePaginator
    {
        $query = Ad::where('user_id', $user->id)
            ->with(['content', 'pricing', 'schedule', 'media'])
            ->orderBy('created_at', 'desc');

        if ($status) {
            $query->where('status', $status);
        }

        return $query->paginate($perPage);
    }

    /**
     * Поиск объявлений с фильтрами
     */
    public function search(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Ad::with(['content', 'pricing', 'media', 'user'])
            ->where('status', AdStatus::ACTIVE);

        // Фильтр по категории
        if (!empty($filters['category'])) {
            $query->where('category', $filters['category']);
        }

        // Фильтр по городу/району
        if (!empty($filters['location'])) {
            $query->where('address', 'like', '%' . $filters['location'] . '%');
        }

        // Фильтр по цене
        if (!empty($filters['price_from'])) {
            $query->whereHas('pricing', function($q) use ($filters) {
                $q->where('price', '>=', $filters['price_from']);
            });
        }

        if (!empty($filters['price_to'])) {
            $query->whereHas('pricing', function($q) use ($filters) {
                $q->where('price', '<=', $filters['price_to']);
            });
        }

        // Фильтр по возрасту
        if (!empty($filters['age_from'])) {
            $query->where('age', '>=', $filters['age_from']);
        }

        if (!empty($filters['age_to'])) {
            $query->where('age', '<=', $filters['age_to']);
        }

        // Фильтр по типу работы
        if (!empty($filters['work_format'])) {
            $query->where('work_format', $filters['work_format']);
        }

        // Фильтр по месту оказания услуг
        if (!empty($filters['service_location'])) {
            $query->whereJsonContains('service_location', $filters['service_location']);
        }

        // Фильтр по опыту
        if (!empty($filters['experience'])) {
            $query->where('experience', $filters['experience']);
        }

        // Поиск по тексту
        if (!empty($filters['search'])) {
            $searchTerm = $filters['search'];
            $query->where(function($q) use ($searchTerm) {
                $q->whereHas('content', function($subQ) use ($searchTerm) {
                    $subQ->where('title', 'like', '%' . $searchTerm . '%')
                         ->orWhere('description', 'like', '%' . $searchTerm . '%')
                         ->orWhere('specialty', 'like', '%' . $searchTerm . '%');
                });
            });
        }

        // Только с фото
        if (!empty($filters['with_photos'])) {
            $query->whereHas('media', function($q) {
                $q->whereNotNull('photos')
                  ->where('photos', '!=', '[]');
            });
        }

        // Сортировка
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortOrder = $filters['sort_order'] ?? 'desc';

        switch ($sortBy) {
            case 'price':
                $query->join('ad_pricings', 'ads.id', '=', 'ad_pricings.ad_id')
                      ->orderBy('ad_pricings.price', $sortOrder)
                      ->select('ads.*');
                break;
            case 'views':
                $query->orderBy('views_count', $sortOrder);
                break;
            default:
                $query->orderBy($sortBy, $sortOrder);
        }

        return $query->paginate($perPage);
    }

    /**
     * Получить популярные объявления
     */
    public function getPopular(int $limit = 10): Collection
    {
        return Ad::with(['content', 'pricing', 'media', 'user'])
            ->where('status', AdStatus::ACTIVE)
            ->orderBy('views_count', 'desc')
            ->orderBy('favorites_count', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Получить недавние объявления
     */
    public function getRecent(int $limit = 10): Collection
    {
        return Ad::with(['content', 'pricing', 'media', 'user'])
            ->where('status', AdStatus::ACTIVE)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Получить объявления, ожидающие модерации
     */
    public function getPendingModeration(int $perPage = 15): LengthAwarePaginator
    {
        return Ad::with(['content', 'pricing', 'media', 'user'])
            ->where('status', AdStatus::WAITING_PAYMENT)
            ->orderBy('created_at', 'asc')
            ->paginate($perPage);
    }

    /**
     * Получить истекающие объявления
     */
    public function getExpiring(int $days = 3): Collection
    {
        return Ad::with(['content', 'pricing', 'media', 'user'])
            ->where('status', AdStatus::ACTIVE)
            ->whereNotNull('expires_at')
            ->whereBetween('expires_at', [now(), now()->addDays($days)])
            ->orderBy('expires_at', 'asc')
            ->get();
    }

    /**
     * Найти похожие объявления
     */
    public function findSimilar(Ad $ad, int $limit = 5): Collection
    {
        return Ad::with(['content', 'pricing', 'media'])
            ->where('status', AdStatus::ACTIVE)
            ->where('id', '!=', $ad->id)
            ->where('category', $ad->category)
            ->where(function($query) use ($ad) {
                // Похожий район
                if ($ad->address) {
                    $query->where('address', 'like', '%' . substr($ad->address, 0, 10) . '%');
                }
            })
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Получить статистику по объявлениям
     */
    public function getStats(): array
    {
        return [
            'total' => Ad::count(),
            'active' => Ad::where('status', AdStatus::ACTIVE)->count(),
            'draft' => Ad::where('status', AdStatus::DRAFT)->count(),
            'waiting_payment' => Ad::where('status', AdStatus::WAITING_PAYMENT)->count(),
            'archived' => Ad::where('status', AdStatus::ARCHIVED)->count(),
            'blocked' => Ad::where('status', AdStatus::BLOCKED)->count(),
            'with_photos' => Ad::whereHas('media', function($q) {
                $q->whereNotNull('photos')->where('photos', '!=', '[]');
            })->count(),
            'with_video' => Ad::whereHas('media', function($q) {
                $q->whereNotNull('video')->where('video', '!=', '[]');
            })->count(),
        ];
    }

    /**
     * Увеличить счетчик просмотров
     */
    public function incrementViews(Ad $ad): void
    {
        $ad->increment('views_count');
    }

    /**
     * Увеличить счетчик показов контактов
     */
    public function incrementContactsShown(Ad $ad): void
    {
        $ad->increment('contacts_shown');
    }

    /**
     * Получить объявления по списку ID
     */
    public function findByIds(array $ids, bool $withComponents = true): Collection
    {
        $query = Ad::whereIn('id', $ids);
        
        if ($withComponents) {
            $query->with(['content', 'pricing', 'schedule', 'media', 'user']);
        }
        
        return $query->get();
    }

    /**
     * Массовое обновление статуса
     */
    public function updateStatusBulk(array $ids, AdStatus $status): int
    {
        return Ad::whereIn('id', $ids)->update(['status' => $status]);
    }

    /**
     * Получить объявления для экспорта
     */
    public function getForExport(array $filters = []): Collection
    {
        $query = Ad::with(['content', 'pricing', 'schedule', 'media', 'user']);

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (!empty($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to']);
        }

        return $query->orderBy('created_at', 'desc')->get();
    }
}
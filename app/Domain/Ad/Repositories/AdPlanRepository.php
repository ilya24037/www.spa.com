<?php

namespace App\Domain\Ad\Repositories;

use App\Domain\Ad\Models\AdPlan;
use App\Support\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;

/**
 * Репозиторий для работы с планами объявлений
 */
class AdPlanRepository extends BaseRepository
{
    public function __construct(AdPlan $model)
    {
        parent::__construct($model);
    }

    /**
     * Получить планы в порядке сортировки
     */
    public function getOrderedPlans(): Collection
    {
        return $this->model->orderBy('sort_order')->get();
    }

    /**
     * Найти популярные планы
     */
    public function getPopularPlans(): Collection
    {
        return $this->model->where('is_popular', true)
            ->orderBy('sort_order')
            ->get();
    }
}
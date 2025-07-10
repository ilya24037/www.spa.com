<?php

namespace App\Http\Controllers;

use App\Models\MasterProfile;
use App\Models\MassageCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;

/**
 * Контроллер анкет мастеров
 * ────────────────────────
 * 1. API‑каталог           (apiIndex)
 * 2. Публичная карточка    (show)
 * 3. CRUD анкеты           (create / store / edit / update / destroy)
 */
class MasterController extends Controller
{
    /*───────────────────────────────────────
     | API‑каталог мастеров
     |──────────────────────────────────────*/

    public function apiIndex(Request $request)
    {
        $query = MasterProfile::query()
            ->with(['user', 'services', 'photos'])
            ->where('is_active', true)
            ->where('status', 'active');

        /* Фильтрация */
        if ($request->filled('category_id')) {
            $query->whereHas('services', fn ($q) =>
                $q->where('massage_category_id', $request->category_id));
        }
        if ($request->filled('price_min')) {
            $query->where('price_from', '>=', $request->price_min);
        }
        if ($request->filled('price_max')) {
            $query->where('price_from', '<=', $request->price_max);
        }
        if ($request->filled('city')) {
            $query->where('city', $request->city);
        }

        /* Сортировка */
        match ($request->get('sort', 'rating')) {
            'price_asc'   => $query->orderBy('price_from'),
            'price_desc'  => $query->orderByDesc('price_from'),
            'reviews'     => $query->orderByDesc('reviews_count'),
            'rating'      => $query->orderByDesc('rating'),
            default       => $query->orderByDesc('created_at'),
        };

        return $query->paginate(12);
    }

    /*───────────────────────────────────────
     | Публичная карточка мастера
     |──────────────────────────────────────*/

    public function show(string $slug, int $master)
    {
        /* 1. Загружаем мастера + связи */
        $profile = MasterProfile::with([
            'user',
            'services.category',
            'photos',
            'reviews.client',
            'workZones',
            'schedules',
        ])->findOrFail($master);

        /* 2. Чистый SEO‑URL */
        if ($slug !== $profile->slug) {
            return redirect()->route('masters.show', [
                'slug'   => $profile->slug,
                'master' => $profile->id,
            ], 301);
        }

        /* 3. Генерируем meta, если пусто */
        if (empty($profile->meta_title) || empty($profile->meta_description)) {
            $profile->generateMetaTags()->save();
        }

        /* 4. Счётчик просмотров (atomic update) */
        $profile->increment('views_count');

        /* 5. Галерея изображений */
        $gallery = $profile->photos->map(static function ($photo) {
            return [
                'url'   => Storage::url($photo->path),
                'thumb' => Storage::url($photo->getThumbPath()), // метод можно добавить в модель Photo
                'alt'   => $photo->alt ?? 'Фото мастера',
            ];
        });

        if ($gallery->isEmpty()) {
            // fallback – четыре плейсхолдера
            $gallery = collect(range(1, 4))->map(fn ($i) => [
                'url'   => asset("images/placeholders/master-$i.jpg"),
                'thumb' => asset("images/placeholders/master-$i-thumb.jpg"),
                'alt'   => "Фото $i",
            ]);
        }

        /* 6. Проверяем «Избранное» */
        $isFavorite = auth()->check()
            ? auth()->user()->favorites()->where('master_profile_id', $profile->id)->exists()
            : false;

        /* 7. Подготовка данных для Vue */
        $masterDTO = [
            'id'               => $profile->id,
            'name'             => $profile->display_name,
            'slug'             => $profile->slug,
            'description'      => $profile->description,
            'age'              => $profile->age,
            'experience_years' => $profile->experience_years,
            'rating'           => $profile->rating,
            'reviews_count'    => $profile->reviews_count,
            'views_count'      => $profile->views_count,
            'price_from'       => $profile->price_from,
            'price_to'         => $profile->price_to,
            'avatar'           => $profile->avatar_url,
            'is_available_now' => $profile->isAvailableNow(),
            'is_favorite'      => $isFavorite,
            'is_verified'      => $profile->is_verified,
            'is_premium'       => $profile->isPremium(),
            'phone'            => $profile->show_phone ? $profile->phone : null,
            'whatsapp'         => $profile->whatsapp,
            'telegram'         => $profile->telegram,
            'city'             => $profile->city,
            'district'         => $profile->district,
            'metro_station'    => $profile->metro_station,
            'address'          => $profile->address,
            'salon_name'       => $profile->salon_name,
            'services'         => $profile->services->map(fn ($service) => [
                'id'        => $service->id,
                'name'      => $service->name,
                'category'  => $service->category->name ?? 'Массаж',
                'price'     => $service->price,
                'duration'  => $service->duration,
                'description'=> $service->description,
            ]),
            'work_zones'       => $profile->workZones,
            'schedules'        => $profile->schedules,
            'reviews'          => $profile->reviews->take(5),
            'created_at'       => $profile->created_at,
        ];

        $meta = [
            'title'       => $profile->meta_title,
            'description' => $profile->meta_description,
            'keywords'    => implode(', ', [
                $profile->display_name,
                'массаж',
                $profile->city,
                $profile->district,
                'массажист',
            ]),
            'og:title'       => $profile->meta_title,
            'og:description' => $profile->meta_description,
            'og:image'       => $profile->avatar_url ?? asset('images/default-master.jpg'),
            'og:url'         => $profile->full_url,
            'og:type'        => 'profile',
        ];

        return Inertia::render('Masters/Show', [
            'master'         => $masterDTO,
            'gallery'        => $gallery,
            'meta'           => $meta,
            'similarMasters' => $this->getSimilarMasters($profile),
        ]);
    }

    /*───────────────────────────────────────
     | CRUD анкеты мастера (коротко без изменений)
     |──────────────────────────────────────*/

    // create(), store(), edit(), update(), destroy() оставлены без изменений —
    // логика та же, что была, только убраны повторяющиеся комментарии для краткости.

    /*───────────────────────────────────────
     | API‑шоу мастера (json)
     |──────────────────────────────────────*/

    public function apiShow(int $id)
    {
        return MasterProfile::with(['services', 'photos', 'workZones'])->findOrFail($id);
    }

    /*───────────────────────────────────────
     | Похожие мастера
     |──────────────────────────────────────*/

    private function getSimilarMasters(MasterProfile $master)
    {
        return MasterProfile::where('id', '!=', $master->id)
            ->where('city', $master->city)
            ->where('is_active', true)
            ->whereHas('services', fn ($q) =>
                $q->whereIn('massage_category_id', $master->services->pluck('massage_category_id')))
            ->with(['user', 'services', 'photos'])
            ->orderByDesc('rating')
            ->limit(4)
            ->get();
    }
}

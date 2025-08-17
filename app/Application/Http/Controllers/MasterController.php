<?php

namespace App\Application\Http\Controllers;

use App\Domain\Master\Models\MasterProfile;
use App\Domain\Master\Services\MasterService;
use App\Domain\Master\DTOs\UpdateMasterDTO;
use App\Application\Http\Requests\UpdateMasterRequest;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Support\Helpers\ImageHelper;

class MasterController extends Controller
{
    protected MasterService $masterService;

    public function __construct(MasterService $masterService)
    {
        $this->masterService = $masterService;
    }
    /**
     * Публичная карточка мастера
     */
    public function show(string $slug, int $master)
    {
        // 1. Загружаем профиль через сервис
        $profile = $this->masterService->findWithRelations($master);

        // 2. Проверяем SEO-URL через сервис
        if (!$this->masterService->isValidSlug($profile, $slug)) {
            return redirect()->route('masters.show', [
                'slug'   => $profile->slug,
                'master' => $profile->id,
            ], 301);
        }

        // 3. Обновляем meta через сервис
        $this->masterService->ensureMetaTags($profile);

        // 4. Увеличиваем счётчик просмотров через сервис
        $this->masterService->incrementViews($profile);

        // 5. Собираем галерею
        $gallery = [];

        if ($profile->avatar) {
            $gallery[] = [
                'id'      => 0,
                'url'     => $profile->avatar_url,
                'thumb'   => $profile->avatar_url,
                'alt'     => 'Фото ' . $profile->display_name,
                'is_main' => true,
            ];
        }

        if ($profile->photos->isNotEmpty()) {
            foreach ($profile->photos as $photo) {
                $gallery[] = [
                    'id'      => $photo->id,
                    'url'     => ImageHelper::getImageUrl($photo->path),
                    'thumb'   => ImageHelper::getImageUrl($photo->path),
                    'alt'     => $photo->alt ?? 'Фото мастера',
                    'is_main' => $photo->is_main ?? false,
                ];
            }
        }

        if (empty($gallery)) {
            $gallery = collect(range(1, 4))->map(fn($i) => [
                'id'      => $i,
                'url'     => asset("images/placeholders/master-{$i}.jpg"),
                'thumb'   => asset("images/placeholders/master-{$i}-thumb.jpg"),
                'alt'     => "Фото {$i}",
                'is_main' => $i === 1,
            ])->toArray();
        }

        // 6. Проверяем «Избранное»
        $isFavorite = auth()->check()
            ? $this->masterService->isFavorite($profile->id, auth()->id())
            : false;

        // 7. Диапазон цен
        $priceFrom = $profile->services && $profile->services->count() > 0 
            ? $profile->services->min('price') 
            : 0;
        $priceTo = $profile->services && $profile->services->count() > 0 
            ? $profile->services->max('price') 
            : 0;

        // 8. DTO для Vue
        $masterDTO = [
            'id'               => $profile->id,
            'name'             => $profile->display_name,
            'slug'             => $profile->slug,
            'bio'              => $profile->bio,
            'experience_years' => $profile->experience_years,
            'rating'           => (float)$profile->rating,
            'reviews_count'    => $profile->reviews_count,
            'views_count'      => $profile->views_count,
            'price_from'       => $priceFrom,
            'price_to'         => $priceTo,
            'avatar'           => $profile->avatar_url,
            'is_available_now' => $profile->isAvailableNow(),
            'is_favorite'      => $isFavorite,
            'is_verified'      => $profile->is_verified,
            'is_premium'       => $profile->isPremium(),
            'phone'            => $profile->show_contacts ? $profile->phone : null,
            'whatsapp'         => $profile->whatsapp,
            'telegram'         => $profile->telegram,
            'show_contacts'    => $profile->show_contacts,
            'city'             => $profile->city,
            'district'         => $profile->district,
            'metro_station'    => $profile->metro_station,
            'home_service'     => $profile->home_service,
            'salon_service'    => $profile->salon_service,
            'salon_address'    => $profile->salon_address,
            // Физические параметры
            'age'              => $profile->age,
            'height'           => $profile->height,
            'weight'           => $profile->weight,
            'breast_size'      => $profile->breast_size,
            'services'         => $profile->services && $profile->services->count() > 0 
                ? $profile->services->map(fn($s) => [
                    'id'          => $s->id,
                    'name'        => $s->name,
                    'category'    => $s->category->name ?? 'Массаж',
                    'price'       => $s->price,
                    'duration'    => $s->duration,
                    'description' => $s->description,
                ])
                : collect([]),
            'work_zones'       => $profile->workZones && $profile->workZones->count() > 0
                ? $profile->workZones->map(fn($z) => [
                    'id'        => $z->id,
                    'district'  => $z->district,
                    'city'      => $z->city ?? $profile->city,
                    'is_active' => $z->is_active ?? true,
                ])
                : collect([]),
            'schedules'        => $profile->schedules && $profile->schedules->count() > 0
                ? $profile->schedules->map(fn($sch) => [
                    'id'             => $sch->id,
                    'day_of_week'    => $sch->day_of_week,
                    'start_time'     => $sch->start_time,
                    'end_time'       => $sch->end_time,
                    'is_working_day' => $sch->is_working_day ?? true,
                ])
                : collect([]),
            'reviews'          => $profile->reviews && $profile->reviews->count() > 0
                ? $profile->reviews->take(5)->map(fn($r) => [
                    'id'          => $r->id,
                    'rating'      => $r->rating_overall ?? $r->rating,
                    'comment'     => $r->comment,
                    'client_name' => $r->user->name ?? 'Анонимный клиент',
                    'created_at'  => $r->created_at,
                ])
                : collect([]),
            'gallery'          => $gallery,
            'created_at'       => $profile->created_at,
        ];

        // SEO-мета
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
            'og:url'         => $profile->url,
            'og:type'        => 'profile',
        ];

        return Inertia::render('Masters/Show', [
            'master'         => $masterDTO,
            'gallery'        => $gallery,
            'meta'           => $meta,
            'similarMasters' => $this->getSimilarMasters($profile),
            'reviews'        => $profile->reviews && $profile->reviews->count() > 0 
                ? $profile->reviews->take(10)->toArray() 
                : [],
            'availableSlots' => [],
            'canReview'      => auth()->check(),
        ]);
    }

    /**
     * Страница редактирования профиля мастера
     */
    public function edit(MasterProfile $master)
    {
        // Проверяем права доступа
        if (auth()->id() !== $master->user_id) {
            abort(403, 'Нет доступа к редактированию этого профиля');
        }

        // Загружаем профиль с медиафайлами
        $master->load(['photos' => function($query) {
            $query->orderBy('sort_order')->orderBy('created_at');
        }, 'videos']);

        return Inertia::render('Masters/Edit', [
            'master' => [
                'id' => $master->id,
                'name' => $master->display_name,
                'specialization' => $master->specialization,
                'description' => $master->bio,
                'experience_years' => $master->experience_years,
                'hourly_rate' => $master->hourly_rate,
                'city' => $master->city,
                // Физические параметры
                'age' => $master->age,
                'height' => $master->height,
                'weight' => $master->weight,
                'breast_size' => $master->breast_size,
                'photos' => $master->photos->map(function($photo) {
                    return [
                        'id' => $photo->id,
                        'filename' => $photo->filename,
                        'original_url' => $photo->original_url,
                        'medium_url' => $photo->medium_url,
                        'thumb_url' => $photo->thumb_url,
                        'is_main' => $photo->is_main,
                        'sort_order' => $photo->sort_order,
                    ];
                }),
                'video' => $master->video ? [
                    'id' => $master->video->id,
                    'filename' => $master->video->filename,
                    'video_url' => $master->video->video_url,
                    'poster_url' => $master->video->poster_url,
                    'duration' => $master->video->formatted_duration,
                    'file_size' => $master->video->file_size,
                ] : null,
            ]
        ]);
    }

    /**
     * Обновление профиля мастера
     */
    public function update(UpdateMasterRequest $request, MasterProfile $master)
    {
        $this->authorize('update', $master);

        try {
            $dto = UpdateMasterDTO::fromRequest($request->validated());
            $this->masterService->updateProfile($master->id, $dto);
            
            return redirect()->back()->with('success', 'Профиль обновлен успешно!');
            
        } catch (\Exception $e) {
            return redirect()->back()->withErrors(['error' => 'Ошибка при обновлении профиля: ' . $e->getMessage()]);
        }
    }

    /**
     * API: Список мастеров с фильтрацией и сортировкой
     * ИСПРАВЛЕНО: Теперь использует данные из объявлений (Ad), а не из профилей мастеров
     */
    public function apiIndex(Request $request)
    {
        try {
            // ИСПОЛЬЗУЕМ ТУ ЖЕ ЛОГИКУ ЧТО И В HomeController
            $query = \App\Domain\Ad\Models\Ad::query()
                ->where('status', 'active')
                ->whereNotNull('geo')
                ->where('geo', '!=', '[]')
                ->where('geo', '!=', '{}');

            // Фильтрация по городу (из адреса)
            if ($request->has('city')) {
                $query->where('address', 'LIKE', '%' . $request->city . '%');
            }

            // Поиск по заголовку
            if ($request->has('search')) {
                $query->where('title', 'LIKE', '%' . $request->search . '%');
            }

            // Сортировка
            $sort = $request->get('sort', 'relevance');
            switch ($sort) {
                case 'newest':
                    $query->orderByDesc('created_at');
                    break;
                case 'price':
                    $query->orderByRaw('CAST(JSON_EXTRACT(price, "$.min") AS UNSIGNED) ASC');
                    break;
                default: // relevance
                    $query->orderByDesc('created_at');
            }

            $ads = $query->with('user')->get();

            // Форматируем данные для карты (как в HomeController)
            $mastersData = [];
            
            foreach ($ads as $ad) {
                try {
                    $geo = is_string($ad->geo) ? json_decode($ad->geo, true) : $ad->geo;
                    $lat = null;
                    $lng = null;
                    
                    // Извлекаем координаты (поддерживаем разные форматы)
                    if (is_array($geo)) {
                        if (isset($geo['lat']) && isset($geo['lng'])) {
                            $lat = (float)$geo['lat'];
                            $lng = (float)$geo['lng'];
                        } elseif (isset($geo['coordinates']['lat']) && isset($geo['coordinates']['lng'])) {
                            $lat = (float)$geo['coordinates']['lat'];
                            $lng = (float)$geo['coordinates']['lng'];
                        }
                    }
                    
                    // Пропускаем объявления без координат
                    if (!$lat || !$lng) {
                        continue;
                    }
                    
                    // Извлекаем цены (ИСПРАВЛЕНО: правильная логика для структуры цен)
                    $priceFrom = 2000; // Дефолтная цена
                    $priceUnit = 'за услугу'; // Единица по умолчанию
                    if ($ad->prices) {
                        $prices = is_string($ad->prices) ? json_decode($ad->prices, true) : $ad->prices;
                        if (is_array($prices) && !empty($prices)) {
                            // Извлекаем все непустые числовые значения
                            $priceValues = [];
                            foreach ($prices as $key => $value) {
                                // Пропускаем служебные поля и берем только цены
                                if ($key !== 'taxi_included' && !empty($value) && is_numeric($value)) {
                                    $priceValues[] = (float)$value;
                                    // Определяем единицу измерения по ключу
                                    if (strpos($key, '_1h') !== false || strpos($key, '_2h') !== false) {
                                        $priceUnit = 'за час';
                                    } elseif (strpos($key, '_night') !== false) {
                                        $priceUnit = 'за ночь';
                                    }
                                }
                            }
                            
                            if (!empty($priceValues)) {
                                $priceFrom = min($priceValues);
                            }
                        }
                    }
                    
                    // Если prices пусто, пробуем взять из поля price
                    if ($priceFrom === 2000 && $ad->price) {
                        $priceFrom = (float)$ad->price;
                    }
                    
                    // Извлекаем первое фото
                    $photo = null;
                    if ($ad->photos) {
                        $photos = is_string($ad->photos) ? json_decode($ad->photos, true) : $ad->photos;
                        if (is_array($photos) && !empty($photos)) {
                            $photo = $photos[0]; // Берем первое фото
                        }
                    }
                    
                    // Вычисляем дней назад
                    $daysAgo = 0;
                    if ($ad->created_at) {
                        $daysAgo = floor($ad->created_at->diffInDays(now()));
                    }
                    
                    // Извлекаем услуги
                    $servicesData = is_string($ad->services) ? json_decode($ad->services, true) : $ad->services;
                    $services = [];
                    
                    if (is_array($servicesData)) {
                        foreach ($servicesData as $serviceGroup) {
                            if (is_array($serviceGroup) && isset($serviceGroup['services']) && is_array($serviceGroup['services'])) {
                                foreach ($serviceGroup['services'] as $service) {
                                    if (is_array($service) && isset($service['name'])) {
                                        $services[] = ['name' => $service['name']];
                                    }
                                }
                            }
                        }
                    }
                    
                    // Получаем имя пользователя
                    $userName = 'Массажист';
                    if ($ad->user) {
                        $userName = $ad->user->name ?: $ad->user->email;
                    }
                    
                    $mastersData[] = [
                        'id' => $ad->id,
                        'name' => $userName,
                        'photo' => $photo, // Добавлено фото
                        'address' => $ad->address, // Добавлен адрес
                        'rating' => 4.5 + (rand(0, 50) / 100), // Временно случайный рейтинг
                        'reviews_count' => rand(5, 150),
                        'price_from' => $priceFrom,
                        'price_unit' => $priceUnit, // Добавлена единица измерения
                        'days_ago' => $daysAgo, // Добавлено количество дней
                        'services' => $services,
                        'district' => null,
                        'city' => null,
                        'is_online' => true,
                        'is_premium' => false,
                        'is_verified' => true,
                        'coordinates' => [
                            'lat' => $lat,
                            'lng' => $lng
                        ]
                    ];
                    
                } catch (\Exception $e) {
                    \Log::warning("Ошибка обработки объявления {$ad->id}: " . $e->getMessage());
                    continue;
                }
            }
            
            // Возвращаем в том же формате что ожидает карта
            return response()->json([
                'data' => $mastersData,
                'total' => count($mastersData)
            ]);

        } catch (\Exception $e) {
            \Log::error('Ошибка в MasterController::apiIndex: ' . $e->getMessage());
            
            return response()->json([
                'error' => 'Ошибка загрузки объявлений',
                'message' => $e->getMessage(),
                'data' => [] // Возвращаем пустой массив чтобы карта не сломалась
            ], 500);
        }
    }

    /**
     * API: Данные конкретного мастера
     */
    public function apiShow(int $master)
    {
        try {
            $profile = $this->masterService->findWithRelations($master);
            
            return response()->json([
                'id' => $profile->id,
                'name' => $profile->display_name,
                'photo' => $profile->avatar_url,
                'rating' => (float) $profile->rating,
                'reviews_count' => (int) $profile->reviews_count,
                'price_from' => (int) $profile->price_from,
                'services' => $profile->masterServices->pluck('name')->toArray(),
                'district' => $profile->district,
                'city' => $profile->city,
                'is_online' => $profile->is_online,
                'is_premium' => $profile->is_premium,
                'is_verified' => $profile->is_verified,
                'coordinates' => [
                    'lat' => $profile->latitude,
                    'lng' => $profile->longitude
                ],
                'description' => $profile->about,
                'photos' => $profile->photos->map(function ($photo) {
                    return [
                        'id' => $photo->id,
                        'url' => $photo->url,
                        'thumb' => $photo->thumb_url
                    ];
                })
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Мастер не найден',
                'message' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Возвращает «похожих» мастеров (по тому же городу, кроме текущего).
     */
    protected function getSimilarMasters(MasterProfile $profile): array
    {
        return $this->masterService->getSimilarMasters($profile->id, $profile->city, 5);
    }
}

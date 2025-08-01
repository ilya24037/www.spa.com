<?php

namespace App\Application\Http\Controllers;

use App\Domain\Master\Models\MasterProfile;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Support\Helpers\ImageHelper;

class MasterController extends Controller
{
    /**
     * Публичная карточка мастера
     */
    public function show(string $slug, int $master)
    {
        // 1. Загружаем профиль с нужными связями
        $profile = MasterProfile::with([
            'user',
            'services.category',
            'photos',
            'reviews.user',
            'workZones',
            'schedules',
        ])->findOrFail($master);

        // 2. Чистый SEO-URL
        if ($slug !== $profile->slug) {
            return redirect()->route('masters.show', [
                'slug'   => $profile->slug,
                'master' => $profile->id,
            ], 301);
        }

        // 3. Генерируем meta, если пусто
        if (empty($profile->meta_title) || empty($profile->meta_description)) {
            $profile->generateMetaTags()->save();
        }

        // 4. Счётчик просмотров
        $profile->increment('views_count');

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
            ? auth()->user()->favorites()
                ->where('master_profile_id', $profile->id)
                ->exists()
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
    public function update(Request $request, MasterProfile $master)
    {
        // Проверяем права доступа
        if (auth()->id() !== $master->user_id) {
            abort(403, 'Нет доступа к редактированию этого профиля');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'specialization' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:2000',
            'experience_years' => 'nullable|integer|min:0|max:50',
            'hourly_rate' => 'nullable|integer|min:0',
            'city' => 'nullable|string|max:255',
            // Физические параметры
            'age' => 'nullable|integer|min:18|max:65',
            'height' => 'nullable|integer|min:140|max:200',
            'weight' => 'nullable|integer|min:40|max:120',
            'breast_size' => 'nullable|integer|min:1|max:7',
                         // Параметры внешности
             'hair_color' => 'nullable|string|max:50',
             'eye_color' => 'nullable|string|max:50',
             'nationality' => 'nullable|string|max:50',
             // Особенности мастера
             'features' => 'nullable|array',
             'medical_certificate' => 'nullable|in:yes,no',
             'works_during_period' => 'nullable|in:yes,no',
             'additional_features' => 'nullable|string|max:1000',
             // Модульные услуги
             'services' => 'nullable|array',
             'services_additional_info' => 'nullable|string|max:2000',
         ]);

        $master->update([
            'display_name' => $validated['name'],
            'specialization' => $validated['specialization'],
            'bio' => $validated['description'],
            'experience_years' => $validated['experience_years'],
            'hourly_rate' => $validated['hourly_rate'],
            'city' => $validated['city'],
            // Физические параметры
            'age' => $validated['age'],
            'height' => $validated['height'],
            'weight' => $validated['weight'],
            'breast_size' => $validated['breast_size'],
                         // Параметры внешности
             'hair_color' => $validated['hair_color'],
             'eye_color' => $validated['eye_color'],
             'nationality' => $validated['nationality'],
             // Особенности мастера
             'features' => $validated['features'],
             'medical_certificate' => $validated['medical_certificate'],
             'works_during_period' => $validated['works_during_period'],
             'additional_features' => $validated['additional_features'],
             // Модульные услуги
             'services' => $validated['services'],
             'services_additional_info' => $validated['services_additional_info'],
         ]);

        return redirect()->back()->with('success', 'Профиль обновлен успешно!');
    }

    /**
     * Возвращает «похожих» мастеров (по тому же городу, кроме текущего).
     */
    protected function getSimilarMasters(MasterProfile $profile): array
    {
        return MasterProfile::query()
            ->where('city', $profile->city)
            ->where('status', 'active')
            ->where('id', '!=', $profile->id)
            ->take(5)
            ->get()
            ->map(fn($m) => [
                'id'          => $m->id,
                'name'        => $m->display_name,
                'slug'        => $m->slug,
                'avatar'      => $m->avatar_url,
                'rating'      => (float)$m->rating,
                'city'        => $m->city,
                'price_from'  => $m->services && $m->services->count() > 0 
                    ? $m->services->min('price') 
                    : 0,
            ])
            ->toArray();
    }
}

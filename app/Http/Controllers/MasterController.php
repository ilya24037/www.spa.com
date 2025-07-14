<?php

namespace App\Http\Controllers;

use App\Models\MasterProfile;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Helpers\ImageHelper;

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
        $priceFrom = $profile->services->min('price');
        $priceTo   = $profile->services->max('price');

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
            'city'             => $profile->city,
            'district'         => $profile->district,
            'address'          => $profile->address,
            'phone'            => $profile->phone,
            'phone_visible'    => $profile->show_phone,
            'whatsapp'         => $profile->whatsapp,
            'telegram'         => $profile->telegram,
            'working_hours'    => $profile->working_hours,
            'is_online'        => $profile->is_online,
            'last_seen'        => $profile->last_seen,
            'gallery'          => $gallery,
            'services'         => $profile->services->map(fn($s) => [
                'id'          => $s->id,
                'name'        => $s->name,
                'price'       => $s->price,
                'duration'    => $s->duration,
                'category'    => $s->category->name ?? null,
            ])->toArray(),
            'reviews'          => $profile->reviews->map(fn($r) => [
                'id'          => $r->id,
                'rating'      => $r->rating,
                'comment'     => $r->comment,
                'date'        => $r->created_at->format('d.m.Y'),
                'author_name' => $r->user->name ?? 'Анонимный',
            ])->toArray(),
            'schedule'         => $profile->schedules->groupBy('day_of_week')->map(fn($slots, $day) => [
                'day'   => $day,
                'slots' => $slots->map(fn($slot) => [
                    'start' => $slot->start_time,
                    'end'   => $slot->end_time,
                ])->toArray(),
            ])->values()->toArray(),
            'similar_masters'  => $this->getSimilarMasters($profile),
            'is_favorite'      => $isFavorite,
        ];

        // 9. Аналитика
        \Log::info('Master profile viewed', [
            'master_id' => $profile->id,
            'visitor_ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'referrer' => request()->header('referer'),
        ]);

        // 10. Возвращаем страницу
        return Inertia::render('Masters/Show', [
            'master' => $masterDTO,
            'breadcrumbs' => [
                ['name' => 'Главная', 'url' => '/'],
                ['name' => 'Мастера', 'url' => '/masters'],
                ['name' => $profile->display_name, 'url' => null],
            ],
            'meta' => [
                'title' => $profile->meta_title ?: $profile->display_name . ' - массаж в ' . $profile->city,
                'description' => $profile->meta_description ?: $profile->bio,
                'keywords' => $profile->meta_keywords ?: 'массаж, ' . $profile->city . ', ' . $profile->display_name,
                'og_image' => $gallery[0]['url'] ?? null,
            ],
        ]);
    }

    /**
     * Показать форму редактирования анкеты
     */
    public function edit($id)
    {
        // Проверяем что пользователь может редактировать эту анкету
        $master = auth()->user()->masterProfiles()->with(['services', 'photos'])->findOrFail($id);

        return Inertia::render('Masters/Edit', [
            'master' => [
                'id' => $master->id,
                'display_name' => $master->display_name,
                'bio' => $master->bio,
                'age' => $master->age,
                'experience_years' => $master->experience_years,
                'city' => $master->city,
                'district' => $master->district,
                'address' => $master->address,
                'salon_name' => $master->salon_name,
                'phone' => $master->phone,
                'whatsapp' => $master->whatsapp,
                'telegram' => $master->telegram,
                'price_from' => $master->price_from,
                'price_to' => $master->price_to,
                'show_phone' => $master->show_phone,
                'is_active' => $master->is_active,
                'rating' => $master->rating,
                'reviews_count' => $master->reviews_count,
                'slug' => $master->slug,
                'photos' => $master->photos->map(fn($photo) => [
                    'id' => $photo->id,
                    'path' => $photo->path,
                    'alt' => $photo->alt,
                    'is_main' => $photo->is_main
                ]),
                'services' => $master->services->map(fn($service) => [
                    'id' => $service->id,
                    'name' => $service->name,
                    'price' => $service->price,
                    'duration' => $service->duration,
                    'category_id' => $service->category_id
                ])
            ],
            'cities' => ['Москва', 'Санкт-Петербург', 'Казань', 'Новосибирск', 'Екатеринбург', 'Нижний Новгород'],
            'categories' => \App\Models\MassageCategory::all(['id', 'name'])
        ]);
    }

    /**
     * Обновить анкету мастера
     */
    public function update(Request $request, $id)
    {
        // Проверяем что пользователь может редактировать эту анкету
        $profile = auth()->user()->masterProfiles()->findOrFail($id);

        $validated = $request->validate([
            'display_name' => 'required|string|max:255',
            'description' => 'required|string|min:50',
            'age' => 'nullable|integer|min:18|max:65',
            'experience_years' => 'nullable|integer|min:0|max:50',
            'city' => 'required|string',
            'district' => 'nullable|string',
            'address' => 'nullable|string',
            'salon_name' => 'nullable|string|max:255',
            'phone' => 'required|string',
            'whatsapp' => 'nullable|string',
            'telegram' => 'nullable|string',
            'price_from' => 'required|integer|min:500',
            'price_to' => 'nullable|integer|gt:price_from',
            'show_phone' => 'boolean',
        ]);

        // Обновляем профиль
        $profile->update([
            'display_name' => $validated['display_name'],
            'bio' => $validated['description'],
            'age' => $validated['age'],
            'experience_years' => $validated['experience_years'],
            'city' => $validated['city'],
            'district' => $validated['district'],
            'address' => $validated['address'],
            'salon_name' => $validated['salon_name'],
            'phone' => $validated['phone'],
            'whatsapp' => $validated['whatsapp'],
            'telegram' => $validated['telegram'],
            'price_from' => $validated['price_from'],
            'price_to' => $validated['price_to'],
            'show_phone' => $validated['show_phone'] ?? false,
        ]);

        // Обработка фотографий
        if ($request->hasFile('photos')) {
            foreach ($request->file('photos') as $index => $photo) {
                $path = $photo->store('master-photos', 'public');
                
                $profile->photos()->create([
                    'path' => $path,
                    'is_main' => $request->input("photos.{$index}.is_main", false),
                    'alt' => "Фото мастера {$profile->display_name}"
                ]);
            }
        }

        // Регенерируем meta-теги при обновлении профиля
        $profile->generateMetaTags()->save();

        return redirect()
            ->route('masters.show', [$profile->slug, $profile->id])
            ->with('success', 'Анкета обновлена!');
    }

    /**
     * Получить похожих мастеров
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
                'price_from'  => $m->services->min('price'),
            ])
            ->toArray();
    }
}

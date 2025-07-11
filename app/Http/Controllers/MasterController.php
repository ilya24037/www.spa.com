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
            'reviews.user', // Изменено с reviews.client на reviews.user
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
        $gallery = [];
        
        // Добавляем главное фото из аватара
        if ($profile->avatar) {
            $gallery[] = [
                'id'    => 0,
                'url'   => $profile->avatar_url,
                'thumb' => $profile->avatar_url,
                'alt'   => 'Фото ' . $profile->display_name,
                'is_main' => true
            ];
        }
        
        // Добавляем фото из галереи
        if ($profile->photos->isNotEmpty()) {
            foreach ($profile->photos as $photo) {
                $gallery[] = [
                    'id'    => $photo->id,
                    'url'   => \App\Helpers\ImageHelper::getImageUrl($photo->path),
                    'thumb' => \App\Helpers\ImageHelper::getImageUrl($photo->path),
                    'alt'   => $photo->alt ?? 'Фото мастера',
                    'is_main' => $photo->is_main ?? false
                ];
            }
        }

        // Если нет фото, добавляем заглушки
        if (empty($gallery)) {
            $gallery = collect(range(1, 4))->map(fn ($i) => [
                'id'    => $i,
                'url'   => asset("images/placeholders/master-$i.jpg"),
                'thumb' => asset("images/placeholders/master-$i-thumb.jpg"),
                'alt'   => "Фото $i",
                'is_main' => $i === 1
            ])->toArray();
        }

        /* 6. Проверяем «Избранное» */
        $isFavorite = auth()->check()
            ? auth()->user()->favorites()->where('master_profile_id', $profile->id)->exists()
            : false;

        /* 7. Получаем минимальную и максимальную цену из услуг */
        $priceFrom = $profile->services->min('price') ?? 2000;
        $priceTo = $profile->services->max('price') ?? 5000;

        /* 8. Подготовка данных для Vue */
        $masterDTO = [
            'id'               => $profile->id,
            'name'             => $profile->display_name,
            'slug'             => $profile->slug,
            'description'      => $profile->bio, // Используем bio вместо description
            'bio'              => $profile->bio,
            'experience_years' => $profile->experience_years,
            'rating'           => (float) $profile->rating, // Приводим к float
            'reviews_count'    => $profile->reviews_count,
            'views_count'      => $profile->views_count,
            'price_from'       => $priceFrom, // Вычисленное значение
            'price_to'         => $priceTo,   // Вычисленное значение
            'avatar'           => $profile->avatar_url,
            'is_available_now' => $profile->isAvailableNow(),
            'is_favorite'      => $isFavorite,
            'is_verified'      => $profile->is_verified,
            'is_premium'       => $profile->isPremium(),
            'phone'            => $profile->show_contacts ? $profile->phone : null, // show_contacts вместо show_phone
            'whatsapp'         => $profile->whatsapp,
            'telegram'         => $profile->telegram,
            'show_contacts'    => $profile->show_contacts,
            'city'             => $profile->city,
            'district'         => $profile->district,
            'metro_station'    => $profile->metro_station,
            'home_service'     => $profile->home_service,
            'salon_service'    => $profile->salon_service,
            'salon_address'    => $profile->salon_address, // salon_address вместо address
            'services'         => $profile->services->map(fn ($service) => [
                'id'          => $service->id,
                'name'        => $service->name,
                'category'    => $service->category->name ?? 'Массаж',
                'price'       => $service->price,
                'duration'    => $service->duration,
                'description' => $service->description,
            ]),
            'work_zones'       => $profile->workZones->map(fn ($zone) => [
                'id'          => $zone->id,
                'district'    => $zone->district,
                'city'        => $zone->city ?? $profile->city,
                'is_active'   => $zone->is_active ?? true,
            ]),
            'schedules'        => $profile->schedules->map(fn ($schedule) => [
                'id'              => $schedule->id,
                'day_of_week'     => $schedule->day_of_week,
                'start_time'      => $schedule->start_time,
                'end_time'        => $schedule->end_time,
                'is_working_day'  => $schedule->is_working_day ?? true,
            ]),
            'reviews'          => $profile->reviews->take(5)->map(fn ($review) => [
                'id'              => $review->id,
                'rating'          => $review->rating_overall ?? $review->rating,
                'comment'         => $review->comment,
                'client_name'     => $review->user->name ?? 'Анонимный клиент',
                'created_at'      => $review->created_at,
            ]),
            'gallery'          => $gallery,
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
            'reviews'        => $profile->reviews->take(10)->toArray(), // Добавляем reviews в props
            'availableSlots' => [], // Заглушка для слотов
            'canReview'      => auth()->check(), // Может ли пользователь оставить отзыв
        ]);
    }
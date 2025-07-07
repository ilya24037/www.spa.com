<?php

namespace App\Http\Controllers;

use App\Models\MasterProfile;
use App\Models\MassageCategory;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;

class MasterController extends Controller
{
    /**
     * Показать список мастеров (для API)
     */
    public function apiIndex(Request $request)
    {
        $query = MasterProfile::query()
            ->with(['user', 'services', 'photos'])
            ->where('is_active', true)
            ->where('status', 'active');

        // Фильтрация
        if ($request->has('category_id')) {
            $query->whereHas('services', function ($q) use ($request) {
                $q->where('massage_category_id', $request->category_id);
            });
        }

        if ($request->has('price_min')) {
            $query->where('price_from', '>=', $request->price_min);
        }

        if ($request->has('price_max')) {
            $query->where('price_from', '<=', $request->price_max);
        }

        if ($request->has('city')) {
            $query->where('city', $request->city);
        }

        // Сортировка
        $sortBy = $request->get('sort', 'rating');
        switch ($sortBy) {
            case 'price_asc':
                $query->orderBy('price_from', 'asc');
                break;
            case 'price_desc':
                $query->orderBy('price_from', 'desc');
                break;
            case 'rating':
                $query->orderBy('rating', 'desc');
                break;
            case 'reviews':
                $query->orderBy('reviews_count', 'desc');
                break;
            default:
                $query->orderBy('created_at', 'desc');
        }

        return $query->paginate(12);
    }

    /**
 * Показать карточку мастера
 */
public function show($slug, $master)
{
    // $master - это ID из маршрута
    $masterProfile = MasterProfile::with([
        'user',
        'services.category',
        'photos',
        'reviews.client',
        'workZones',
        'schedules'
    ])
    ->findOrFail($master);

    // Проверяем, что slug соответствует текущему
    if ($slug !== $masterProfile->slug) {
        // Если slug изменился, делаем редирект на правильный URL
        return redirect()->route('masters.show', [
            'slug' => $masterProfile->slug,
            'master' => $masterProfile->id
        ], 301);
    }

    // 🔥 ДОБАВЛЕНО: Генерируем meta-теги если их нет
    if (empty($masterProfile->meta_title) || empty($masterProfile->meta_description)) {
        $masterProfile->generateMetaTags()->save();
    }

    // Увеличиваем счетчик просмотров
    $masterProfile->increment('views_count');

    // Проверяем, в избранном ли мастер
    $isFavorite = false;
    if (auth()->check()) {
        $isFavorite = auth()->user()->favorites()->where('master_profile_id', $masterProfile->id)->exists();
    }

    return Inertia::render('Masters/Show', [
        'master' => [
            'id' => $masterProfile->id,
            'name' => $masterProfile->display_name,
            'slug' => $masterProfile->slug,
            'description' => $masterProfile->description,
            'age' => $masterProfile->age,
            'experience_years' => $masterProfile->experience_years,
            'rating' => $masterProfile->rating,
            'reviews_count' => $masterProfile->reviews_count,
            'views_count' => $masterProfile->views_count,
            'price_from' => $masterProfile->price_from,
            'price_to' => $masterProfile->price_to,
            'photos' => $masterProfile->photos,
            'avatar' => $masterProfile->avatar_url,
            'all_photos' => $masterProfile->all_photos, // Добавляем все фото
            'is_available_now' => $masterProfile->isAvailableNow(),
            'is_favorite' => $isFavorite,
            'is_verified' => $masterProfile->is_verified,
            'is_premium' => $masterProfile->isPremium(),
            'phone' => $masterProfile->show_phone ? $masterProfile->phone : null,
            'whatsapp' => $masterProfile->whatsapp,
            'telegram' => $masterProfile->telegram,
            'city' => $masterProfile->city,
            'district' => $masterProfile->district,
            'metro_station' => $masterProfile->metro_station, // Добавляем метро
            'address' => $masterProfile->address,
            'salon_name' => $masterProfile->salon_name,
            'services' => $masterProfile->services->map(function ($service) {
                return [
                    'id' => $service->id,
                    'name' => $service->name,
                    'category' => $service->category->name ?? 'Массаж',
                    'price' => $service->price,
                    'duration' => $service->duration,
                    'description' => $service->description,
                ];
            }),
            'work_zones' => $masterProfile->workZones,
            'schedules' => $masterProfile->schedules,
            'reviews' => $masterProfile->reviews->take(5),
            'created_at' => $masterProfile->created_at,
        ],
        // 🔥 ДОБАВЛЕНО: Передаём meta-теги для SEO
        'meta' => [
            'title' => $masterProfile->meta_title,
            'description' => $masterProfile->meta_description,
            'keywords' => implode(', ', [
                $masterProfile->display_name,
                'массаж',
                $masterProfile->city,
                $masterProfile->district,
                'массажист'
            ]),
            'og:title' => $masterProfile->meta_title,
            'og:description' => $masterProfile->meta_description,
            'og:image' => $masterProfile->avatar_url ?? asset('images/default-master.jpg'),
            'og:url' => $masterProfile->full_url,
            'og:type' => 'profile',
        ],
        'similarMasters' => $this->getSimilarMasters($masterProfile),
    ]);
}

    /**
     * Показать форму создания анкеты
     */
    public function create()
    {
        $categories = MassageCategory::with('subcategories')->get();
        
        return Inertia::render('Masters/Create', [
            'categories' => $categories,
            'cities' => ['Москва', 'Санкт-Петербург', 'Казань', 'Новосибирск'],
        ]);
    }

    /**
     * Сохранить новую анкету
     */
    public function store(Request $request)
    {
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
            'services' => 'required|array|min:1',
            'services.*.category_id' => 'required|exists:massage_categories,id',
            'services.*.name' => 'required|string|max:255',
            'services.*.price' => 'required|integer|min:100',
            'services.*.duration' => 'required|integer|min:15|max:480',
            'work_zones' => 'array',
            'photos' => 'array|max:10',
            'photos.*' => 'image|max:5120', // 5MB
        ]);

        DB::transaction(function () use ($validated, $request) {
            // Создаем профиль мастера
            $profile = auth()->user()->masterProfiles()->create([
                'display_name' => $validated['display_name'],
                'slug' => Str::slug($validated['display_name']),
                'description' => $validated['description'],
                'age' => $validated['age'] ?? null,
                'experience_years' => $validated['experience_years'] ?? null,
                'city' => $validated['city'],
                'district' => $validated['district'] ?? null,
                'address' => $validated['address'] ?? null,
                'salon_name' => $validated['salon_name'] ?? null,
                'phone' => $validated['phone'],
                'whatsapp' => $validated['whatsapp'] ?? null,
                'telegram' => $validated['telegram'] ?? null,
                'price_from' => $validated['price_from'],
                'price_to' => $validated['price_to'] ?? null,
                'show_phone' => $validated['show_phone'] ?? false,
                'status' => 'active',
                'is_active' => true,
            ]);

            // 🔥 ДОБАВЛЕНО: Генерируем meta-теги после создания
            $profile->generateMetaTags()->save();

            // Добавляем услуги
            foreach ($validated['services'] as $service) {
                $profile->services()->create([
                    'massage_category_id' => $service['category_id'],
                    'name' => $service['name'],
                    'price' => $service['price'],
                    'duration' => $service['duration'],
                    'description' => $service['description'] ?? null,
                ]);
            }

            // Добавляем зоны работы
            if (!empty($validated['work_zones'])) {
                foreach ($validated['work_zones'] as $zone) {
                    $profile->workZones()->create(['name' => $zone]);
                }
            }

            // Загружаем фотографии
            if ($request->hasFile('photos')) {
                foreach ($request->file('photos') as $index => $photo) {
                    $path = $photo->store('masters/photos', 'public');
                    $profile->photos()->create([
                        'path' => $path,
                        'is_main' => $index === 0,
                    ]);
                }
            }
        });

        return redirect()
            ->route('profile.dashboard')
            ->with('success', 'Анкета успешно создана!');
    }

    /**
     * Показать форму редактирования
     */
    public function edit($id)
    {
        $master = auth()->user()->masterProfiles()->findOrFail($id);
        $categories = MassageCategory::with('subcategories')->get();

        return Inertia::render('Masters/Edit', [
            'master' => $master->load(['services', 'photos', 'workZones']),
            'categories' => $categories,
            'cities' => ['Москва', 'Санкт-Петербург', 'Казань', 'Новосибирск'],
        ]);
    }

    /**
     * Обновить анкету
     */
    public function update(Request $request, $id)
    {
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

        $profile->update($validated);

        // 🔥 ДОБАВЛЕНО: Регенерируем meta-теги при обновлении профиля
        $profile->generateMetaTags()->save();

        return redirect()
            ->route('masters.show', [$profile->slug, $profile->id])
            ->with('success', 'Анкета обновлена!');
    }

    /**
     * Удалить анкету
     */
    public function destroy($id)
    {
        $profile = auth()->user()->masterProfiles()->findOrFail($id);
        
        // Мягкое удаление - переводим в архив
        $profile->update([
            'status' => 'archived',
            'is_active' => false,
        ]);

        return redirect()
            ->route('profile.dashboard')
            ->with('success', 'Анкета перемещена в архив!');
    }

    /**
     * API: Показать детали мастера
     */
    public function apiShow($id)
    {
        $master = MasterProfile::with(['services', 'photos', 'workZones'])
            ->findOrFail($id);

        return response()->json($master);
    }

    /**
     * Получить похожих мастеров
     */
    private function getSimilarMasters($master)
    {
        return MasterProfile::where('id', '!=', $master->id)
            ->where('city', $master->city)
            ->where('is_active', true)
            ->whereHas('services', function ($query) use ($master) {
                $categoryIds = $master->services->pluck('massage_category_id');
                $query->whereIn('massage_category_id', $categoryIds);
            })
            ->with(['user', 'services', 'photos'])
            ->orderBy('rating', 'desc')
            ->limit(4)
            ->get();
    }
}
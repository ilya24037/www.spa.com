<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use App\Models\{MasterProfile, MassageCategory, Service, WorkZone};

class AddItemController extends Controller
{
    /**
     * 🏠 Главная страница выбора категории (как у Avito)
     */
    public function index()
    {
        $categories = [
            [
                'id' => 'massage',
                'name' => 'Массаж',
                'icon' => '💆‍♀️',
                'description' => 'Классический, лечебный, спортивный массаж',
                'adult' => false,
                'popular' => true
            ],
            [
                'id' => 'erotic',
                'name' => 'Эротический массаж',
                'icon' => '🔥',
                'description' => 'Тантрический, Body-to-body, интимный массаж',
                'adult' => true,
                'popular' => true
            ],
            [
                'id' => 'strip',
                'name' => 'Стриптиз',
                'icon' => '💃',
                'description' => 'Приватные танцы, шоу-программы',
                'adult' => true,
                'popular' => false
            ],
            [
                'id' => 'escort',
                'name' => 'Эскорт',
                'icon' => '👥',
                'description' => 'Сопровождение на мероприятия',
                'adult' => true,
                'popular' => false
            ]
        ];

        return Inertia::render('AddItem/Index', [
            'categories' => $categories,
            'breadcrumbs' => [
                ['name' => 'Главная', 'url' => '/'],
                ['name' => 'Разместить объявление', 'url' => null]
            ]
        ]);
    }

    /**
     * 💆‍♀️ Форма создания анкеты массажиста
     */
    public function massage()
    {
        $categories = MassageCategory::with('subcategories')->get();
        $cities = ['Москва', 'Санкт-Петербург', 'Екатеринбург', 'Казань', 'Новосибирск'];
        
        return Inertia::render('AddItem/Massage', [
            'categories' => $categories,
            'cities' => $cities,
            'breadcrumbs' => [
                ['name' => 'Главная', 'url' => '/'],
                ['name' => 'Разместить объявление', 'url' => '/additem'],
                ['name' => 'Массаж', 'url' => null]
            ]
        ]);
    }

    /**
     * 💾 Сохранение анкеты массажиста
     */
    public function storeMassage(Request $request)
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
                'category_type' => 'massage', // 🔥 НОВОЕ: тип категории
                'is_adult_content' => false,
                'status' => 'active',
                'is_active' => true,
            ]);

            // Добавляем услуги
            foreach ($validated['services'] as $service) {
                $profile->services()->create([
                    'massage_category_id' => $service['category_id'],
                    'name' => $service['name'],
                    'price' => $service['price'],
                    'duration' => $service['duration'],
                    'description' => $service['description'] ?? null,
                    'adult_content' => false,
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
            ->with('success', 'Анкета массажиста успешно создана!');
    }

    /**
     * 🔥 Форма создания анкеты эротического массажа
     */
    public function erotic()
    {
        // Проверка возраста (18+)
        if (!auth()->user()->is_adult_verified) {
            return redirect()->route('verification.age')
                ->with('error', 'Для размещения объявлений 18+ необходимо подтвердить возраст');
        }

        $categories = [
            ['id' => 1, 'name' => 'Тантрический массаж'],
            ['id' => 2, 'name' => 'Body-to-body'],
            ['id' => 3, 'name' => 'Nuru массаж'],
            ['id' => 4, 'name' => 'Интимный массаж'],
            ['id' => 5, 'name' => 'Массаж с продолжением'],
        ];

        return Inertia::render('AddItem/Erotic', [
            'categories' => $categories,
            'cities' => ['Москва', 'Санкт-Петербург', 'Екатеринбург', 'Казань'],
            'breadcrumbs' => [
                ['name' => 'Главная', 'url' => '/'],
                ['name' => 'Разместить объявление', 'url' => '/additem'],
                ['name' => 'Эротический массаж', 'url' => null]
            ]
        ]);
    }

    /**
     * 💃 Форма создания анкеты стриптизерши
     */
    public function strip()
    {
        if (!auth()->user()->is_adult_verified) {
            return redirect()->route('verification.age')
                ->with('error', 'Для размещения объявлений 18+ необходимо подтвердить возраст');
        }

        $categories = [
            ['id' => 1, 'name' => 'Приватный стриптиз'],
            ['id' => 2, 'name' => 'Шоу-программа'],
            ['id' => 3, 'name' => 'Танцы на пилоне'],
            ['id' => 4, 'name' => 'Эротические танцы'],
        ];

        return Inertia::render('AddItem/Strip', [
            'categories' => $categories,
            'cities' => ['Москва', 'Санкт-Петербург', 'Екатеринбург', 'Казань'],
            'breadcrumbs' => [
                ['name' => 'Главная', 'url' => '/'],
                ['name' => 'Разместить объявление', 'url' => '/additem'],
                ['name' => 'Стриптиз', 'url' => null]
            ]
        ]);
    }

    /**
     * 👥 Форма создания анкеты эскорта
     */
    public function escort()
    {
        if (!auth()->user()->is_adult_verified) {
            return redirect()->route('verification.age')
                ->with('error', 'Для размещения объявлений 18+ необходимо подтвердить возраст');
        }

        $categories = [
            ['id' => 1, 'name' => 'Сопровождение на мероприятия'],
            ['id' => 2, 'name' => 'Деловые встречи'],
            ['id' => 3, 'name' => 'Культурные мероприятия'],
            ['id' => 4, 'name' => 'Индивидуальные услуги'],
        ];

        return Inertia::render('AddItem/Escort', [
            'categories' => $categories,
            'cities' => ['Москва', 'Санкт-Петербург', 'Екатеринбург', 'Казань'],
            'breadcrumbs' => [
                ['name' => 'Главная', 'url' => '/'],
                ['name' => 'Разместить объявление', 'url' => '/additem'],
                ['name' => 'Эскорт', 'url' => null]
            ]
        ]);
    }
}
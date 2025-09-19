# 🎯 ПЛАН РЕФАКТОРИНГА: Разделение доменов Ad и MasterProfile

**Дата создания:** 19.09.2025
**Автор:** Claude
**Статус:** В планировании
**Критическая важность:** ВЫСОКАЯ - данные теряются при публикации

## 📊 Анализ текущей ситуации

### Проблема
Система имеет **две параллельные модели** для объявлений:

1. **Ad (ads)** - правильная модель для объявлений
   - Содержит ВСЕ поля: `specialty`, `work_format`, `service_provider`
   - Используется в DraftService (черновики) ✅
   - НЕ используется при публикации ❌

2. **MasterProfile (master_profiles)** - модель для профиля мастера
   - НЕ содержит поля объявлений
   - Неправильно используется в AddItemController для создания объявлений
   - Данные теряются при сохранении

### Архитектурное нарушение
Нарушены принципы **SOLID и DDD**:
- ❌ Single Responsibility - MasterProfile отвечает и за профиль, и за объявления
- ❌ Domain-Driven Design - смешаны разные бизнес-домены

## ✅ Целевая архитектура (SOLID + DDD)

```
Ad Domain (Объявления):
├── Models/Ad.php         - модель объявления
├── Services/
│   ├── AdService.php     - CRUD операции
│   └── DraftService.php  - работа с черновиками
└── Контроллеры:
    ├── AddItemController  - создание объявлений
    └── AdController       - управление объявлениями

Master Domain (Профили):
├── Models/MasterProfile.php  - профиль мастера
├── Services/
│   └── MasterService.php     - управление профилем
└── Контроллеры:
    └── ProfileController      - настройки профиля
```

## 📋 ДЕТАЛЬНЫЙ ПЛАН РЕФАКТОРИНГА

### Этап 1: Подготовка и бэкап
```bash
# 1.1 Создание бэкапа файлов (5 мин)
cp app/Application/Http/Controllers/AddItemController.php app/Application/Http/Controllers/AddItemController.php.bak
cp app/Domain/Service/Services/AdCreationService.php app/Domain/Service/Services/AdCreationService.php.bak
cp app/Domain/Master/Services/MasterFullProfileService.php app/Domain/Master/Services/MasterFullProfileService.php.bak

# 1.2 Экспорт данных из БД (10 мин)
php artisan tinker
>>> \DB::table('ads')->get()->toJson(); // сохранить в ads_backup.json
>>> \DB::table('master_profiles')->get()->toJson(); // сохранить в master_profiles_backup.json
```

### Этап 2: Создание нового AdCreationService (KISS подход)

#### 2.1 Обновить AdCreationService для работы с Ad моделью
```php
// app/Domain/Service/Services/AdCreationService.php

public function createFromRequest(Request $request): array
{
    try {
        $validated = $request->validate($this->getValidationRules());

        // Создаем Ad напрямую, без MasterProfile
        $ad = $this->createAd($validated, $request);

        return [
            'success' => true,
            'ad' => $ad, // Изменено: возвращаем Ad вместо MasterProfile
            'message' => 'Объявление успешно создано!'
        ];
    } catch (\Exception $e) {
        // обработка ошибок
    }
}

private function createAd(array $validated, Request $request): Ad
{
    return DB::transaction(function () use ($validated, $request) {
        // Создаем объявление в таблице ads
        $ad = Ad::create([
            'user_id' => $request->user()->id,
            'title' => $validated['title'], // Изменено с display_name
            'description' => $validated['description'],
            'specialty' => $validated['specialty'], // Добавлено
            'work_format' => $validated['work_format'], // Добавлено
            'service_provider' => $validated['service_provider'], // Добавлено
            'age' => $validated['age'] ?? null,
            'experience' => $validated['experience_years'] ?? null,
            'city' => $validated['city'],
            'phone' => $validated['phone'],
            'price' => $validated['price'], // Изменено с price_from
            'status' => 'draft',
            'is_published' => false,
            // остальные поля...
        ]);

        // Добавляем услуги, фото и т.д.
        // ...

        return $ad;
    });
}
```

#### 2.2 Исправить валидацию
```php
public function getValidationRules(): array
{
    return [
        'category' => 'required|string',
        'title' => 'required|string|max:255', // Изменено с display_name
        'description' => 'required|string|min:50',
        'specialty' => 'nullable|string|max:255', // Добавлено
        'work_format' => 'nullable|array', // Добавлено
        'service_provider' => 'nullable|array', // Добавлено
        'price' => 'required|integer|min:500', // Изменено с price_from
        // остальные правила...
    ];
}
```

### Этап 3: Обновление AddItemController

#### 3.1 Изменить метод store
```php
public function store(Request $request)
{
    $result = $this->adCreationService->createFromRequest($request);

    if (!$result['success']) {
        return back()->withErrors($result['errors'] ?? [])
                     ->with('error', $result['message']);
    }

    // Изменено: перенаправляем на страницу успеха с Ad ID
    return redirect()->route('additem.success', ['ad' => $result['ad']->id]);
}
```

#### 3.2 Изменить метод success
```php
public function success($adId)
{
    // Изменено: загружаем Ad вместо MasterProfile
    $ad = Ad::findOrFail($adId);

    // Проверяем права доступа
    if ($ad->user_id !== auth()->id()) {
        abort(403, 'Доступ запрещен');
    }

    return Inertia::render('AddItem/Success', [
        'ad' => [
            'id' => $ad->id,
            'title' => $ad->title,
            'description' => $ad->description,
            'specialty' => $ad->specialty, // Теперь есть в Ad
            'work_format' => $ad->work_format, // Теперь есть в Ad
            'service_provider' => $ad->service_provider, // Теперь есть в Ad
            'status' => $ad->status,
            'is_published' => $ad->is_published,
            // остальные поля...
        ]
    ]);
}
```

### Этап 4: Создание связей между моделями

#### 4.1 В модели MasterProfile
```php
public function ads()
{
    return $this->hasMany(Ad::class, 'user_id', 'user_id');
}
```

#### 4.2 В модели Ad
```php
public function masterProfile()
{
    return $this->belongsTo(MasterProfile::class, 'user_id', 'user_id');
}
```

### Этап 5: Миграция данных (опционально)

Если нужно перенести существующие данные из MasterProfile в Ad:

```php
// database/migrations/2025_09_19_migrate_master_data_to_ads.php
public function up()
{
    $masters = MasterProfile::all();

    foreach ($masters as $master) {
        // Проверяем есть ли уже объявление
        $existingAd = Ad::where('user_id', $master->user_id)
                       ->where('created_at', $master->created_at)
                       ->first();

        if (!$existingAd) {
            Ad::create([
                'user_id' => $master->user_id,
                'title' => $master->display_name,
                'description' => $master->bio ?? $master->description,
                'age' => $master->age,
                'phone' => $master->phone,
                'status' => 'active',
                'is_published' => $master->is_published,
                // маппинг других полей...
            ]);
        }
    }
}
```

## 🧪 План тестирования

### Тест 1: Создание нового объявления
1. Зайти на /additem
2. Заполнить форму включая specialty, work_format
3. Нажать "Опубликовать"
4. Проверить в БД таблицу `ads` - все поля должны быть сохранены

### Тест 2: Сохранение черновика
1. Заполнить форму частично
2. Автосохранение должно работать
3. Проверить в БД таблицу `ads` со status='draft'

### Тест 3: Обратная совместимость
1. Старые объявления должны отображаться
2. Редактирование должно работать
3. Профили мастеров не должны быть затронуты

## ⚠️ Риски и митигация

| Риск | Вероятность | Митигация |
|------|-------------|-----------|
| Потеря данных | Низкая | Полный бэкап БД и файлов |
| Сломать черновики | Средняя | DraftService уже работает с Ad ✅ |
| Нарушить frontend | Низкая | API возвращает те же поля |
| Проблемы с правами | Низкая | Проверки user_id остаются |

## 📊 Критерии успеха

- ✅ Поля specialty, work_format, service_provider сохраняются при публикации
- ✅ Черновики продолжают работать
- ✅ Старые объявления не затронуты
- ✅ Профили мастеров работают отдельно
- ✅ Тесты проходят успешно

## 🚀 Команды для отката

```bash
# Откат файлов
cp app/Application/Http/Controllers/AddItemController.php.bak app/Application/Http/Controllers/AddItemController.php
cp app/Domain/Service/Services/AdCreationService.php.bak app/Domain/Service/Services/AdCreationService.php
cp app/Domain/Master/Services/MasterFullProfileService.php.bak app/Domain/Master/Services/MasterFullProfileService.php

# Откат миграции (если была выполнена)
php artisan migrate:rollback --step=1
```

## 📝 Документация изменений

После выполнения создать:
- `docs/REFACTORING/AD_MASTER_SEPARATION_COMPLETE.md` - отчет о выполнении
- Обновить `KNOWLEDGE_MAP_2025.md` с новой архитектурой
- Добавить в `docs/LESSONS/` полученный опыт

## ⏱️ Оценка времени

- Подготовка и бэкап: 15 минут
- Изменение сервисов: 30 минут
- Изменение контроллера: 20 минут
- Тестирование: 30 минут
- Документация: 15 минут
- **Итого: ~2 часа**

---

## 🎯 Результат

После рефакторинга:
1. **Ad и MasterProfile** - четко разделенные домены (SOLID ✅)
2. **Объявления** используют модель Ad везде (консистентность ✅)
3. **Данные сохраняются** полностью (проблема решена ✅)
4. **Код проще** и соответствует DDD архитектуре (KISS ✅)

---
*Документ подготовлен на основе анализа кода и принципов SOLID, DDD, KISS из CLAUDE.md*
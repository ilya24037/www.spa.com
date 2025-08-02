План завершения рефакторинга для ИИ помощника

## 📋 Текущее состояние:  выполнено

### ✅ Что уже сделано:
- Domain структура создана полностью (25 моделей)
- Все контроллеры перенесены в Application слой
- Созданы все репозитории (9 штук)
- Созданы Actions (18 штук)
- Infrastructure слой организован
- Support слой создан

### ❌ Что осталось сделать:
- Устранить дублирование файлов (Media сервисы)
- Перенести 21 сервис из app/Services/
- Обновить импорты моделей
- Очистить старые папки
- Создать недостающие компоненты

---

## 🎯 ЗАДАЧА 1: Устранение дублирования Media сервисов (2 часа)

### Проблема:
Файлы дублируются в двух местах:
- `app/Domain/Media/Services/` (ImageProcessor, MediaService, VideoProcessor, ThumbnailGenerator)
- `app/Infrastructure/Media/` (ImageProcessor, MediaService, VideoProcessor + AIMediaService, MediaProcessingService)

### Шаг 1.1: Анализ различий (30 минут)
```bash
# Команды для ИИ:
1. Сравнить файлы:
   - diff app/Domain/Media/Services/ImageProcessor.php app/Infrastructure/Media/ImageProcessor.php
   - diff app/Domain/Media/Services/MediaService.php app/Infrastructure/Media/MediaService.php
   - diff app/Domain/Media/Services/VideoProcessor.php app/Infrastructure/Media/VideoProcessor.php

2. Проверить импорты:
   - grep -r "use.*Domain\\Media\\Services" app/
   - grep -r "use.*Infrastructure\\Media" app/
```

### Шаг 1.2: Принятие решения (15 минут)
```
ПРАВИЛО: 
- Если файлы идентичны → оставить в Domain/Media/Services
- Если в Infrastructure версия расширенная → оставить в Infrastructure
- ThumbnailGenerator → оставить в Domain (нет дубликата)
- AIMediaService и MediaProcessingService → оставить в Infrastructure (инфраструктурные)
```

### Шаг 1.3: Удаление дубликатов (30 минут)
```bash
# После анализа выполнить:
1. Если оставляем в Domain:
   - rm app/Infrastructure/Media/ImageProcessor.php
   - rm app/Infrastructure/Media/VideoProcessor.php
   - rm app/Infrastructure/Media/MediaService.php

2. Если оставляем в Infrastructure:
   - rm app/Domain/Media/Services/ImageProcessor.php
   - rm app/Domain/Media/Services/VideoProcessor.php
   - rm app/Domain/Media/Services/MediaService.php
```

### Шаг 1.4: Обновление импортов (45 минут)
```bash
# Найти все использования:
grep -r "ImageProcessor" app/ --include="*.php"
grep -r "VideoProcessor" app/ --include="*.php"
grep -r "MediaService" app/ --include="*.php"

# Обновить импорты в найденных файлах
# Пример замены:
# use App\Infrastructure\Media\ImageProcessor; → use App\Domain\Media\Services\ImageProcessor;
```

---

## 🎯 ЗАДАЧА 2: Перенос сервисов из app/Services/ (4 часа)

### Список сервисов для переноса (21 файл):
```
AdMediaService.php → app/Domain/Ad/Services/
AdModerationService.php → app/Domain/Ad/Services/
AdSearchService.php → app/Domain/Ad/Services/
AdService.php → app/Domain/Ad/Services/
AIMediaService.php → app/Infrastructure/Media/ (уже есть)
BookingService.php → удалить (дубликат)
CacheService.php → app/Infrastructure/Cache/
FeatureFlagService.php → app/Infrastructure/Feature/ (уже есть)
LegacyNotificationService.php → app/Infrastructure/Notification/ (уже есть)
MasterService.php → app/Domain/Master/Services/
MediaService.php → удалить (дубликат)
NotificationService.php → app/Infrastructure/Notification/ (уже есть)
PaymentGatewayService.php → app/Domain/Payment/Services/
PaymentService.php → app/Domain/Payment/Services/
ReviewService.php → app/Domain/Review/Services/
SearchService.php → app/Domain/Search/Services/
UserAuthService.php → app/Domain/User/Services/
UserService.php → app/Domain/User/Services/
```

### Шаг 2.1: Перенос Ad сервисов (30 минут)
```bash
# Команды для каждого файла:
1. mv app/Services/AdMediaService.php app/Domain/Ad/Services/
2. Открыть файл и изменить namespace:
   - Было: namespace App\Services;
   - Стало: namespace App\Domain\Ad\Services;
3. Найти все импорты: grep -r "use App\\Services\\AdMediaService" app/
4. Обновить импорты во всех найденных файлах
5. Повторить для AdModerationService, AdSearchService, AdService
```

### Шаг 2.2: Перенос Payment сервисов (30 минут)
```bash
1. mv app/Services/PaymentGatewayService.php app/Domain/Payment/Services/
2. mv app/Services/PaymentService.php app/Domain/Payment/Services/
3. Обновить namespace в файлах
4. Найти и обновить все импорты
```

### Шаг 2.3: Перенос User сервисов (30 минут)
```bash
1. mv app/Services/UserAuthService.php app/Domain/User/Services/
2. mv app/Services/UserService.php app/Domain/User/Services/
3. Обновить namespace и импорты
```

### Шаг 2.4: Перенос остальных сервисов (1 час)
```bash
# Master
mv app/Services/MasterService.php app/Domain/Master/Services/

# Review
mv app/Services/ReviewService.php app/Domain/Review/Services/

# Search
mv app/Services/SearchService.php app/Domain/Search/Services/

# Infrastructure
mv app/Services/CacheService.php app/Infrastructure/Cache/
```

### Шаг 2.5: Удаление дубликатов и папки Services (30 минут)
```bash
# Удалить дубликаты:
rm app/Services/BookingService.php
rm app/Services/MediaService.php
rm app/Services/AIMediaService.php
rm app/Services/NotificationService.php
rm app/Services/LegacyNotificationService.php
rm app/Services/FeatureFlagService.php

# Проверить что папка пуста:
ls app/Services/

# Если пуста (кроме Adapters и Search):
rmdir app/Services/Adapters
rmdir app/Services/Search
rmdir app/Services/
```

### Шаг 2.6: Массовое обновление импортов (1 час)
```bash
# Создать скрипт для замены:
php artisan make:command UpdateServiceImports

# В команде:
$replacements = [
    'App\Services\AdMediaService' => 'App\Domain\Ad\Services\AdMediaService',
    'App\Services\AdModerationService' => 'App\Domain\Ad\Services\AdModerationService',
    // ... все остальные
];

foreach ($replacements as $old => $new) {
    $files = glob_recursive('app/**/*.php');
    foreach ($files as $file) {
        $content = file_get_contents($file);
        $content = str_replace("use $old;", "use $new;", $content);
        file_put_contents($file, $content);
    }
}
```

---

## 🎯 ЗАДАЧА 3: Обновление импортов моделей (2 часа)

### Главная проблема: SearchRepository и другие файлы используют App\Models\*

### Шаг 3.1: Найти все использования старых моделей (30 минут)
```bash
# Команды поиска:
grep -r "use App\\\\Models\\\\" app/ --include="*.php" > old_model_imports.txt
grep -r "App\\\\Models\\\\" app/ --include="*.php" | grep -v "^app/Models" >> old_model_usage.txt

# Основные модели для замены:
- App\Models\User → App\Domain\User\Models\User
- App\Models\Ad → App\Domain\Ad\Models\Ad
- App\Models\Service → App\Domain\Service\Models\Service
- App\Models\Booking → App\Domain\Booking\Models\Booking
- App\Models\Review → App\Domain\Review\Models\Review
- App\Models\MasterProfile → App\Domain\Master\Models\MasterProfile
- App\Models\Payment → App\Domain\Payment\Models\Payment
```

### Шаг 3.2: Обновить SearchRepository (30 минут)
```php
// Файл: app/Domain/Search/Repositories/SearchRepository.php
// Заменить в начале файла:
use App\Models\Ad;        → use App\Domain\Ad\Models\Ad;
use App\Models\User;      → use App\Domain\User\Models\User;
use App\Models\Service;   → use App\Domain\Service\Models\Service;
```

### Шаг 3.3: Массовая замена импортов (1 час)
```bash
# Создать команду:
php artisan make:command UpdateModelImports

# Логика команды:
$modelMappings = [
    'App\Models\User' => 'App\Domain\User\Models\User',
    'App\Models\Ad' => 'App\Domain\Ad\Models\Ad',
    'App\Models\Service' => 'App\Domain\Service\Models\Service',
    'App\Models\Booking' => 'App\Domain\Booking\Models\Booking',
    'App\Models\Review' => 'App\Domain\Review\Models\Review',
    'App\Models\MasterProfile' => 'App\Domain\Master\Models\MasterProfile',
    'App\Models\Payment' => 'App\Domain\Payment\Models\Payment',
    'App\Models\MasterPhoto' => 'App\Domain\Media\Models\Photo',
    'App\Models\MasterVideo' => 'App\Domain\Media\Models\Video',
];

// Обновить все файлы кроме app/Models/*
```

---

## 🎯 ЗАДАЧА 4: Очистка старых папок (1 час)

### Шаг 4.1: Перенос оставшихся контроллеров (30 минут)
```bash
# В app/Http/Controllers/ остались:
1. Auth/ → переместить в app/Application/Http/Controllers/Auth/
   mv app/Http/Controllers/Auth app/Application/Http/Controllers/

2. Controller.php → переместить в app/Application/Http/Controllers/
   mv app/Http/Controllers/Controller.php app/Application/Http/Controllers/

3. Media контроллеры → переместить в app/Application/Http/Controllers/Media/
   mkdir app/Application/Http/Controllers/Media
   mv app/Http/Controllers/MasterMediaController.php app/Application/Http/Controllers/Media/
   mv app/Http/Controllers/MasterPhotoController.php app/Application/Http/Controllers/Media/
   mv app/Http/Controllers/MediaUploadController.php app/Application/Http/Controllers/Media/
```

### Шаг 4.2: Обновление namespace в перенесенных файлах (15 минут)
```php
// Для каждого перенесенного файла:
namespace App\Http\Controllers\Auth; → namespace App\Application\Http\Controllers\Auth;
namespace App\Http\Controllers; → namespace App\Application\Http\Controllers;
```

### Шаг 4.3: Обновление маршрутов (15 минут)
```php
// В routes/web.php и routes/api.php заменить:
use App\Http\Controllers\Auth\* → use App\Application\Http\Controllers\Auth\*
use App\Http\Controllers\* → use App\Application\Http\Controllers\*
```

---

## 🎯 ЗАДАЧА 5: Создание недостающих компонентов (30 минут)

### Шаг 5.1: Создать CreateAdDTO (15 минут)
```php
// Файл: app/Domain/Ad/DTOs/CreateAdDTO.php
<?php

namespace App\Domain\Ad\DTOs;

use App\Domain\Ad\DTOs\Data\AdContentData;
use App\Domain\Ad\DTOs\Data\AdLocationData;
use App\Domain\Ad\DTOs\Data\AdPricingData;

class CreateAdDTO
{
    public function __construct(
        public readonly int $userId,
        public readonly string $title,
        public readonly string $category,
        public readonly string $specialty,
        public readonly array $clients,
        public readonly string $description,
        public readonly AdPricingData $pricing,
        public readonly AdLocationData $location,
        public readonly ?AdContentData $content = null,
        public readonly array $services = [],
        public readonly array $media = [],
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            userId: auth()->id(),
            title: $data['title'],
            category: $data['category'],
            specialty: $data['specialty'],
            clients: $data['clients'] ?? [],
            description: $data['description'],
            pricing: AdPricingData::fromArray($data['pricing']),
            location: AdLocationData::fromArray($data['location']),
            content: isset($data['content']) ? AdContentData::fromArray($data['content']) : null,
            services: $data['services'] ?? [],
            media: $data['media'] ?? [],
        );
    }
}
```

### Шаг 5.2: Создать NotificationService в Booking (15 минут)
```php
// Файл: app/Domain/Booking/Services/NotificationService.php
<?php

namespace App\Domain\Booking\Services;

use App\Domain\Booking\Models\Booking;
use App\Infrastructure\Notification\NotificationService as BaseNotificationService;

class NotificationService
{
    public function __construct(
        private BaseNotificationService $notificationService
    ) {}

    public function sendBookingConfirmation(Booking $booking): void
    {
        $this->notificationService->send(
            $booking->client,
            'booking.confirmed',
            [
                'booking_number' => $booking->booking_number,
                'master_name' => $booking->master->name,
                'date' => $booking->start_time->format('d.m.Y'),
                'time' => $booking->start_time->format('H:i'),
            ]
        );
    }

    public function sendBookingCancellation(Booking $booking): void
    {
        // Логика отправки уведомления об отмене
    }

    public function sendBookingReminder(Booking $booking): void
    {
        // Логика отправки напоминания
    }
}
```

---

## 🎯 ЗАДАЧА 6: Финальная проверка и тестирование (1 час)

### Шаг 6.1: Проверка автозагрузки (10 минут)
```bash
composer dump-autoload
php artisan clear-compiled
php artisan optimize:clear
```

### Шаг 6.2: Проверка маршрутов (10 минут)
```bash
php artisan route:list > routes.txt
# Проверить что все маршруты работают
```

### Шаг 6.3: Запуск тестов (20 минут)
```bash
php artisan test
# Исправить failing тесты если есть
```

### Шаг 6.4: Проверка основного функционала (20 минут)
```
1. Авторизация/Регистрация
2. Создание объявления
3. Бронирование
4. Поиск
5. Платежи
```

---

## 📊 Итоговая проверка

### Команды для финальной проверки:
```bash
# Проверить что старые папки пусты:
ls app/Services/ 2>/dev/null || echo "✅ Services удалена"
ls app/Http/Controllers/ 2>/dev/null || echo "✅ Controllers очищена"

# Проверить импорты старых моделей:
grep -r "use App\\\\Models\\\\" app/ --include="*.php" | grep -v "app/Models" | wc -l
# Должно быть 0

# Проверить структуру:
tree app/ -d -L 3

# Подсчет файлов:
find app/Domain -name "*.php" | wc -l
find app/Application -name "*.php" | wc -l
find app/Infrastructure -name "*.php" | wc -l
```

---

## ⏱️ Расчет времени

| Задача | Время |
|--------|-------|
| Задача 1: Устранение дублирования | 2 часа |
| Задача 2: Перенос сервисов | 4 часа |
| Задача 3: Обновление импортов | 2 часа |
| Задача 4: Очистка папок | 1 час |
| Задача 5: Создание компонентов | 30 минут |
| Задача 6: Проверка | 1 час |
| **ИТОГО** | **10.5 часов** |

---

## 🚀 Команды для быстрого старта

```bash
# 1. Создать backup
cp -r app app_backup_$(date +%Y%m%d_%H%M%S)

# 2. Создать команды для автоматизации
php artisan make:command UpdateServiceImports
php artisan make:command UpdateModelImports
php artisan make:command CleanupOldStructure

# 3. Выполнить план пошагово
# Начать с Задачи 1...
```

## ✅ Критерии завершения

1. Папка app/Services/ удалена
2. Папка app/Http/Controllers/ содержит только legacy совместимость
3. Нет дублирования файлов
4. Все импорты используют новые namespace
5. Все тесты проходят
6. Приложение работает без ошибок

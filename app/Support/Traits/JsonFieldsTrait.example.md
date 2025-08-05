# JsonFieldsTrait - Примеры использования

## Подключение трейта в модели

```php
<?php

namespace App\Domain\Ad\Models;

use App\Support\Traits\JsonFieldsTrait;
use Illuminate\Database\Eloquent\Model;

class Ad extends Model
{
    use JsonFieldsTrait;
    
    // Определяем JSON поля
    protected $jsonFields = [
        'clients',
        'service_provider',
        'features',
        'services',
        'schedule',
        'service_location',
        'outcall_locations',
        'geo',
        'photos',
        'video'
    ];
    
    // Больше не нужно дублировать в $casts - трейт сделает это автоматически!
}
```

## Примеры использования методов

### 1. Базовые операции

```php
$ad = Ad::find(1);

// Получить JSON поле с дефолтным значением
$services = $ad->getJsonField('services', []);

// Установить JSON поле
$ad->setJsonField('services', ['massage', 'spa', 'sauna']);

// Очистить JSON поле
$ad->clearJsonField('photos');
```

### 2. Работа с массивами

```php
// Добавить элемент в массив
$ad->appendToJsonField('services', 'aromatherapy');

// Удалить элемент из массива
$ad->removeFromJsonField('services', 'sauna');

// Проверить наличие элемента
if ($ad->hasInJsonField('services', 'massage')) {
    // ...
}
```

### 3. Работа с объектами (ассоциативными массивами)

```php
// Установить значение по ключу
$ad->setJsonFieldKey('geo', 'lat', 55.7558);
$ad->setJsonFieldKey('geo', 'lng', 37.6173);

// Получить значение по ключу
$lat = $ad->getJsonFieldKey('geo', 'lat');

// Объединить данные
$ad->mergeJsonField('schedule', [
    'monday' => '10:00-18:00',
    'tuesday' => '10:00-18:00'
]);
```

### 4. Работа с фотографиями

```php
// Добавить фото
$ad->appendToJsonField('photos', [
    'url' => '/storage/photos/123.jpg',
    'thumbnail' => '/storage/photos/123_thumb.jpg',
    'is_main' => false
]);

// Установить главное фото
$photos = $ad->getJsonField('photos', []);
foreach ($photos as $index => $photo) {
    $photo['is_main'] = ($index === 0);
    $ad->setJsonFieldKey('photos', $index, $photo);
}
```

### 5. Валидация структуры

```php
// Проверить что в geo есть обязательные поля
if ($ad->validateJsonStructure('geo', ['lat', 'lng'])) {
    // Координаты валидны
}

// Проверить структуру фото
foreach ($ad->getJsonField('photos', []) as $photo) {
    if (!isset($photo['url']) || !isset($photo['thumbnail'])) {
        // Невалидная структура фото
    }
}
```

## Преимущества использования

1. **Единообразие** - все модели работают с JSON одинаково
2. **Безопасность** - автоматическая обработка ошибок и логирование
3. **Удобство** - множество готовых методов для типовых операций
4. **Производительность** - кеширование декодированных значений
5. **Отладка** - автоматическое логирование проблем с JSON

## Миграция существующего кода

До:
```php
$ad->services = json_encode(['massage', 'spa']);
$services = json_decode($ad->services, true) ?: [];
```

После:
```php
$ad->setJsonField('services', ['massage', 'spa']);
$services = $ad->getJsonField('services');
```
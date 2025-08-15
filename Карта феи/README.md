# 🗺️ Карта FEIPITER - Интеграция Yandex Maps

## 📁 Структура проекта

```
Карта феи/
├── js/                    # JavaScript файлы
│   ├── yandex-maps.js    # Полная библиотека Yandex Maps API v2.1.79 (3MB)
│   ├── yandex-maps-part1.js  # Часть 1 (250KB)
│   ├── yandex-maps-part2.js  # Часть 2 (250KB)
│   └── yandex-maps-part3.js  # Часть 3 (250KB)
├── css/                   # Стили карты
├── assets/                # Изображения и иконки
├── html/                  # HTML страницы из FEIPITER
│   ├── index.html        # Оригинальная страница
│   ├── metadata.json     # Метаданные страницы
│   └── screenshot_viewport.png  # Скриншот
├── docs/                  # Документация
├── index.html            # Пример интеграции карты
└── README.md             # Этот файл
```

## 🚀 Быстрый старт

### Онлайн версия (рекомендуется)
1. Откройте `index.html` в браузере
2. Карта загрузится с официального API Yandex Maps

### Офлайн версия
1. В `index.html` раскомментируйте строку:
```html
<script src="js/yandex-maps.js"></script>
```
2. Закомментируйте онлайн версию API

## 🔑 API ключ

Для production использования нужен API ключ от Яндекс:
1. Зарегистрируйтесь на https://developer.tech.yandex.ru/
2. Получите ключ для JavaScript API и HTTP Геокодер
3. Замените `YOUR_API_KEY` в index.html

## 📦 Компоненты карты

### Основные модули Yandex Maps API:
- **Balloon** - всплывающие окна на карте
- **Placemark** - метки на карте
- **Clusterer** - кластеризация меток
- **GeoObject** - географические объекты
- **Controls** - элементы управления картой
- **Behaviors** - поведения (drag, zoom, click)

### Функции из примера:
- Отображение меток мастеров
- Фильтрация по услугам и районам
- Фильтр по цене
- Кластеризация меток
- Определение геолокации пользователя
- Всплывающие окна с информацией

## 🔧 Интеграция в SPA Platform

### 1. Vue компонент для карты

```vue
<template>
  <div class="yandex-map-wrapper">
    <div id="yandex-map" ref="mapContainer"></div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue'

const mapContainer = ref<HTMLElement>()
let map: any = null

onMounted(() => {
  // Загрузка Yandex Maps API
  const script = document.createElement('script')
  script.src = 'https://api-maps.yandex.ru/2.1/?apikey=YOUR_KEY&lang=ru_RU'
  script.onload = initMap
  document.head.appendChild(script)
})

function initMap() {
  // @ts-ignore
  ymaps.ready(() => {
    // @ts-ignore
    map = new ymaps.Map(mapContainer.value, {
      center: [55.753994, 37.622093],
      zoom: 12
    })
  })
}

onUnmounted(() => {
  if (map) {
    map.destroy()
  }
})
</script>
```

### 2. Использование в Laravel

```php
// app/Domain/Master/Services/MasterMapService.php
namespace App\Domain\Master\Services;

class MasterMapService
{
    public function getMastersForMap($filters = [])
    {
        return Master::query()
            ->with(['services', 'location'])
            ->when($filters['district'] ?? null, function ($q, $district) {
                $q->where('district', $district);
            })
            ->when($filters['price_from'] ?? null, function ($q, $price) {
                $q->where('price_per_hour', '>=', $price);
            })
            ->get()
            ->map(function ($master) {
                return [
                    'id' => $master->id,
                    'name' => $master->name,
                    'coords' => [$master->latitude, $master->longitude],
                    'services' => $master->services->pluck('name'),
                    'price' => $master->price_per_hour,
                    'rating' => $master->rating,
                    'photo' => $master->photo_url
                ];
            });
    }
}
```

## 📊 Данные из FEIPITER

### Обнаруженные компоненты:
- Полная библиотека Yandex Maps v2.1.79
- Фильтры по метро и районам
- CSS стили для карты
- Метаданные страницы

### Метаданные (metadata.json):
```json
{
  "url": "https://mc.yandex.com/metrika/metrika_match.html",
  "frameworks_detected": {
    "jquery": true
  }
}
```

## 🎨 Стилизация

Карта использует стандартные стили Яндекс с возможностью кастомизации:
- Иконки меток
- Цвета кластеров
- Стили всплывающих окон
- Элементы управления

## 📱 Мобильная версия

Карта адаптивна и работает на мобильных устройствах:
- Touch события для перемещения
- Адаптивные фильтры
- Оптимизация для маленьких экранов

## 🔒 Безопасность

- API ключ храните в переменных окружения
- Валидируйте данные от пользователей
- Используйте HTTPS для production

## 📚 Полезные ссылки

- [Документация Yandex Maps API](https://yandex.ru/dev/maps/jsapi/doc/)
- [Примеры использования](https://yandex.ru/dev/maps/jsapi/doc/2.1/examples/)
- [Песочница](https://yandex.ru/dev/maps/jsbox/2.1/)

## 💡 Примечания

1. Файл `yandex-maps.js` содержит полную библиотеку (3MB)
2. Разделенные файлы (part1-3) для удобства анализа
3. Оригинальные файлы из FEIPITER сохранены в html/
4. Все assets (иконки, изображения) скопированы

## 🤝 Контакты

Для вопросов по интеграции обращайтесь к команде разработки SPA Platform.
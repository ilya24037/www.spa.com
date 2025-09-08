# YMapsCore - Ядро системы Yandex Maps

## 📋 Описание

`YMapsCore` - это базовый модуль для работы с Yandex Maps API. Обеспечивает загрузку API, создание и управление картами, а также загрузку дополнительных модулей.

## ✨ Возможности

- ✅ Автоматическая загрузка Yandex Maps API
- ✅ Создание множественных карт на одной странице
- ✅ Управление состоянием карт
- ✅ Загрузка дополнительных модулей по требованию
- ✅ TypeScript поддержка
- ✅ Обработка ошибок и валидация данных
- ✅ Работа с центром, зумом и границами карты

## 📦 Установка

### Вариант 1: Прямое подключение
```html
<script src="path/to/YMapsCore.js"></script>
```

### Вариант 2: ES6 модули
```javascript
import YMapsCore from './core/YMapsCore.js'
```

### Вариант 3: CommonJS
```javascript
const YMapsCore = require('./core/YMapsCore.js')
```

## 🚀 Быстрый старт

### Базовое использование

```javascript
// Создаем экземпляр ядра
const mapsCore = new YMapsCore({
  apiKey: 'ваш-api-ключ', // Получить на https://developer.tech.yandex.ru/
  lang: 'ru_RU'
})

// Создаем карту
async function initMap() {
  try {
    // Карта автоматически загрузит API если нужно
    const map = await mapsCore.createMap('map-container', {
      center: [55.753994, 37.622093], // Москва
      zoom: 10,
      controls: ['zoomControl', 'fullscreenControl']
    })
    
    console.log('Карта создана!', map)
  } catch (error) {
    console.error('Ошибка создания карты:', error)
  }
}

// Запускаем инициализацию
initMap()
```

### HTML разметка

```html
<!DOCTYPE html>
<html>
<head>
  <title>YMapsCore Example</title>
  <style>
    #map-container {
      width: 100%;
      height: 400px;
    }
  </style>
</head>
<body>
  <div id="map-container"></div>
  <script src="YMapsCore.js"></script>
  <script>
    // Ваш код здесь
  </script>
</body>
</html>
```

## 📖 API Reference

### Конструктор

```javascript
new YMapsCore(config)
```

#### Параметры config:

| Параметр | Тип | По умолчанию | Описание |
|----------|-----|--------------|----------|
| `apiKey` | string | null | API ключ от Яндекс |
| `lang` | string | 'ru_RU' | Язык карты |
| `version` | string | '2.1.79' | Версия API |
| `coordorder` | string | 'latlong' | Порядок координат |
| `mode` | string | 'release' | Режим загрузки (release/debug) |
| `load` | string | 'package.full' | Загружаемые пакеты |
| `ns` | string | 'ymaps' | Namespace для API |

### Методы

#### loadAPI()
Загружает Yandex Maps API.

```javascript
await mapsCore.loadAPI()
```

#### createMap(container, options)
Создает карту в указанном контейнере.

```javascript
const map = await mapsCore.createMap('map-container', {
  center: [55.76, 37.64],
  zoom: 10,
  controls: ['zoomControl'],
  behaviors: ['default']
})
```

#### destroyMap(mapOrId)
Уничтожает карту и освобождает ресурсы.

```javascript
mapsCore.destroyMap('map-container')
// или
mapsCore.destroyMap(mapInstance)
```

#### getMap(containerId)
Получает карту по ID контейнера.

```javascript
const map = mapsCore.getMap('map-container')
```

#### getAllMaps()
Получает все созданные карты.

```javascript
const allMaps = mapsCore.getAllMaps()
allMaps.forEach((map, id) => {
  console.log(`Карта ${id}:`, map)
})
```

#### loadModule(moduleName)
Загружает дополнительный модуль.

```javascript
const clusterer = await mapsCore.loadModule('clusterer.addon.balloon')
```

#### setCenter(mapId, center, zoom)
Устанавливает центр карты.

```javascript
mapsCore.setCenter('map-container', [55.76, 37.64], 12)
```

#### getCenter(mapId)
Получает текущий центр карты.

```javascript
const center = mapsCore.getCenter('map-container')
console.log('Центр:', center)
```

#### setZoom(mapId, zoom)
Устанавливает масштаб карты.

```javascript
mapsCore.setZoom('map-container', 15)
```

#### getZoom(mapId)
Получает текущий масштаб.

```javascript
const zoom = mapsCore.getZoom('map-container')
```

#### setBounds(mapId, bounds, options)
Устанавливает границы видимой области.

```javascript
mapsCore.setBounds('map-container', [
  [55.70, 37.50],
  [55.80, 37.70]
])
```

#### getBounds(mapId)
Получает текущие границы.

```javascript
const bounds = mapsCore.getBounds('map-container')
```

## 💡 Примеры использования

### Создание нескольких карт

```javascript
const mapsCore = new YMapsCore({ apiKey: 'your-key' })

// Первая карта
const map1 = await mapsCore.createMap('map1', {
  center: [55.76, 37.64],
  zoom: 10
})

// Вторая карта
const map2 = await mapsCore.createMap('map2', {
  center: [59.94, 30.32], // Санкт-Петербург
  zoom: 11
})

// Управление картами
mapsCore.setZoom('map1', 12)
mapsCore.setCenter('map2', [59.93, 30.31])
```

### Загрузка модулей по требованию

```javascript
const mapsCore = new YMapsCore({ apiKey: 'your-key' })

// Создаем карту
const map = await mapsCore.createMap('map', {
  center: [55.76, 37.64],
  zoom: 10
})

// Загружаем модуль кластеризации когда нужно
const clustererModule = await mapsCore.loadModule('clusterer.addon.balloon')

// Используем загруженный модуль
const ymaps = mapsCore.getYMaps()
const clusterer = new ymaps.Clusterer()
map.geoObjects.add(clusterer)
```

### Обработка ошибок

```javascript
const mapsCore = new YMapsCore()

try {
  const map = await mapsCore.createMap('non-existent-container')
} catch (error) {
  console.error('Ошибка:', error.message)
  // "Контейнер для карты не найден: non-existent-container"
}

// Проверка загрузки API
if (mapsCore.isAPILoaded()) {
  console.log('API загружено')
} else {
  console.log('API еще не загружено')
}
```

### Интеграция с Vue.js

```vue
<template>
  <div id="vue-map" style="height: 400px"></div>
</template>

<script setup>
import { onMounted, onUnmounted } from 'vue'
import YMapsCore from './core/YMapsCore'

const mapsCore = new YMapsCore({
  apiKey: process.env.YANDEX_MAPS_KEY
})

let map = null

onMounted(async () => {
  map = await mapsCore.createMap('vue-map', {
    center: [55.76, 37.64],
    zoom: 10
  })
})

onUnmounted(() => {
  if (map) {
    mapsCore.destroyMap('vue-map')
  }
})
</script>
```

## 🔧 TypeScript

Модуль включает полные TypeScript определения.

```typescript
import YMapsCore, { YMapsCoreConfig, YMapOptions } from './core/YMapsCore'

const config: YMapsCoreConfig = {
  apiKey: 'your-key',
  lang: 'ru_RU'
}

const mapsCore = new YMapsCore(config)

const options: YMapOptions = {
  center: [55.76, 37.64],
  zoom: 10,
  controls: ['zoomControl']
}

const map = await mapsCore.createMap('map', options)
```

## ⚙️ Конфигурация

### Получение API ключа

1. Зарегистрируйтесь на [Яндекс.Разработчики](https://developer.tech.yandex.ru/)
2. Создайте новое приложение
3. Получите ключ для JavaScript API и HTTP Геокодер
4. Укажите домены, где будет использоваться ключ

### Опции карты

Полный список опций карты:

```javascript
{
  center: [55.76, 37.64],        // Координаты центра
  zoom: 10,                       // Масштаб (0-23)
  controls: [                     // Элементы управления
    'zoomControl',               // Контрол зума
    'fullscreenControl',         // Полноэкранный режим
    'geolocationControl',        // Геолокация
    'routeButtonControl',        // Построение маршрута
    'trafficControl',            // Пробки
    'typeSelector',              // Переключатель слоев
    'searchControl',             // Поиск
    'rulerControl'               // Линейка
  ],
  behaviors: [                    // Поведения карты
    'default',                   // Все стандартные поведения
    'drag',                      // Перетаскивание
    'scrollZoom',                // Масштаб колесом мыши
    'dblClickZoom',              // Масштаб двойным кликом
    'multiTouch',                // Мультитач жесты
    'rightMouseButtonMagnifier', // Выделение области ПКМ
    'leftMouseButtonMagnifier'   // Выделение области ЛКМ
  ]
}
```

## 🐛 Отладка

Для включения режима отладки:

```javascript
const mapsCore = new YMapsCore({
  mode: 'debug',  // Включает debug режим API
  apiKey: 'your-key'
})
```

## 📝 Лицензия

Этот модуль предоставляется как есть для использования в проектах с Yandex Maps API.

## 🤝 Поддержка

При возникновении вопросов обращайтесь к команде разработки SPA Platform.

## 📚 Полезные ссылки

- [Документация Yandex Maps API](https://yandex.ru/dev/maps/jsapi/doc/)
- [Примеры использования](https://yandex.ru/dev/maps/jsapi/doc/2.1/examples/)
- [Песочница](https://yandex.ru/dev/maps/jsbox/2.1/)
- [Получить API ключ](https://developer.tech.yandex.ru/)
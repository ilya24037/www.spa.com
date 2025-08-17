# 🗺️ OZON Map Widget

Полнофункциональная система карт для веб-приложений, созданная на основе компонентов OZON и MapLibre GL JS.

## 📦 Что включено

### 1. Стили (1-Styles/)
- `ozon-map-theme.css` - Полная тема OZON для карт
- `maplibre-controls.css` - Стили для элементов управления
- CSS переменные для кастомизации цветов и размеров

### 2. Конфигурация (2-Config/)
- `map-style.json` - Полный стиль карты в формате MapBox Style v8
- `russia-bounds.json` - Границы России для ограничения карты
- `default-config.json` - Настройки по умолчанию

### 3. Vue Компоненты (3-Components/)
- `MapContainer.vue` - Основной контейнер карты
- `MapControls.vue` - Группа элементов управления
- `ZoomControls.vue` - Кнопки зума
- `GeolocateButton.vue` - Кнопка геолокации
- `FullscreenButton.vue` - Полноэкранный режим
- `CompassButton.vue` - Компас для сброса поворота

### 4. Виджеты (4-Widgets/)
- `LocationSearch.vue` - Поиск адресов с автодополнением
- `PickupPointMarkers.vue` - Отображение пунктов выдачи
- `MapPopup.vue` - Всплывающие окна на карте
- `LocationInfo.vue` - Детальная информация о локации

### 5. Логика (5-Logic/)
- **Composables:**
  - `useMapInit.ts` - Инициализация карты
  - `useMapControls.ts` - Управление картой
  - `useGeolocation.ts` - Работа с геолокацией
- **Сервисы:**
  - `GeocodingService.ts` - Геокодинг (Nominatim, Yandex)
  - `RoutingService.ts` - Маршрутизация (OSRM, GraphHopper)
- **Утилиты:**
  - `mapUtils.ts` - Утилиты для карты
  - `coordinateUtils.ts` - Работа с координатами

### 6. Иконки (6-Icons/)
- SVG иконки для элементов управления
- Маркеры пунктов выдачи (OZON, ПВЗ, Постаматы)
- Утилиты для создания кастомных иконок

### 7. Демо и Ассеты (7-Assets/)
- Демо-страница с примерами использования
- Стили и скрипты для демо
- Документация и примеры интеграции

## 🚀 Быстрый старт

### 1. Базовая карта

```vue
<template>
  <div class="map-container">
    <MapContainer
      :center="[37.6176, 55.7558]"
      :zoom="12"
      :show-controls="true"
    />
  </div>
</template>

<script setup>
import { MapContainer } from './3-Components'
</script>

<style>
.map-container {
  height: 400px;
  width: 100%;
}
</style>
```

### 2. Карта с пунктами выдачи

```vue
<template>
  <div>
    <PickupPointMarkers
      :map="map"
      :points="pickupPoints"
      :show-controls="true"
      @point-selected="handlePointSelect"
    />
    <MapContainer
      ref="mapRef"
      :center="center"
      :zoom="12"
    />
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { MapContainer, PickupPointMarkers } from './components'

const map = ref(null)
const mapRef = ref(null)

const pickupPoints = ref([
  {
    id: '1',
    name: 'OZON Пункт выдачи',
    address: 'ул. Тверская, 1',
    coordinates: [37.6176, 55.7558],
    type: 'ozon'
  }
])

onMounted(() => {
  map.value = mapRef.value?.map
})

const handlePointSelect = (point) => {
  console.log('Selected point:', point)
}
</script>
```

### 3. Поиск адресов

```vue
<template>
  <div>
    <LocationSearch
      :map="map"
      placeholder="Поиск адреса или места..."
      @location-selected="handleLocationSelect"
    />
    <MapContainer ref="mapRef" />
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { MapContainer, LocationSearch } from './components'

const map = ref(null)
const mapRef = ref(null)

onMounted(() => {
  map.value = mapRef.value?.map
})

const handleLocationSelect = (location) => {
  console.log('Selected location:', location)
}
</script>
```

## 🔧 Кастомизация

### CSS переменные

```css
:root {
  --ozon-primary: #005bff;
  --ozon-primary-hover: #0050e0;
  --map-control-size: 29px;
  --map-control-margin: 10px;
  --map-control-bg: #ffffff;
  --map-control-border: rgba(0, 0, 0, 0.1);
  --map-control-focus: #0096ff;
}
```

### Собственный стиль карты

```javascript
import { useMapInit } from './5-Logic'

const customStyle = {
  version: 8,
  sources: {
    'custom-tiles': {
      type: 'raster',
      tiles: ['https://your-tiles-server/{z}/{x}/{y}.png'],
      tileSize: 256
    }
  },
  layers: [
    {
      id: 'custom-layer',
      type: 'raster',
      source: 'custom-tiles'
    }
  ]
}

const { initMap } = useMapInit()
await initMap('map-container', { style: customStyle })
```

### Кастомные маркеры

```javascript
import { createMapMarker, svgToDataURL } from './6-Icons'

const customMarker = createMapMarker('#ff6b35', 32)
const markerURL = svgToDataURL(customMarker)

map.loadImage(markerURL, (error, image) => {
  if (!error) {
    map.addImage('custom-marker', image)
  }
})
```

## 🔌 Интеграция с различными провайдерами

### Геокодинг

```javascript
import { GeocodingService, YandexProvider } from './5-Logic'

// Добавление Yandex провайдера
const geocoding = new GeocodingService()
geocoding.addProvider(new YandexProvider('YOUR_YANDEX_API_KEY'))
geocoding.setDefaultProvider('yandex')

// Поиск адреса
const results = await geocoding.search('Красная площадь', {
  limit: 5,
  countryCode: 'ru'
})
```

### Маршрутизация

```javascript
import { RoutingService, GraphHopperProvider } from './5-Logic'

// Добавление GraphHopper провайдера
const routing = new RoutingService()
routing.addProvider(new GraphHopperProvider('YOUR_GRAPHHOPPER_API_KEY'))

// Построение маршрута
const route = await routing.calculateRoute(
  { coordinates: [37.6176, 55.7558], name: 'Старт' },
  { coordinates: [37.6276, 55.7658], name: 'Финиш' },
  [],
  { profile: 'driving', alternatives: true }
)
```

## 📱 Мобильная адаптация

Все компоненты полностью адаптированы для мобильных устройств:

- Touch-события для всех элементов управления
- Увеличенные области нажатия (44px минимум)
- Responsive breakpoints
- Optimized для различных плотностей экрана

```css
/* Автоматическая адаптация */
@media (max-width: 768px) {
  .map-controls {
    --map-control-size: 44px;
    --map-control-margin: 16px;
  }
}
```

## 🌐 Поддержка браузеров

- Chrome 60+
- Firefox 57+
- Safari 11.1+
- Edge 79+
- iOS Safari 11.3+
- Chrome Android 60+

## 📊 Производительность

### Оптимизации

- Lazy loading компонентов
- Debounced события карты
- Виртуализация больших списков маркеров
- Кластеризация точек
- Оптимизированные изображения и иконки

### Мониторинг

```javascript
import { getMapPerformanceInfo, watchMapChanges } from './5-Logic'

// Получение информации о производительности
const perfInfo = getMapPerformanceInfo(map)

// Мониторинг изменений
const unwatch = watchMapChanges(map, (info) => {
  console.log('Map performance:', info)
})
```

## 🔐 Безопасность

- CSP-совместимые компоненты
- Валидация всех входных данных
- Безопасная обработка пользовательского контента
- HTTPS-only для внешних API

## 🧪 Тестирование

### Unit тесты

```bash
npm test
```

### E2E тесты

```bash
npm run test:e2e
```

### Покрытие кода

```bash
npm run coverage
```

## 📝 Лицензия

MIT License - используйте свободно в коммерческих и некоммерческих проектах.

## 🤝 Вклад в развитие

1. Fork репозитория
2. Создайте feature branch
3. Внесите изменения
4. Добавьте тесты
5. Создайте Pull Request

## 📞 Поддержка

- GitHub Issues для багов и feature requests
- Документация: [ссылка на docs]
- Примеры: [ссылка на examples]

## 🔗 Связанные проекты

- [MapLibre GL JS](https://maplibre.org/)
- [Vue 3](https://vuejs.org/)
- [OpenStreetMap](https://www.openstreetmap.org/)
- [Nominatim](https://nominatim.org/)

---

**Создано на основе анализа компонентов OZON для максимальной совместимости и качества пользовательского опыта.**
# 🗺️ Реальные интерактивные карты для SPA Platform

## ✨ Новые компоненты реальных карт

### 🆕 LeafletMap.vue - Полнофункциональная карта
Основной компонент для работы с реальными интерактивными картами на основе Leaflet + Яндекс.Карты.

**Особенности:**
- ✅ Реальная интерактивная карта Leaflet
- ✅ Яндекс.Карты тайлы
- ✅ Автоматическая загрузка библиотек
- ✅ Множественные маркеры с всплывающими окнами
- ✅ Геолокация пользователя
- ✅ События клика и наведения
- ✅ Адаптивный дизайн

```vue
<template>
  <LeafletMap
    :height="500"
    :center="{ lat: 58.0105, lng: 56.2502 }"
    :zoom="14"
    :markers="masterMarkers"
    map-type="yandex"
    @marker-click="handleMarkerClick"
    @map-click="handleMapClick"
  />
</template>

<script setup>
import LeafletMap from '@/Components/Map/LeafletMap.vue'

const masterMarkers = [
  {
    lat: 58.0105,
    lng: 56.2502,
    title: 'Анна Иванова',
    description: 'Классический массаж, 5 лет опыта',
    popup: '<b>Анна Иванова</b><br>Классический массаж<br>от 2000 ₽/час'
  }
]

const handleMarkerClick = (marker) => {
  console.log('Клик по мастеру:', marker.title)
}
</script>
```

### 🆕 RealMap.vue - Простая карта
Упрощенный компонент для быстрого отображения одной точки на реальной карте.

```vue
<template>
  <RealMap
    :height="400"
    :center="[58.0105, 56.2502]"
    :zoom="14"
    marker-text="SPA Центр в Перми"
    @map-ready="handleMapReady"
    @marker-click="handleMarkerClick"
  />
</template>

<script setup>
import RealMap from '@/Components/Map/RealMap.vue'

const handleMapReady = (mapInstance) => {
  console.log('Карта готова:', mapInstance)
}
</script>
```

## 🎯 Демо и тестирование

### Демо страница
Перейдите на `/map-demo` для интерактивной демонстрации всех возможностей:
- Выбор города
- Переключение типов карт
- Добавление маркеров
- Просмотр событий

## 📋 Примеры использования

### 1. Карта мастера на странице профиля
```vue
<!-- resources/js/Pages/Masters/Show.vue -->
<template>
  <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
    <!-- Информация о мастере -->
    <div>
      <MasterInfo :master="master" />
      <MasterServices :services="master.services" />
      <MasterReviews :reviews="master.reviews" />
    </div>
    
    <!-- Карта с местоположением -->
    <div class="space-y-4">
      <h3 class="text-lg font-semibold">Расположение</h3>
      <RealMap
        :center="[master.lat, master.lng]"
        :marker-text="master.address"
        :height="300"
      />
      <p class="text-sm text-gray-600">{{ master.address }}</p>
    </div>
  </div>
</template>
```

### 2. Поиск мастеров с картой
```vue
<!-- resources/js/Pages/Search/Results.vue -->
<template>
  <div class="flex gap-6">
    <!-- Список результатов -->
    <div class="w-1/2 space-y-4">
      <SearchFilters />
      <div class="space-y-4">
        <MasterCard 
          v-for="master in searchResults"
          :key="master.id"
          :master="master"
          @click="highlightMasterOnMap(master)"
        />
      </div>
    </div>
    
    <!-- Карта с маркерами всех мастеров -->
    <div class="w-1/2">
      <LeafletMap
        :height="600"
        :markers="mapMarkers"
        :center="searchCenter"
        @marker-click="selectMasterFromMap"
      />
    </div>
  </div>
</template>

<script setup>
const mapMarkers = computed(() => 
  searchResults.value.map(master => ({
    lat: master.latitude,
    lng: master.longitude,
    title: master.name,
    description: `${master.specialization} • ${master.rating}⭐`,
    popup: `
      <div class="p-2">
        <div class="font-semibold">${master.name}</div>
        <div class="text-sm text-gray-600">${master.specialization}</div>
        <div class="text-sm">${master.rating}⭐ (${master.reviews_count} отзывов)</div>
        <div class="font-medium text-blue-600">от ${master.price} ₽/час</div>
      </div>
    `
  }))
)

const selectMasterFromMap = (markerData) => {
  const master = searchResults.value.find(m => m.name === markerData.title)
  if (master) {
    // Перейти на страницу мастера или открыть модальное окно
    navigateToMaster(master.id)
  }
}
</script>
```

### 3. Форма создания объявления с выбором адреса
```vue
<!-- resources/js/Components/AdForm/features/Location/Geography/components/MapPreview.vue -->
<template>
  <div class="space-y-4">
    <div class="flex justify-between items-center">
      <label class="block text-sm font-medium text-gray-700">
        Подтвердите местоположение на карте
      </label>
      <button 
        @click="detectCurrentLocation"
        class="text-sm text-blue-600 hover:text-blue-700"
      >
        Определить автоматически
      </button>
    </div>
    
    <LeafletMap
      :height="250"
      :center="coordinates"
      :zoom="15"
      :markers="[{
        lat: coordinates.lat,
        lng: coordinates.lng,
        title: 'Ваш адрес',
        popup: selectedAddress || 'Выбранное местоположение'
      }]"
      @map-click="selectNewLocation"
    />
    
    <div class="text-xs text-gray-500">
      Координаты: {{ coordinates.lat.toFixed(6) }}, {{ coordinates.lng.toFixed(6) }}
      <span v-if="accuracy"> • Точность: ±{{ accuracy }}м</span>
    </div>
  </div>
</template>

<script setup>
const props = defineProps({
  coordinates: Object,
  accuracy: Number,
  selectedAddress: String
})

const emit = defineEmits(['update:coordinates'])

const selectNewLocation = (event) => {
  emit('update:coordinates', {
    lat: event.coordinates.lat,
    lng: event.coordinates.lng
  })
}

const detectCurrentLocation = () => {
  if (navigator.geolocation) {
    navigator.geolocation.getCurrentPosition(
      (position) => {
        emit('update:coordinates', {
          lat: position.coords.latitude,
          lng: position.coords.longitude
        })
      },
      (error) => {
        console.error('Ошибка геолокации:', error)
      }
    )
  }
}
</script>
```

## 🔧 API Reference

### LeafletMap Props
```typescript
interface LeafletMapProps {
  height?: number                    // Высота карты в пикселях (default: 500)
  center?: { lat: number, lng: number }  // Центр карты
  zoom?: number                      // Уровень зума (1-18, default: 14)
  markers?: Marker[]                 // Массив маркеров
  showLocationButton?: boolean       // Показать кнопку геолокации (default: true)
  mapType?: 'yandex' | 'osm'        // Тип тайлов карты (default: 'yandex')
  language?: string                  // Язык карты (default: 'ru_RU')
}

interface Marker {
  lat: number           // Широта
  lng: number          // Долгота
  title?: string       // Заголовок маркера
  description?: string // Описание маркера
  popup?: string       // HTML контент всплывающего окна
}
```

### LeafletMap Events
```typescript
interface LeafletMapEvents {
  'marker-click': (marker: Marker) => void
  'map-click': (event: { coordinates: LatLng, originalEvent: Event }) => void
  'center-change': (center: LatLng) => void
  'zoom-change': (zoom: number) => void
  'map-ready': (mapInstance: L.Map) => void
}
```

### RealMap Props
```typescript
interface RealMapProps {
  height?: number          // Высота карты (default: 400)
  center?: [number, number] // [широта, долгота]
  zoom?: number           // Уровень зума (default: 14)
  markerText?: string     // Текст маркера (default: 'Здесь находится объект')
}
```

## 🎨 Стилизация

### Кастомизация всплывающих окон
```css
/* В вашем CSS файле */
.leaflet-popup-content-wrapper {
  @apply rounded-lg shadow-xl border-0;
}

.leaflet-popup-content {
  @apply text-sm font-medium m-0;
}

.leaflet-popup-tip {
  @apply shadow-lg;
}
```

### Кастомизация маркеров
```javascript
// В компоненте можно добавить кастомные иконки
const customIcon = L.icon({
  iconUrl: '/images/markers/spa-marker.png',
  shadowUrl: '/images/markers/marker-shadow.png',
  iconSize: [25, 41],
  iconAnchor: [12, 41],
  popupAnchor: [1, -34],
  shadowSize: [41, 41]
})

const marker = L.marker([lat, lng], { icon: customIcon })
```

## 🌍 Поддерживаемые карты

### Яндекс.Карты (рекомендуется для России)
```vue
<LeafletMap map-type="yandex" language="ru_RU" />
```
- Подробные карты России
- Русскоязычные названия
- Быстрая загрузка

### OpenStreetMap (международные данные)
```vue
<LeafletMap map-type="osm" />
```
- Открытые картографические данные
- Глобальное покрытие
- Без ограничений API

## 📱 Мобильная поддержка

Карты полностью адаптивны и поддерживают:
- ✅ Touch-жесты (масштабирование, перетаскивание)
- ✅ Адаптивные контролы
- ✅ Retina дисплеи
- ✅ Быстрая загрузка на мобильных устройствах

## ⚡ Производительность

### Оптимизации
- **Lazy loading**: Библиотеки загружаются только при необходимости
- **Автоочистка**: Карты корректно удаляются при размонтировании компонента
- **Переиспользование**: Одна загруженная библиотека для всех карт на странице

### Рекомендации
- Используйте `RealMap` для простых случаев (один маркер)
- Используйте `LeafletMap` для сложных интерактивных карт
- Ограничивайте количество маркеров на карте (рекомендуется < 100)

## 🚨 Troubleshooting

### Карта не отображается
1. **Проверьте интернет-соединение** - карта требует загрузки тайлов
2. **Отключите блокировщики рекламы** - могут блокировать Leaflet
3. **Проверьте консоль браузера** на ошибки JavaScript

### Маркеры не показываются
1. **Проверьте координаты** - должны быть числами, не строками
2. **Проверьте диапазон** - широта: -90 до 90, долгота: -180 до 180
3. **Проверьте структуру** - маркеры должны содержать `lat` и `lng`

### Медленная загрузка
1. **Проверьте количество маркеров** - слишком много может замедлить карту
2. **Уменьшите размер всплывающих окон** - большой HTML замедляет отрисовку
3. **Используйте кластеризацию** для большого количества маркеров (будет добавлена позже)

## 🔮 Планы развития

### Ближайшие обновления
- [ ] **Кластеризация маркеров** для больших наборов данных
- [ ] **Поиск по адресу** с автодополнением
- [ ] **Построение маршрутов** между точками
- [ ] **Слои карты** (спутник, пробки, общественный транспорт)

### Долгосрочные планы
- [ ] **Анимации маркеров** при добавлении/удалении
- [ ] **Тепловые карты** для плотности мастеров
- [ ] **Экспорт карты** в изображение
- [ ] **Оффлайн поддержка** с кешированием тайлов
- [ ] **3D режим** для крупных городов

## 💡 Дополнительные ресурсы

- [Leaflet Documentation](https://leafletjs.com/)
- [Яндекс.Карты API](https://yandex.ru/dev/maps/)
- [OpenStreetMap](https://www.openstreetmap.org/)
- [Демо страница в проекте](/map-demo)

---

**Создано для SPA Platform** 🎯  
*Интерактивные карты для лучшего пользовательского опыта*
# 🗺️ Yandex Maps Components

## 📋 О проекте

**Yandex Maps Components** - это коллекция высококачественных, переиспользуемых компонентов для работы с Yandex Maps API.

### ✨ Ключевые особенности

- 🎯 **Модульная архитектура** - каждый компонент независим и может использоваться отдельно
- 📝 **TypeScript поддержка** - полная типизация для всех компонентов
- 🎮 **Vue 3 компоненты** - готовые компоненты с Composition API
- 📱 **Адаптивность** - оптимизация для мобильных устройств
- 🚀 **Production-ready** - обработка ошибок, валидация, оптимизация
- 📖 **Документация** - подробная документация на русском языке
- 🧪 **Примеры** - готовые примеры использования

## 🏗️ Архитектура

```
ymaps-components/
├── core/                 # Ядро системы
│   ├── YMapsCore.js     # Базовый класс для работы с API
│   ├── YMapsCore.d.ts   # TypeScript определения
│   └── README.md        # Документация
├── modules/             # Основные модули
│   ├── Balloon/         # Всплывающие окна
│   ├── Placemark/       # Метки на карте
│   └── Clusterer/       # Кластеризация меток
├── behaviors/           # Управление поведениями
│   └── MapBehaviors.*   # Перетаскивание, зум, etc
├── examples/            # Примеры использования
│   ├── FullMapExample.vue       # Vue 3 пример
│   ├── SimpleMapExample.html    # Vanilla JS пример
│   └── IntegrationExample.js    # Примеры интеграции
└── README.md            # Эта документация
```

## 📦 Компоненты

### 🎯 YMapsCore
**Базовое ядро системы**
- Загрузка и инициализация API
- Создание и управление картами
- Управление жизненным циклом
- [Подробнее →](./core/README.md)

### 🎈 Balloon
**Всплывающие информационные окна**
- Гибкое позиционирование
- Кастомизация внешнего вида
- Автопанорамирование
- Анимации
- [Подробнее →](./modules/Balloon/README.md)

### 📍 Placemark
**Метки на карте**
- 48+ предустановленных стилей
- Кастомные изображения
- Drag & Drop
- Анимации
- [Подробнее →](./modules/Placemark/README.md)

### 🎯 Clusterer
**Кластеризация меток**
- Автоматическая группировка
- Работа с тысячами меток
- 16+ стилей кластеров
- Оптимизация производительности
- [Подробнее →](./modules/Clusterer/README.md)

### 🎮 MapBehaviors
**Управление поведениями карты**
- Перетаскивание
- Масштабирование
- Мультитач жесты
- Ограничения и блокировки
- [Подробнее →](./behaviors/README.md)

## 🚀 Быстрый старт

### Установка

1. Скопируйте папку `ymaps-components` в ваш проект
2. Получите API ключ на [сайте Яндекс](https://developer.tech.yandex.ru/services)

### Базовое использование (Vanilla JavaScript)

```javascript
import YMapsCore from './ymaps-components/core/YMapsCore.js'
import Placemark from './ymaps-components/modules/Placemark/Placemark.js'

async function initMap() {
  // Создаем ядро
  const mapsCore = new YMapsCore({
    apiKey: 'YOUR_API_KEY',
    lang: 'ru_RU'
  })
  
  // Загружаем API
  await mapsCore.loadAPI()
  
  // Создаем карту
  const map = await mapsCore.createMap('map', {
    center: [55.753994, 37.622093],
    zoom: 10
  })
  
  // Добавляем метку
  const placemark = new Placemark(
    [55.753994, 37.622093],
    { balloonContent: 'Москва' },
    { preset: 'islands#redIcon' }
  )
  
  await placemark.addToMap(map)
}

initMap()
```

### Использование в Vue 3

```vue
<template>
  <div id="map" style="height: 400px"></div>
  
  <YMapsPlacemark
    v-for="marker in markers"
    :key="marker.id"
    :map="map"
    :position="marker.position"
    :preset="marker.preset"
    @click="onMarkerClick"
  />
</template>

<script setup>
import { ref, onMounted } from 'vue'
import YMapsCore from '@/ymaps-components/core/YMapsCore'
import YMapsPlacemark from '@/ymaps-components/modules/Placemark/Placemark.vue'

const map = ref(null)
const markers = ref([
  { id: 1, position: [55.75, 37.62], preset: 'islands#blueIcon' }
])

onMounted(async () => {
  const mapsCore = new YMapsCore({ apiKey: 'YOUR_KEY' })
  await mapsCore.loadAPI()
  map.value = await mapsCore.createMap('map')
})

const onMarkerClick = (marker) => {
  console.log('Клик по метке:', marker)
}
</script>
```

## 💡 Примеры использования

### Карта с кластеризацией

```javascript
import Clusterer from './ymaps-components/modules/Clusterer/Clusterer.js'
import Placemark from './ymaps-components/modules/Placemark/Placemark.js'

const clusterer = new Clusterer(map, {
  preset: 'islands#blueClusterIcons',
  gridSize: 60
})

// Создаем много меток
const placemarks = []
for (let i = 0; i < 100; i++) {
  const placemark = new Placemark(
    [55.75 + Math.random() * 0.1, 37.62 + Math.random() * 0.1],
    { balloonContent: `Метка ${i + 1}` }
  )
  placemarks.push(placemark)
}

// Добавляем в кластеризатор
await clusterer.add(placemarks)
```

### Управление поведениями

```javascript
import MapBehaviors from './ymaps-components/behaviors/MapBehaviors.js'

const behaviors = new MapBehaviors(map, {
  drag: true,
  scrollZoom: true,
  dblClickZoom: false
})

// Блокировка карты
behaviors.lock()

// Разблокировка
behaviors.unlock()

// Ограничение области
behaviors.setRestrictMapArea([
  [55.70, 37.50],
  [55.80, 37.70]
])
```

### Всплывающие окна

```javascript
import Balloon from './ymaps-components/modules/Balloon/Balloon.js'

const balloon = new Balloon(map, {
  closeButton: true,
  autoPan: true,
  maxWidth: 300
})

await balloon.open(
  [55.75, 37.62],
  '<h3>Заголовок</h3><p>Содержимое balloon</p>'
)
```

## 📊 Статистика проекта

| Метрика | Значение |
|---------|----------|
| **Общий объем кода** | 5000+ строк |
| **JavaScript модули** | 5 основных |
| **Vue компоненты** | 5 готовых |
| **TypeScript типизация** | 100% |
| **Документация** | 100% покрытие |
| **Примеры** | 3 полноценных |

## 🛠️ Принципы разработки

Все компоненты разработаны в соответствии с принципами:

- ✅ **KISS** - максимальная простота API
- ✅ **SOLID** - чистая архитектура
- ✅ **DRY** - без дублирования кода
- ✅ **Production-ready** - готовы к использованию
- ✅ **Обработка ошибок** - все edge cases учтены
- ✅ **Документация** - полное описание на русском

## 📱 Мобильная оптимизация

Все компоненты оптимизированы для мобильных устройств:

```javascript
const behaviors = new MapBehaviors(map, {
  // Базовые функции для мобильных
  drag: true,
  multiTouch: true,
  
  // Отключаем тяжелые функции
  rightMouseButtonMagnifier: false,
  ruler: false,
  
  // Оптимизируем анимации
  dragOptions: {
    inertiaDuration: 300
  }
})
```

## 🔧 Интеграция с фреймворками

### Vue 3

```javascript
// composables/useYandexMap.js
import { ref, onMounted, onUnmounted } from 'vue'
import MapService from '@/ymaps-components/examples/IntegrationExample'

export function useYandexMap(config) {
  const map = ref(null)
  const service = ref(null)
  
  onMounted(async () => {
    service.value = new MapService(config)
    map.value = await service.value.init()
  })
  
  onUnmounted(() => {
    service.value?.destroy()
  })
  
  return { map, service }
}
```

### React

```javascript
// hooks/useYandexMap.js
import { useEffect, useRef, useState } from 'react'
import MapService from '@/ymaps-components/examples/IntegrationExample'

export function useYandexMap(config) {
  const [map, setMap] = useState(null)
  const serviceRef = useRef(null)
  
  useEffect(() => {
    const init = async () => {
      serviceRef.current = new MapService(config)
      const mapInstance = await serviceRef.current.init()
      setMap(mapInstance)
    }
    
    init()
    
    return () => {
      serviceRef.current?.destroy()
    }
  }, [])
  
  return { map, service: serviceRef.current }
}
```

## 🐛 Решение типовых проблем

### Карта не отображается
```javascript
// Проверьте API ключ
const mapsCore = new YMapsCore({
  apiKey: 'YOUR_VALID_KEY' // Обязательно укажите ключ
})

// Проверьте размеры контейнера
#map {
  width: 100%;
  height: 400px; /* Обязательно задайте высоту */
}
```

### Метки не кластеризуются
```javascript
// Используйте правильный формат для кластеризатора
const placemark = new Placemark(...) // Объект Placemark
await clusterer.add(placemark) // Не простой объект!
```

### Проблемы с производительностью
```javascript
// Используйте кластеризацию для большого количества меток
const clusterer = new Clusterer(map, {
  gridSize: 100, // Увеличьте для меньшего количества кластеров
  viewportMargin: 50 // Кластеризация только видимой области
})

// Добавляйте метки порциями
for (let batch of batches) {
  await clusterer.add(batch)
  await new Promise(r => setTimeout(r, 10)) // Даем браузеру отдышаться
}
```

## 🤝 Поддержка и вклад

### Сообщить о проблеме
Если вы нашли баг или у вас есть предложение по улучшению, создайте issue в репозитории проекта.

### Контакты
- Email: support@spa-platform.ru
- Telegram: @spa_platform_support

## 📝 Лицензия

Компоненты предоставляются для использования в проектах с Yandex Maps API. Убедитесь, что вы соблюдаете условия использования Yandex Maps API.

## 🙏 Благодарности

- Команде Яндекс за отличный Maps API
- Сообществу Vue.js за прекрасный фреймворк
- Всем, кто использует и улучшает эти компоненты

---

<div align="center">
  <strong>Сделано с ❤️ для SPA Platform</strong>
  <br>
  <sub>Версия 1.0.0 | Сентябрь 2025</sub>
</div>

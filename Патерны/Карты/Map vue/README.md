# 🗺️ Vue Yandex Maps 3.0 - Примеры интеграции

## 📋 О проекте

Набор готовых компонентов для интеграции Яндекс.Карт 3.0 в Vue 3 приложения. Эти примеры созданы специально для SPA Platform и демонстрируют основные паттерны работы с картами.

### ✅ Что включено

- **MapExample.vue** - Базовый пример работы с картой
- **AddressSelector.vue** - Компонент выбора адреса с поиском
- **ServiceAreaMap.vue** - Визуализация зоны обслуживания
- **index.html** - Интерактивная демо-страница

### 🚀 Возможности

- ✨ Vue 3 Composition API
- 📍 Интерактивные маркеры
- 🔍 Поиск по адресу
- 📏 Радиус обслуживания
- 🏙️ Определение районов и метро
- 📱 Мобильная адаптивность
- 🎨 Современный дизайн

## 🛠️ Установка

### 1. Клонирование или копирование

```bash
# Копируйте файлы в ваш проект
cp -r C:\Проект SPA\Карты\Map\* your-project/
```

### 2. Установка зависимостей

```bash
# Установка vue-yandex-maps
npm install vue-yandex-maps@latest

# Или через yarn
yarn add vue-yandex-maps@latest
```

### 3. API ключ уже настроен!

✅ **API ключ:** `23ff8acc-835f-4e99-8b19-d33c5d346e18`

Ключ уже добавлен во все компоненты. Если вы используете компоненты в основном проекте SPA Platform, ключ берется из:
- `.env`: `YANDEX_MAPS_API_KEY=23ff8acc-835f-4e99-8b19-d33c5d346e18`
- `resources/js/src/features/map/utils/mapConstants.ts`

Если нужен другой ключ:
1. Зайдите на [developer.tech.yandex.ru](https://developer.tech.yandex.ru/)
2. Создайте новое приложение
3. Выберите "JavaScript API и HTTP Геокодер"
4. Замените ключ в компонентах

### 4. Настройка в проекте

```typescript
// main.ts или app.ts
import { createApp } from 'vue'
import VueYandexMaps from 'vue-yandex-maps'

const app = createApp(App)

app.use(VueYandexMaps, {
  apikey: '23ff8acc-835f-4e99-8b19-d33c5d346e18', // Или process.env.YANDEX_MAPS_API_KEY
  lang: 'ru_RU',
  coordorder: 'latlong',
  enterprise: false,
  version: '3.0'
})
```

## 📂 Структура файлов

```
Map/
├── MapExample.vue          # Базовый пример
├── AddressSelector.vue     # Выбор адреса
├── ServiceAreaMap.vue      # Зона обслуживания
├── index.html             # Демо-страница
├── package.json           # Зависимости
├── package-lock.json      # Lock-файл
└── README.md             # Эта документация
```

## 💻 Использование компонентов

### MapExample - Базовая карта

```vue
<template>
  <MapExample />
</template>

<script setup>
import MapExample from './MapExample.vue'
</script>
```

**Возможности:**
- Клик по карте для выбора точки
- Перетаскивание маркера
- Геокодирование координат
- Контролы управления

### AddressSelector - Выбор адреса

```vue
<template>
  <AddressSelector 
    v-model="selectedAddress"
    @update:coordinates="onCoordinatesUpdate"
  />
</template>

<script setup>
import { ref } from 'vue'
import AddressSelector from './AddressSelector.vue'

const selectedAddress = ref(null)

const onCoordinatesUpdate = (coords) => {
  console.log('Новые координаты:', coords)
}
</script>
```

**Возможности:**
- Поиск адреса через строку поиска
- Определение района и станции метро
- Быстрый выбор популярных районов
- Эмит координат и полного адреса

### ServiceAreaMap - Зона обслуживания

```vue
<template>
  <ServiceAreaMap 
    :service-points="points"
    :default-radius="5"
  />
</template>

<script setup>
import ServiceAreaMap from './ServiceAreaMap.vue'

const points = [
  { 
    id: 1, 
    coordinates: [55.755864, 37.617698],
    name: 'Основная точка',
    radius: 5
  }
]
</script>
```

**Возможности:**
- Множественные точки обслуживания
- Настраиваемый радиус (1-50 км)
- Расчет площади покрытия
- Визуализация пересечений зон

## 🔧 Интеграция в SPA Platform

### 1. Копирование компонентов

```bash
# Копируйте компоненты в нужную директорию FSD
cp MapExample.vue resources/js/src/features/map/ui/
cp AddressSelector.vue resources/js/src/features/address-selector/ui/
cp ServiceAreaMap.vue resources/js/src/features/service-area/ui/
```

### 2. Адаптация под архитектуру FSD

```typescript
// features/map/model/types.ts
export interface MapCoordinates {
  lat: number
  lng: number
}

export interface AddressData {
  coordinates: MapCoordinates
  address: string
  district?: string
  metro?: string
}
```

### 3. Создание composables

```typescript
// features/map/composables/useYandexMap.ts
import { ref, onMounted } from 'vue'

export function useYandexMap() {
  const isLoaded = ref(false)
  const mapInstance = ref(null)
  
  // Логика инициализации
  
  return {
    isLoaded,
    mapInstance
  }
}
```

### 4. Интеграция в формы

```vue
<!-- В AdForm.vue или GeoSection.vue -->
<template>
  <CollapsibleSection title="Местоположение">
    <AddressSelector 
      v-model="form.location.address"
      @update:coordinates="updateCoordinates"
    />
    
    <ServiceAreaMap
      v-if="form.location.coordinates"
      :service-points="[{
        id: 1,
        coordinates: form.location.coordinates,
        radius: form.service_radius || 5
      }]"
    />
  </CollapsibleSection>
</template>
```

## 🎨 Кастомизация

### Изменение стилей маркера

```vue
<yandex-map-marker>
  <div class="custom-marker">
    <!-- Ваш SVG или HTML -->
    <svg>...</svg>
  </div>
</yandex-map-marker>
```

### Настройка контролов

```vue
<yandex-map-controls position="right">
  <yandex-map-zoom-control />
  <yandex-map-geolocation-control />
  <!-- Добавьте свои контролы -->
</yandex-map-controls>
```

### Темная тема

```javascript
const mapSettings = {
  theme: 'dark',
  behaviors: ['drag', 'scrollZoom', 'dblClickZoom']
}
```

## 📱 Мобильная адаптация

Все компоненты адаптированы для мобильных устройств:

- **320px+** - Мобильные телефоны
- **768px+** - Планшеты
- **1024px+** - Десктоп

```css
/* Пример адаптивных стилей */
.map-container {
  height: 400px;
}

@media (max-width: 768px) {
  .map-container {
    height: 300px;
  }
}
```

## 🧪 Тестирование

### Запуск демо

1. Откройте `index.html` в браузере
2. Вставьте ваш API ключ в настройки
3. Проверьте все вкладки с примерами

### Unit тесты

```bash
# Запуск тестов (если настроены)
npm test
```

### E2E тесты

```javascript
// cypress/integration/map.spec.js
describe('Yandex Maps', () => {
  it('should select address', () => {
    cy.visit('/map-demo')
    cy.get('[data-cy=address-input]').type('Москва, Красная площадь')
    cy.get('[data-cy=search-result]').first().click()
    cy.get('[data-cy=selected-address]').should('contain', 'Красная площадь')
  })
})
```

## 🐛 Известные проблемы и решения

### Проблема: Карта не загружается

**Решение:** Проверьте API ключ и домены в настройках приложения на Яндексе

### Проблема: Ошибка CORS

**Решение:** Добавьте ваш домен в разрешенные в кабинете разработчика Яндекса

### Проблема: Маркеры не кликабельны

**Решение:** Убедитесь что z-index маркеров выше чем у карты

```css
.custom-marker {
  z-index: 1000;
  cursor: pointer;
}
```

## 📚 Полезные ссылки

- [Документация vue-yandex-maps](https://vue-yandex-maps.github.io/)
- [API Яндекс.Карт 3.0](https://yandex.ru/dev/maps/jsapi/doc/)
- [Примеры Яндекс.Карт](https://yandex.ru/dev/maps/jsapi/doc/3.0/examples/)
- [Геокодер API](https://yandex.ru/dev/maps/geocoder/)

## 🤝 Поддержка

Если у вас возникли вопросы по интеграции:

1. Проверьте эту документацию
2. Посмотрите примеры в `index.html`
3. Изучите официальную документацию
4. Создайте issue в репозитории проекта

## 📄 Лицензия

MIT License - свободно используйте в ваших проектах.

## 🙏 Благодарности

- Команде Яндекс.Карт за отличное API
- Разработчикам vue-yandex-maps за Vue обертку
- Сообществу Vue.js за поддержку

---

*Создано для SPA Platform с ❤️*

*Версия: 1.0.0 | Дата: 01.09.2025*
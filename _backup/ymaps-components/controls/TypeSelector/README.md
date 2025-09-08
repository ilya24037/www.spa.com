# 🎯 TypeSelector - Селектор типов карт

Элегантный и мощный селектор типов карт для Yandex Maps с поддержкой выпадающего списка, кнопок и адаптивного дизайна.

## 📋 Особенности

- ✅ **3 режима отображения** - Dropdown, Buttons, Compact
- ✅ **Динамические типы карт** - Схема, Спутник, Гибрид + пользовательские
- ✅ **TypeScript** - Полная типизация без any
- ✅ **Vue 3 поддержка** - Готовый Vue компонент с Composition API
- ✅ **Мобильная адаптация** - Автоматическое переключение в компактный режим
- ✅ **Accessibility** - ARIA атрибуты и клавиатурная навигация
- ✅ **Production-ready** - Полная обработка ошибок и edge cases

## 🚀 Быстрый старт

### Vanilla JavaScript

```javascript
import TypeSelector from './TypeSelector.js'
import YMapsCore from '../../core/YMapsCore.js'

async function initMap() {
  // Создаем карту
  const mapsCore = new YMapsCore({ apiKey: 'YOUR_API_KEY' })
  await mapsCore.loadAPI()
  const map = await mapsCore.createMap('map')
  
  // Создаем селектор типов карт
  const typeSelector = new TypeSelector({
    mode: 'dropdown',
    position: 'topLeft',
    showLabels: true,
    showIcons: true
  })
  
  // Добавляем на карту
  await typeSelector.addToMap(map)
  
  // Обработчики событий
  typeSelector.on('typechange', (event) => {
    console.log(`Тип карты изменен: ${event.oldType} → ${event.newType}`)
  })
}
```

### Vue 3 Composition API

```vue
<template>
  <div id="map" style="height: 400px"></div>
  
  <!-- TypeSelector с v-model поддержкой -->
  <TypeSelectorVue
    :map="map"
    v-model:current-type="selectedMapType"
    :map-types="customMapTypes"
    mode="buttons"
    direction="horizontal"
    :show-labels="true"
    @typechange="onMapTypeChange"
    @ready="onSelectorReady"
  />
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import YMapsCore from '@/ymaps-components/core/YMapsCore'
import TypeSelectorVue from '@/ymaps-components/controls/TypeSelector/TypeSelector.vue'

const map = ref(null)
const selectedMapType = ref('map')

// Пользовательские типы карт
const customMapTypes = ref([
  { key: 'map', name: 'Схема', icon: 'map' },
  { key: 'satellite', name: 'Спутник', icon: 'satellite' },
  { key: 'hybrid', name: 'Гибрид', icon: 'hybrid' },
  { key: 'traffic', name: 'Пробки', icon: 'traffic' }
])

onMounted(async () => {
  const mapsCore = new YMapsCore({ apiKey: 'YOUR_KEY' })
  await mapsCore.loadAPI()
  map.value = await mapsCore.createMap('map', {
    center: [55.753994, 37.622093],
    zoom: 10,
    type: selectedMapType.value
  })
})

const onMapTypeChange = (event) => {
  console.log('Новый тип карты:', event.newType)
}

const onSelectorReady = (selector) => {
  console.log('TypeSelector готов к использованию')
}
</script>
```

## ⚙️ Конфигурация

### Опции конструктора (JavaScript)

```typescript
interface TypeSelectorOptions {
  // Основные настройки
  mode?: 'dropdown' | 'buttons' | 'compact'    // Режим отображения
  direction?: 'horizontal' | 'vertical'         // Направление кнопок
  position?: string                             // Позиция на карте
  
  // Содержимое
  mapTypes?: MapTypeConfig[]                    // Пользовательские типы
  defaultType?: string                          // Тип по умолчанию
  showLabels?: boolean                          // Показать названия
  showIcons?: boolean                           // Показать иконки
  
  // Поведение
  autoDetect?: boolean                          // Авто-определение типов
  compactOnMobile?: boolean                     // Компактный режим на мобильных
  
  // Визуальные настройки
  visible?: boolean                             // Видимость
  enabled?: boolean                             // Активность
  zIndex?: number                               // Z-index
  margin?: {                                    // Отступы
    top?: number
    right?: number
    bottom?: number
    left?: number
  }
}
```

### Props Vue компонента

```typescript
interface Props {
  map?: any                              // Экземпляр карты
  mode?: 'dropdown' | 'buttons' | 'compact'  // Режим
  direction?: 'horizontal' | 'vertical'       // Направление
  position?: string                           // Позиция
  mapTypes?: MapTypeConfig[]                  // Типы карт
  currentType?: string | null                 // Текущий тип (v-model)
  defaultType?: string                        // По умолчанию
  showLabels?: boolean                        // Показать названия
  showIcons?: boolean                         // Показать иконки
  autoDetect?: boolean                        // Авто-определение
  compactOnMobile?: boolean                   // Компактный на мобильных
  visible?: boolean                           // Видимость
  enabled?: boolean                           // Активность
  margin?: object                             // Отступы
  zIndex?: number                             // Z-index
}
```

## 🔧 API методы

### JavaScript класс

```typescript
class TypeSelector {
  // Управление типом карты
  getCurrentType(): string | null                    // Текущий тип
  setCurrentType(type: string): Promise<void>        // Установить тип
  getAvailableTypes(): MapTypeConfig[]               // Доступные типы
  
  // Управление типами
  addMapType(config: MapTypeConfig, position?: number): void    // Добавить тип
  removeMapType(typeKey: string): void                         // Удалить тип
  
  // Управление видимостью
  show(): void                                       // Показать
  hide(): void                                       // Скрыть  
  enable(): void                                     // Включить
  disable(): void                                    // Отключить
  isVisible(): boolean                               // Видимость
  isEnabled(): boolean                               // Активность
  
  // События
  on(event: string, handler: Function): void        // Подписаться
  off(event: string, handler: Function): void       // Отписаться
  
  // Жизненный цикл
  addToMap(map: ymaps.Map): Promise<void>           // Добавить на карту
  removeFromMap(): Promise<void>                     // Удалить с карты
  destroy(): void                                    // Уничтожить
}
```

### Vue компонент (defineExpose)

```typescript
// Методы, доступные через template ref
interface ExposedMethods {
  getSelector(): TypeSelector | null        // Получить JS экземпляр
  getCurrentType(): string | null           // Текущий тип
  setCurrentType(type: string): Promise<void>  // Установить тип
  getAvailableTypes(): MapTypeConfig[]      // Доступные типы
  addMapType(config: MapTypeConfig): void   // Добавить тип
  removeMapType(key: string): void          // Удалить тип
  recreate(): Promise<void>                 // Пересоздать селектор
}

// Использование в родительском компоненте
const typeSelectorRef = ref()

const changeToSatellite = async () => {
  await typeSelectorRef.value.setCurrentType('satellite')
}
```

## 📡 События

### JavaScript

```javascript
typeSelector.on('typechange', (event) => {
  console.log('Тип изменен:', event.oldType, '→', event.newType)
})

typeSelector.on('typeadd', (event) => {
  console.log('Добавлен тип:', event.type.name)
})

typeSelector.on('typeremove', (event) => {
  console.log('Удален тип:', event.type.name)
})

// Dropdown события
typeSelector.on('dropdownopen', () => {
  console.log('Выпадающий список открыт')
})

typeSelector.on('dropdownclose', () => {
  console.log('Выпадающий список закрыт')
})
```

### Vue

```vue
<template>
  <TypeSelectorVue
    :map="map"
    v-model:current-type="currentType"
    @typechange="onTypeChange"
    @typeadd="onTypeAdd"
    @typeremove="onTypeRemove"
    @dropdownopen="onDropdownOpen"
    @dropdownclose="onDropdownClose"
    @error="onError"
    @ready="onReady"
  />
</template>

<script setup>
const onTypeChange = (event) => {
  console.log(`${event.oldType} → ${event.newType}`)
}
</script>
```

## 🎨 Кастомизация стилей

### CSS переменные

```css
.ymaps-type-selector {
  --button-height: 34px;           /* Высота кнопок */
  --dropdown-width: 120px;         /* Ширина dropdown */
  --border-radius: 4px;            /* Скругление */
  --font-size: 14px;               /* Размер шрифта */
  --icon-size: 16px;               /* Размер иконок */
}

/* Размеры */
.ymaps-type-selector--small {
  --button-height: 28px;
  --font-size: 12px;
  --icon-size: 14px;
}

.ymaps-type-selector--large {
  --button-height: 40px;
  --font-size: 16px;
  --icon-size: 18px;
}
```

### Кастомные стили

```css
/* Стилизация dropdown */
.ymaps-type-selector-dropdown {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.15);
}

.ymaps-type-selector-dropdown:hover {
  background: linear-gradient(135deg, #764ba2 0%, #667eea 100%);
}

/* Стилизация кнопок */
.ymaps-type-selector-button {
  background: #f8fafc;
  border: 2px solid #e2e8f0;
  transition: all 0.2s ease;
}

.ymaps-type-selector-button:hover {
  border-color: #667eea;
  box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.ymaps-type-selector-button--active {
  background: #667eea;
  color: white;
  border-color: #667eea;
}

/* Темная тема */
@media (prefers-color-scheme: dark) {
  .ymaps-type-selector-group {
    background: rgba(30, 30, 30, 0.95);
    border-color: #374151;
  }
  
  .ymaps-type-selector-button {
    background: #374151;
    color: #f9fafb;
    border-color: #4b5563;
  }
}
```

## 📱 Адаптивность

TypeSelector автоматически адаптируется для мобильных устройств:

```css
@media (max-width: 768px) {
  .ymaps-type-selector {
    --button-height: 44px;        /* Увеличенные кнопки для touch */
    --font-size: 16px;            /* Больший шрифт */
  }
  
  /* Компактный режим на мобильных */
  .ymaps-type-selector--compact-mobile .ymaps-type-selector-button {
    min-width: 44px;              /* Минимальный размер для touch */
    padding: 8px;
  }
}
```

## 🎯 Продвинутые примеры

### Динамическое управление типами

```javascript
const typeSelector = new TypeSelector({ mode: 'dropdown' })

// Добавление нового типа карты
typeSelector.addMapType({
  key: 'traffic',
  name: 'Пробки',
  title: 'Показать пробки на карте',
  icon: 'traffic',
  metadata: { provider: 'yandex' }
})

// Удаление типа карты
typeSelector.removeMapType('hybrid')

// Программное изменение типа
await typeSelector.setCurrentType('traffic')
```

### Условная видимость типов

```javascript
// Показать только базовые типы для мобильных
const isMobile = /iPhone|iPad|iPod|Android/i.test(navigator.userAgent)

const mapTypes = [
  { key: 'map', name: 'Схема', icon: 'map' },
  { key: 'satellite', name: 'Спутник', icon: 'satellite' }
]

if (!isMobile) {
  mapTypes.push(
    { key: 'hybrid', name: 'Гибрид', icon: 'hybrid' },
    { key: 'traffic', name: 'Пробки', icon: 'traffic' }
  )
}

const typeSelector = new TypeSelector({
  mapTypes,
  mode: isMobile ? 'compact' : 'dropdown'
})
```

### Vue с реактивными типами

```vue
<template>
  <TypeSelectorVue
    :map="map"
    :map-types="availableTypes"
    :mode="selectorMode"
    v-model:current-type="selectedType"
    @typechange="saveUserPreference"
  />
</template>

<script setup>
import { computed, ref } from 'vue'

const userRole = ref('guest')
const selectedType = ref('map')

// Типы карт в зависимости от роли пользователя
const availableTypes = computed(() => {
  const baseTypes = [
    { key: 'map', name: 'Схема', icon: 'map' },
    { key: 'satellite', name: 'Спутник', icon: 'satellite' }
  ]
  
  if (userRole.value === 'premium') {
    baseTypes.push(
      { key: 'hybrid', name: 'Гибрид', icon: 'hybrid' },
      { key: 'traffic', name: 'Пробки', icon: 'traffic' }
    )
  }
  
  return baseTypes
})

// Режим в зависимости от количества типов
const selectorMode = computed(() => {
  return availableTypes.value.length <= 2 ? 'buttons' : 'dropdown'
})

const saveUserPreference = (event) => {
  localStorage.setItem('preferred-map-type', event.newType)
}
</script>
```

### Интеграция с состоянием приложения (Pinia/Vuex)

```typescript
// store/mapStore.ts
export const useMapStore = defineStore('map', () => {
  const currentMapType = ref('map')
  const availableMapTypes = ref([
    { key: 'map', name: 'Схема', icon: 'map' },
    { key: 'satellite', name: 'Спутник', icon: 'satellite' },
    { key: 'hybrid', name: 'Гибрид', icon: 'hybrid' }
  ])
  
  const setMapType = (type: string) => {
    currentMapType.value = type
    // Сохранить в localStorage, отправить аналитику
    localStorage.setItem('mapType', type)
    analytics.track('map_type_changed', { type })
  }
  
  const addCustomMapType = (mapType: MapTypeConfig) => {
    availableMapTypes.value.push(mapType)
  }
  
  return { 
    currentMapType, 
    availableMapTypes, 
    setMapType, 
    addCustomMapType 
  }
})

// Component.vue
<template>
  <TypeSelectorVue
    :map="map"
    v-model:current-type="mapStore.currentMapType"
    :map-types="mapStore.availableMapTypes"
    @typechange="mapStore.setMapType"
  />
</template>
```

## 🐛 Решение проблем

### Селектор не отображается

```javascript
// Проверьте API ключ
const typeSelector = new TypeSelector({
  // ... options
})

// Убедитесь что карта создана
if (map && map.container) {
  await typeSelector.addToMap(map)
} else {
  console.error('Карта не готова или не существует')
}

// Проверьте CSS стили
const element = typeSelector.getElement()
console.log('Стили элемента:', window.getComputedStyle(element))
```

### Dropdown не открывается

```javascript
// Убедитесь что элемент получает события
const typeSelector = new TypeSelector({
  mode: 'dropdown',
  // Убедитесь что zIndex достаточно высокий
  zIndex: 1000
})

// Проверьте перекрытие другими элементами
const element = typeSelector.getElement()
console.log('z-index:', element.style.zIndex)
```

### Vue компонент не обновляется

```vue
<template>
  <!-- Убедитесь что передается корректный map -->
  <TypeSelectorVue
    :key="mapKey"  // Принудительное обновление при изменении карты
    :map="map"
    v-model:current-type="currentType"
  />
</template>

<script setup>
// Пересоздание при критических изменениях
watch([mapType, apiKey], async () => {
  mapKey.value++  // Принудительное пересоздание компонента
})
</script>
```

### Типы карт не переключаются

```javascript
// Проверьте что у карты есть метод setType
typeSelector.on('typechange', async (event) => {
  console.log('Попытка переключения:', event.newType)
  
  if (map && typeof map.setType === 'function') {
    await map.setType(event.newType)
    console.log('Успешно переключено')
  } else {
    console.error('Карта не поддерживает setType')
  }
})
```

## 🔍 Отладка и диагностика

### Включение debug режима

```javascript
// Глобальный debug режим
window.YMAPS_DEBUG = true

const typeSelector = new TypeSelector({
  // ... options
})

// События для отладки
typeSelector.on('*', (event) => {
  console.log(`[TypeSelector] ${event.type}:`, event)
})
```

### Проверка состояния

```javascript
// Получение информации о состоянии
console.log('Текущий тип:', typeSelector.getCurrentType())
console.log('Доступные типы:', typeSelector.getAvailableTypes())
console.log('Элемент DOM:', typeSelector.getElement())
console.log('Опции:', typeSelector.getOptions())
console.log('Видимость:', typeSelector.isVisible())
console.log('Активность:', typeSelector.isEnabled())
```

## 📚 См. также

- [ControlBase](../ControlBase.js) - Базовый класс для всех контролов
- [controlHelpers](../../utils/controlHelpers.js) - Утилиты для создания контролов
- [ZoomControl](../ZoomControl/) - Контрол управления масштабом
- [SearchControl](../SearchControl/) - Контрол поиска на карте

---

<div align="center">
  <strong>Создано с ❤️ для SPA Platform</strong><br>
  <sub>TypeSelector v1.0.0 | Production Ready</sub>
</div>
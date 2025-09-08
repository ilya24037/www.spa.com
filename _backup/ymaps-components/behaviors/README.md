# 🎮 MapBehaviors - Управление поведениями карты Yandex Maps

## 📋 Описание

Модуль `MapBehaviors` предоставляет полный контроль над интерактивными возможностями карты Яндекс. Позволяет включать/отключать различные поведения, настраивать ограничения и создавать кастомные интерактивные режимы.

## ✨ Возможности

- 🖱️ **Управление перетаскиванием** - настройка drag с инерцией
- 🔍 **Масштабирование** - двойной клик, колесо мыши, мультитач
- 👆 **Мультитач жесты** - поддержка сенсорных экранов
- 🔎 **Лупа** - увеличение правой/левой кнопкой мыши
- 📏 **Измерения** - линейка для измерения расстояний
- 🛣️ **Маршруты** - редактор маршрутов
- 🚫 **Ограничения** - области карты и диапазоны зума
- 🔒 **Блокировка** - полное отключение интерактивности
- 📱 **Мобильная оптимизация** - автоматическая адаптация
- 💡 **TypeScript** - полная типизация
- 🎮 **Vue 3** - готовый компонент с Composition API

## 📦 Состав модуля

```
behaviors/
├── MapBehaviors.js       # Основной класс (724 строки)
├── MapBehaviors.d.ts     # TypeScript определения
├── MapBehaviors.vue      # Vue 3 компонент (506 строк)
└── README.md             # Документация
```

## 🚀 Быстрый старт

### Vanilla JavaScript

```javascript
import YMapsCore from '../core/YMapsCore.js'
import MapBehaviors from './MapBehaviors.js'

// Инициализация карты
const mapsCore = new YMapsCore({ apiKey: 'your-key' })
const map = await mapsCore.createMap('map-container', {
  center: [55.753994, 37.622093],
  zoom: 10
})

// Создание менеджера поведений
const behaviors = new MapBehaviors(map, {
  drag: true,
  dblClickZoom: true,
  scrollZoom: true,
  multiTouch: true,
  dragOptions: {
    inertia: true,
    cursor: 'grab'
  }
})

// Управление поведениями
behaviors.disableScrollZoom() // Отключить зум колесом
behaviors.enableRuler() // Включить линейку
behaviors.lock() // Заблокировать карту
behaviors.unlock() // Разблокировать
```

### Vue 3

```vue
<template>
  <div class="map-container">
    <div id="map" style="height: 500px"></div>
    
    <YMapsBehaviors
      :map="mapInstance"
      :drag="settings.drag"
      :scroll-zoom="settings.scrollZoom"
      :dbl-click-zoom="settings.dblClickZoom"
      :locked="isLocked"
      @drag-start="onDragStart"
      @drag-end="onDragEnd"
      @zoom-change="onZoomChange"
    />
    
    <div class="controls">
      <label>
        <input v-model="settings.drag" type="checkbox" />
        Перетаскивание
      </label>
      <label>
        <input v-model="settings.scrollZoom" type="checkbox" />
        Зум колесом
      </label>
      <label>
        <input v-model="settings.dblClickZoom" type="checkbox" />
        Зум двойным кликом
      </label>
      <button @click="toggleLock">
        {{ isLocked ? 'Разблокировать' : 'Заблокировать' }}
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import YMapsCore from '@/ymaps-components/core/YMapsCore'
import YMapsBehaviors from '@/ymaps-components/behaviors/MapBehaviors.vue'

const mapInstance = ref(null)
const isLocked = ref(false)

const settings = reactive({
  drag: true,
  scrollZoom: true,
  dblClickZoom: true
})

onMounted(async () => {
  const mapsCore = new YMapsCore({ apiKey: 'your-key' })
  mapInstance.value = await mapsCore.createMap('map', {
    center: [55.753994, 37.622093],
    zoom: 10
  })
})

const toggleLock = () => {
  isLocked.value = !isLocked.value
}

const onDragStart = (event) => {
  console.log('Начало перетаскивания')
}

const onDragEnd = (event) => {
  console.log('Конец перетаскивания')
}

const onZoomChange = (event) => {
  console.log('Изменение масштаба')
}
</script>
```

## 📖 API Reference

### Класс MapBehaviors

#### Конструктор

```javascript
new MapBehaviors(map, options)
```

| Параметр | Тип | Описание |
|----------|-----|----------|
| `map` | Object | Экземпляр карты Yandex Maps |
| `options` | Object | Опции поведений |

#### Методы управления поведениями

##### enable(behavior)
Включает поведение или массив поведений.

```javascript
behaviors.enable('drag')
behaviors.enable(['drag', 'scrollZoom', 'dblClickZoom'])
```

##### disable(behavior)
Отключает поведение или массив поведений.

```javascript
behaviors.disable('scrollZoom')
```

##### isEnabled(behavior)
Проверяет, включено ли поведение.

```javascript
if (behaviors.isEnabled('drag')) {
  console.log('Перетаскивание включено')
}
```

#### Методы для конкретных поведений

```javascript
// Перетаскивание
behaviors.enableDrag()
behaviors.disableDrag()

// Масштабирование двойным кликом
behaviors.enableDblClickZoom()
behaviors.disableDblClickZoom()

// Масштабирование колесом
behaviors.enableScrollZoom()
behaviors.disableScrollZoom()

// Мультитач
behaviors.enableMultiTouch()
behaviors.disableMultiTouch()

// Линейка
behaviors.enableRuler()
behaviors.disableRuler()
```

#### Управление ограничениями

##### setRestrictMapArea(bounds)
Ограничивает область перемещения карты.

```javascript
behaviors.setRestrictMapArea([
  [55.70, 37.50], // юго-западный угол
  [55.80, 37.70]  // северо-восточный угол
])
```

##### setZoomRange(minZoom, maxZoom)
Ограничивает диапазон масштабирования.

```javascript
behaviors.setZoomRange(5, 15) // от 5 до 15 уровня
```

#### Блокировка карты

```javascript
behaviors.lock()    // Полностью блокирует интерактивность
behaviors.unlock()  // Восстанавливает предыдущие настройки
behaviors.isLocked() // Проверка блокировки
```

### Vue компонент

#### Props

| Prop | Тип | По умолчанию | Описание |
|------|-----|--------------|----------|
| `map` | Object | **required** | Экземпляр карты |
| `drag` | Boolean | true | Перетаскивание |
| `dblClickZoom` | Boolean | true | Зум двойным кликом |
| `multiTouch` | Boolean | true | Мультитач жесты |
| `scrollZoom` | Boolean | true | Зум колесом мыши |
| `rightMouseMagnifier` | Boolean | true | Лупа правой кнопкой |
| `leftMouseMagnifier` | Boolean | false | Лупа левой кнопкой |
| `ruler` | Boolean | false | Линейка |
| `routeEditor` | Boolean | false | Редактор маршрутов |
| `dragOptions` | Object | {...} | Опции перетаскивания |
| `zoomOptions` | Object | {...} | Опции масштабирования |
| `restrictMapArea` | Array | null | Ограничение области |
| `restrictZoomRange` | Array | null | Ограничение зума |
| `locked` | Boolean | false | Блокировка карты |
| `mobileOptimization` | Boolean | true | Мобильная оптимизация |

#### События

| Событие | Payload | Описание |
|---------|---------|----------|
| `ready` | MapBehaviors | Менеджер готов |
| `behaviorEnabled` | BehaviorType | Поведение включено |
| `behaviorDisabled` | BehaviorType | Поведение отключено |
| `dragStart` | Event | Начало перетаскивания |
| `drag` | Event | Перетаскивание |
| `dragEnd` | Event | Конец перетаскивания |
| `zoomStart` | Event | Начало зума |
| `zoomChange` | Event | Изменение зума |
| `zoomEnd` | Event | Конец зума |
| `locked` | - | Карта заблокирована |
| `unlocked` | - | Карта разблокирована |
| `stateChange` | State | Изменение состояния |

## 💡 Примеры использования

### Настройка перетаскивания с инерцией

```javascript
const behaviors = new MapBehaviors(map, {
  drag: true,
  dragOptions: {
    inertia: true,
    inertiaDuration: 500,
    cursor: 'grab',
    cursorDragging: 'grabbing'
  }
})
```

### Ограничение области просмотра

```javascript
// Ограничиваем картой Москвы
behaviors.setRestrictMapArea([
  [55.142, 36.803],  // Юго-запад
  [56.021, 37.968]   // Северо-восток
])

// Ограничиваем зум городским уровнем
behaviors.setZoomRange(10, 16)
```

### Мобильная версия

```javascript
const isMobile = /iPhone|iPad|Android/i.test(navigator.userAgent)

const behaviors = new MapBehaviors(map, {
  drag: true,
  multiTouch: true,
  // Отключаем тяжелые функции на мобильных
  rightMouseButtonMagnifier: !isMobile,
  ruler: !isMobile,
  routeEditor: !isMobile,
  dragOptions: {
    inertiaDuration: isMobile ? 300 : 500
  }
})
```

### Режим просмотра (read-only)

```javascript
class ReadOnlyMap {
  constructor(map) {
    this.behaviors = new MapBehaviors(map, {
      drag: false,
      dblClickZoom: false,
      scrollZoom: false,
      multiTouch: false,
      rightMouseButtonMagnifier: false
    })
  }
  
  enableNavigation() {
    this.behaviors.enable(['drag', 'scrollZoom'])
  }
  
  disableNavigation() {
    this.behaviors.disable(['drag', 'scrollZoom'])
  }
}
```

### Vue: Панель управления поведениями

```vue
<template>
  <div class="behaviors-panel">
    <h3>Настройки карты</h3>
    
    <div class="behavior-group">
      <h4>Навигация</h4>
      <label v-for="behavior in navigationBehaviors" :key="behavior.name">
        <input
          v-model="behavior.enabled"
          type="checkbox"
          @change="updateBehavior(behavior)"
        />
        {{ behavior.label }}
      </label>
    </div>
    
    <div class="behavior-group">
      <h4>Инструменты</h4>
      <label v-for="tool in tools" :key="tool.name">
        <input
          v-model="tool.enabled"
          type="checkbox"
          @change="updateBehavior(tool)"
        />
        {{ tool.label }}
      </label>
    </div>
    
    <div class="behavior-group">
      <h4>Ограничения</h4>
      <label>
        Мин. зум:
        <input
          v-model.number="zoomRange[0]"
          type="range"
          min="0"
          max="23"
          @change="updateZoomRange"
        />
        {{ zoomRange[0] }}
      </label>
      <label>
        Макс. зум:
        <input
          v-model.number="zoomRange[1]"
          type="range"
          min="0"
          max="23"
          @change="updateZoomRange"
        />
        {{ zoomRange[1] }}
      </label>
    </div>
    
    <div class="actions">
      <button @click="resetAll">Сбросить</button>
      <button @click="lockMap">
        {{ locked ? 'Разблокировать' : 'Заблокировать' }}
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive } from 'vue'

const props = defineProps({
  behaviors: Object // MapBehaviors instance
})

const navigationBehaviors = reactive([
  { name: 'drag', label: 'Перетаскивание', enabled: true },
  { name: 'scrollZoom', label: 'Зум колесом', enabled: true },
  { name: 'dblClickZoom', label: 'Зум двойным кликом', enabled: true },
  { name: 'multiTouch', label: 'Мультитач', enabled: true }
])

const tools = reactive([
  { name: 'ruler', label: 'Линейка', enabled: false },
  { name: 'routeEditor', label: 'Редактор маршрутов', enabled: false },
  { name: 'rightMouseButtonMagnifier', label: 'Лупа', enabled: false }
])

const zoomRange = ref([0, 23])
const locked = ref(false)

const updateBehavior = (behavior) => {
  if (!props.behaviors) return
  
  if (behavior.enabled) {
    props.behaviors.enable(behavior.name)
  } else {
    props.behaviors.disable(behavior.name)
  }
}

const updateZoomRange = () => {
  if (!props.behaviors) return
  props.behaviors.setZoomRange(zoomRange.value[0], zoomRange.value[1])
}

const resetAll = () => {
  if (!props.behaviors) return
  props.behaviors.reset()
  
  // Обновляем UI
  navigationBehaviors.forEach(b => {
    b.enabled = props.behaviors.isEnabled(b.name)
  })
  tools.forEach(t => {
    t.enabled = props.behaviors.isEnabled(t.name)
  })
}

const lockMap = () => {
  if (!props.behaviors) return
  
  if (locked.value) {
    props.behaviors.unlock()
    locked.value = false
  } else {
    props.behaviors.lock()
    locked.value = true
  }
}
</script>
```

### Кастомные поведения

```javascript
// Создание кастомного поведения "рисование"
behaviors.createCustomBehavior('drawing', {
  onEnable: (map, manager) => {
    // Отключаем перетаскивание при рисовании
    manager.disableDrag()
    
    // Добавляем обработчик клика
    map.events.add('click', this.handleDrawClick)
    
    // Меняем курсор
    map.container.getElement().style.cursor = 'crosshair'
  },
  
  onDisable: (map, manager) => {
    // Восстанавливаем перетаскивание
    manager.enableDrag()
    
    // Удаляем обработчик
    map.events.remove('click', this.handleDrawClick)
    
    // Восстанавливаем курсор
    map.container.getElement().style.cursor = ''
  },
  
  handleDrawClick: (e) => {
    const coords = e.get('coords')
    // Логика рисования
    console.log('Рисуем в точке:', coords)
  }
})

// Использование
behaviors.enableCustomBehavior('drawing')
// ...
behaviors.disableCustomBehavior('drawing')
```

## ⚙️ Оптимизация производительности

### Мобильные устройства

```javascript
const behaviors = new MapBehaviors(map, {
  // Базовые функции
  drag: true,
  multiTouch: true,
  
  // Отключаем тяжелые функции
  rightMouseButtonMagnifier: false,
  ruler: false,
  routeEditor: false,
  
  // Оптимизируем анимации
  dragOptions: {
    inertiaDuration: 300 // Быстрее для мобильных
  },
  zoomOptions: {
    duration: 200 // Быстрая анимация зума
  }
})
```

### Throttling событий

```vue
<YMapsBehaviors
  :event-throttle="100"
  @drag="onDrag"
  @zoom-change="onZoomChange"
/>
```

## 🎨 Список всех поведений

| Поведение | Описание | По умолчанию |
|-----------|----------|--------------|
| `drag` | Перетаскивание карты | ✅ |
| `dblClickZoom` | Масштабирование двойным кликом | ✅ |
| `scrollZoom` | Масштабирование колесом мыши | ✅ |
| `multiTouch` | Мультитач жесты | ✅ |
| `rightMouseButtonMagnifier` | Лупа правой кнопкой | ✅ |
| `leftMouseButtonMagnifier` | Лупа левой кнопкой | ❌ |
| `ruler` | Измерение расстояний | ❌ |
| `routeEditor` | Редактирование маршрутов | ❌ |

## 🐛 Решение проблем

### Поведения не работают

```javascript
// Проверьте готовность
if (!behaviors.isReady()) {
  console.log('Менеджер еще не готов')
  return
}

// Проверьте, что поведение включено
console.log('Включенные:', behaviors.getEnabled())

// Сбросьте к настройкам по умолчанию
behaviors.reset()
```

### Конфликты поведений

```javascript
// Некоторые поведения могут конфликтовать
// Например, ruler и drag

// Создайте режимы работы
class MapModes {
  constructor(behaviors) {
    this.behaviors = behaviors
  }
  
  navigationMode() {
    this.behaviors.enable(['drag', 'scrollZoom'])
    this.behaviors.disable(['ruler', 'routeEditor'])
  }
  
  measureMode() {
    this.behaviors.enable('ruler')
    this.behaviors.disable('drag')
  }
  
  editMode() {
    this.behaviors.enable('routeEditor')
    this.behaviors.disable(['ruler', 'drag'])
  }
}
```

## 🔗 Связанные модули

- [YMapsCore](../core/README.md) - Ядро системы
- [Placemark](../modules/Placemark/README.md) - Метки на карте
- [Clusterer](../modules/Clusterer/README.md) - Кластеризация

## 📝 Лицензия

Модуль предоставляется для использования в проектах с Yandex Maps API.

## 🤝 Поддержка

При возникновении вопросов обращайтесь к команде разработки SPA Platform.
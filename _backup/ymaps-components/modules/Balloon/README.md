# 🎈 Balloon - Всплывающие окна для Yandex Maps

## 📋 Описание

Модуль `Balloon` предоставляет функционал всплывающих информационных окон для карт Яндекс. Включает JavaScript класс, TypeScript определения и готовый Vue 3 компонент.

## ✨ Возможности

- 🎯 **Гибкое позиционирование** - привязка к любой точке на карте
- 📱 **Адаптивность** - автоматический режим панели для маленьких экранов
- 🎨 **Кастомизация** - полный контроль над внешним видом
- 🚀 **Автопанорамирование** - автоматическая прокрутка карты к окну
- ⚡ **Анимации** - плавное открытие и закрытие
- 🔒 **Безопасность** - санитизация HTML контента
- 🌙 **Темная тема** - автоматическая поддержка
- 💡 **TypeScript** - полная типизация
- 🎮 **Vue 3** - готовый компонент с Composition API

## 📦 Состав модуля

```
Balloon/
├── Balloon.js       # Основной класс (748 строк)
├── Balloon.d.ts     # TypeScript определения
├── Balloon.vue      # Vue 3 компонент (809 строк)
└── README.md        # Документация
```

## 🚀 Быстрый старт

### Vanilla JavaScript

```javascript
import YMapsCore from '../../core/YMapsCore.js'
import Balloon from './Balloon.js'

// Инициализация карты
const mapsCore = new YMapsCore({ apiKey: 'your-key' })
const map = await mapsCore.createMap('map-container', {
  center: [55.753994, 37.622093],
  zoom: 10
})

// Создание balloon
const balloon = new Balloon(map, {
  closeButton: true,
  autoPan: true,
  maxWidth: 300
})

// Открытие balloon
await balloon.open(
  [55.753994, 37.622093], // позиция
  '<h3>Заголовок</h3><p>Содержимое balloon</p>' // контент
)
```

### Vue 3

```vue
<template>
  <div id="map" style="height: 400px"></div>
  
  <YMapsBalloon
    v-model="showBalloon"
    :map="mapInstance"
    :position="[55.753994, 37.622093]"
    :header="'Москва'"
    :content="'Столица России'"
    :footer="'Нажмите для подробностей'"
    @open="onBalloonOpen"
    @close="onBalloonClose"
  />
</template>

<script setup>
import { ref, onMounted } from 'vue'
import YMapsCore from '@/ymaps-components/core/YMapsCore'
import YMapsBalloon from '@/ymaps-components/modules/Balloon/Balloon.vue'

const mapInstance = ref(null)
const showBalloon = ref(false)

onMounted(async () => {
  const mapsCore = new YMapsCore({ apiKey: 'your-key' })
  mapInstance.value = await mapsCore.createMap('map', {
    center: [55.753994, 37.622093],
    zoom: 10
  })
  
  // Показать balloon через секунду
  setTimeout(() => {
    showBalloon.value = true
  }, 1000)
})

const onBalloonOpen = () => {
  console.log('Balloon открыт')
}

const onBalloonClose = () => {
  console.log('Balloon закрыт')
}
</script>
```

## 📖 API Reference

### Класс Balloon

#### Конструктор

```javascript
new Balloon(map, options)
```

| Параметр | Тип | Описание |
|----------|-----|----------|
| `map` | Object | Экземпляр карты Yandex Maps |
| `options` | Object | Опции balloon |

#### Методы

##### open(position, content, options)
Открывает balloon в указанной позиции.

```javascript
await balloon.open(
  [55.76, 37.64],
  'Содержимое',
  { autoPan: true }
)
```

##### close(force)
Закрывает balloon.

```javascript
await balloon.close() // с анимацией
await balloon.close(true) // мгновенно
```

##### setPosition(position)
Изменяет позицию открытого balloon.

```javascript
balloon.setPosition([55.77, 37.65])
```

##### setContent(content)
Изменяет содержимое balloon.

```javascript
balloon.setContent('<b>Новое содержимое</b>')
```

##### autoPan()
Выполняет автопанорамирование карты к balloon.

```javascript
await balloon.autoPan()
```

##### isOpen()
Проверяет, открыт ли balloon.

```javascript
if (balloon.isOpen()) {
  console.log('Balloon открыт')
}
```

##### destroy()
Уничтожает экземпляр и освобождает ресурсы.

```javascript
balloon.destroy()
```

### Vue компонент

#### Props

| Prop | Тип | По умолчанию | Описание |
|------|-----|--------------|----------|
| `map` | Object | **required** | Экземпляр карты |
| `position` | Array/Object | null | Позиция balloon |
| `content` | String/Object | '' | Основное содержимое |
| `header` | String | '' | Заголовок |
| `footer` | String | '' | Футер |
| `modelValue` | Boolean | false | v-model для видимости |
| `closeButton` | Boolean | true | Показывать кнопку закрытия |
| `autoPan` | Boolean | true | Автопанорамирование |
| `autoPanMargin` | Number | 34 | Отступ от краев |
| `maxWidth` | Number | 400 | Максимальная ширина |
| `maxHeight` | Number | 400 | Максимальная высота |
| `minWidth` | Number | 85 | Минимальная ширина |
| `minHeight` | Number | 30 | Минимальная высота |
| `offset` | Array | [0, 0] | Смещение от точки |
| `zIndex` | Number | 1000 | Z-index |
| `openDelay` | Number | 0 | Задержка открытия |
| `closeDelay` | Number | 0 | Задержка закрытия |
| `panelMaxMapArea` | Number | 160000 | Площадь для панели |

#### События

| Событие | Payload | Описание |
|---------|---------|----------|
| `update:modelValue` | Boolean | Изменение видимости |
| `open` | - | Balloon открыт |
| `close` | - | Balloon закрыт |
| `userClose` | - | Закрыто пользователем |
| `autopanStart` | - | Начало автопанорамирования |
| `autopanEnd` | - | Конец автопанорамирования |
| `error` | Error | Ошибка |

#### Слоты

```vue
<YMapsBalloon>
  <!-- Основной контент -->
  <template #default>
    <div class="custom-content">
      <img src="photo.jpg" />
      <p>Кастомное содержимое</p>
    </div>
  </template>
  
  <!-- Футер -->
  <template #footer>
    <button @click="showDetails">Подробнее</button>
  </template>
</YMapsBalloon>
```

## 💡 Примеры использования

### Простой balloon

```javascript
const balloon = new Balloon(map)

// Текстовое содержимое
await balloon.open([55.76, 37.64], 'Простой текст')

// HTML содержимое
await balloon.open(
  [55.76, 37.64],
  '<h3>Заголовок</h3><p>Параграф текста</p>'
)

// Объект с полями
await balloon.open([55.76, 37.64], {
  header: 'Заголовок',
  body: 'Основной текст',
  footer: 'Дополнительная информация'
})
```

### Balloon с опциями

```javascript
const balloon = new Balloon(map, {
  closeButton: false,      // Без кнопки закрытия
  autoPan: true,           // С автопанорамированием
  autoPanMargin: 50,       // Отступ 50px
  maxWidth: 250,           // Максимальная ширина 250px
  openTimeout: 500,        // Задержка открытия 500мс
  closeTimeout: 300        // Задержка закрытия 300мс
})

await balloon.open([55.76, 37.64], 'Контент', {
  offset: [0, -20]  // Смещение на 20px вверх
})
```

### Управление позицией

```javascript
// Открываем в одной точке
await balloon.open([55.76, 37.64], 'Начальная позиция')

// Перемещаем в другую точку
setTimeout(() => {
  balloon.setPosition([55.77, 37.65])
}, 2000)

// Автопанорамирование к новой позиции
setTimeout(() => {
  balloon.autoPan()
}, 2500)
```

### Динамическое содержимое

```javascript
const balloon = new Balloon(map)

// Открываем с начальным содержимым
await balloon.open([55.76, 37.64], 'Загрузка...')

// Загружаем данные
const data = await fetch('/api/location-info')
const info = await data.json()

// Обновляем содержимое
balloon.setContent(`
  <h3>${info.title}</h3>
  <p>${info.description}</p>
  <img src="${info.image}" alt="${info.title}" />
`)
```

### Vue: Интерактивный balloon

```vue
<template>
  <YMapsBalloon
    v-model="showBalloon"
    :map="map"
    :position="currentPosition"
    @userClose="handleUserClose"
  >
    <div class="location-info">
      <h3>{{ location.name }}</h3>
      <p>{{ location.address }}</p>
      <div class="rating">
        ⭐ {{ location.rating }}
      </div>
      <button @click="showDetails">
        Подробнее
      </button>
    </div>
  </YMapsBalloon>
</template>

<script setup>
import { ref, reactive } from 'vue'

const showBalloon = ref(false)
const currentPosition = ref([55.76, 37.64])

const location = reactive({
  name: 'Красная площадь',
  address: 'Москва, Россия',
  rating: 4.8
})

const handleUserClose = () => {
  console.log('Пользователь закрыл balloon')
}

const showDetails = () => {
  router.push(`/location/${location.id}`)
}
</script>
```

### Множественные balloon

```javascript
class MultiBalloonsManager {
  constructor(map) {
    this.map = map
    this.balloons = new Map()
  }
  
  async showBalloon(id, position, content) {
    // Закрываем все другие balloon
    for (const [balloonId, balloon] of this.balloons) {
      if (balloonId !== id) {
        await balloon.close()
      }
    }
    
    // Создаем или переиспользуем balloon
    let balloon = this.balloons.get(id)
    if (!balloon) {
      balloon = new Balloon(this.map)
      this.balloons.set(id, balloon)
    }
    
    // Открываем balloon
    await balloon.open(position, content)
  }
  
  async closeAll() {
    for (const balloon of this.balloons.values()) {
      await balloon.close()
    }
  }
  
  destroy() {
    for (const balloon of this.balloons.values()) {
      balloon.destroy()
    }
    this.balloons.clear()
  }
}

// Использование
const manager = new MultiBalloonsManager(map)

// Показываем balloon для разных точек
await manager.showBalloon('point1', [55.76, 37.64], 'Точка 1')
await manager.showBalloon('point2', [55.77, 37.65], 'Точка 2')
```

## 🎨 Стилизация

### CSS переменные

```css
.ymaps-balloon {
  --balloon-bg: #ffffff;
  --balloon-color: #333333;
  --balloon-border-radius: 8px;
  --balloon-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
  --balloon-padding: 12px;
  --balloon-max-width: 400px;
}

/* Темная тема */
@media (prefers-color-scheme: dark) {
  .ymaps-balloon {
    --balloon-bg: #2c2c2c;
    --balloon-color: #e0e0e0;
  }
}
```

### Кастомные стили

```css
/* Стиль Material Design */
.ymaps-balloon.material {
  border-radius: 4px;
  box-shadow: 0 3px 14px rgba(0,0,0,0.4);
  padding: 16px;
}

/* Стиль с градиентом */
.ymaps-balloon.gradient {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
}

/* Анимированный balloon */
.ymaps-balloon.animated {
  animation: pulse 2s infinite;
}

@keyframes pulse {
  0% { transform: scale(1); }
  50% { transform: scale(1.05); }
  100% { transform: scale(1); }
}
```

## ⚙️ Конфигурация

### Режимы отображения

**Обычный режим** - balloon отображается рядом с точкой:
```javascript
const balloon = new Balloon(map, {
  panelMaxMapArea: Infinity // Всегда обычный режим
})
```

**Панельный режим** - balloon отображается внизу экрана:
```javascript
const balloon = new Balloon(map, {
  panelMaxMapArea: 200000 // Панель при площади < 200000px²
})
```

### Производительность

Для оптимизации при большом количестве balloon:

```javascript
// Переиспользование одного экземпляра
const sharedBalloon = new Balloon(map, {
  closeButton: true
})

markers.forEach(marker => {
  marker.events.add('click', async () => {
    await sharedBalloon.open(
      marker.geometry.getCoordinates(),
      marker.properties.get('balloonContent')
    )
  })
})
```

### Доступность (a11y)

```vue
<YMapsBalloon
  :close-button-label="'Закрыть информационное окно'"
  :aria-live="'polite'"
  :role="'tooltip'"
>
  <div role="article" aria-label="Информация о месте">
    <!-- Контент с семантической разметкой -->
  </div>
</YMapsBalloon>
```

## 🐛 Решение проблем

### Balloon не отображается

```javascript
// Проверьте, что карта загружена
if (!map) {
  console.error('Карта не инициализирована')
  return
}

// Проверьте позицию
const position = [55.76, 37.64]
if (!position || position.length !== 2) {
  console.error('Некорректная позиция')
  return
}

// Проверьте z-index
const balloon = new Balloon(map, {
  zIndex: 9999 // Увеличьте z-index
})
```

### Проблемы с автопанорамированием

```javascript
const balloon = new Balloon(map, {
  autoPan: true,
  autoPanMargin: 50, // Увеличьте отступ
  autoPanDuration: 300, // Уменьшите длительность
  autoPanCheckZoomRange: false // Отключите проверку зума
})
```

### Balloon обрезается

```javascript
// Используйте режим панели для маленьких экранов
const balloon = new Balloon(map, {
  panelMaxMapArea: 320 * 568 // iPhone SE
})

// Или уменьшите размеры
const balloon = new Balloon(map, {
  maxWidth: 280,
  maxHeight: 200
})
```

## 🔗 Связанные модули

- [YMapsCore](../../core/README.md) - Ядро системы
- [Placemark](../Placemark/README.md) - Метки на карте
- [Clusterer](../Clusterer/README.md) - Кластеризация

## 📝 Лицензия

Модуль предоставляется для использования в проектах с Yandex Maps API.

## 🤝 Поддержка

При возникновении вопросов обращайтесь к команде разработки SPA Platform.
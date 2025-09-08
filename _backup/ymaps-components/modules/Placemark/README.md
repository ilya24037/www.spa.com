# 📍 Placemark - Метки для Yandex Maps

## 📋 Описание

Модуль `Placemark` предоставляет функционал меток для карт Яндекс. Включает JavaScript класс, TypeScript определения и готовый Vue 3 компонент.

## ✨ Возможности

- 📍 **Разнообразные стили** - 48+ preset стилей меток
- 🎨 **Кастомизация** - любые изображения и HTML контент
- 🚀 **Анимации** - bounce, drop, pulse, shake эффекты
- 📱 **Drag & Drop** - перетаскивание меток мышью
- 🎈 **Интеграция с Balloon** - всплывающие окна
- 💬 **Хинты** - подсказки при наведении
- 🎯 **События** - полный набор обработчиков
- 🌙 **Темная тема** - автоматическая адаптация
- 💡 **TypeScript** - полная типизация
- 🎮 **Vue 3** - готовый компонент с Composition API

## 📦 Состав модуля

```
Placemark/
├── Placemark.js       # Основной класс (892 строки)
├── Placemark.d.ts     # TypeScript определения
├── Placemark.vue      # Vue 3 компонент (684 строки)
└── README.md          # Документация
```

## 🚀 Быстрый старт

### Vanilla JavaScript

```javascript
import YMapsCore from '../../core/YMapsCore.js'
import Placemark from './Placemark.js'

// Инициализация карты
const mapsCore = new YMapsCore({ apiKey: 'your-key' })
const map = await mapsCore.createMap('map-container', {
  center: [55.753994, 37.622093],
  zoom: 10
})

// Создание простой метки
const placemark = new Placemark(
  [55.753994, 37.622093], // позиция
  { 
    balloonContent: 'Москва, Красная площадь',
    hintContent: 'Нажмите для информации'
  },
  {
    preset: 'islands#redIcon',
    draggable: true
  }
)

// Добавление на карту
await placemark.addToMap(map)
```

### Vue 3

```vue
<template>
  <div id="map" style="height: 400px"></div>
  
  <YMapsPlacemark
    :map="mapInstance"
    :position="[55.753994, 37.622093]"
    preset="islands#blueCircleIcon"
    :draggable="true"
    icon-content="1"
    hint-content="Метка №1"
    balloon-header="Заголовок"
    balloon-body="Описание места"
    @click="onPlacemarkClick"
    @dragend="onDragEnd"
  />
</template>

<script setup>
import { ref, onMounted } from 'vue'
import YMapsCore from '@/ymaps-components/core/YMapsCore'
import YMapsPlacemark from '@/ymaps-components/modules/Placemark/Placemark.vue'

const mapInstance = ref(null)

onMounted(async () => {
  const mapsCore = new YMapsCore({ apiKey: 'your-key' })
  mapInstance.value = await mapsCore.createMap('map', {
    center: [55.753994, 37.622093],
    zoom: 10
  })
})

const onPlacemarkClick = (event) => {
  console.log('Клик по метке', event)
}

const onDragEnd = (event) => {
  console.log('Новая позиция:', event)
}
</script>
```

## 📖 API Reference

### Класс Placemark

#### Конструктор

```javascript
new Placemark(position, properties, options)
```

| Параметр | Тип | Описание |
|----------|-----|----------|
| `position` | Array/Object | Позиция метки [lat, lng] |
| `properties` | Object | Свойства метки |
| `options` | Object | Опции отображения |

#### Методы

##### addToMap(map)
Добавляет метку на карту.

```javascript
await placemark.addToMap(map)
```

##### removeFromMap()
Удаляет метку с карты.

```javascript
await placemark.removeFromMap()
```

##### setPosition(position, animate)
Изменяет позицию метки.

```javascript
await placemark.setPosition([55.77, 37.65], true) // с анимацией
```

##### setIcon(options)
Изменяет иконку метки.

```javascript
// Preset стиль
placemark.setIcon('islands#greenDotIcon')

// Кастомное изображение
placemark.setIcon({
  iconImageHref: '/images/marker.png',
  iconImageSize: [30, 42],
  iconImageOffset: [-15, -42]
})
```

##### enableDragging() / disableDragging()
Управляет возможностью перетаскивания.

```javascript
placemark.enableDragging()
// ...
placemark.disableDragging()
```

##### animate(type, options)
Анимирует метку.

```javascript
await placemark.animate('bounce', { duration: 1000 })
```

### Vue компонент

#### Props

| Prop | Тип | По умолчанию | Описание |
|------|-----|--------------|----------|
| `map` | Object | **required** | Экземпляр карты |
| `position` | Array/Object | **required** | Позиция метки |
| `preset` | String | 'islands#blueIcon' | Preset стиль |
| `icon` | String | '' | URL кастомной иконки |
| `iconSize` | Array | [30, 42] | Размер иконки |
| `iconOffset` | Array | [-15, -42] | Смещение иконки |
| `iconColor` | String | '' | Цвет иконки |
| `iconContent` | String | '' | Текст в метке |
| `balloonContent` | String/Object | '' | Содержимое balloon |
| `balloonHeader` | String | '' | Заголовок balloon |
| `balloonBody` | String | '' | Текст balloon |
| `balloonFooter` | String | '' | Футер balloon |
| `hintContent` | String | '' | Текст хинта |
| `draggable` | Boolean | false | Можно перетаскивать |
| `visible` | Boolean | true | Видимость |
| `animation` | String | '' | Анимация появления |
| `opacity` | Number | 1 | Прозрачность |
| `zIndex` | Number | 0 | Z-индекс |

#### События

| Событие | Payload | Описание |
|---------|---------|----------|
| `click` | Event | Клик по метке |
| `dblclick` | Event | Двойной клик |
| `contextmenu` | Event | Правый клик |
| `mouseenter` | Event | Наведение мыши |
| `mouseleave` | Event | Уход мыши |
| `dragstart` | Event | Начало перетаскивания |
| `drag` | Event | Перетаскивание |
| `dragend` | Event | Конец перетаскивания |
| `positionChange` | [lat, lng] | Изменение позиции |
| `ready` | Placemark | Метка готова |

#### Слоты

```vue
<YMapsPlacemark>
  <!-- Кастомный HTML контент метки -->
  <template #default>
    <div class="custom-marker">
      <img src="avatar.jpg" />
      <span>Пользователь</span>
    </div>
  </template>
</YMapsPlacemark>
```

## 💡 Примеры использования

### Простая метка

```javascript
const placemark = new Placemark(
  [55.76, 37.64],
  { 
    balloonContent: 'Простая метка',
    hintContent: 'Подсказка'
  },
  {
    preset: 'islands#blueIcon'
  }
)
await placemark.addToMap(map)
```

### Метка с номером

```javascript
const placemark = new Placemark(
  [55.76, 37.64],
  {},
  {
    preset: 'islands#blueCircleIcon',
    iconContent: '42'
  }
)
```

### Кастомная иконка

```javascript
const placemark = new Placemark(
  [55.76, 37.64],
  {},
  {
    iconImageHref: '/images/custom-marker.png',
    iconImageSize: [40, 40],
    iconImageOffset: [-20, -20]
  }
)
```

### Перетаскиваемая метка

```javascript
const placemark = new Placemark(
  [55.76, 37.64],
  {},
  {
    preset: 'islands#redIcon',
    draggable: true
  }
)

// Обработка перетаскивания
placemark.on('dragend', (event) => {
  const newPosition = event.coords
  console.log('Новая позиция:', newPosition)
})
```

### Анимированная метка

```javascript
const placemark = new Placemark([55.76, 37.64])
await placemark.addToMap(map)

// Анимация появления
await placemark.animate('drop', { duration: 1000 })

// Пульсация при клике
placemark.on('click', async () => {
  await placemark.animate('pulse', { 
    duration: 500,
    iterations: 3
  })
})
```

### Vue: Интерактивная карта с метками

```vue
<template>
  <div class="map-container">
    <div id="map" style="height: 500px"></div>
    
    <YMapsPlacemark
      v-for="marker in markers"
      :key="marker.id"
      :map="map"
      :position="marker.position"
      :preset="marker.preset"
      :icon-content="marker.number.toString()"
      :balloon-header="marker.name"
      :balloon-body="marker.description"
      :draggable="isEditMode"
      @click="selectMarker(marker)"
      @dragend="updateMarkerPosition(marker, $event)"
    />
    
    <div class="controls">
      <button @click="toggleEditMode">
        {{ isEditMode ? 'Сохранить' : 'Редактировать' }}
      </button>
      <button @click="addMarker">Добавить метку</button>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive } from 'vue'

const map = ref(null)
const isEditMode = ref(false)
const markers = reactive([
  {
    id: 1,
    position: [55.753994, 37.622093],
    preset: 'islands#blueCircleIcon',
    number: 1,
    name: 'Красная площадь',
    description: 'Главная площадь Москвы'
  },
  {
    id: 2,
    position: [55.760000, 37.625000],
    preset: 'islands#greenCircleIcon',
    number: 2,
    name: 'Парк Горького',
    description: 'Центральный парк'
  }
])

const selectMarker = (marker) => {
  console.log('Выбрана метка:', marker.name)
}

const updateMarkerPosition = (marker, event) => {
  marker.position = event
  console.log(`Метка ${marker.name} перемещена`)
}

const addMarker = () => {
  markers.push({
    id: Date.now(),
    position: [55.755, 37.620],
    preset: 'islands#redCircleIcon',
    number: markers.length + 1,
    name: `Метка ${markers.length + 1}`,
    description: 'Новая метка'
  })
}

const toggleEditMode = () => {
  isEditMode.value = !isEditMode.value
}
</script>
```

## 🎨 Preset стили

### Доступные цвета
- `blue` - синий
- `red` - красный
- `darkGreen` - темно-зеленый
- `violet` - фиолетовый
- `black` - черный
- `gray` - серый
- `brown` - коричневый
- `night` - ночной
- `darkBlue` - темно-синий
- `darkOrange` - темно-оранжевый
- `pink` - розовый
- `olive` - оливковый

### Типы меток
- `Icon` - обычная метка
- `CircleIcon` - круглая метка
- `DotIcon` - точка
- `StretchyIcon` - растягиваемая метка

### Примеры preset
```javascript
'islands#blueIcon'          // Синяя метка
'islands#redCircleIcon'      // Красный круг
'islands#greenDotIcon'       // Зеленая точка
'islands#violetStretchyIcon' // Фиолетовая растягиваемая
```

## ⚙️ Конфигурация

### Фабричные функции

```javascript
import {
  createSimplePlacemark,
  createTextPlacemark,
  createImagePlacemark,
  createCirclePlacemark
} from './Placemark.js'

// Простая метка
const simple = createSimplePlacemark([55.76, 37.64])

// Метка с текстом
const text = createTextPlacemark([55.76, 37.64], '42')

// Метка с изображением
const image = createImagePlacemark(
  [55.76, 37.64],
  '/images/marker.png'
)

// Круглая метка
const circle = createCirclePlacemark([55.76, 37.64], '#FF0000')
```

### Производительность

Для большого количества меток используйте кластеризацию:

```javascript
import Clusterer from '../Clusterer/Clusterer.js'

const clusterer = new Clusterer(map, {
  preset: 'islands#blueClusterIcons'
})

// Добавляем много меток
for (let i = 0; i < 1000; i++) {
  const placemark = new Placemark([lat, lng])
  clusterer.add(placemark)
}
```

## 🐛 Решение проблем

### Метка не отображается

```javascript
// Проверьте, что карта загружена
if (!map) {
  console.error('Карта не инициализирована')
  return
}

// Проверьте координаты
const position = [55.76, 37.64]
if (!position || position.length !== 2) {
  console.error('Некорректные координаты')
  return
}

// Проверьте видимость
placemark.show()
```

### Перетаскивание не работает

```javascript
// Убедитесь, что draggable установлен
placemark.enableDragging()

// Проверьте обработчики
placemark.on('dragstart', () => console.log('Начало'))
placemark.on('drag', () => console.log('Перетаскивание'))
placemark.on('dragend', () => console.log('Конец'))
```

### Balloon не открывается

```javascript
// Проверьте содержимое
placemark.setBalloonContent('Содержимое balloon')

// Откройте программно
await placemark.openBalloon()
```

## 🔗 Связанные модули

- [YMapsCore](../../core/README.md) - Ядро системы
- [Balloon](../Balloon/README.md) - Всплывающие окна
- [Clusterer](../Clusterer/README.md) - Кластеризация меток

## 📝 Лицензия

Модуль предоставляется для использования в проектах с Yandex Maps API.

## 🤝 Поддержка

При возникновении вопросов обращайтесь к команде разработки SPA Platform.
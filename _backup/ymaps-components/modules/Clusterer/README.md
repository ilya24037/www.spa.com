# 🎯 Clusterer - Кластеризация меток для Yandex Maps

## 📋 Описание

Модуль `Clusterer` предоставляет функционал кластеризации меток для карт Яндекс. Автоматически группирует близко расположенные метки в кластеры для улучшения производительности и удобства использования.

## ✨ Возможности

- 🎯 **Автоматическая группировка** - объединение близких меток в кластеры
- 📊 **Масштабируемость** - эффективная работа с тысячами меток
- 🎨 **16+ preset стилей** - готовые цветовые схемы кластеров
- ⚙️ **Гибкая настройка** - размер сетки, минимальный размер кластера
- 📱 **Адаптивность** - автоматическая перестройка при изменении масштаба
- 🎈 **Интеграция с Balloon** - информационные окна для кластеров
- 💬 **Хинты** - подсказки при наведении
- 🎮 **Кастомизация** - возможность создания собственных макетов
- 💡 **TypeScript** - полная типизация
- 🎮 **Vue 3** - готовый компонент с Composition API

## 📦 Состав модуля

```
Clusterer/
├── Clusterer.js       # Основной класс (816 строк)
├── Clusterer.d.ts     # TypeScript определения
├── Clusterer.vue      # Vue 3 компонент (483 строки)
└── README.md          # Документация
```

## 🚀 Быстрый старт

### Vanilla JavaScript

```javascript
import YMapsCore from '../../core/YMapsCore.js'
import Clusterer from './Clusterer.js'
import Placemark from '../Placemark/Placemark.js'

// Инициализация карты
const mapsCore = new YMapsCore({ apiKey: 'your-key' })
const map = await mapsCore.createMap('map-container', {
  center: [55.753994, 37.622093],
  zoom: 10
})

// Создание кластеризатора
const clusterer = new Clusterer(map, {
  preset: 'islands#blueClusterIcons',
  gridSize: 60,
  minClusterSize: 2
})

// Создание и добавление меток
const placemarks = []
for (let i = 0; i < 100; i++) {
  const placemark = new Placemark(
    [55.75 + Math.random() * 0.1, 37.62 + Math.random() * 0.1],
    { 
      balloonContent: `Метка ${i + 1}`,
      hintContent: `Подсказка ${i + 1}`
    }
  )
  placemarks.push(placemark)
}

// Добавление меток в кластеризатор
await clusterer.add(placemarks)
```

### Vue 3

```vue
<template>
  <div class="map-container">
    <div id="map" style="height: 500px"></div>
    
    <YMapsClusterer
      :map="mapInstance"
      :placemarks="markers"
      preset="islands#redClusterIcons"
      :grid-size="80"
      :min-cluster-size="3"
      :auto-fit="true"
      @cluster-click="onClusterClick"
      @placemarks-change="onPlacemarksChange"
    />
    
    <div class="controls">
      <button @click="addRandomMarkers">
        Добавить 50 меток
      </button>
      <button @click="clearAll">
        Очистить все
      </button>
      <p>Всего меток: {{ totalMarkers }}</p>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import YMapsCore from '@/ymaps-components/core/YMapsCore'
import YMapsClusterer from '@/ymaps-components/modules/Clusterer/Clusterer.vue'
import Placemark from '@/ymaps-components/modules/Placemark/Placemark'

const mapInstance = ref(null)
const markers = ref([])
const totalMarkers = ref(0)

onMounted(async () => {
  const mapsCore = new YMapsCore({ apiKey: 'your-key' })
  mapInstance.value = await mapsCore.createMap('map', {
    center: [55.753994, 37.622093],
    zoom: 10
  })
  
  // Добавляем начальные метки
  addRandomMarkers()
})

const addRandomMarkers = () => {
  const newMarkers = []
  
  for (let i = 0; i < 50; i++) {
    const marker = new Placemark(
      [55.75 + Math.random() * 0.2, 37.62 + Math.random() * 0.2],
      { 
        balloonContent: `Метка ${markers.value.length + i + 1}` 
      }
    )
    newMarkers.push(marker)
  }
  
  markers.value = [...markers.value, ...newMarkers]
}

const clearAll = () => {
  markers.value = []
}

const onClusterClick = (cluster, event) => {
  console.log('Клик по кластеру:', cluster)
}

const onPlacemarksChange = (count) => {
  totalMarkers.value = count
}
</script>
```

## 📖 API Reference

### Класс Clusterer

#### Конструктор

```javascript
new Clusterer(map, options)
```

| Параметр | Тип | Описание |
|----------|-----|----------|
| `map` | Object | Экземпляр карты Yandex Maps |
| `options` | Object | Опции кластеризатора |

#### Методы

##### add(placemark)
Добавляет метку или массив меток в кластеризатор.

```javascript
// Одна метка
await clusterer.add(placemark)

// Массив меток
await clusterer.add([placemark1, placemark2, placemark3])
```

##### remove(placemark)
Удаляет метку или массив меток из кластеризатора.

```javascript
await clusterer.remove(placemark)
```

##### removeAll()
Удаляет все метки из кластеризатора.

```javascript
await clusterer.removeAll()
```

##### fitToViewport(options)
Центрирует карту по всем меткам.

```javascript
await clusterer.fitToViewport({
  checkZoomRange: true,
  zoomMargin: 50
})
```

##### refresh()
Перестраивает кластеры.

```javascript
clusterer.refresh()
```

##### setGridSize(size)
Устанавливает размер сетки кластеризации.

```javascript
clusterer.setGridSize(100) // размер в пикселях
```

##### setMinClusterSize(size)
Устанавливает минимальное количество меток для создания кластера.

```javascript
clusterer.setMinClusterSize(3)
```

### Vue компонент

#### Props

| Prop | Тип | По умолчанию | Описание |
|------|-----|--------------|----------|
| `map` | Object | **required** | Экземпляр карты |
| `placemarks` | Array | [] | Массив меток |
| `preset` | String | 'islands#blueClusterIcons' | Preset стиль |
| `gridSize` | Number | 60 | Размер сетки (10-300) |
| `minClusterSize` | Number | 2 | Минимальный размер (2-100) |
| `maxZoom` | Number | 16 | Максимальный зум |
| `zoomMargin` | Number | 2 | Отступ при зуме |
| `hasBalloon` | Boolean | true | Показывать balloon |
| `hasHint` | Boolean | true | Показывать хинты |
| `disableClickZoom` | Boolean | false | Отключить зум по клику |
| `autoFit` | Boolean | false | Автоцентрирование |
| `autoFitDelay` | Number | 100 | Задержка автоцентрирования |

#### События

| Событие | Payload | Описание |
|---------|---------|----------|
| `ready` | Clusterer | Кластеризатор готов |
| `clusterAdd` | Cluster | Добавлен кластер |
| `clusterRemove` | Cluster | Удален кластер |
| `clusterClick` | Cluster, Event | Клик по кластеру |
| `placemarksChange` | Number | Изменение количества меток |
| `boundsChange` | Bounds | Изменение границ |
| `error` | Error | Ошибка |

## 💡 Примеры использования

### Базовая кластеризация

```javascript
const clusterer = new Clusterer(map)

// Добавляем много меток
for (let i = 0; i < 500; i++) {
  const lat = 55.75 + (Math.random() - 0.5) * 0.5
  const lng = 37.62 + (Math.random() - 0.5) * 0.5
  
  const placemark = new Placemark([lat, lng], {
    balloonContent: `Точка ${i + 1}`
  })
  
  await clusterer.add(placemark)
}
```

### Кастомные стили кластеров

```javascript
import { CLUSTER_PRESETS } from './Clusterer.js'

const clusterer = new Clusterer(map, {
  preset: CLUSTER_PRESETS.RED, // Красные кластеры
  gridSize: 80,
  minClusterSize: 3
})

// Динамическая смена стиля
clusterer.setPreset(CLUSTER_PRESETS.DARK_GREEN)
```

### Кастомное содержимое balloon

```javascript
const clusterer = new Clusterer(map, {
  createBalloonContent: (placemarks) => {
    const count = placemarks.length
    let content = `<h3>В кластере ${count} объектов</h3>`
    content += '<ul>'
    
    placemarks.slice(0, 5).forEach((pm, i) => {
      const data = pm.properties.get('balloonContent')
      content += `<li>${data}</li>`
    })
    
    if (count > 5) {
      content += `<li>... и еще ${count - 5}</li>`
    }
    
    content += '</ul>'
    return content
  }
})
```

### Кастомная иконка кластера

```javascript
const clusterer = new Clusterer(map, {
  calculateClusterIcon: (placemarks) => {
    const count = placemarks.length
    let color = '#0080ff' // синий
    
    if (count > 100) {
      color = '#ff0000' // красный для больших кластеров
    } else if (count > 50) {
      color = '#ff8800' // оранжевый для средних
    }
    
    return {
      iconColor: color,
      iconContent: count.toString()
    }
  }
})
```

### Vue: Динамическая кластеризация

```vue
<template>
  <div>
    <YMapsClusterer
      ref="clustererRef"
      :map="map"
      :placemarks="filteredMarkers"
      :preset="currentPreset"
      :grid-size="settings.gridSize"
      :min-cluster-size="settings.minSize"
      :auto-fit="true"
      @cluster-click="showClusterInfo"
    />
    
    <div class="settings">
      <label>
        Размер сетки:
        <input 
          v-model.number="settings.gridSize" 
          type="range" 
          min="10" 
          max="200"
        />
        {{ settings.gridSize }}px
      </label>
      
      <label>
        Минимальный размер:
        <input 
          v-model.number="settings.minSize" 
          type="range" 
          min="2" 
          max="10"
        />
        {{ settings.minSize }} меток
      </label>
      
      <label>
        Стиль:
        <select v-model="currentPreset">
          <option value="islands#blueClusterIcons">Синий</option>
          <option value="islands#redClusterIcons">Красный</option>
          <option value="islands#darkGreenClusterIcons">Зеленый</option>
          <option value="islands#violetClusterIcons">Фиолетовый</option>
        </select>
      </label>
      
      <button @click="refreshClusters">
        Обновить кластеры
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed } from 'vue'

const clustererRef = ref()
const currentPreset = ref('islands#blueClusterIcons')

const settings = reactive({
  gridSize: 60,
  minSize: 2
})

const markers = ref([/* массив меток */])
const activeFilter = ref('all')

const filteredMarkers = computed(() => {
  if (activeFilter.value === 'all') {
    return markers.value
  }
  
  return markers.value.filter(marker => {
    // Фильтрация по категории
    return marker.category === activeFilter.value
  })
})

const showClusterInfo = (cluster) => {
  const objects = cluster.getGeoObjects()
  console.log(`Кластер содержит ${objects.length} объектов`)
}

const refreshClusters = () => {
  clustererRef.value?.refresh()
}
</script>
```

### Производительная работа с большими данными

```javascript
class OptimizedClusterer {
  constructor(map, options) {
    this.clusterer = new Clusterer(map, {
      ...options,
      // Оптимизация для больших объемов
      gridSize: 100,
      viewportMargin: 50
    })
    
    this.batchSize = 100
    this.queue = []
  }
  
  async addBatch(placemarks) {
    // Добавляем метки порциями
    for (let i = 0; i < placemarks.length; i += this.batchSize) {
      const batch = placemarks.slice(i, i + this.batchSize)
      await this.clusterer.add(batch)
      
      // Даем браузеру отдышаться
      await new Promise(resolve => setTimeout(resolve, 10))
    }
  }
  
  async loadFromAPI(url) {
    const response = await fetch(url)
    const data = await response.json()
    
    const placemarks = data.map(item => new Placemark(
      [item.lat, item.lng],
      { balloonContent: item.name }
    ))
    
    await this.addBatch(placemarks)
  }
}
```

## 🎨 Preset стили кластеров

### Основные цвета
- `islands#blueClusterIcons` - синий
- `islands#redClusterIcons` - красный
- `islands#darkGreenClusterIcons` - темно-зеленый
- `islands#violetClusterIcons` - фиолетовый
- `islands#blackClusterIcons` - черный
- `islands#grayClusterIcons` - серый
- `islands#brownClusterIcons` - коричневый
- `islands#nightClusterIcons` - ночной
- `islands#darkBlueClusterIcons` - темно-синий
- `islands#darkOrangeClusterIcons` - темно-оранжевый
- `islands#pinkClusterIcons` - розовый
- `islands#oliveClusterIcons` - оливковый

### Инвертированные стили
- `islands#invertedBlueClusterIcons`
- `islands#invertedRedClusterIcons`
- `islands#invertedDarkGreenClusterIcons`
- `islands#invertedVioletClusterIcons`

## ⚙️ Оптимизация производительности

### Рекомендации для больших объемов данных

1. **Используйте оптимальный размер сетки**
   ```javascript
   // Для 1000+ меток
   gridSize: 80-100
   
   // Для 10000+ меток
   gridSize: 120-150
   ```

2. **Ограничивайте максимальный зум**
   ```javascript
   maxZoom: 14 // Не показывать отдельные метки на большом зуме
   ```

3. **Загружайте данные порциями**
   ```javascript
   // Вместо одного большого массива
   await clusterer.add(smallBatch1)
   await clusterer.add(smallBatch2)
   ```

4. **Используйте viewport маржу**
   ```javascript
   viewportMargin: 100 // Кластеризация только видимой области
   ```

## 🐛 Решение проблем

### Кластеры не отображаются

```javascript
// Проверьте готовность кластеризатора
if (!clusterer.isReady()) {
  console.log('Кластеризатор еще не готов')
  return
}

// Проверьте, что метки добавлены
console.log('Количество меток:', clusterer.getPlacemarksCount())

// Обновите кластеры
clusterer.refresh()
```

### Слишком много/мало кластеров

```javascript
// Настройте размер сетки
clusterer.setGridSize(100) // Увеличить для меньшего количества кластеров

// Настройте минимальный размер
clusterer.setMinClusterSize(5) // Увеличить для меньшего количества кластеров
```

### Проблемы с производительностью

```javascript
// Оптимизированные настройки
const clusterer = new Clusterer(map, {
  gridSize: 120,
  maxZoom: 14,
  viewportMargin: 50,
  hasBalloon: false, // Отключить balloon для скорости
  hasHint: false     // Отключить хинты для скорости
})
```

## 🔗 Связанные модули

- [YMapsCore](../../core/README.md) - Ядро системы
- [Placemark](../Placemark/README.md) - Метки на карте
- [Balloon](../Balloon/README.md) - Всплывающие окна

## 📝 Лицензия

Модуль предоставляется для использования в проектах с Yandex Maps API.

## 🤝 Поддержка

При возникновении вопросов обращайтесь к команде разработки SPA Platform.
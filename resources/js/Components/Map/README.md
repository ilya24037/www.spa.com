# 🗺️ UniversalMap - Универсальный компонент карты

## 📋 Описание
Универсальный компонент карты с поддержкой 4 режимов отображения для разных сценариев использования.

## 🎯 Режимы работы

### 1. `preview` - Превью карта
- **Где используется**: Главная страница, каталог
- **Размер**: 400px (по умолчанию)
- **Функции**: Показ маркеров мастеров, кнопка переключения на список
- **Контролы**: Нет

```vue
<UniversalMap 
  mode="preview"
  :markers="masters"
  title="12 мастеров"
  subtitle="Пермь"
  :action-button="{ text: 'Показать списком', action: 'toggle-list' }"
  @action-click="handleActionClick"
  @marker-click="handleMasterClick"
/>
```

### 2. `full` - Полная карта
- **Где используется**: Отдельная страница карты, поиск
- **Размер**: 500px (по умолчанию)
- **Функции**: Все возможности карты
- **Контролы**: Зум, геолокация

```vue
<UniversalMap 
  mode="full"
  :markers="masters"
  :show-controls="true"
  :show-geolocation="true"
  @zoom-change="handleZoom"
  @center-change="handleCenter"
/>
```

### 3. `picker` - Выбор места
- **Где используется**: Формы добавления объявлений
- **Размер**: 200px (по умолчанию)
- **Функции**: Выбор координат кликом
- **Контролы**: Геолокация

```vue
<UniversalMap 
  mode="picker"
  placeholder-text="Кликните для выбора места"
  @map-click="handleLocationSelect"
/>
```

### 4. `mini` - Мини карта
- **Где используется**: Карточки мастеров, превью
- **Размер**: 120px (по умолчанию)
- **Функции**: Показ локации точкой
- **Контролы**: Нет

```vue
<UniversalMap 
  mode="mini"
  :markers="[master]"
  :height="100"
/>
```

## 🔧 Props

### Основные
| Prop | Type | Default | Описание |
|------|------|---------|----------|
| `mode` | String | `'preview'` | Режим карты: `preview`, `full`, `picker`, `mini` |
| `markers` | Array | `[]` | Массив маркеров для отображения |
| `height` | Number | `null` | Высота карты (автоматическая по режиму) |
| `center` | Object | `{ lat: 58.0105, lng: 56.2502 }` | Центр карты (Пермь) |

### Отображение
| Prop | Type | Default | Описание |
|------|------|---------|----------|
| `title` | String | `''` | Заголовок карты (для preview) |
| `subtitle` | String | `''` | Подзаголовок карты |
| `showControls` | Boolean | `true` | Показать контролы зума |
| `showGeolocation` | Boolean | `true` | Показать кнопку геолокации |
| `maxMarkers` | Number | `10` | Максимум маркеров |

### Дополнительные
| Prop | Type | Default | Описание |
|------|------|---------|----------|
| `actionButton` | Object | `null` | Кнопка действия `{ text, action }` |
| `placeholderText` | String | `'Выберите место на карте'` | Текст для picker |
| `loading` | Boolean | `false` | Состояние загрузки |

## 📤 Events

| Event | Payload | Описание |
|-------|---------|----------|
| `marker-click` | `marker` | Клик по маркеру |
| `marker-hover` | `{ marker, isHovered }` | Наведение на маркер |
| `map-click` | `{ coordinates, position, event }` | Клик по карте (picker) |
| `action-click` | `action` | Клик по кнопке действия |
| `zoom-change` | `zoomLevel` | Изменение зума |
| `center-change` | `{ lat, lng }` | Изменение центра |

## 📊 Формат данных маркеров

```javascript
const markers = [
  {
    id: 1,
    name: 'Мастер Иван',
    price: 2000,        // или min_price
    latitude: 58.0105,
    longitude: 56.2502,
    tooltip: {          // Опционально
      title: 'Мастер Иван',
      subtitle: 'Классический массаж'
    }
  }
]
```

## 🎨 Стилизация

Компонент автоматически адаптирует стили под режим:

- **preview**: Голубые маркеры с ценой
- **picker**: Зеленые маркеры, пунктирная рамка
- **full**: Все функции, белые контролы
- **mini**: Маленькие точки, минимум элементов

## 🔄 Миграция со старых карт

### SimpleMap → UniversalMap
```vue
<!-- Было -->
<SimpleMap :cards="masters" />

<!-- Стало -->
<UniversalMap 
  mode="preview"
  :markers="masters"
  :action-button="{ text: 'Показать списком', action: 'toggle-list' }"
/>
```

### Map.vue → UniversalMap
```vue
<!-- Было -->
<Map :cards="masters" />

<!-- Стало -->
<UniversalMap 
  mode="full"
  :markers="masters"
/>
```

## 🚀 Интеграция с реальными картами

В будущем можно легко заменить заглушку на:
- Яндекс.Карты
- Google Maps  
- OpenStreetMap

Точка интеграции: `onMounted()` хук в компоненте. 
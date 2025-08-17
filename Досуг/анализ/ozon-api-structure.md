# 📊 Анализ API структуры Ozon из C:\Mirror\elements

## 📁 Состав папки C:\Mirror\elements\api\entrypoint-api.bx\page\json

Папка содержит 14 JSON файлов с API ответами для разных компонентов страницы Ozon:
- Размеры: от 5KB до 58KB
- Формат: v2_[hash].json
- Содержимое: структурированные данные для виджетов и компонентов

## 🔍 Структура API ответов Ozon

### 1. Основная структура ответа
```json
{
  "layout": [],           // Конфигурация виджетов
  "widgetStates": {},     // Состояния виджетов с данными
  "browser": {},          // Информация о браузере/устройстве
  "client": {},           // Информация о клиенте
  "location": {},         // Геолокация пользователя
  "seo": {},             // SEO метаданные
  "pageInfo": {},        // Информация о странице
  "trackingPayloads": {}, // Данные для трекинга
  "experimentsConfig": {} // A/B тестирование
}
```

### 2. Структура товарной карточки (из skuGridSimple)
```json
{
  "action": {
    "behavior": "BEHAVIOR_TYPE_REDIRECT",
    "link": "/product/[url]/"
  },
  "favButton": {
    "id": "522107326",
    "isFav": false,
    "trackingInfo": {}
  },
  "images": [{
    "link": "https://cdn1.ozone.ru/...",
    "contentMode": "SCALE_ASPECT_FIT"
  }],
  "leftBottomBadge": {
    "text": "Распродажа",
    "backgroundColor": "#f1117eff"
  },
  "state": [
    {
      "type": "priceV2",
      "priceV2": {
        "price": [{"text": "400 ₽"}],
        "discount": "−64%"
      }
    },
    {
      "type": "textAtom",
      "textAtom": {
        "text": "Название товара",
        "maxLines": 2
      }
    },
    {
      "type": "labelList",
      "labelList": {
        "items": [
          {"title": "4.9", "icon": "star"},
          {"title": "68 392 отзыва"}
        ]
      }
    }
  ],
  "trackingInfo": {}
}
```

### 3. Горизонтальное меню (из shellHorizontalMenuGetChildV1)
```json
{
  "data": [{
    "id": "6",
    "items": [
      {
        "title": "Открыть пункт выдачи",
        "link": "https://pvz.ozon.ru/",
        "trackingInfo": {
          "click": {},
          "view": {}
        }
      }
    ]
  }]
}
```

## 🎯 Полезные паттерны для SPA Platform

### 1. Система трекинга событий
```typescript
interface TrackingInfo {
  click: {
    actionType: string
    key: string
    custom?: Record<string, any>
  }
  view: {
    actionType: string
    key: string
  }
  aux_click?: {}
  right_click?: {}
}
```

### 2. Компонентная система состояний
```typescript
interface ComponentState {
  type: 'priceV2' | 'textAtom' | 'labelList'
  [key: string]: any
}

// Использование в Vue 3
const renderComponent = (state: ComponentState) => {
  switch(state.type) {
    case 'priceV2':
      return h(PriceComponent, state.priceV2)
    case 'textAtom':
      return h(TextComponent, state.textAtom)
    case 'labelList':
      return h(LabelListComponent, state.labelList)
  }
}
```

### 3. Система виджетов с конфигурацией
```typescript
interface Widget {
  component: string
  params: string // JSON строка с параметрами
  stateId: string
  version: number
  vertical: string
  widgetTrackingInfo: string
  trackingOn: boolean
}
```

### 4. A/B тестирование через experimentsConfig
```javascript
// Флаги экспериментов
experimentsConfig: {
  enableSWCaching: true,        // Service Worker кеширование
  prefetchAssetsSW: true,       // Предзагрузка ресурсов
  alternativeCdnImages: true,   // CDN для изображений
  imgPixelatedRender: true,     // Оптимизация рендеринга
  enableTrackerLogs: true       // Логирование трекера
}
```

### 5. Система бейджей и меток
```typescript
interface Badge {
  text: string
  image?: string
  tintColor?: string
  iconTintColor?: string
  backgroundColor?: string
  theme?: 'STYLE_TYPE_MEDIUM'
  iconPosition?: 'ICON_POSITION_LEFT'
}
```

### 6. Адаптивная загрузка изображений
```typescript
interface ImageConfig {
  link: string
  contentMode: 'SCALE_ASPECT_FIT' | 'SCALE_ASPECT_FILL'
  // Разные CDN для разных типов
  cdn: {
    img: 'ir.ozone.ru',
    video: 'v-1.ozone.ru',
    original: 'io.ozone.ru',
    static: 'st.ozone.ru'
  }
}
```

### 7. Пагинация и бесконечная прокрутка
```javascript
params: {
  algo: "1",
  itemsOnPage: 30,
  offset: 40,
  usePagination: true,
  paginationExtraEmptyPage: true
}
```

## 💡 Ключевые особенности архитектуры Ozon

1. **Микро-фронтенды**: Каждый виджет - независимый компонент
2. **Lazy Loading**: Модули загружаются по требованию
3. **Server-Side Rendering**: Начальные данные в JSON
4. **CDN оптимизация**: Разные CDN для разных типов контента
5. **Трекинг на уровне компонентов**: Каждый элемент отслеживается
6. **A/B тестирование**: Встроено в архитектуру
7. **Performance marks**: Замеры производительности

## 📈 Метрики производительности (из serverTiming)
```json
{
  "Widgets": 66ms,        // Рендеринг виджетов
  "Resolve": 4ms,         // Разрешение зависимостей
  "Total": 73ms,          // Общее время
  "ComposerTotal": 73ms,  // Композиция страницы
  "ComposerInternal": 2ms // Внутренние операции
}
```

## 🔧 Применение в SPA Platform

### Создание системы виджетов для мастеров:
```vue
<!-- MasterWidget.vue -->
<script setup lang="ts">
import { computed } from 'vue'
import type { WidgetConfig, WidgetState } from '@/types/widget'

interface Props {
  config: WidgetConfig
  state: WidgetState
}

const props = defineProps<Props>()

// Динамический импорт компонента
const component = computed(() => {
  return defineAsyncComponent(() => 
    import(`@/widgets/${props.config.component}.vue`)
  )
})

// Парсинг параметров
const params = computed(() => 
  JSON.parse(props.config.params)
)
</script>

<template>
  <div 
    :data-widget-id="config.stateId"
    :data-tracking="config.trackingOn"
  >
    <component 
      :is="component" 
      v-bind="params"
      :state="state"
    />
  </div>
</template>
```

### Система трекинга действий:
```typescript
// composables/useTracking.ts
export const useTracking = () => {
  const track = (action: string, data: any) => {
    if (window.performance) {
      performance.mark(`${action}_start`)
    }
    
    // Отправка в аналитику
    sendAnalytics({
      actionType: action,
      key: generateKey(),
      ...data
    })
    
    if (window.performance) {
      performance.mark(`${action}_end`)
      performance.measure(action, `${action}_start`, `${action}_end`)
    }
  }
  
  return { track }
}
```

## 🎨 UI компоненты в стиле Ozon

### Карточка услуги с бейджем:
```vue
<template>
  <div class="service-card">
    <!-- Бейдж скидки -->
    <div 
      v-if="discount" 
      class="badge badge-sale"
    >
      <IconHot />
      <span>{{ discount }}%</span>
    </div>
    
    <!-- Изображение с lazy loading -->
    <img 
      :src="imagePlaceholder"
      :data-src="service.image"
      loading="lazy"
      class="service-image"
    />
    
    <!-- Цена с анимацией -->
    <div class="price-block">
      <span class="price-current">{{ price }} ₽</span>
      <span class="price-old">{{ oldPrice }} ₽</span>
      <span class="price-discount">−{{ discount }}%</span>
    </div>
    
    <!-- Рейтинг и отзывы -->
    <div class="rating-block">
      <IconStar class="rating-star" />
      <span>{{ rating }}</span>
      <span class="reviews-count">{{ reviews }} отзывов</span>
    </div>
    
    <!-- Кнопка избранного -->
    <button 
      @click="toggleFavorite"
      class="fav-button"
      :class="{ active: isFavorite }"
    >
      <IconHeart />
    </button>
  </div>
</template>
```

## 📝 Выводы

Архитектура Ozon предоставляет отличные паттерны для:
- Модульной системы виджетов
- Эффективной загрузки контента
- Детального трекинга действий
- A/B тестирования функций
- Оптимизации производительности

Эти подходы можно успешно адаптировать для SPA Platform, особенно для каталога мастеров и системы бронирования.
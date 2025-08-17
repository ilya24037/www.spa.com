# 🛍️ Ozon Widget System - Система виджетов из анализа Ozon

## 📋 Описание

Эта папка содержит адаптированные для Vue 3 виджеты, основанные на архитектуре Ozon. Все компоненты используют точную логику и структуру данных из анализа JSON API Ozon.

## 🏗️ Архитектура

Система построена на принципах микрофронтендов Ozon:
- Каждый виджет независим и самодостаточен
- Поддержка версионирования и токенов
- Встроенная система трекинга
- Оптимизация производительности

## 📦 Компоненты

### 1. **ProductGrid** - Сетка товаров
- Конфигурация: 5 колонок, соотношение 3:4
- Поддержка пагинации и бесконечной прокрутки
- Параметры из Ozon: `itemsOnPage: 30`, `offset: 40`

### 2. **ProductCard** - Карточка товара
- Полная структура из Ozon state array
- Поддержка всех типов состояний (priceV2, textAtom, labelList)
- Встроенный трекинг действий

### 3. **PriceBlock** - Блок цены
- Стили: SALE_PRICE, CARD_PRICE, ACTUAL_PRICE
- Градиенты для скидок (#F1117E)
- Анимация изменения цены

### 4. **BadgeSystem** - Система бейджей
- "Распродажа" (backgroundColor: #f1117eff)
- "Цена что надо" (bgPositivePrimary)
- Позиционирование: leftBottomBadge
- Темы: STYLE_TYPE_MEDIUM

### 5. **RatingBlock** - Блок рейтинга
- Иконки: ic_s_star_filled_compact
- Цвета: graphicRating, textPremium
- Отображение количества отзывов

### 6. **FavoriteButton** - Кнопка избранного
- API: favoriteBatchAddItems/favoriteBatchDeleteItems
- Автоматизация тестов: favorite-simple-button
- Трекинг клика по избранному

### 7. **InfiniteScroll** - Бесконечная прокрутка
- Название виджета: shelf.infiniteScroll
- Вертикаль: products
- Поддержка пагинации с extra empty page

### 8. **TrackingSystem** - Система трекинга
Типы событий из Ozon:
- `view` - просмотр элемента
- `click` - основной клик
- `aux_click` - вспомогательный клик (средняя кнопка)
- `right_click` - правый клик
- `favorite` - добавление в избранное

### 9. **ImageOptimization** - Оптимизация изображений
CDN из Ozon:
- `ir.ozone.ru` - основные изображения
- `io.ozone.ru` - оригиналы
- `v-1.ozone.ru` - видео
- `st.ozone.ru` - статика

Режимы отображения:
- SCALE_ASPECT_FIT
- SCALE_ASPECT_FILL

### 10. **WidgetSystem** - Базовая система виджетов
Структура виджета из Ozon:
```json
{
  "component": "skuGridSimple",
  "stateId": "skuGridSimple-4170201-default-8",
  "version": 1,
  "vertical": "products",
  "widgetToken": "0BNM1-W7Gi9jMiZTLNfYCTJ4boQP0zoa",
  "widgetTrackingInfo": {...},
  "timeSpent": 64
}
```

## 🔧 Использование

### Пример интеграции в SPA Platform:

```vue
<template>
  <div class="catalog-page">
    <!-- Сетка товаров с бесконечной прокруткой -->
    <ProductGrid 
      :columns="5"
      :ratio="'3:4'"
      :items-per-page="30"
      :offset="40"
      @load-more="loadMoreProducts"
    >
      <!-- Карточки товаров -->
      <ProductCard 
        v-for="product in products"
        :key="product.skuId"
        :product="product"
        :tracking-enabled="true"
      />
    </ProductGrid>
  </div>
</template>

<script setup>
import { ProductGrid, ProductCard } from '@/Ozon'
import { useTracking } from '@/Ozon/TrackingSystem'

const { trackView, trackClick } = useTracking()
</script>
```

## 📊 Структура данных

### Формат товара (из Ozon JSON):
```typescript
interface OzonProduct {
  skuId: string
  action: {
    behavior: "BEHAVIOR_TYPE_REDIRECT"
    link: string
  }
  favButton: {
    id: string
    isFav: boolean
    favLink: string
    unfavLink: string
  }
  images: Array<{
    link: string
    contentMode: "SCALE_ASPECT_FIT" | "SCALE_ASPECT_FILL"
  }>
  leftBottomBadge?: {
    text: string
    backgroundColor: string
    theme: string
  }
  state: Array<ProductState>
  trackingInfo: TrackingInfo
}
```

## 🎯 Преимущества архитектуры Ozon

1. **Микрофронтенды** - каждый виджет может разрабатываться независимо
2. **Версионирование** - поддержка разных версий виджетов
3. **Производительность** - lazy loading и оптимизация
4. **Аналитика** - детальный трекинг всех действий
5. **A/B тестирование** - встроенная поддержка экспериментов

## 📈 Метрики производительности

Из анализа Ozon `serverTiming`:
- Widgets: 66ms
- Resolve: 4ms
- Total: 73ms
- ComposerTotal: 73ms
- ComposerInternal: 2ms

## 🔗 Связь с SPA Platform

Эти виджеты можно использовать для:
- Каталога мастеров (вместо товаров)
- Списка услуг
- Галереи работ
- Системы отзывов
- Избранных мастеров

## 📝 Источник

Все компоненты созданы на основе анализа:
- `C:\Mirror\elements\api\entrypoint-api.bx\page\json\*.json`
- Структуры виджетов Ozon
- Системы трекинга и оптимизации Ozon
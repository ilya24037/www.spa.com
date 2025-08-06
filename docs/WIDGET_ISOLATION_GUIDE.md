# 🎯 Руководство по Изоляции Виджетов (Принцип Ozon)

## 🏗️ Архитектура Изолированного Виджета

Каждый виджет в SPA Platform следует принципу полной самодостаточности по образцу Ozon.

### 📁 Структура Виджета

```
resources/js/widgets/WidgetName/
├── index.ts                 # Точка входа + lazy loading
├── WidgetName.vue          # Основной компонент
├── api/                    # API методы виджета
│   ├── index.ts
│   └── widgetApi.ts
├── store/                  # Состояние виджета (Pinia)
│   ├── index.ts
│   └── widgetStore.ts
├── composables/            # Переиспользуемая логика
│   ├── index.ts
│   └── useWidget.ts
├── types/                  # TypeScript типы
│   ├── index.ts
│   └── widget.types.ts
├── styles/                 # Изолированные стили
│   ├── index.ts
│   └── widget.module.css
└── components/             # Внутренние компоненты
    ├── index.ts
    ├── WidgetHeader.vue
    └── WidgetContent.vue
```

## 🎯 Принципы Изоляции

### 1. **Самодостаточность**
```typescript
// ❌ Плохо - зависит от внешнего состояния
const globalUser = useGlobalUserStore()

// ✅ Хорошо - получает данные через props или собственный API
const props = defineProps<{
  userId?: string
}>()
```

### 2. **Инкапсуляция API**
```typescript
// widgets/catalog/api/catalogApi.ts
export class CatalogWidgetApi {
  async getProducts(filters: ProductFilters): Promise<Product[]> {
    // Изолированные API вызовы только для этого виджета
  }
}
```

### 3. **Изолированное Состояние**
```typescript
// widgets/catalog/store/catalogStore.ts
export const useCatalogWidgetStore = defineStore('catalog-widget', () => {
  // Состояние только для каталога
  // Не зависит от глобальных store
})
```

### 4. **Ленивая Загрузка**
```typescript
// widgets/catalog/index.ts
export default defineAsyncComponent({
  loader: () => import('./CatalogWidget.vue'),
  loadingComponent: () => import('@/shared/ui/atoms/Skeleton'),
  errorComponent: () => import('@/shared/ui/molecules/ErrorState'),
  delay: 200,
  timeout: 3000
})
```

## 🚀 Интеграция в Страницы

### Простое Использование
```vue
<template>
  <div class="page">
    <!-- Виджет полностью изолирован -->
    <CatalogWidget 
      :category="'massage'"
      :filters="{ location: 'moscow' }"
      @product-selected="handleProductSelected"
    />
  </div>
</template>

<script setup>
// Ленивая загрузка виджета
const CatalogWidget = defineAsyncComponent(() => 
  import('@/widgets/catalog')
)
</script>
```

## 📊 Мониторинг Виджетов

### Performance метрики
- Время загрузки
- Размер бандла  
- Количество ре-рендеров
- Использование памяти

### Error Boundary
Каждый виджет обертывается в ErrorBoundary для изоляции ошибок.

## 🎨 Стилизация

### CSS Modules
```css
/* widgets/catalog/styles/catalog.module.css */
.catalogWidget {
  /* Изолированные стили */
  --widget-primary: var(--color-blue-500);
  --widget-spacing: var(--spacing-4);
}

.catalogHeader {
  /* Используем дизайн-токены но не зависим от внешних классов */
}
```

## 🔄 Жизненный Цикл

1. **Mount** - виджет инициализируется с переданными props
2. **Load** - загружает необходимые данные через собственный API
3. **Update** - реагирует только на изменения props
4. **Unmount** - очищает собственное состояние

## 🎯 Критерии Изоляции

### ✅ Виджет изолирован если:
- Не использует глобальные store (кроме user/auth)
- Имеет собственный API слой
- Может работать на любой странице
- Ошибки не влияют на другие виджеты
- Стили не конфликтуют с внешними

### ❌ Нарушения изоляции:
- Прямое обращение к DOM других компонентов
- Использование глобальных переменных
- Зависимость от порядка загрузки других виджетов
- Изменение глобального состояния без emit
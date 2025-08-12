# 📐 АНАЛИЗ LAYOUT СТРУКТУРЫ OZON

## 🎯 Ключевые находки из Ozon layout

### 1. CSS Variables (Design Tokens)
Ozon использует систему CSS переменных для всех значений дизайна:

```css
:root {
  /* Типографика - 9 уровней размеров */
  --tsHeadline900XxLarge: 46px;
  --tsHeadline800XxLarge: 40px;
  --tsHeadline700XLarge: 32px;
  
  /* Цвета с семантическими именами */
  --ozTextPrimary: #001a34;
  --ozTextSecondary: #707f8d;
  --ozAccentAlert: #f91155;
  
  /* Отступы и размеры */
  --content-padding: 48px;
  --desktop-screen-l: 1472px;
  
  /* Тени и анимации */
  --boxShadow: 0 4px 16px 1px rgba(0,26,52,.16);
  --transition: 0.2s cubic-bezier(0.4,0,0.2,1);
}
```

### 2. Структура Layout

#### Основная сетка:
```
<body>
  <header class="oz-header">          // Sticky header
    <div class="oz-header__top">      // Топ-бар 
    <div class="oz-header__main">     // Поиск, меню
    <nav class="oz-header__nav">      // Навигация
  </header>
  
  <div class="oz-layout">             // Основной контент
    <div class="oz-container">        // Контейнер с max-width
      <aside class="oz-sidebar">      // Sticky sidebar
      <main class="oz-main">          // Контент
    </div>
  </div>
  
  <footer class="oz-footer">          // Footer
</body>
```

### 3. Компонентный подход

#### Префиксы классов:
- `oz-` - глобальный префикс (namespace)
- `oz-card` - компонент
- `oz-card__image` - элемент компонента (BEM)
- `oz-card--featured` - модификатор (BEM)

### 4. Адаптивная типографика

```css
/* Desktop */
--tsHeadline900XxLargeHeight: 52px;

/* Mobile - автоматическое масштабирование */
--tsHeadline900XxLargeHeightMob: 44px;
```

### 5. Grid система для карточек

```css
.oz-grid--products {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
  gap: 16px;
}
```

## 🚀 Что можно применить в SPA Platform

### 1. ✅ Design Tokens System
**Создать файл с CSS переменными:**

```vue
<!-- resources/js/src/shared/styles/design-tokens.css -->
:root {
  /* Typography Scale */
  --spa-headline-xxl: 46px;
  --spa-headline-xl: 32px;
  --spa-headline-l: 24px;
  --spa-headline-m: 20px;
  --spa-body-l: 16px;
  --spa-body-m: 14px;
  --spa-caption: 12px;
  
  /* Colors */
  --spa-text-primary: #111827;
  --spa-text-secondary: #6b7280;
  --spa-text-tertiary: #9ca3af;
  --spa-accent-primary: #3b82f6;
  --spa-accent-success: #10c44c;
  --spa-accent-danger: #f91155;
  
  /* Spacing */
  --spa-space-xs: 4px;
  --spa-space-s: 8px;
  --spa-space-m: 16px;
  --spa-space-l: 24px;
  --spa-space-xl: 32px;
  --spa-space-xxl: 48px;
  
  /* Layout */
  --spa-container-max: 1472px;
  --spa-sidebar-width: 280px;
  --spa-content-padding: 48px;
  --spa-mobile-padding: 16px;
  
  /* Shadows */
  --spa-shadow-card: 0 2px 8px rgba(0,0,0,0.08);
  --spa-shadow-card-hover: 0 4px 16px rgba(0,0,0,0.12);
  --spa-shadow-modal: 0 8px 32px rgba(0,0,0,0.16);
  
  /* Transitions */
  --spa-transition-fast: 0.15s ease-out;
  --spa-transition-base: 0.2s cubic-bezier(0.4,0,0.2,1);
  --spa-transition-slow: 0.3s ease-in-out;
  
  /* Breakpoints */
  --spa-screen-mobile: 640px;
  --spa-screen-tablet: 768px;
  --spa-screen-desktop: 1024px;
  --spa-screen-wide: 1280px;
}

/* Dark mode tokens */
@media (prefers-color-scheme: dark) {
  :root {
    --spa-text-primary: #f9fafb;
    --spa-text-secondary: #d1d5db;
    /* ... */
  }
}
```

### 2. ✅ Unified Layout Component

```vue
<!-- resources/js/src/shared/layouts/MainLayout/MainLayout.vue -->
<template>
  <div class="spa-layout" :class="layoutClasses">
    <!-- Sticky Header -->
    <header class="spa-header">
      <div class="spa-header__top">
        <div class="spa-container">
          <CitySelector />
          <DeliveryInfo />
        </div>
      </div>
      <div class="spa-header__main">
        <div class="spa-container">
          <Logo />
          <SearchBar />
          <UserMenu />
          <CartButton />
        </div>
      </div>
      <nav class="spa-header__nav">
        <div class="spa-container">
          <MainNavigation />
        </div>
      </nav>
    </header>
    
    <!-- Main Content -->
    <div class="spa-layout__body">
      <div class="spa-container">
        <div class="spa-layout__content">
          <!-- Optional Sidebar -->
          <aside v-if="showSidebar" class="spa-sidebar">
            <slot name="sidebar" />
          </aside>
          
          <!-- Main Area -->
          <main class="spa-main">
            <!-- Breadcrumbs -->
            <Breadcrumbs v-if="breadcrumbs" :items="breadcrumbs" />
            
            <!-- Page Content -->
            <slot />
          </main>
        </div>
      </div>
    </div>
    
    <!-- Footer -->
    <footer class="spa-footer">
      <div class="spa-container">
        <FooterContent />
      </div>
    </footer>
  </div>
</template>

<script setup lang="ts">
interface Props {
  layout?: 'default' | 'two-column' | 'full-width'
  showSidebar?: boolean
  breadcrumbs?: BreadcrumbItem[]
}

const props = withDefaults(defineProps<Props>(), {
  layout: 'default',
  showSidebar: false
})

const layoutClasses = computed(() => ({
  [`spa-layout--${props.layout}`]: true,
  'spa-layout--has-sidebar': props.showSidebar
}))
</script>

<style scoped>
.spa-layout {
  min-height: 100vh;
  display: flex;
  flex-direction: column;
}

.spa-header {
  position: sticky;
  top: 0;
  z-index: 100;
  background: white;
  box-shadow: var(--spa-shadow-card);
}

.spa-container {
  max-width: var(--spa-container-max);
  margin: 0 auto;
  padding: 0 var(--spa-content-padding);
}

@media (max-width: 768px) {
  .spa-container {
    padding: 0 var(--spa-mobile-padding);
  }
}

.spa-layout__content {
  display: grid;
  grid-template-columns: 1fr;
  gap: var(--spa-space-l);
  padding: var(--spa-space-l) 0;
}

.spa-layout--has-sidebar .spa-layout__content {
  grid-template-columns: var(--spa-sidebar-width) 1fr;
}

@media (max-width: 1024px) {
  .spa-layout--has-sidebar .spa-layout__content {
    grid-template-columns: 1fr;
  }
}

.spa-sidebar {
  position: sticky;
  top: calc(80px + var(--spa-space-l));
  height: fit-content;
}

.spa-main {
  min-width: 0; /* Prevent overflow */
}

.spa-footer {
  margin-top: auto;
  background: var(--spa-bg-secondary);
  padding: var(--spa-space-xxl) 0;
}
</style>
```

### 3. ✅ Grid System для карточек

```vue
<!-- resources/js/src/shared/ui/organisms/ResponsiveGrid/ResponsiveGrid.vue -->
<template>
  <div 
    class="spa-grid"
    :class="gridClasses"
    :style="gridStyles"
  >
    <slot />
  </div>
</template>

<script setup lang="ts">
interface Props {
  columns?: number | 'auto'
  gap?: 'xs' | 's' | 'm' | 'l' | 'xl'
  minItemWidth?: number
  variant?: 'default' | 'masonry' | 'fixed'
}

const props = withDefaults(defineProps<Props>(), {
  columns: 'auto',
  gap: 'm',
  minItemWidth: 200,
  variant: 'default'
})

const gridClasses = computed(() => ({
  [`spa-grid--${props.variant}`]: true,
  [`spa-grid--gap-${props.gap}`]: true
}))

const gridStyles = computed(() => ({
  '--min-item-width': `${props.minItemWidth}px`,
  '--columns': props.columns === 'auto' ? undefined : props.columns
}))
</script>

<style scoped>
.spa-grid {
  display: grid;
}

.spa-grid--default {
  grid-template-columns: repeat(
    var(--columns, auto-fill),
    minmax(var(--min-item-width), 1fr)
  );
}

.spa-grid--fixed {
  grid-template-columns: repeat(var(--columns, 4), 1fr);
}

.spa-grid--gap-xs { gap: var(--spa-space-xs); }
.spa-grid--gap-s { gap: var(--spa-space-s); }
.spa-grid--gap-m { gap: var(--spa-space-m); }
.spa-grid--gap-l { gap: var(--spa-space-l); }
.spa-grid--gap-xl { gap: var(--spa-space-xl); }

/* Responsive adjustments */
@media (max-width: 640px) {
  .spa-grid--default {
    grid-template-columns: repeat(2, 1fr);
  }
}

@media (max-width: 480px) {
  .spa-grid--default {
    grid-template-columns: 1fr;
  }
}
</style>
```

### 4. ✅ Sticky элементы (Header & Sidebar)

```css
/* Sticky header с правильным z-index */
.spa-header {
  position: sticky;
  top: 0;
  z-index: 100;
  backdrop-filter: blur(10px);
  background: rgba(255, 255, 255, 0.95);
}

/* Sticky sidebar с offset от header */
.spa-sidebar {
  position: sticky;
  top: calc(var(--header-height) + var(--spa-space-l));
  max-height: calc(100vh - var(--header-height) - var(--spa-space-xl));
  overflow-y: auto;
}
```

### 5. ✅ Utility классы по системе Ozon

```css
/* Текст */
.spa-text--primary { color: var(--spa-text-primary); }
.spa-text--secondary { color: var(--spa-text-secondary); }
.spa-text--bold { font-weight: 700; }
.spa-text--medium { font-weight: 500; }

/* Отступы */
.spa-mt-1 { margin-top: var(--spa-space-s); }
.spa-mt-2 { margin-top: var(--spa-space-m); }
.spa-mt-3 { margin-top: var(--spa-space-l); }
.spa-mb-1 { margin-bottom: var(--spa-space-s); }
.spa-mb-2 { margin-bottom: var(--spa-space-m); }
.spa-mb-3 { margin-bottom: var(--spa-space-l); }

/* Паддинги */
.spa-p-1 { padding: var(--spa-space-s); }
.spa-p-2 { padding: var(--spa-space-m); }
.spa-p-3 { padding: var(--spa-space-l); }
```

## 📋 План внедрения

### Приоритет 1 (Сейчас):
1. **Design Tokens** - создать файл с CSS переменными
2. **MainLayout** - обновить текущий layout под структуру Ozon
3. **Grid System** - внедрить для каталога мастеров

### Приоритет 2 (Следующий спринт):
1. **Utility классы** - создать систему утилитарных классов
2. **Sticky элементы** - улучшить UX с sticky header/sidebar
3. **Container система** - унифицировать контейнеры

### Приоритет 3 (Будущее):
1. **Dark mode** - поддержка через CSS переменные
2. **Print styles** - оптимизация для печати
3. **Accessibility** - улучшение доступности

## 🎨 Примеры использования

### Страница каталога с новым layout:
```vue
<template>
  <MainLayout layout="two-column" :show-sidebar="true">
    <template #sidebar>
      <FilterPanel />
    </template>
    
    <div class="spa-page">
      <h1 class="spa-text--primary spa-mb-3">
        Мастера массажа
      </h1>
      
      <ResponsiveGrid :min-item-width="280" gap="m">
        <MasterCard 
          v-for="master in masters"
          :key="master.id"
          :master="master"
        />
      </ResponsiveGrid>
    </div>
  </MainLayout>
</template>
```

## 🚀 Быстрый старт

1. Скопировать design tokens в проект
2. Обновить MainLayout компонент
3. Применить Grid систему к MastersCatalog
4. Добавить sticky behavior к header
5. Протестировать на разных устройствах

## 📊 Ожидаемые улучшения

- **Консистентность**: Единая система отступов и размеров
- **Производительность**: CSS переменные быстрее SCSS
- **Масштабируемость**: Легко менять тему через переменные
- **Адаптивность**: Grid автоматически подстраивается
- **UX**: Sticky элементы улучшают навигацию

---

*Документ подготовлен на основе анализа production-кода Ozon*
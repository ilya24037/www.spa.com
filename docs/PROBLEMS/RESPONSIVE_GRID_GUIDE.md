# 📱 Руководство по Адаптивной Сетке SPA Platform

## 🎯 Принципы больших маркетплейсов

### 📊 Анализ брейкпоинтов

#### **🛒 Ozon:**
```css
/* Mobile:   320px-767px  = 1-2 колонки */
/* Tablet:   768px-1023px = 2-3 колонки */
/* Desktop:  1024px-1439px = 3-4 колонки */
/* Wide:     1440px+      = 4-6 колонок */
```

#### **🛍️ Wildberries:**
```css
/* Mobile:   до 767px    = 2 колонки плотно */
/* Tablet:   768px-1199px = 3-4 колонки */
/* Desktop:  1200px+     = 4-5 колонок */
```

#### **📱 Avito:**
```css
/* Mobile:   до 639px    = 1-2 колонки */
/* Tablet:   640px-1023px = 2-3 колонки */
/* Desktop:  1024px+     = 3-4 колонки */
```

## 🏗️ Наша система сеток

### **🎨 Брейкпоинты SPA Platform:**

```css
:root {
  /* Брейкпоинты (Mobile First) */
  --breakpoint-xs: 320px;   /* Маленькие телефоны */
  --breakpoint-sm: 640px;   /* Большие телефоны */
  --breakpoint-md: 768px;   /* Планшеты */
  --breakpoint-lg: 1024px;  /* Небольшие десктопы */
  --breakpoint-xl: 1280px;  /* Большие десктопы */
  --breakpoint-2xl: 1536px; /* Широкие экраны */
  
  /* Размеры карточек */
  --card-min-width: 280px;  /* Минимальная ширина карточки */
  --card-max-width: 320px;  /* Максимальная ширина карточки */
  --card-aspect-ratio: 1.2; /* Соотношение сторон */
  
  /* Отступы */
  --grid-gap: 1rem;         /* Основной отступ */
  --grid-gap-mobile: 0.75rem; /* Отступ на мобильных */
  --grid-gap-desktop: 1.5rem; /* Отступ на десктопе */
}
```

### **📐 Типы сеток:**

#### **1. Service Grid - Сетка услуг (основная)**
```css
.service-grid {
  /* Автоматическая адаптивная сетка */
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(var(--card-min-width), 1fr));
  gap: var(--grid-gap);
  
  /* Mobile: 320px-639px = 1 колонка */
  @media (max-width: 639px) {
    grid-template-columns: 1fr;
    gap: var(--grid-gap-mobile);
  }
  
  /* Small: 640px-767px = 2 колонки */
  @media (min-width: 640px) and (max-width: 767px) {
    grid-template-columns: repeat(2, 1fr);
  }
  
  /* Medium: 768px-1023px = 2-3 колонки */
  @media (min-width: 768px) and (max-width: 1023px) {
    grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
  }
  
  /* Large: 1024px-1279px = 3-4 колонки */
  @media (min-width: 1024px) and (max-width: 1279px) {
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: var(--grid-gap-desktop);
  }
  
  /* XL: 1280px+ = 4-5 колонок */
  @media (min-width: 1280px) {
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: var(--grid-gap-desktop);
  }
}
```

#### **2. Master Grid - Сетка мастеров**
```css
.master-grid {
  display: grid;
  gap: var(--grid-gap);
  
  /* Мастера занимают больше места */
  grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
  
  @media (max-width: 767px) {
    grid-template-columns: 1fr;
  }
  
  @media (min-width: 768px) and (max-width: 1023px) {
    grid-template-columns: repeat(2, 1fr);
  }
  
  @media (min-width: 1024px) {
    grid-template-columns: repeat(auto-fill, minmax(380px, 1fr));
  }
}
```

#### **3. Compact Grid - Компактная сетка**
```css
.compact-grid {
  display: grid;
  gap: calc(var(--grid-gap) * 0.75);
  
  /* Более плотная упаковка как на Wildberries */
  grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
  
  @media (max-width: 639px) {
    grid-template-columns: repeat(2, 1fr);
    gap: 0.5rem;
  }
  
  @media (min-width: 640px) and (max-width: 1023px) {
    grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
  }
  
  @media (min-width: 1024px) {
    grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
  }
}
```

## 🎛️ Переключение видов

### **📋 List View - Списочный вид (как на Avito)**
```css
.list-view {
  display: flex;
  flex-direction: column;
  gap: var(--grid-gap);
}

.list-view .card {
  display: flex;
  flex-direction: row;
  align-items: stretch;
  
  /* На мобильных остается вертикальным */
  @media (max-width: 767px) {
    flex-direction: column;
  }
}

.list-view .card__image {
  width: 200px;
  height: 150px;
  flex-shrink: 0;
  
  @media (max-width: 767px) {
    width: 100%;
    height: 200px;
  }
}

.list-view .card__content {
  flex: 1;
  padding: 1.5rem;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
}
```

### **🔲 Grid View - Сеточный вид**
```css
.grid-view {
  @apply service-grid;
}

.grid-view .card {
  display: flex;
  flex-direction: column;
}

.grid-view .card__image {
  width: 100%;
  height: 200px;
  object-fit: cover;
}
```

## 📱 Адаптивные карточки

### **🎴 Универсальная карточка**
```css
.adaptive-card {
  /* Базовые стили */
  background: white;
  border-radius: 0.75rem;
  overflow: hidden;
  transition: all 0.2s ease;
  border: 1px solid var(--border-color, #e5e7eb);
  
  /* Тени адаптируются под размер экрана */
  box-shadow: 
    0 1px 3px rgba(0, 0, 0, 0.1),
    0 1px 2px rgba(0, 0, 0, 0.06);
  
  @media (min-width: 768px) {
    box-shadow: 
      0 4px 6px rgba(0, 0, 0, 0.05),
      0 1px 3px rgba(0, 0, 0, 0.1);
  }
  
  /* Hover эффекты только на устройствах с hover */
  @media (hover: hover) {
    &:hover {
      transform: translateY(-2px);
      box-shadow: 
        0 10px 25px rgba(0, 0, 0, 0.1),
        0 5px 10px rgba(0, 0, 0, 0.05);
    }
  }
  
  /* На тач-устройствах убираем hover */
  @media (hover: none) {
    &:active {
      transform: scale(0.98);
    }
  }
}
```

## 🎯 Специальные сетки

### **⭐ Featured Grid - Рекомендуемые**
```css
.featured-grid {
  display: grid;
  gap: var(--grid-gap-desktop);
  
  /* Первый элемент занимает 2 колонки */
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  
  .featured-item:first-child {
    @media (min-width: 768px) {
      grid-column: span 2;
    }
    
    @media (min-width: 1024px) {
      grid-column: span 2;
      grid-row: span 2;
    }
  }
}
```

### **🔥 Hot Deals Grid - Горячие предложения**
```css
.hot-deals-grid {
  display: grid;
  gap: 0.75rem;
  
  /* Плотная упаковка как на распродажах */
  grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
  
  @media (max-width: 639px) {
    grid-template-columns: repeat(2, 1fr);
    gap: 0.5rem;
  }
  
  @media (min-width: 1024px) {
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
  }
}
```

## 🔧 Утилитарные классы

### **📏 Responsive Containers**
```css
.container-adaptive {
  width: 100%;
  margin: 0 auto;
  padding: 0 1rem;
  
  @media (min-width: 640px) {
    padding: 0 1.5rem;
  }
  
  @media (min-width: 768px) {
    padding: 0 2rem;
    max-width: 1200px;
  }
  
  @media (min-width: 1024px) {
    padding: 0 2.5rem;
    max-width: 1400px;
  }
  
  @media (min-width: 1280px) {
    padding: 0 3rem;
    max-width: 1600px;
  }
}
```

### **🎛️ Grid Controls**
```css
.grid-controls {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 1.5rem;
  
  @media (max-width: 639px) {
    flex-direction: column;
    gap: 1rem;
    align-items: stretch;
  }
}

.view-toggle {
  display: flex;
  border-radius: 0.5rem;
  overflow: hidden;
  border: 1px solid var(--border-color);
  
  button {
    padding: 0.5rem 1rem;
    border: none;
    background: white;
    cursor: pointer;
    
    &.active {
      background: var(--primary-color);
      color: white;
    }
    
    &:not(.active):hover {
      background: var(--gray-50);
    }
  }
}
```

## 🎨 Принципы дизайна

### **✅ Лучшие практики:**

1. **Mobile First** - начинаем с мобильной версии
2. **Content First** - контент определяет сетку
3. **Flexible** - сетка адаптируется к содержимому
4. **Performance** - минимум лишних расчетов
5. **Accessibility** - поддержка скрин-ридеров

### **❌ Чего избегать:**

1. Фиксированных размеров в пикселях
2. Слишком маленьких карточек на мобильных
3. Слишком больших карточек на десктопе
4. Игнорирования соотношений сторон
5. Переполнения контейнеров

## 📊 Метрики производительности

### **🎯 Целевые показатели:**

- **Загрузка сетки:** < 100ms
- **Перестроение:** < 50ms  
- **Скролл:** 60 FPS
- **Resize:** < 16ms

### **📈 Мониторинг:**

```javascript
// Измерение производительности сетки
const observer = new PerformanceObserver((list) => {
  for (const entry of list.getEntries()) {
    if (entry.name === 'grid-render') {
      console.log(`Grid render: ${entry.duration}ms`)
    }
  }
})

observer.observe({ entryTypes: ['measure'] })

// Замер перестроения сетки
performance.mark('grid-start')
// ... код перестроения сетки
performance.mark('grid-end')
performance.measure('grid-render', 'grid-start', 'grid-end')
```
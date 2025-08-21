# 🎨 Руководство по дизайн-токенам SPA Platform

## 📋 Обзор

Система дизайн-токенов SPA Platform построена по 3-уровневой иерархии, аналогичной Ozon:

```
Core Layer      → Базовые значения (цвета, размеры)
Semantic Layer  → Смысловые названия (bg-primary, text-error)
Component Layer → Токены компонентов (button-primary-bg)
```

## 🎯 Принципы использования

### ✅ ПРАВИЛЬНО - Используйте семантические токены:
```css
.my-button {
  background: var(--bg-primary);
  color: var(--text-inverse);
  border: 1px solid var(--border-brand);
}
```

### ❌ НЕПРАВИЛЬНО - Избегайте прямых значений:
```css
.my-button {
  background: #3b82f6;
  color: white;
  border: 1px solid #2563eb;
}
```

## 🎨 Категории токенов

### 1. Цветовые токены

#### Основные цвета
```css
var(--color-primary)      /* Основной цвет бренда */
var(--color-primary-light) /* Светлый оттенок */
var(--color-primary-dark)  /* Темный оттенок */
```

#### Статусные цвета
```css
var(--color-success)  /* #16a34a - зеленый */
var(--color-warning)  /* #f97316 - оранжевый */
var(--color-error)    /* #dc2626 - красный */
var(--color-info)     /* #3b82f6 - синий */
```

#### Фоны
```css
var(--bg-surface)           /* Основной фон */
var(--bg-surface-secondary) /* Вторичный фон */
var(--bg-muted)            /* Приглушенный фон */
var(--bg-primary)          /* Фон для акцентов */
```

#### Текст
```css
var(--text-primary)    /* Основной текст */
var(--text-secondary)  /* Вторичный текст */
var(--text-muted)      /* Приглушенный текст */
var(--text-inverse)    /* Инверсный текст (белый) */
```

### 2. Размерные токены

#### Отступы
```css
var(--spacing-1)   /* 4px */
var(--spacing-4)   /* 16px */
var(--spacing-6)   /* 24px */
var(--spacing-8)   /* 32px */
```

#### Типографика
```css
var(--font-size-sm)    /* 14px */
var(--font-size-base)  /* 16px */
var(--font-size-lg)    /* 18px */
var(--font-size-xl)    /* 20px */
```

#### Скругления
```css
var(--radius-sm)   /* 2px */
var(--radius-md)   /* 6px */
var(--radius-lg)   /* 8px */
var(--radius-xl)   /* 12px */
```

### 3. Компонентные токены

#### Кнопки
```css
/* Основная кнопка */
var(--button-primary-bg)
var(--button-primary-bg-hover)
var(--button-primary-text)

/* Вторичная кнопка */
var(--button-secondary-bg)
var(--button-secondary-text)
var(--button-secondary-border)

/* Размеры */
var(--button-padding-sm)   /* Маленькая */
var(--button-padding-md)   /* Средняя */
var(--button-padding-lg)   /* Большая */
```

#### Карточки
```css
var(--card-bg)           /* Фон карточки */
var(--card-border)       /* Граница */
var(--card-shadow)       /* Тень */
var(--card-shadow-hover) /* Тень при hover */
var(--card-radius)       /* Скругление */
var(--card-padding)      /* Внутренние отступы */
```

#### Поля ввода
```css
var(--input-bg)           /* Фон поля */
var(--input-border)       /* Граница */
var(--input-border-focus) /* Граница в фокусе */
var(--input-border-error) /* Граница ошибки */
var(--input-padding)      /* Отступы */
var(--input-shadow-focus) /* Тень фокуса */
```

## 🛠️ Практические примеры

### Создание кнопки с токенами
```vue
<template>
  <button class="btn btn--primary btn--md">
    Сохранить
  </button>
</template>

<style scoped>
.btn {
  padding: var(--button-padding-md);
  border-radius: var(--button-radius);
  font-weight: var(--button-font-weight);
  transition: all 0.2s ease;
}

.btn--primary {
  background: var(--button-primary-bg);
  color: var(--button-primary-text);
  border: 1px solid var(--button-primary-border);
}

.btn--primary:hover {
  background: var(--button-primary-bg-hover);
}
</style>
```

### Создание карточки мастера
```vue
<template>
  <div class="master-card">
    <div class="master-card__image">
      <img :src="master.photo" :alt="master.name" />
    </div>
    <div class="master-card__content">
      <h3 class="master-card__name">{{ master.name }}</h3>
      <p class="master-card__specialty">{{ master.specialty }}</p>
      <div class="master-card__price">от {{ master.price }} ₽</div>
    </div>
  </div>
</template>

<style scoped>
.master-card {
  background: var(--card-bg);
  border: 1px solid var(--card-border);
  border-radius: var(--card-radius);
  box-shadow: var(--card-shadow);
  transition: all 0.2s ease;
  overflow: hidden;
}

.master-card:hover {
  box-shadow: var(--card-shadow-hover);
}

.master-card__content {
  padding: var(--card-padding);
}

.master-card__name {
  color: var(--text-primary);
  font-size: var(--font-size-lg);
  font-weight: var(--font-weight-semibold);
  margin-bottom: var(--spacing-2);
}

.master-card__specialty {
  color: var(--text-secondary);
  font-size: var(--font-size-sm);
  margin-bottom: var(--spacing-3);
}

.master-card__price {
  color: var(--text-brand);
  font-size: var(--font-size-xl);
  font-weight: var(--font-weight-bold);
}
</style>
```

### Форма с токенами
```vue
<template>
  <form class="form">
    <div class="form__field">
      <label class="form__label">Имя</label>
      <input 
        type="text" 
        class="input"
        v-model="name"
        placeholder="Введите ваше имя"
      />
    </div>
    
    <div class="form__field">
      <label class="form__label">Описание</label>
      <textarea 
        class="textarea"
        v-model="description"
        placeholder="Расскажите о себе"
      ></textarea>
    </div>
    
    <button type="submit" class="btn btn--primary">
      Сохранить
    </button>
  </form>
</template>

<style scoped>
.form__field {
  margin-bottom: var(--spacing-6);
}

.form__label {
  display: block;
  color: var(--text-primary);
  font-weight: var(--font-weight-medium);
  margin-bottom: var(--spacing-2);
}
</style>
```

## 📱 Адаптивность и темная тема

### Автоматическая темная тема
Токены автоматически переключаются в темную тему при `prefers-color-scheme: dark`:

```css
/* Светлая тема */
var(--bg-surface)     /* белый */
var(--text-primary)   /* черный */

/* Темная тема (автоматически) */
var(--bg-surface)     /* темно-серый */
var(--text-primary)   /* белый */
```

### Переопределение для конкретных компонентов
```css
.special-component {
  /* Принудительно светлая тема */
  --bg-surface: #ffffff;
  --text-primary: #111827;
}
```

## 🚀 Миграция существующих компонентов

### Шаг 1: Найти жестко заданные значения
```css
/* Было */
.old-button {
  background: #3b82f6;
  color: white;
  padding: 12px 24px;
  border-radius: 6px;
}
```

### Шаг 2: Заменить на токены
```css
/* Стало */
.new-button {
  background: var(--button-primary-bg);
  color: var(--button-primary-text);
  padding: var(--button-padding-md);
  border-radius: var(--button-radius);
}
```

### Шаг 3: Использовать готовые классы
```vue
<!-- Было -->
<button class="old-button">Кнопка</button>

<!-- Стало -->
<button class="btn btn--primary btn--md">Кнопка</button>
```

## 📏 Правила именования

### Структура имени токена
```
--[category]-[property]-[variant]-[state]

Примеры:
--button-primary-bg-hover
--text-error
--spacing-4
--border-brand
```

### Категории
- `color-*` - базовые цвета
- `bg-*` - фоны
- `text-*` - текст
- `border-*` - границы
- `button-*` - кнопки
- `card-*` - карточки
- `input-*` - поля ввода
- `nav-*` - навигация

## 💡 Советы по использованию

### 1. Всегда используйте семантические токены
```css
/* ✅ Хорошо */
background: var(--bg-primary);

/* ❌ Плохо */
background: var(--color-blue-500);
```

### 2. Создавайте компонентные токены для сложных компонентов
```css
:root {
  --master-card-image-height: 200px;
  --master-card-content-padding: var(--spacing-4);
  --master-card-price-color: var(--text-brand);
}
```

### 3. Группируйте связанные токены
```css
/* Все токены кнопки вместе */
--button-radius: var(--radius-md);
--button-font-weight: var(--font-weight-medium);
--button-padding-sm: var(--spacing-2) var(--spacing-3);
--button-padding-md: var(--spacing-3) var(--spacing-4);
--button-padding-lg: var(--spacing-4) var(--spacing-6);
```

### 4. Используйте каскадность для вариаций
```css
.card--featured {
  --card-border: var(--border-brand);
  --card-shadow: var(--shadow-lg);
}
```

## 🔧 Инструменты разработки

### VS Code расширения
1. **CSS Var Complete** - автодополнение CSS переменных
2. **Color Highlight** - подсветка цветов в коде

### Полезные команды
```bash
# Поиск использования токенов
grep -r "var(--" resources/

# Поиск жестко заданных цветов
grep -r "#[0-9a-fA-F]\{6\}" resources/css/
```

---

**🎯 Результат:** Унифицированная система дизайна, аналогичная крупным маркетплейсам, с легкой поддержкой и расширением.
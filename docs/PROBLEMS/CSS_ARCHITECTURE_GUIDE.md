# 🎨 CSS Архитектура SPA Platform (SMACSS + БЭМ)

## 🏗️ Методология по образцу Ozon/Wildberries

### 📁 Структура CSS файлов

```
resources/css/
├── 01-base/                    # Базовые стили
│   ├── reset.css              # Сброс браузерных стилей
│   ├── typography.css         # Типографика
│   ├── forms.css              # Базовые стили форм
│   └── utilities.css          # Утилитарные классы
├── 02-layout/                 # Макетные стили
│   ├── grid.css               # Сетка страниц
│   ├── header.css             # Шапка сайта
│   ├── footer.css             # Подвал сайта
│   ├── sidebar.css            # Боковые панели
│   └── container.css          # Контейнеры
├── 03-modules/                # Модульные компоненты
│   ├── buttons.css            # Кнопки
│   ├── cards.css              # Карточки
│   ├── forms.css              # Формы
│   ├── modals.css             # Модальные окна
│   ├── navigation.css         # Навигация
│   └── widgets.css            # Стили виджетов
├── 04-state/                  # Состояния компонентов
│   ├── interactive.css        # Интерактивные состояния
│   ├── loading.css            # Состояния загрузки
│   ├── error.css              # Состояния ошибок
│   └── responsive.css         # Адаптивные состояния
├── 05-theme/                  # Темизация
│   ├── colors.css             # Цветовая схема
│   ├── spacing.css            # Отступы и размеры
│   ├── shadows.css            # Тени и эффекты
│   └── animations.css         # Анимации
└── app.css                    # Главный файл импортов
```

## 🎯 Принципы именования (БЭМ)

### **Блок (Block)**
Самостоятельная сущность, которая имеет смысл сама по себе.

```css
/* ✅ Правильно */
.card { }
.button { }
.master-profile { }
.catalog-item { }

/* ❌ Неправильно */
.red-button { }        /* Слишком конкретно */
.big-text { }          /* Описывает внешний вид */
```

### **Элемент (Element)**
Часть блока, которая не имеет смысла отдельно от блока.

```css
/* ✅ Правильно */
.card__header { }
.card__body { }
.card__footer { }
.button__icon { }
.button__text { }

/* ❌ Неправильно */
.card__header__title { }    /* Слишком глубокая вложенность */
.header { }                 /* Без связи с блоком */
```

### **Модификатор (Modifier)**
Флаг на блоке или элементе, определяющий внешний вид или поведение.

```css
/* ✅ Правильно - модификатор блока */
.button--primary { }
.button--secondary { }
.button--large { }
.button--disabled { }
.card--highlighted { }

/* ✅ Правильно - модификатор элемента */
.card__header--centered { }
.button__icon--left { }
.button__icon--right { }

/* ❌ Неправильно */
.primary-button { }         /* Не БЭМ формат */
.button-primary { }         /* Неправильный разделитель */
```

## 🏢 Примеры из больших компаний

### **🛒 Стиль Ozon:**
```css
/* Карточка товара */
.product-card { }
.product-card__image { }
.product-card__title { }
.product-card__price { }
.product-card__rating { }
.product-card--promoted { }
.product-card--sale { }

/* Фильтры каталога */
.catalog-filters { }
.catalog-filters__section { }
.catalog-filters__title { }
.catalog-filters__item { }
.catalog-filters--collapsed { }
```

### **🛍️ Стиль Wildberries:**
```css
/* Корзина покупок */
.shopping-cart { }
.shopping-cart__item { }
.shopping-cart__product { }
.shopping-cart__quantity { }
.shopping-cart__total { }
.shopping-cart--empty { }

/* Список желаний */
.wishlist { }
.wishlist__item { }
.wishlist__actions { }
.wishlist--grid { }
.wishlist--list { }
```

### **📱 Стиль Avito:**
```css
/* Объявление */
.listing { }
.listing__gallery { }
.listing__info { }
.listing__contacts { }
.listing__description { }
.listing--premium { }
.listing--urgent { }
```

## 🎨 SMACSS Категории

### **1. Base (Базовые стили)**
```css
/* Элементы без классов */
html, body { }
h1, h2, h3 { }
a { }
button { }
input { }
```

### **2. Layout (Макетные стили)**
```css
/* Префикс .l- */
.l-header { }
.l-main { }
.l-sidebar { }
.l-footer { }
.l-container { }
.l-grid { }
```

### **3. Module (Модули)**
```css
/* БЭМ именование */
.module-name { }
.module-name__element { }
.module-name--modifier { }
```

### **4. State (Состояния)**
```css
/* Префикс .is- или .has- */
.is-active { }
.is-loading { }
.is-disabled { }
.is-hidden { }
.has-error { }
.has-success { }
```

### **5. Theme (Темы)**
```css
/* Переменные и темизация */
:root {
  --theme-primary: #3b82f6;
  --theme-secondary: #6b7280;
}

.theme-dark { }
.theme-light { }
```

## 🔧 Правила именования для SPA Platform

### **Блоки (Компоненты):**
```css
.master-profile { }        /* Профиль мастера */
.service-card { }          /* Карточка услуги */
.booking-form { }          /* Форма бронирования */
.review-item { }           /* Элемент отзыва */
.catalog-grid { }          /* Сетка каталога */
```

### **Элементы:**
```css
.master-profile__avatar { }
.master-profile__name { }
.master-profile__bio { }
.master-profile__services { }
.master-profile__gallery { }
.master-profile__contacts { }
```

### **Модификаторы:**
```css
.master-profile--compact { }     /* Компактный вид */
.master-profile--featured { }    /* Рекомендуемый */
.service-card--popular { }       /* Популярная услуга */
.booking-form--quick { }         /* Быстрое бронирование */
```

### **Состояния:**
```css
.is-loading { }
.is-available { }
.is-booked { }
.has-discount { }
.has-reviews { }
```

## 📱 Адаптивность

### **Брейкпоинты:**
```css
/* Mobile first подход */
.module { }                    /* Mobile по умолчанию */
.module--tablet { }            /* 768px+ */
.module--desktop { }           /* 1024px+ */
.module--wide { }              /* 1280px+ */
```

### **Состояния экранов:**
```css
.is-mobile-hidden { }
.is-tablet-visible { }
.is-desktop-only { }
```

## 🎯 Лучшие практики

### **✅ Делать:**
- Использовать БЭМ для всех компонентов
- Группировать стили по SMACSS категориям
- Применять дизайн-токены через CSS переменные
- Писать mobile-first CSS
- Использовать семантические имена классов

### **❌ Не делать:**
- Глубокую вложенность (.block__element__subelement)
- Привязку к структуре HTML (.sidebar > .widget > .title)
- Стили на основе тегов в компонентах (div, span)
- Использование !important без крайней необходимости
- Смешивание модификаторов (.button.primary вместо .button--primary)

## 🚀 Миграция существующего CSS

### **Этапы:**
1. Аудит текущих стилей
2. Создание новой структуры папок  
3. Переименование классов по БЭМ
4. Разделение на SMACSS категории
5. Оптимизация и очистка

### **Приоритет миграции:**
1. Критичные компоненты (кнопки, формы)
2. Макетные стили (header, footer)
3. Виджеты и модули
4. Утилитарные классы
5. Состояния и анимации
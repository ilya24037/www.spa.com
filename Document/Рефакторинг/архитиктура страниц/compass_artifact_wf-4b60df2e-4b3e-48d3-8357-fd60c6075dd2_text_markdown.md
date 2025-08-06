# Архитектура унифицированного дизайна маркетплейсов: исследование Ozon, Avito, Wildberries и Amazon

## 1. Дизайн-система Ozon

Ozon использует **уникальный подход с двумя дизайн-системами**, каждая из которых оптимизирована под свои задачи:

### **Buyers Experience Design System (BX)**
- **Назначение**: Система для клиентских интерфейсов
- **Платформы**: Сайт ozon.ru и мобильные приложения
- **Архитектура**: Универсальные кросс-платформенные компоненты с единым API
- **Технология**: SDUI (Server-Driven UI) с рендерингом компонентов на стороне сервера

### **Ozon Internal Design System (OZI)**
- **Масштаб**: 500+ продуктов, сервисов, админ-панелей
- **Архитектура**: Платформо-специфичные библиотеки (отдельные для Web и Mobile)
- **Подход**: Более гибкий и атомарный

### Техническая реализация

**Система токенов**:
```
Иерархия токенов:
Core Layer:     blue-500, spacing-16
Semantic Layer: bg.primary.active, text.error  
Component Layer: button.primary.hover
```

**Ключевые технические решения**:
- **Цветовая модель LCH** для перцептивной консистентности
- **Семантическое именование** вместо конкретного (button.primary vs button.blue)
- **Числовая типографика**: heading-500, heading-600 вместо размеров S/M/L
- **Версионирование на уровне библиотек** для консистентности компонентов
- **W3C Design Tokens Format Module** в качестве стандарта

## 2. Архитектура лейаутов на больших e-commerce платформах

### Wildberries - лидер по производительности

**Архитектура данных**:
- **Денормализованная структура**: Вся информация о товаре (наличие, отзывы, цены) агрегирована в единые запросы
- **Dual-database подход**: PostgreSQL для транзакций + MongoDB для быстрого чтения каталога
- **Время отклика**: ~7мс для страниц товаров, ~30-40мс для каталога
- **Kubernetes orchestration** для автоматического деплоя и масштабирования

### Ozon - API-first архитектура

- **Push-based API** для интеграций с третьими сторонами
- **Real-time обновления**: Мгновенная синхронизация остатков через API
- **Виджетная система**: Каждая страница состоит из изолированных виджетов
- **Микрофронтенды**: Команды могут независимо разрабатывать и деплоить компоненты

### Общие паттерны архитектуры

1. **Трёхуровневая архитектура**: Presentation → Business Logic → Data Storage
2. **Headless/Composable commerce**: Разделение фронтенда и бэкенда
3. **Component-driven development**: Переиспользуемые UI-компоненты
4. **Mobile-first подход**: 95% пользователей с мобильных устройств

## 3. Система сеток и контейнеров

### Стандартные реализации Grid Systems

**12-колоночная сетка (Bootstrap-подход)**:
```css
/* Контейнеры с максимальной шириной */
.container {
  max-width: 540px;  /* sm */
  max-width: 720px;  /* md */
  max-width: 960px;  /* lg */
  max-width: 1140px; /* xl */
  max-width: 1320px; /* xxl */
}

/* Отступы между колонками */
.row {
  --gutter-x: 1.5rem; /* 24px */
}
```

**CSS Grid для листингов товаров**:
```css
.product-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 1.5rem;
}
```

### Адаптивные брейкпоинты

- **Мобильные**: Одноколоночные лейауты
- **Планшеты**: 2-3 колонки товаров
- **Десктоп**: 3-4+ колонки с сайдбарами
- **Фиксированная ширина контента**: 1200-1320px для больших экранов

## 4. Организация переиспользуемых компонентов

### Архитектура компонента Header

**Стандартные элементы**:
- Логотип (верхний левый угол)
- Основная навигация с мега-меню
- Поиск с автодополнением
- Управление аккаунтом
- Виджет корзины с real-time обновлениями
- Мобильное hamburger-меню

**Техническая реализация**:
```css
.header {
  position: sticky;
  top: 0;
  z-index: 1000;
  /* Critical CSS для above-fold контента */
}
```

### Структура Footer

- **Многоколоночная структура**: 3-4 колонки на десктопе
- **SEO-оптимизация**: Структурированная разметка для бизнес-информации
- **Консистентность**: Одинаковый футер на всех страницах

### Система сайдбаров

**Компоненты сайдбара**:
1. Навигация по категориям с expand/collapse
2. Фильтры (цена, бренд, атрибуты)
3. История просмотров
4. Промо-контент
5. Виджеты поддержки

**Адаптивное поведение**:
```css
/* Мобильная версия - скрытый сайдбар */
.sidebar {
  width: 250px;
  transform: translateX(-100%);
  transition: transform 0.3s ease;
}

/* Десктоп - видимый сайдбар */
@media (min-width: 1024px) {
  .sidebar {
    position: static;
    transform: translateX(0);
  }
}
```

## 5. CSS архитектура и методологии

### БЭМ - стандарт индустрии

Все три маркетплейса (Ozon, Avito, Wildberries) используют или требуют знание БЭМ:

```scss
/* Блок */
.product-card {
  padding: 1rem;
  border: 1px solid #ddd;
}

/* Элемент */
.product-card__image {
  width: 100%;
  object-fit: cover;
}

.product-card__title {
  font-size: 1.25rem;
  font-weight: 600;
}

/* Модификатор */
.product-card--featured {
  border-color: #3498db;
  box-shadow: 0 4px 8px rgba(52, 152, 219, 0.2);
}
```

### CSS организация в масштабе

**Ozon**:
- Компонентная CSS-организация aligned с виджетной архитектурой
- Изоляция стилей на уровне виджетов

**Avito**:
- CSS Modules для изоляции стилей
- Web Components с Shadow DOM для инкапсуляции

**Wildberries**:
- Sass/SCSS препроцессинг
- БЭМ методология в требованиях к разработчикам

## 6. Микрофронтенды и модульная архитектура

### Avito - пионер микрофронтендов

**Масштаб реализации**:
- **70+ модулей** в продакшене к концу 2022
- **36 команд** перешли на микрофронтенды
- **Время деплоя**: сокращено с ~20 минут до ~5 минут

**Техническая реализация**:
```javascript
// Webpack Module Federation для CSR
const ModuleFederationPlugin = require("webpack/lib/container/ModuleFederationPlugin");

module.exports = {
  plugins: [
    new ModuleFederationPlugin({
      name: "shell",
      remotes: {
        catalog: "catalog@http://localhost:3001/remoteEntry.js",
        cart: "cart@http://localhost:3002/remoteEntry.js"
      }
    })
  ]
};
```

**Архитектурные решения**:
- Каждый микрофронтенд обёрнут в Web Components
- CustomEvents для коммуникации между модулями
- Независимый деплой каждого микрофронтенда
- SSR поддержка через кастомное решение на PaaS

### Ozon - виджетная архитектура

- **Изолированные виджеты**: Не знают о своём окружении
- **Композиция страниц**: Сборка из независимых виджетов
- **Чёткие границы команд**: Нет зависимости от типа страницы
- **Переиспользование**: Компоненты работают на любой странице

## 7. UI Kit и компонентные библиотеки

### Wildberries - публичный NPM пакет

```bash
# Установка
npm i @wildberries/ui-kit

# Использование
import { Button, Radio } from '@wildberries/ui-kit'
```

**Статистика**:
- 30,896 загрузок в неделю
- Активная поддержка (обновления в течение дней)
- Плоская архитектура компонентов

### Avito - масштабная Figma-библиотека

**Структура библиотеки**:
```
100-Web-Components
200-Mobile-Components  
300-Icons-Illustrations
400-Developer-Specs
500-Design-Guidelines
```

**Управление компонентами**:
- Master Components с вариантами
- Auto Layout для гибкости
- Интеграция с Google Sheets для контента
- Трёхзначная система индексации

### Amazon Cloudscape

**Open Source дизайн-система**:
```javascript
// Установка
npm install @cloudscape-design/components

// Структура пакетов
@cloudscape-design/components    // Основные компоненты
@cloudscape-design/design-tokens // Дизайн токены
@cloudscape-design/global-styles // Глобальные стили
@cloudscape-design/test-utils    // Утилиты тестирования
```

- 60+ компонентов
- Apache 2.0 лицензия
- Полная поддержка TypeScript
- Темизация и dark mode

## 8. Лучшие практики унификации дизайна

### Стратегии управления дизайн-системой

**1. Версионирование и распространение**:
```bash
# Семантическое версионирование
MAJOR version: Несовместимые изменения API
MINOR version: Обратно-совместимые функции
PATCH version: Обратно-совместимые исправления
```

**2. Автоматизированное тестирование**:
- Visual regression testing (Chromatic, Percy, Applitools)
- Unit тесты компонентов
- Accessibility тестирование
- Cross-browser валидация

**3. Процессы коллаборации**:
- Design System Office Hours
- Стандартизированный процесс запроса компонентов
- Автоматические уведомления о breaking changes
- Детальные migration guides

### Управление цветами в масштабе (Avito)

**Проблема**: 2,000+ вариаций цветов в кодовой базе
**Решение**: 
- Автоматизированный анализ репозиториев
- Web crawler для поиска цветов
- Сокращение до ~400 цветов
- Dashboard со статистикой использования

## 9. Технический стек фронтенда Ozon

### Основной стек

**Frameworks & Libraries**:
- **Vue.js/Vuex/Nuxt.js** - основной фреймворк
- **TypeScript** - статическая типизация
- **Server-Side Rendering** через Nuxt.js

**Архитектурные подходы**:
- **Микрофронтенды** с виджетной композицией
- **SDUI** (Server-Driven UI) для BX системы
- **Пересмотр стека каждые 6 месяцев** для оптимизации

### Инструменты разработки

**Ozon Kelp Plugin** для Android Studio:
- Автодополнение цветовой палитры в IDE
- Навигация по компонентам
- Live templates для ускорения разработки
- Автоматическая установка showcase приложения

## 10. Примеры кода и структуры проекта

### Elegant Frontend Architecture (EFA)

```
src/
├── application/         # Страницы и роутинг
│   ├── App.tsx
│   └── pages/
│       ├── CheckoutPage.tsx
│       └── ProductPage.tsx
├── domain/             # Бизнес-логика
│   ├── customers/
│   ├── orders/
│   └── products/
├── ui/                 # Дизайн-система
│   ├── Button/
│   │   ├── Button.tsx
│   │   ├── Button.spec.ts
│   │   └── Button.stories.ts
│   └── Input/
└── infrastructure/     # Внешние сервисы
    ├── persistence/
    └── services/
```

### Конфигурация Webpack для масштабных приложений

```javascript
module.exports = {
  entry: {
    main: './src/index.js',
    vendor: ['react', 'react-dom']
  },
  optimization: {
    splitChunks: {
      chunks: 'all',
      cacheGroups: {
        vendor: {
          test: /[\\/]node_modules[\\/]/,
          name: 'vendors'
        },
        common: {
          minChunks: 2,
          name: 'common'
        }
      }
    }
  }
};
```

### Организация CSS по SMACSS

```scss
// 1. Base
@import 'base/normalize';
@import 'base/typography';

// 2. Layout
@import 'layout/grid';
@import 'layout/header';

// 3. Module
@import 'modules/buttons';
@import 'modules/cards';

// 4. State
@import 'states/loading';
@import 'states/error';

// 5. Theme
@import 'themes/default';
@import 'themes/dark';
```

## Заключение

Исследование показывает, что крупные маркетплейсы инвестировали значительные ресурсы в создание сложных дизайн-систем, выходящих далеко за рамки простых библиотек компонентов. **Ключевые инновации** включают двойные дизайн-системы Ozon, продвинутую микрофронтенд архитектуру Avito с 70+ модулями, и публичный UI kit Wildberries с 30,000+ еженедельных загрузок. Все платформы используют БЭМ методологию, современные фреймворки (Vue.js для Ozon, React для Avito) и фокусируются на производительности через денормализацию данных и CDN-оптимизацию. Особенно примечательны инновационные подходы, такие как использование LCH цветового пространства и SDUI технологии в Ozon, а также автоматизированное управление цветами в Avito, сократившее количество цветовых вариаций с 2,000 до 400.
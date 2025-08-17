# 📚 Полезные находки из анализа dosugbar

## 📁 Структура папки

```
Досуг/
├── логика/           # JavaScript/TypeScript логика
├── стили/            # CSS стили
├── компоненты/       # Vue компоненты
└── формы/            # Формы авторизации и регистрации
```

## 🎯 Что здесь находится

### 📂 Папка `логика/`

#### `searchCity.js` (оригинал из dosugbar)
- Поиск по городам, районам и метро в реальном времени
- Использует jQuery и setInterval для фильтрации
- **Недостатки:** устаревший подход с jQuery

#### `useLocationSearch.ts` (адаптированная версия)
- ✅ Переписана для Vue 3 Composition API
- ✅ TypeScript типизация
- ✅ Реактивная фильтрация через computed
- ✅ Дебаунс вместо setInterval
- ✅ Поддержка координат для карт

**Использование в компоненте:**
```vue
<script setup>
import { useLocationSearch } from '@/Досуг/логика/useLocationSearch'

const {
  searchCity,
  visibleCities,
  filteredLocations,
  selectLocation
} = useLocationSearch()
</script>

<template>
  <input v-model="searchCity" placeholder="Начните вводить город...">
  <ul>
    <li v-for="city in visibleCities" @click="selectLocation(city)">
      {{ city }}
    </li>
  </ul>
</template>
```

#### `recentlyViewed.js` (оригинал)
- Система отслеживания недавно просмотренных элементов
- Сохранение в localStorage
- Можно адаптировать для истории просмотров мастеров

#### `telegramAuthIntegration.ts` (новая разработка)
- 🚀 Интеграция авторизации через Telegram Bot
- ✅ TypeScript типизация
- ✅ Composable для Vue 3
- ✅ Проверка статуса авторизации
- ✅ Deep links для Telegram
- ✅ Webhook обработчики

### 📂 Папка `стили/`

#### `booking.css`
- Стили для календаря бронирования
- Легенда статусов (свободно/занято/выбрано)
- Адаптивная сетка временных слотов

#### `file-upload.css`
- Drag & drop зона загрузки
- Стили для превью изображений и видео
- Индикаторы прогресса загрузки
- Визуальная индикация ошибок

#### `popup.css`
- Модальные окна
- Анимации появления/скрытия
- Оверлей и позиционирование

#### `plans-chart.css`
- Визуализация тарифных планов
- Сравнительные таблицы
- Выделение рекомендованного тарифа

### 📂 Папка `компоненты/`

#### `BookingCalendar.vue`
- ✅ Полностью адаптирован для Vue 3
- ✅ TypeScript типизация
- ✅ Календарь с выбором временных слотов
- ✅ Легенда статусов
- ✅ Реактивное обновление

**Особенности:**
- 7-дневный календарь
- Временные слоты с 9:00 до 21:00
- Визуальные статусы: свободно, занято, выбрано
- Responsive дизайн

#### `FileUpload.vue`
- ✅ Drag & drop загрузка файлов
- ✅ Множественная загрузка
- ✅ Превью изображений
- ✅ Индикация видео файлов
- ✅ Прогресс-бар загрузки
- ✅ Сортировка drag & drop
- ✅ Ограничения по размеру и количеству

**Props:**
```typescript
interface Props {
  multiple?: boolean      // Множественная загрузка
  maxFiles?: number       // Макс. количество файлов (10)
  maxSizeMB?: number      // Макс. размер в MB (10)
  accept?: string         // Типы файлов
}
```

### 📂 Папка `формы/`

#### `LoginForm.vue` (из анализа dosugbar_form)
- ✅ Полноценная форма авторизации для Vue 3
- ✅ **Правильные inputmode атрибуты для мобильных**:
  - `inputmode="email"` для поля email
  - Предотвращение zoom на iOS (font-size: 16px)
- ✅ Показ/скрытие пароля
- ✅ Валидация в реальном времени
- ✅ Интеграция с Telegram Bot
- ✅ Социальные сети (VK, Google, Яндекс)
- ✅ Две версии: полная и компактная (для хедера)

**Ключевые особенности для мобильных:**
```html
<!-- Правильный inputmode для клавиатуры email на мобильных -->
<input
  type="email"
  inputmode="email"
  autocomplete="email"
  style="font-size: 16px" /* Предотвращает zoom на iOS */
/>
```

## 🚀 Как использовать в SPA Platform

### 1. Импорт компонентов

```vue
// В вашем компоненте страницы мастера
<script setup>
import BookingCalendar from '@/Досуг/компоненты/BookingCalendar.vue'
import FileUpload from '@/Досуг/компоненты/FileUpload.vue'
</script>

<template>
  <!-- Календарь бронирования -->
  <BookingCalendar />
  
  <!-- Загрузка фото работ -->
  <FileUpload 
    :max-files="20"
    :max-size-mb="5"
    accept="image/*"
    @upload="handlePhotosUpload"
  />
</template>
```

### 2. Использование логики поиска

```vue
// Компонент фильтра мастеров по локации
<script setup>
import { useLocationSearch } from '@/Досуг/логика/useLocationSearch'

const { 
  searchCity, 
  searchDistrict,
  searchMetro,
  filteredLocations 
} = useLocationSearch()

// Фильтрация мастеров по выбранной локации
const filteredMasters = computed(() => {
  return masters.value.filter(master => {
    const location = filteredLocations.value.find(
      loc => loc.id === master.locationId
    )
    return location !== undefined
  })
})
</script>
```

### 3. Использование формы авторизации

```vue
// Главная форма авторизации
<script setup>
import LoginForm from '@/Досуг/формы/LoginForm.vue'
</script>

<template>
  <!-- Полная форма на странице -->
  <LoginForm />
  
  <!-- Компактная форма в хедере -->
  <LoginForm :is-dropdown="true" />
</template>
```

### 4. Telegram Bot авторизация

```typescript
// Использование Telegram авторизации
import { useTelegramAuth } from '@/Досуг/логика/telegramAuthIntegration'

const { 
  startTelegramAuth, 
  isAuthenticating,
  checkExistingAuth 
} = useTelegramAuth()

// Проверка существующей авторизации
const telegramUser = checkExistingAuth()
if (telegramUser) {
  console.log('Пользователь авторизован:', telegramUser.first_name)
}

// Начать авторизацию
const loginWithTelegram = () => {
  startTelegramAuth()
}
```

### 5. Применение стилей

```vue
<style lang="css">
/* Импорт нужных стилей */
@import '@/Досуг/стили/booking.css';
@import '@/Досуг/стили/file-upload.css';

/* Или использование отдельных классов */
.my-booking-calendar {
  /* Переиспользование классов из booking.css */
  @apply booking-calendar;
}
</style>
```

## ⚠️ Важные замечания

### Что НЕ стоит использовать:
1. **jQuery зависимости** - устарело для Vue проектов
2. **setInterval для фильтрации** - используйте computed/watch
3. **Inline стили через JS** - используйте классы и v-bind:class

### Что требует адаптации:
1. **API endpoints** - замените на ваши
2. **Структура данных** - приведите к вашим моделям
3. **Стили** - адаптируйте под ваш дизайн-систему

## 📈 Метрики производительности

- **BookingCalendar.vue**: ~15KB (gzip: ~4KB)
- **FileUpload.vue**: ~12KB (gzip: ~3KB)
- **LoginForm.vue**: ~18KB (gzip: ~5KB)
- **useLocationSearch.ts**: ~5KB (gzip: ~1.5KB)
- **telegramAuthIntegration.ts**: ~8KB (gzip: ~2.5KB)

## 🔧 TODO: Дальнейшие улучшения

- [ ] Добавить тесты для компонентов
- [ ] Интеграция с Pinia store
- [ ] Добавить i18n для мультиязычности
- [ ] Оптимизация для мобильных устройств
- [ ] Добавить Storybook stories
- [ ] Интеграция с API бэкенда

## 📝 Лицензия

Эти компоненты созданы на основе анализа публично доступных ресурсов и полностью переписаны для использования в проекте SPA Platform.
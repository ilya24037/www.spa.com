\# 🏗️ АРХИТЕКТУРА SPA PLATFORM
# CLAUDE.md - Архитектура и правила проекта SPA Platform




\## Технический стек

\- Backend: Laravel 12 + Clean Architecture

\- Frontend: Vue 3 + Inertia.js + Tailwind CSS

\- DB: MySQL с миграциями

\- Архитектура: Domain-Driven Design



# CLAUDE.md - Архитектура и правила проекта SPA Platform

## 🧠 КОНТЕКСТНАЯ ИНЖЕНЕРИЯ (из статьи Habr "Анатомия памяти LLM")

### 5 хаков управления контекстом LLM

#### 1. 🎯 "Последнее слово" (Last Word)
**КРИТИЧЕСКИ ВАЖНЫЕ инструкции размещаем В КОНЦЕ промпта**
```
// ❌ ПЛОХО: важное в начале
"ВАЖНО: не удаляй существующий код! 
Добавь новый метод calculateTotal в класс Order"

// ✅ ХОРОШО: важное в конце
"Добавь новый метод calculateTotal в класс Order
КРИТИЧЕСКИ ВАЖНО: НЕ УДАЛЯЙ существующий код!"
```

#### 2. 💉 "Принудительная инъекция" (Forced Injection)
**В длинных диалогах НАПОМИНАЕМ ключевые факты**
```
"Помни: мы работаем с Laravel 12, Vue 3, TypeScript обязателен"
"Не забудь: архитектура FSD, компоненты в entities/master/ui/"
"Контекст: рефакторинг MasterCard, сохраняем обратную совместимость"
```

#### 3. ⚓ "Структурный якорь" (Structural Anchor)
**Даем ШАБЛОНЫ для направления внимания модели**
```
Шаблон компонента:
1. TypeScript интерфейсы для props
2. Composables для логики  
3. Computed для защиты от null
4. Template с v-if проверками
5. Skeleton loader для загрузки
```

#### 4. 📋 "Ручная суммаризация" (Manual Summarization)
**Просим модель РЕЗЮМИРОВАТЬ свой контекст**
```
"Резюмируй, что мы уже сделали по задаче"
"Перечисли ключевые требования, которые нужно соблюсти"
"Какие файлы мы уже изменили и что осталось?"
```

#### 5. 🎭 "Разделение сил" (Power Division)
**Разные модели для разных задач**
- Простые правки → быстрая модель
- Архитектурные решения → мощная модель
- Генерация документации → специализированная модель

### Оптимизация контекста

#### Принципы структурирования информации:
1. **Иерархия важности**: критическое → важное → дополнительное
2. **Группировка по темам**: архитектура | код | требования | примеры
3. **Временная релевантность**: текущая задача → контекст → история

#### Техники снижения нагрузки на контекст:
- Удаляем избыточные комментарии после выполнения
- Суммаризируем длинные обсуждения в короткие выводы
- Выносим примеры в отдельные блоки (загружаем по запросу)

## 📌 НАПОМИНАНИЕ ДЛЯ РАЗРАБОТЧИКА

### При начале работы проверьте:
1. **AI_CONTEXT.md** - актуальный контекст задач
2. **storage/ai-sessions/watch_*.md** - последние изменения от Watch Mode
3. Попросите Claude прочитать эти файлы командой:
   - "Прочитай AI_CONTEXT.md"
   - "Прочитай последний файл из storage/ai-sessions/"

## 🎯 КРИТИЧЕСКИ ВАЖНО: Методика работы

### Ключевые команды для ИИ (обновлено с учетом контекстной инженерии)
1. **Начало задачи**: "Ultrathink, вспомни CLAUDE.md и AI_CONTEXT.md"
2. **Напоминание контекста**: "Помни: [ключевые требования задачи]"
3. **Проверка понимания**: "Резюмируй задачу и ключевые ограничения"
4. **После создания кода**: "Критически оцени, всё ли ты сделал правильно?"
5. **Финальная проверка**: "Проверь по чек-листу. ВАЖНО: все требования соблюдены?"

### Стандартный флоу разработки (УЛУЧШЕН)
1. **Планирование** → "Создай план для [задачи]. КРИТИЧЕСКИ ВАЖНО: учти [ограничения]"
2. **Проверка плана** → Я проверяю и корректирую
3. **Напоминание контекста** → "Помни: архитектура FSD, TypeScript, Laravel 12"
4. **Реализация** → "Выполни пункт 1. Используй шаблон из CLAUDE.md"
5. **Критическая оценка** → "Резюмируй что сделано. Соответствует ли требованиям?"
6. **Доработка** → "Доработай до production. НЕ ЛОМАЙ существующий функционал!"
7. **Финал** → "Проверь чек-лист. КРИТИЧЕСКИ ВАЖНО: тесты пройдены?"

### Шаблоны для сложных задач

#### Шаблон рефакторинга компонента:
```
1. Прочитай текущий компонент
2. Определи зависимости и использование
3. Создай TypeScript интерфейсы
4. Вынеси логику в composables
5. Добавь обработку состояний (loading, error, empty)
6. Добавь skeleton loader
7. Проверь мобильную адаптивность
КРИТИЧЕСКИ ВАЖНО: сохрани обратную совместимость!
```

#### Шаблон создания нового домена:
```
1. Создай структуру папок Domain/[Name]/
2. Создай Models/ с Eloquent моделями
3. Создай Services/ с бизнес-логикой
4. Создай Repositories/ для работы с БД
5. Создай DTOs/ для передачи данных
6. Добавь Actions/ для сложных операций
ВАЖНО: никакой логики в контроллерах!
```

## 📐 НОВАЯ АРХИТЕКТУРА (Frontend - FSD)

### Feature-Sliced Design структура
resources/js/src/ ├── shared/ # Переиспользуемый код │ ├── api/ # API клиенты
│ ├── config/ # Конфигурация │ ├── layouts/ # Layouts (MainLayout, ProfileLayout) │ │ ├── MainLayout/ │ │ ├── ProfileLayout/ │ │ └── components/ # SidebarWrapper, ContentCard │ ├── lib/ # Библиотеки, хелперы │ └── ui/ # UI-kit │ ├── atoms/ # Button, Input, Icon │ ├── molecules/ # Card, Modal, Toast, Breadcrumbs │ └── organisms/ # Header, Footer, Sidebar ├── entities/ # Бизнес-сущности │ ├── master/ # Мастер │ │ ├── ui/ # MasterCard, MasterInfo, MasterGallery │ │ ├── model/ # Stores, types │ │ └── api/ # API методы │ ├── booking/ # Бронирование │ ├── ad/ # Объявление │ ├── user/ # Пользователь │ └── service/ # Услуга ├── features/ # Функциональности │ ├── masters-filter/ # Фильтры мастеров │ ├── booking-form/ # Форма бронирования │ ├── auth/ # Авторизация │ ├── gallery/ # Галерея фото │ └── map/ # Карта с мастерами ├── widgets/ # Композиционные блоки │ ├── masters-catalog/ # Каталог мастеров (главная) │ ├── master-profile/ # Профиль мастера │ └── profile-dashboard/# Личный кабинет └── pages/ # Страницы приложения ├── home/ ├── masters/ └── profile/

## 📐 НОВАЯ АРХИТЕКТУРА (Backend - DDD)

### Domain-Driven Design структура
app/ ├── Domain/ # Бизнес-логика по доменам │ ├── User/
│ │ ├── Models/ # User, UserProfile, UserSettings │ │ ├── Services/ # UserService, AuthService │ │ ├── Repositories/ # UserRepository │ │ ├── DTOs/ # CreateUserDTO, UpdateUserDTO │ │ ├── Actions/ # RegisterUserAction, VerifyEmailAction │ │ └── Traits/ # HasRoles, HasBookings │ ├── Master/
│ │ ├── Models/ # MasterProfile, MasterMedia, Schedule │ │ ├── Services/ # MasterService, ScheduleService │ │ └── Repositories/ # MasterRepository │ ├── Booking/
│ │ ├── Models/ # Booking, BookingSlot │ │ ├── Services/ # BookingService, NotificationService │ │ └── Actions/ # CreateBookingAction, CancelBookingAction │ ├── Ad/
│ │ ├── Models/ # Ad, AdMedia, AdPricing, AdLocation │ │ ├── Services/ # AdService, DraftService │ │ └── Actions/ # PublishAdAction, ArchiveAdAction │ └── Media/
│ ├── Models/ # Photo, Video │ ├── Services/ # MediaService, ImageProcessor │ └── DTOs/ # MediaUploadDTO ├── Application/ # Слой приложения │ ├── Http/ │ │ ├── Controllers/ # Только HTTP логика! │ │ ├── Requests/ # Валидация запросов │ │ ├── Resources/ # API Resources │ │ └── Middleware/ # Middleware │ └── Exceptions/ # Обработка исключений ├── Infrastructure/ # Инфраструктура │ ├── Services/ # Внешние сервисы (SMS, Email, Payment) │ ├── Analysis/ # AiContext и аналитика │ └── Cache/ # Кеширование └── Support/ # Вспомогательные ├── Helpers/ # Хелперы └── Traits/ # Общие трейты

## 🛠️ ТЕХНИЧЕСКИЕ ПРАВИЛА

### Laravel (Backend)
```php
// ВСЕГДА: Контроллер → Сервис → Репозиторий → Модель

// ❌ ПЛОХО: Логика в контроллере
public function store(Request $request) {
    $master = new MasterProfile();
    $master->name = $request->name;
    // ... много логики
}

// ✅ ХОРОШО: Делегирование в сервис
public function store(StoreMasterRequest $request) {
    $dto = CreateMasterDTO::fromRequest($request);
    $master = $this->masterService->create($dto);
    return new MasterResource($master);
}
Vue 3 + Composition API (Frontend)
vue
<!-- ВСЕГДА используем TypeScript и <script setup> -->
<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import type { Master } from '@/entities/master/model/types'

// Props с ОБЯЗАТЕЛЬНЫМИ types и defaults
interface Props {
  master: Master
  loading?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  loading: false
})

// Защита от ошибок через computed
const safeMaster = computed(() => props.master || {} as Master)

// Composables для логики
const { bookingModal, openBooking } = useBookingModal()
</script>

<template>
  <!-- ВСЕГДА проверяем данные -->
  <div v-if="safeMaster.id" class="master-card">
    <!-- контент -->
  </div>
  <MasterCardSkeleton v-else />
</template>
Модульная структура компонентов (FSD)
entities/master/ui/MasterCard/
├── MasterCard.vue           # Главный компонент
├── MasterCard.types.ts      # TypeScript типы
├── MasterCard.stories.ts    # Storybook истории
├── components/              # Подкомпоненты
│   ├── MasterCardHeader.vue
│   ├── MasterCardServices.vue  
│   └── MasterCardActions.vue
└── composables/             # Логика
    └── useMasterCard.ts
📋 ЧЕК-ЛИСТ для КАЖДОГО компонента
Обязательные проверки перед сдачей:
• TypeScript типизация всех props и emits
• Default значения для всех опциональных props
• Обработка состояний: loading, error, empty
• v-if защита от undefined/null данных
• Skeleton loader для загрузки
• Error boundary или try/catch
• Мобильная адаптивность (sm:, md:, lg:)
• Семантическая верстка (header, main, article)
• ARIA атрибуты для доступности
• Оптимизация изображений (lazy loading, srcset)
• Composables для переиспользуемой логики
• Storybook story для компонента
🚫 КАТЕГОРИЧЕСКИ ЗАПРЕЩЕНО
1. Бизнес-логика в контроллерах - только в сервисах!
2. Прямые SQL запросы - только через Eloquent/Query Builder
3. Логика во Vue компонентах - выносим в composables
4. any типы в TypeScript - всегда явная типизация
5. Игнорирование ошибок - обязательна обработка
6. Inline стили - только Tailwind классы
7. Дублирование кода - DRY принцип
8. Коммиты без проверки - обязательное тестирование
🎨 UI/UX СТАНДАРТЫ
Референсы (строго следуем)
• Каталог и фильтры: Avito
• Карточки товаров: Ozon
• Формы и валидация: Wildberries
• Модальные окна: Booking.com
• Мобильная версия: Яндекс.Услуги
Обязательные состояния компонентов
vue
<template>
  <!-- 1. Loading state (skeleton) -->
  <div v-if="isLoading" class="animate-pulse">
    <div class="h-48 bg-gray-200 rounded-lg mb-4" />
    <div class="h-4 bg-gray-200 rounded w-3/4 mb-2" />
    <div class="h-4 bg-gray-200 rounded w-1/2" />
  </div>

  <!-- 2. Error state -->
  <div v-else-if="error" class="rounded-lg border-2 border-red-200 bg-red-50 p-6">
    <p class="text-red-600 font-medium mb-2">Произошла ошибка</p>
    <p class="text-red-500 text-sm mb-4">{{ error.message }}</p>
    <button @click="retry" class="btn-secondary">
      Попробовать снова
    </button>
  </div>

  <!-- 3. Empty state -->
  <div v-else-if="isEmpty" class="text-center py-12">
    <EmptyStateIcon class="w-24 h-24 mx-auto mb-4 text-gray-300" />
    <p class="text-gray-500 mb-4">Ничего не найдено</p>
    <button @click="resetFilters" class="btn-primary">
      Сбросить фильтры
    </button>
  </div>

  <!-- 4. Content state -->
  <div v-else class="space-y-4">
    <!-- основной контент -->
  </div>
</template>
📝 GIT WORKFLOW
Формат коммитов (Conventional Commits)
bash
# Новая функциональность
feat(master-card): add skeleton loader for card

# Исправление бага
fix(booking): fix time slot validation

# Рефакторинг
refactor(filters): migrate to FSD structure

# Стили
style(master-card): update mobile layout

# Документация
docs(readme): add FSD migration guide

# Тесты
test(booking): add unit tests for service

# Сборка
build(vite): optimize chunk splitting

# CI/CD
ci(github): add frontend tests workflow
Правила именования веток
bash
feature/add-master-gallery      # Новая функциональность
fix/booking-time-validation     # Исправление бага
refactor/migrate-to-fsd        # Рефакторинг
hotfix/critical-payment-bug    # Срочное исправление
🚀 ПОРЯДОК МИГРАЦИИ НА НОВУЮ АРХИТЕКТУРУ
Неделя 1: Backend рефакторинг
1. День 1: Создание Domain структуры
2. День 2-3: Миграция моделей в домены
3. День 4-5: Создание сервисов и репозиториев
4. День 6-7: Рефакторинг контроллеров
Неделя 2: Frontend миграция на FSD
1. День 1: Настройка FSD структуры
2. День 2: Миграция shared компонентов
3. День 3-4: Создание entities
4. День 5: Создание features
5. День 6-7: Создание widgets и обновление pages
📊 МЕТРИКИ КАЧЕСТВА КОДА
Обязательные показатели:
• Покрытие тестами: минимум 70%
• Размер компонента: не более 200 строк
• Размер функции: не более 50 строк
• Цикломатическая сложность: не более 10
• Уровень вложенности: не более 4
• TypeScript coverage: 100%


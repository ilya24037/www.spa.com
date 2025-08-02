\# 🏗️ АРХИТЕКТУРА SPA PLATFORM
# CLAUDE.md - Архитектура и правила проекта SPA Platform




\## Технический стек

\- Backend: Laravel 12 + Clean Architecture

\- Frontend: Vue 3 + Inertia.js + Tailwind CSS

\- DB: MySQL с миграциями

\- Архитектура: Domain-Driven Design



# CLAUDE.md - Архитектура и правила проекта SPA Platform

## 🎯 КРИТИЧЕСКИ ВАЖНО: Методика работы (из статьи Habr)

### Ключевые команды для ИИ
1. **Начало ЛЮБОЙ задачи**: "Ultrathink, вспомни CLAUDE.md и AI_CONTEXT.md"
2. **После создания кода**: "Критически оцени, всё ли ты сделал правильно?"
3. **Если не всё сделано**: "Делай всё качественно и до конца"
4. **Финальная проверка**: "Критически оцени выполнение [задачи] по CLAUDE.md"

### Стандартный флоу разработки (СТРОГО СОБЛЮДАТЬ!)
1. Создание тикета → "Создай план работы для [задачи] с полным описанием"
2. Проверка плана → Я проверяю и корректирую
3. Реализация → "Ultrathink, выполни пункт 1 плана. Один файл"
4. Критическая оценка → "Критически оцени созданное"
5. Доработка → "Доработай найденные проблемы до production качества"
6. Повтор п.4-5 до полного завершения
7. Финал → "Проверь по чек-листу из CLAUDE.md"

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


# 🤖 AI AGENT CONFIGURATION

## Role (роль)
You are a Senior Full-Stack Developer with 10+ years experience specializing in:
- Laravel (Domain-Driven Design architecture)
- Vue.js 3 (Composition API, TypeScript)
- Feature-Sliced Design (FSD)
- Production-ready enterprise applications

## Context (контекст проекта)
- **Project:** SPA Platform - платформа услуг массажа
- **Tech stack:** Laravel 12, Vue 3, Inertia.js, TypeScript, Tailwind CSS
- **Codebase:** 37,000+ lines of code
- **Architecture:** Domain-Driven Design (backend), Feature-Sliced Design (frontend)
- **Quality standards:** Production-ready, 80% test coverage minimum
- **Team:** Working with AI assistants and human developers

## Principles (принципы работы)
1. **KISS** - максимальная простота решений (Keep It Simple, Stupid)
2. **SOLID** - чистая архитектура без компромиссов
3. **DRY** - без дублирования кода (Don't Repeat Yourself)
4. **Test-first** - сначала тесты, потом код
5. **Security by default** - безопасность из коробки
6. **Performance first** - оптимизация с первой строки

## Code Standards (стандарты кода)
- **PHP:** PSR-12, строгая типизация, declare(strict_types=1)
- **JavaScript/TypeScript:** Airbnb style guide, no any types
- **Vue:** Composition API only, `<script setup lang="ts">`
- **Documentation:** PHPDoc и JSDoc обязательны для публичных методов
- **Test coverage:** минимум 80% для критического кода
- **Naming:** camelCase для JS/TS, snake_case для PHP/DB

## Workflow (рабочий процесс)
1. **Understand requirement** - пойми требование полностью, задай вопросы
2. **Check existing code** - проверь существующий код и паттерны
3. **Design solution** - спроектируй решение по принципу KISS
4. **Write tests first** - напиши тесты до реализации
5. **Implement incrementally** - реализуй пошагово с проверками
6. **Refactor if needed** - рефактори при необходимости
7. **Verify full data chain** - проверь полную цепочку данных

# 📋 INSTRUCTIONS

## Always (всегда делай)
- ✅ Write production-ready code с обработкой всех edge cases
- ✅ Include comprehensive error handling (try/catch, валидация)
- ✅ Add input validation на frontend и backend
- ✅ Write unit tests для критической логики
- ✅ Document complex logic на русском языке в комментариях
- ✅ Check full data chain: component → watcher → emit → backend → DB
- ✅ Use existing patterns и компоненты из проекта
- ✅ Follow FSD/DDD architecture строго
- ✅ Validate all user input на XSS и SQL injection
- ✅ Use TypeScript strict mode везде

## Never (никогда не делай)
- ❌ Use any/unknown types в TypeScript
- ❌ Ignore edge cases или ошибки
- ❌ Skip error handling в промисах и async функциях
- ❌ Write untested critical code
- ❌ Use deprecated methods или библиотеки
- ❌ Add business logic в контроллеры Laravel
- ❌ Create duplicate functionality без рефакторинга
- ❌ Break existing functionality при изменениях
- ❌ Commit console.log или debug код
- ❌ Use inline styles вместо Tailwind классов

## For large codebase (правила для 37k+ строк)
- 🔍 **Maintain existing patterns** - следуй установленным паттернам
- 🔍 **Check for duplicates** - ищи похожий код перед созданием
- 🔍 **Update related documentation** - обновляй связанную документацию
- 🔍 **Use incremental approach** - большие изменения делай пошагово
- 🔍 **Test thoroughly** - тестируй после каждого изменения
- 🔍 **Keep backward compatibility** - сохраняй обратную совместимость
- 🔍 **Profile performance** - проверяй производительность
- 🔍 **Optimize imports** - следи за размером бандла

# 🏗️ АРХИТЕКТУРА SPA PLATFORM

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

## ⚠️ ВАЖНЫЕ УРОКИ И ПРИНЦИПЫ

### Принцип KISS (Keep It Simple, Stupid)
**ВСЕГДА начинай с самого простого решения!**

#### Урок из реальной практики (14.08.2025):
**Задача:** Добавить секцию "Комфорт" после секции "Услуги"

**❌ Как я сделал НЕПРАВИЛЬНО:**
1. Создал отдельное поле `comfort` в БД (миграция)
2. Добавил сложную логику разделения данных в AdForm.vue
3. Делал отдельную обработку в контроллере
4. Усложнил сохранение и загрузку данных

**✅ Как нужно было сделать ПРАВИЛЬНО:**
1. Создать визуально отдельную секцию
2. Передать в неё тот же `form.services`
3. Пусть компонент сам фильтрует нужные категории
4. Никаких изменений в БД и backend

**Вывод:** Когда пользователь просит "отдельную секцию" - это обычно означает ВИЗУАЛЬНОЕ разделение, а не архитектурное!

### Правила на будущее:
1. **Сначала спроси:** "Нужно отдельное поле в БД или только визуальное разделение?"
2. **Начни с простого:** Самое простое решение, которое работает
3. **Усложняй только если необходимо:** И только после подтверждения
4. **Переиспользуй существующую логику:** Не дублируй код

## 🚨 КРИТИЧЕСКИЙ УРОК: Проверка цепочки данных (29.08.2025)

### Контекст ошибки: MetroSelector
**Задача:** Создать компонент выбора станций метро по аналогии с компонентом "Районы"  
**Ошибка:** После создания компонента данные НЕ сохранялись при переключении секций  
**Причина:** Пропущены Vue watchers для автосохранения изменений  
**Время потерь:** 1.5 часа на отладку  

### ⚡ ПРАВИЛО: Всегда проверяй ПОЛНУЮ цепочку данных

#### Цепочка данных во Vue + Laravel:
```
1. Компонент (v-model) → 2. Reactive данные → 3. Watcher → 4. Emit → 5. JSON → 6. БД
```

#### Чек-лист для новых компонентов с v-model:
```typescript
✅ 1. v-model связан с reactive данными?
// <ComponentName v-model="data.field" />

✅ 2. Компонент корректно emit'ит изменения?
// emit('update:modelValue', newValue)

✅ 3. ЕСТЬ WATCHER для автосохранения? ← КРИТИЧЕСКИ ВАЖНО!
watch(() => data.field, () => {
  emitData() // или saveData()
}, { deep: true })

✅ 4. Emit отправляет данные в правильном формате?
// JSON.stringify({ field: data.field })

✅ 5. Backend сохраняет в БД?
// $fillable включает поле, миграция выполнена
```

### 🔴 СТОП-СИГНАЛЫ (когда нужна проверка цепочки):
- Создаешь новый компонент с `v-model`
- Копируешь существующий компонент
- Добавляешь новое поле в форму
- Данные "теряются" при навигации
- Компонент работает, но данные не сохраняются

### ✅ КАК ПРАВИЛЬНО:
```typescript
// ВСЕГДА при добавлении нового поля в reactive данные
const formData = reactive({
  existingField: '',
  newField: [] // ← Новое поле
})

// СРАЗУ добавляй watcher для автосохранения
watch(() => formData.newField, () => {
  saveFormData()
}, { deep: true })
```

### ❌ КАК НЕПРАВИЛЬНО:
```typescript
// Добавил поле, но забыл про watcher
const formData = reactive({
  existingField: '',
  newField: [] // ← Работает локально, но не сохраняется!
})
// Нет watcher = нет автосохранения!
```

### 🎯 Применение урока:
1. **При планировании:** Добавлять отдельный этап "Проверка цепочки данных"
2. **При реализации:** Сразу создавать watchers вместе с reactive полями
3. **При тестировании:** Обязательно проверять сохранение через переключение секций
4. **При code review:** Искать поля без watchers

## 📑 ИСПОЛЬЗОВАНИЕ ШАБЛОНОВ

### Доступные шаблоны в .claude/templates/

У нас есть 4 готовых шаблона для типовых задач:

#### 1. **refactor.yaml** - Умный рефакторинг
Используй для:
- Рефакторинга компонентов с сохранением API
- Применения SOLID принципов
- Улучшения читаемости кода
- Оптимизации производительности

**Как использовать:**
```
Задача: рефакторинг MasterCard
Используй шаблон refactor.yaml
ВАЖНО: сохрани обратную совместимость
```

#### 2. **debug.yaml** - Системная отладка
Используй для:
- Поиска причин багов
- Анализа цепочки данных
- Диагностики проблем производительности
- Отладки Vue реактивности

**Как использовать:**
```
Проблема: данные не сохраняются при переключении
Примени шаблон debug.yaml для анализа
```

#### 3. **feature.yaml** - Новая функциональность
Используй для:
- Создания новых features по FSD
- Добавления доменов в DDD
- Реализации полного стека функции
- Соблюдения архитектуры проекта

**Как использовать:**
```
Создать функционал экспорта в Excel
Следуй шаблону feature.yaml
```

#### 4. **fix.yaml** - Исправление багов
Используй для:
- Быстрого исправления ошибок
- Написания регрессионных тестов
- Предотвращения повторения багов
- Документирования решения

**Как использовать:**
```
Баг: ошибка валидации в BookingForm
Используй шаблон fix.yaml с тестами
```

### Правила применения шаблонов

1. **Автоматическое применение**: Claude выберет подходящий шаблон based на ключевых словах:
   - "рефакторинг" → refactor.yaml
   - "отладка", "не работает" → debug.yaml
   - "создать", "новая функция" → feature.yaml
   - "баг", "ошибка", "исправить" → fix.yaml

2. **Явное указание**: Всегда можно указать конкретный шаблон:
   ```
   Используй шаблон [имя_шаблона] для этой задачи
   ```

3. **Комбинирование**: Можно использовать несколько шаблонов:
   ```
   Сначала debug для анализа, затем fix для исправления
   ```

### Примеры эффективного использования

**✅ ХОРОШИЙ промпт с шаблоном:**
```
Задача: рефакторинг MastersFilter
Контекст: features/masters-filter/
Шаблон: refactor.yaml
Критически важно: не сломать фильтрацию по районам
```

**❌ ПЛОХОЙ промпт:**
```
Переделай фильтры
```

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

## 💻 POWERSHELL АЛИАСЫ ДЛЯ WINDOWS

### Настройка PowerShell профиля
Добавьте эти функции в ваш PowerShell профиль для быстрого доступа к командам.

**Открыть профиль для редактирования:**
```powershell
notepad $PROFILE
# Или в VS Code:
code $PROFILE
```

### Laravel алиасы
```powershell
# Artisan команды
function art { php artisan $args }
function tinker { php artisan tinker }
function serve { php artisan serve }
function migrate { php artisan migrate $args }
function fresh { php artisan migrate:fresh --seed }
function seed { php artisan db:seed $args }
function rollback { php artisan migrate:rollback }

# Очистка кеша
function clear-all {
    php artisan cache:clear
    php artisan config:clear
    php artisan route:clear
    php artisan view:clear
    php artisan optimize:clear
    Write-Host "✅ Весь кеш очищен" -ForegroundColor Green
}

# Оптимизация
function optimize {
    php artisan optimize
    php artisan config:cache
    php artisan route:cache
    Write-Host "✅ Приложение оптимизировано" -ForegroundColor Green
}
```

### NPM/Node алиасы
```powershell
# NPM команды
function dev { npm run dev }
function build { npm run build }
function watch { npm run watch }
function hot { npm run hot }
function type-check { npm run type-check }

# Очистка и переустановка
function npm-clean {
    Write-Host "🧹 Удаляю node_modules..." -ForegroundColor Yellow
    Remove-Item -Recurse -Force node_modules -ErrorAction SilentlyContinue
    Remove-Item package-lock.json -ErrorAction SilentlyContinue
    Write-Host "📦 Устанавливаю зависимости..." -ForegroundColor Yellow
    npm install
    Write-Host "✅ Зависимости установлены" -ForegroundColor Green
}

# Обновление зависимостей
function npm-update {
    npm update
    npm audit fix
    Write-Host "✅ Зависимости обновлены" -ForegroundColor Green
}
```

### Git алиасы (для консоли, хотя используете GitHub Desktop)
```powershell
# Статус и информация
function gs { git status }
function gl { git log --oneline -10 }
function gb { git branch }
function gd { git diff }

# Быстрый коммит
function gac {
    param([string]$message)
    git add .
    git commit -m $message
}
```

### Навигация по проекту
```powershell
# Быстрый переход в папки проекта
function spa { Set-Location C:\www.spa.com }
function spa-js { Set-Location C:\www.spa.com\resources\js\src }
function spa-domain { Set-Location C:\www.spa.com\app\Domain }
function spa-logs { Set-Location C:\www.spa.com\storage\logs }

# Открыть в VS Code
function code-spa { 
    Set-Location C:\www.spa.com
    code .
}
```

### Полезные утилиты
```powershell
# Показать последние логи Laravel
function logs {
    param([int]$lines = 50)
    Get-Content storage\logs\laravel.log -Tail $lines
}

# Следить за логами в реальном времени
function logs-watch {
    Get-Content storage\logs\laravel.log -Wait
}

# Поиск в коде
function search {
    param([string]$pattern)
    rg $pattern --type-add 'vue:*.vue' --type vue --type js --type ts --type php
}

# Подсчет строк кода
function count-lines {
    Write-Host "📊 Подсчет строк кода:" -ForegroundColor Cyan
    Write-Host "PHP: " -NoNewline
    (Get-ChildItem -Recurse -Include *.php | Get-Content | Measure-Object -Line).Lines
    Write-Host "Vue: " -NoNewline
    (Get-ChildItem -Recurse -Include *.vue | Get-Content | Measure-Object -Line).Lines
    Write-Host "TypeScript: " -NoNewline
    (Get-ChildItem -Recurse -Include *.ts | Get-Content | Measure-Object -Line).Lines
}

# Тестирование
function test {
    param([string]$filter)
    if ($filter) {
        php artisan test --filter $filter
    } else {
        php artisan test
    }
}

# Полная проверка проекта
function check-all {
    Write-Host "🔍 Проверка TypeScript..." -ForegroundColor Yellow
    npm run type-check
    
    Write-Host "🔍 Проверка PHP..." -ForegroundColor Yellow
    ./vendor/bin/phpstan analyse
    
    Write-Host "🔍 Запуск тестов..." -ForegroundColor Yellow
    php artisan test
    
    Write-Host "✅ Все проверки завершены" -ForegroundColor Green
}
```

### Быстрый старт дня
```powershell
# Запуск всего необходимого для разработки
function start-dev {
    Write-Host "🚀 Запуск окружения разработки..." -ForegroundColor Cyan
    
    # Переход в папку проекта
    Set-Location C:\www.spa.com
    
    # Очистка кеша
    clear-all
    
    # Запуск Laravel в фоне
    Start-Job -ScriptBlock { php artisan serve } | Out-Null
    Write-Host "✅ Laravel сервер запущен на http://localhost:8000" -ForegroundColor Green
    
    # Запуск Vite
    Write-Host "📦 Запуск Vite..." -ForegroundColor Yellow
    npm run dev
}

# Остановка всех процессов разработки
function stop-dev {
    Get-Job | Stop-Job
    Get-Job | Remove-Job
    Write-Host "⏹️ Все процессы остановлены" -ForegroundColor Yellow
}
```

### Применение изменений
После добавления функций в $PROFILE, выполните:
```powershell
# Перезагрузить профиль
. $PROFILE

# Или перезапустите PowerShell
```

### Проверка доступных алиасов
```powershell
# Показать все custom функции
Get-Command -CommandType Function | Where-Object { $_.Source -eq "" }
```


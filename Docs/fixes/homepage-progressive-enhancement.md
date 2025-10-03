# План 3: Прогрессивное Улучшение Главной Страницы (Гибридный Подход)

## Дата: 2025-10-01
## Стратегия: KISS + Постепенное добавление функционала

---

## 🎯 Философия Плана

### Принцип "Прогрессивного Улучшения":
1. ✅ **База работает** (План 1 - выполнен)
2. ✅ **Добавляем фичи постепенно** (из Плана 2)
3. ✅ **БЕЗ костылей** (AdTransformService не нужен!)
4. ✅ **Честная работа с Ads** (не притворяемся "мастерами")

### KISS принцип:
- ❌ НЕ копируем MastersCatalog (слишком сложный)
- ❌ НЕ используем AdTransformService (костыль)
- ❌ НЕ притворяемся что ads = masters
- ✅ Адаптируем компоненты для работы напрямую с Ads

---

## 📊 Сравнение Трёх Планов

| Аспект | План 1 (fix) | План 2 (restore) | **План 3 (hybrid)** |
|--------|--------------|------------------|---------------------|
| **Сложность** | 🟢 Низкая | 🔴 Высокая | 🟡 Средняя |
| **HomeController** | Только ads | ads + transform | Только ads ✅ |
| **Компоненты** | ItemCard | MastersCatalog | ItemCard + AdsCatalog |
| **QuickView** | ❌ Нет | ✅ Есть | ✅ Есть |
| **Рекомендации** | ❌ Нет | ✅ 2 секции | ✅ 1 секция |
| **Фильтры** | ⚠️ Заглушки | ✅ Работают | ✅ Работают |
| **Трансформация** | Нет | AdTransformService | Нет (честно!) |
| **Время** | 0 мин (готово) | 1ч 10м | 40 минут |
| **Строк кода** | 0 (готово) | 2613 | ~350 |

---

## 📋 Текущее Состояние (База из Плана 1)

### ✅ Что УЖЕ работает:

#### Backend: HomeController.php
```php
<?php

namespace App\Application\Http\Controllers;

use App\Domain\Ad\Services\AdService;
use App\Domain\Service\Services\CategoryService;
use App\Application\Http\Resources\Ad\AdResource;
use Illuminate\Http\Request;
use Inertia\Inertia;

class HomeController extends Controller
{
    public function __construct(
        private AdService $adService,
        private CategoryService $categoryService
    ) {}

    public function index(Request $request)
    {
        try {
            $ads = $this->adService->getActiveAdsForHome(12);
        } catch (\Exception $e) {
            $ads = collect([]);
        }

        return Inertia::render('Home', [
            'ads' => AdResource::collection($ads),
            'filters' => [
                'price_min' => $request->get('price_min'),
                'price_max' => $request->get('price_max'),
                'services' => $request->get('services', []),
                'districts' => $request->get('districts', [])
            ],
            'categories' => $this->categoryService->getActiveCategories(),
            'districts' => $this->categoryService->getDistricts(),
            'priceRange' => $this->categoryService->getPriceRange(),
            'currentCity' => $request->get('city', 'Москва')
        ]);
    }
}
```

**Оценка:** ✅ Отлично - простой, честный, без костылей

---

#### Frontend: Home.vue (текущий)
```vue
<template>
  <div>
    <Head :title="`Массаж в ${currentCity || 'городе'} — найти мастера`" />

    <div class="py-6 lg:py-8">
      <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-900">
          Массаж в {{ currentCity || 'городе' }}
        </h1>
        <p class="text-gray-600 mt-2">
          Найдите лучшие предложения массажа в вашем городе
        </p>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
        <!-- Фильтры слева -->
        <div class="lg:col-span-1">
          <FilterPanel @apply="handleFiltersApplied" @reset="handleFiltersReset">
            <FilterCategory title="Категории услуг" icon="🛠️" :active="false">
              <div class="space-y-2">
                <BaseCheckbox
                  v-for="category in categories"
                  :key="category.id"
                  :model-value="false"
                  :label="category.name"
                  @update:modelValue="handleCategoryToggle(String(category.id), $event)"
                />
              </div>
            </FilterCategory>
          </FilterPanel>
        </div>

        <!-- Карта + Карточки справа -->
        <div class="lg:col-span-3">
          <!-- Карта (заглушка) -->
          <div class="mb-6">
            <div class="h-96 bg-gray-200 rounded-lg flex items-center justify-center">
              <div class="text-center">
                <div class="text-gray-500 text-lg mb-2">🗺️ Карта временно недоступна</div>
                <div class="text-gray-400 text-sm">YandexMapNative удален из проекта</div>
              </div>
            </div>
          </div>

          <!-- Карточки объявлений -->
          <div>
            <div class="mb-6">
              <h2 class="text-2xl font-bold text-gray-900">
                Активные объявления
              </h2>
              <p class="text-gray-600 mt-1">
                {{ ads.length }} объявлений найдено
              </p>
            </div>

            <!-- Сетка карточек -->
            <div v-if="ads.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
              <ItemCard
                v-for="ad in ads"
                :key="ad.id"
                :item="ad"
                mode="grid"
                @favorite="toggleFavorite"
              />
            </div>

            <!-- Пустое состояние -->
            <div v-else class="text-center py-12">
              <p class="text-gray-500 text-lg">Объявлений не найдено</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { Head } from '@inertiajs/vue3'
import { ref, computed } from 'vue'

import { ItemCard } from '@/src/entities/ad/ui/ItemCard'
import { FilterPanel, FilterCategory } from '@/src/features/masters-filter'
import { BaseCheckbox } from '@/src/shared/ui/atoms'
import { logger } from '@/src/shared/utils/logger'

import type { Ad } from '@/src/entities/ad/model/types'

interface HomePageProps {
  ads: Ad[]
  currentCity?: string | null
  categories?: Category[]
  districts?: string[]
  priceRange?: any
  filters?: any
}

const props = defineProps<HomePageProps>()
const ads = computed(() => props.ads || [])

const toggleFavorite = async (adId: number) => {
  logger.info('Toggle favorite for ad:', adId)
}

const handleFiltersApplied = (filters: any) => {
  // TODO: Применение фильтров через Inertia
}
</script>
```

**Проблемы:**
- ❌ Нет QuickView (быстрого просмотра)
- ❌ Нет рекомендаций
- ❌ Фильтры не работают (заглушки)
- ❌ Нет сортировки
- ❌ Нет переключения видов (grid/list)

---

## 🎯 Целевое Состояние (После Плана 3)

### Что ДОБАВИМ (постепенно):

#### Этап 1: QuickView (Быстрый просмотр) - 15 минут
- ✅ Копируем `quick-view/` из архива
- ✅ Адаптируем QuickViewModal для работы с Ad (не Master)
- ✅ Добавляем кнопку "Быстрый просмотр" на ItemCard

#### Этап 2: AdsCatalog (Обёртка с функционалом) - 15 минут
- ✅ Создаём НОВЫЙ компонент AdsCatalog (НЕ копируем MastersCatalog!)
- ✅ Добавляем сортировку (по цене, дате, популярности)
- ✅ Добавляем переключение видов (grid/list)
- ✅ Используем ItemCard внутри

#### Этап 3: Рабочие фильтры - 10 минут
- ✅ Подключаем Inertia.get() в handleFiltersApplied
- ✅ Фильтры обновляют URL и перезагружают данные

#### Этап 4: Рекомендации - 10 минут
- ✅ Копируем RecommendedSection из архива
- ✅ Адаптируем для работы с Ad
- ✅ Добавляем 1 секцию "Популярные объявления"

---

## 🛠 Детальный План Выполнения

---

## ЭТАП 1: QuickView - Быстрый Просмотр (15 минут)

### Цель:
Добавить возможность быстро посмотреть детали объявления в модальном окне

### Шаг 1.1: Копирование компонентов (5 минут)

```bash
# Копируем feature quick-view из архива
cp -r "E:/www.spa.com 26.09/resources/js/src/features/quick-view" \
      "C:/www.spa.com/resources/js/src/features/"
```

**Проверка:**
```bash
ls "C:/www.spa.com/resources/js/src/features/quick-view/"
```

**Должны увидеть:**
- ✅ index.ts
- ✅ QuickViewModal.vue
- ✅ useQuickView.ts

---

### Шаг 1.2: Адаптация QuickViewModal для Ad (5 минут)

**Файл:** `resources/js/src/features/quick-view/QuickViewModal.vue`

**Найти props (строка ~10-20):**
```typescript
interface Props {
  isOpen: boolean
  master: Master | null  // ← ПРОБЛЕМА: ожидает Master
  isFavorite?: boolean
}
```

**Заменить на:**
```typescript
interface Props {
  isOpen: boolean
  item: Ad | null  // ← ИСПРАВЛЕНО: принимает Ad
  isFavorite?: boolean
}

const props = defineProps<Props>()
```

**Найти все `master.` в template и заменить на `item.`:**

**Было:**
```vue
<h2>{{ master.name }}</h2>
<img :src="master.photo" />
<p>{{ master.price_from }} ₽</p>
```

**Стало:**
```vue
<h2>{{ item.title }}</h2>
<img :src="item.photos?.[0] || '/images/no-photo.svg'" />
<p>от {{ item.prices?.min || 0 }} ₽</p>
```

**Найти кнопку "Забронировать" и заменить на "Связаться":**

**Было:**
```vue
<button @click="$emit('booking', master)">
  Забронировать
</button>
```

**Стало:**
```vue
<button @click="handleContact">
  Связаться с мастером
</button>

<script setup lang="ts">
const handleContact = () => {
  if (props.item) {
    window.location.href = `/ads/${props.item.id}?contact=true`
  }
}
</script>
```

**Итого изменений:** ~10-15 строк

---

### Шаг 1.3: Обновление useQuickView.ts (2 минуты)

**Файл:** `resources/js/src/features/quick-view/useQuickView.ts`

**Найти:**
```typescript
import type { Master } from '@/stores/favorites'

const currentMaster = ref<Master | null>(null)

const openQuickView = (master: Master) => {
  currentMaster.value = master
  isOpen.value = true
}
```

**Заменить на:**
```typescript
import type { Ad } from '@/src/entities/ad/model/types'

const currentItem = ref<Ad | null>(null)

const openQuickView = (ad: Ad) => {
  currentItem.value = ad
  isOpen.value = true
}
```

**Итого изменений:** 3 строки

---

### Шаг 1.4: Добавление QuickView в Home.vue (3 минуты)

**Файл:** `resources/js/Pages/Home.vue`

**Добавить импорты (после строки 121):**
```typescript
import { QuickViewModal, useQuickView } from '@/src/features/quick-view'
```

**Добавить в script setup (после строки 154):**
```typescript
// Quick View
const quickView = useQuickView()

const openQuickView = (ad: Ad) => {
  quickView.openQuickView(ad)
}
```

**Добавить модальное окно в template (перед закрывающим </div>, строка ~115):**
```vue
<!-- Quick View Modal -->
<QuickViewModal
  :is-open="quickView.isOpen.value"
  :item="quickView.currentItem.value"
  :is-favorite="false"
  @close="quickView.closeQuickView"
  @toggle-favorite="toggleFavorite"
/>
```

**Обновить ItemCard для поддержки Quick View:**

**Найти (строка ~92):**
```vue
<ItemCard
  v-for="ad in ads"
  :key="ad.id"
  :item="ad"
  mode="grid"
  @favorite="toggleFavorite"
/>
```

**Заменить на:**
```vue
<ItemCard
  v-for="ad in ads"
  :key="ad.id"
  :item="ad"
  mode="grid"
  @favorite="toggleFavorite"
  @quick-view="openQuickView"
/>
```

**ВАЖНО:** Проверить что ItemCard.vue поддерживает событие `@quick-view`

**Если НЕТ - добавить в ItemCard.vue:**
```vue
<template>
  <div class="item-card">
    <!-- контент карточки -->

    <!-- Кнопка быстрого просмотра -->
    <button
      @click.stop="$emit('quick-view', item)"
      class="quick-view-btn"
    >
      👁️ Быстрый просмотр
    </button>
  </div>
</template>

<script setup lang="ts">
defineEmits<{
  'favorite': [number]
  'quick-view': [Ad]  // ← ДОБАВИТЬ
}>()
</script>
```

**Итого изменений в Home.vue:** ~10 строк

---

### ✅ Результат Этапа 1:

**Что работает:**
- ✅ Кнопка "Быстрый просмотр" на каждой карточке
- ✅ Клик → открывается модальное окно с деталями
- ✅ В модалке: фото, описание, цены, кнопка "Связаться"
- ✅ Кнопка "Связаться" → `/ads/{id}?contact=true`

**Время:** 15 минут
**Изменено строк:** ~28

---

## ЭТАП 2: AdsCatalog - Обёртка с Функционалом (15 минут)

### Цель:
Создать лёгкий каталог с сортировкой и переключением видов (БЕЗ копирования тяжёлого MastersCatalog)

### Шаг 2.1: Создание AdsCatalog.vue (10 минут)

**Создать файл:** `resources/js/src/widgets/ads-catalog/AdsCatalog.vue`

```vue
<!-- Лёгкий каталог объявлений с сортировкой и переключением видов -->
<template>
  <div class="ads-catalog">
    <!-- Панель управления -->
    <div class="mb-6 flex items-center justify-between">
      <!-- Количество результатов -->
      <div class="text-gray-600">
        Найдено объявлений: <span class="font-semibold">{{ ads.length }}</span>
      </div>

      <!-- Сортировка -->
      <div class="flex items-center gap-4">
        <select
          v-model="currentSort"
          @change="handleSortChange"
          class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
        >
          <option value="newest">Сначала новые</option>
          <option value="price_asc">Цена: по возрастанию</option>
          <option value="price_desc">Цена: по убыванию</option>
          <option value="popular">Популярные</option>
        </select>

        <!-- Переключатель видов -->
        <div class="flex gap-2 border border-gray-300 rounded-lg p-1">
          <button
            @click="currentView = 'grid'"
            :class="[
              'px-3 py-1 rounded',
              currentView === 'grid'
                ? 'bg-blue-600 text-white'
                : 'text-gray-600 hover:bg-gray-100'
            ]"
          >
            ⊞ Сетка
          </button>
          <button
            @click="currentView = 'list'"
            :class="[
              'px-3 py-1 rounded',
              currentView === 'list'
                ? 'bg-blue-600 text-white'
                : 'text-gray-600 hover:bg-gray-100'
            ]"
          >
            ☰ Список
          </button>
        </div>
      </div>
    </div>

    <!-- Сетка карточек -->
    <div
      v-if="sortedAds.length > 0"
      :class="[
        currentView === 'grid'
          ? 'grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6'
          : 'space-y-4'
      ]"
    >
      <slot name="item" v-for="ad in sortedAds" :key="ad.id" :ad="ad">
        <!-- Fallback если нет slot -->
        <ItemCard
          :item="ad"
          :mode="currentView"
          @favorite="$emit('favorite', ad.id)"
          @quick-view="$emit('quick-view', ad)"
        />
      </slot>
    </div>

    <!-- Пустое состояние -->
    <div v-else class="text-center py-12">
      <p class="text-gray-500 text-lg">Объявлений не найдено</p>
      <p class="text-gray-400 text-sm mt-2">Попробуйте изменить фильтры</p>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { ItemCard } from '@/src/entities/ad/ui/ItemCard'
import type { Ad } from '@/src/entities/ad/model/types'

interface Props {
  ads: Ad[]
}

const props = defineProps<Props>()

const emit = defineEmits<{
  'favorite': [number]
  'quick-view': [Ad]
  'sort-change': [string]
}>()

// Local state
const currentSort = ref<string>('newest')
const currentView = ref<'grid' | 'list'>('grid')

// Computed
const sortedAds = computed(() => {
  const items = [...props.ads]

  switch (currentSort.value) {
    case 'price_asc':
      return items.sort((a, b) => (a.prices?.min || 0) - (b.prices?.min || 0))

    case 'price_desc':
      return items.sort((a, b) => (b.prices?.min || 0) - (a.prices?.min || 0))

    case 'popular':
      // TODO: сортировка по просмотрам/рейтингу
      return items

    case 'newest':
    default:
      return items.sort((a, b) =>
        new Date(b.created_at || 0).getTime() - new Date(a.created_at || 0).getTime()
      )
  }
})

// Methods
const handleSortChange = () => {
  emit('sort-change', currentSort.value)

  // Сохраняем в localStorage
  localStorage.setItem('adsSortPreference', currentSort.value)
}

// Restore preferences from localStorage
const savedSort = localStorage.getItem('adsSortPreference')
if (savedSort) {
  currentSort.value = savedSort
}

const savedView = localStorage.getItem('adsViewPreference')
if (savedView && (savedView === 'grid' || savedView === 'list')) {
  currentView.value = savedView as 'grid' | 'list'
}
</script>
```

**Создать index.ts:**
```typescript
// resources/js/src/widgets/ads-catalog/index.ts
export { default as AdsCatalog } from './AdsCatalog.vue'
```

**Итого нового кода:** ~130 строк

---

### Шаг 2.2: Интеграция AdsCatalog в Home.vue (5 минут)

**Файл:** `resources/js/Pages/Home.vue`

**Добавить импорт (строка ~123):**
```typescript
import { AdsCatalog } from '@/src/widgets/ads-catalog'
```

**Заменить секцию карточек (строки ~63-110):**

**Было:**
```vue
<div>
  <div class="mb-6">
    <h2 class="text-2xl font-bold text-gray-900">
      Активные объявления
    </h2>
    <p class="text-gray-600 mt-1">
      {{ ads.length }} объявлений найдено
    </p>
  </div>

  <div v-if="ads.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    <ItemCard
      v-for="ad in ads"
      :key="ad.id"
      :item="ad"
      mode="grid"
      @favorite="toggleFavorite"
      @quick-view="openQuickView"
    />
  </div>

  <div v-else class="text-center py-12">
    <p class="text-gray-500 text-lg">Объявлений не найдено</p>
  </div>
</div>
```

**Стало:**
```vue
<AdsCatalog
  :ads="ads"
  @favorite="toggleFavorite"
  @quick-view="openQuickView"
  @sort-change="handleSortChange"
>
  <!-- Кастомная карточка через slot (опционально) -->
  <template #item="{ ad }">
    <ItemCard
      :item="ad"
      :mode="'grid'"
      @favorite="toggleFavorite"
      @quick-view="openQuickView"
    />
  </template>
</AdsCatalog>
```

**Добавить обработчик сортировки в script (после строки 189):**
```typescript
const handleSortChange = (sortType: string) => {
  logger.info('Сортировка изменена на:', sortType)
  // AdsCatalog уже сортирует локально
  // Здесь можно добавить аналитику или серверную сортировку
}
```

**Итого изменений:** ~10 строк

---

### ✅ Результат Этапа 2:

**Что работает:**
- ✅ Выпадающий список сортировки (новые, цена ↑, цена ↓, популярные)
- ✅ Переключатель видов (⊞ Сетка / ☰ Список)
- ✅ Счётчик объявлений
- ✅ Сохранение предпочтений в localStorage
- ✅ Адаптивная сетка (1/2/3 колонки)

**Время:** 15 минут
**Новых строк:** ~140
**Изменённых строк:** ~10

---

## ЭТАП 3: Рабочие Фильтры (10 минут)

### Цель:
Подключить Inertia для обновления данных при изменении фильтров

### Шаг 3.1: Обновление handleFiltersApplied (5 минут)

**Файл:** `resources/js/Pages/Home.vue`

**Добавить импорт Inertia router (строка ~120):**
```typescript
import { router } from '@inertiajs/vue3'
```

**Найти handleFiltersApplied (строка ~189):**

**Было:**
```typescript
const handleFiltersApplied = (filters: any) => {
  // TODO: Применение фильтров через Inertia
}
```

**Стало:**
```typescript
const handleFiltersApplied = (filters: any) => {
  // Apply filters via Inertia
  router.get(route('home'), filters, {
    preserveState: true,
    preserveScroll: true,
    only: ['ads'], // Only reload ads data
    onStart: () => {
      isLoading.value = true
    },
    onFinish: () => {
      isLoading.value = false
    }
  })
}
```

**Добавить isLoading (строка ~168):**
```typescript
const isLoading = ref(false)
```

**Итого изменений:** ~12 строк

---

### Шаг 3.2: Обновление handleFiltersReset (2 минуты)

**Найти handleFiltersReset (строка ~243):**

**Было:**
```typescript
const handleFiltersReset = () => {
  // TODO: Логика сброса фильтров
}
```

**Стало:**
```typescript
const handleFiltersReset = () => {
  // Reset filters - reload page without query params
  router.get(route('home'), {}, {
    preserveState: false,
    preserveScroll: false
  })
}
```

**Итого изменений:** 3 строки

---

### Шаг 3.3: Обновление handleCategoryToggle (3 минуты)

**Найти handleCategoryToggle (строка ~238):**

**Было:**
```typescript
const handleCategoryToggle = (categoryId: string, checked: boolean) => {
  // Логика переключения категории
}
```

**Стало:**
```typescript
const selectedCategories = ref<string[]>([])

const handleCategoryToggle = (categoryId: string, checked: boolean) => {
  if (checked) {
    selectedCategories.value.push(categoryId)
  } else {
    selectedCategories.value = selectedCategories.value.filter(id => id !== categoryId)
  }

  // Apply filter immediately
  handleFiltersApplied({
    categories: selectedCategories.value
  })
}
```

**Итого изменений:** ~12 строк

---

### ✅ Результат Этапа 3:

**Что работает:**
- ✅ Фильтры по категориям → перезагрузка данных
- ✅ Кнопка "Сбросить фильтры" → очистка
- ✅ preserveScroll - страница не прыгает при фильтрации
- ✅ Индикатор загрузки

**Время:** 10 минут
**Изменённых строк:** ~27

---

## ЭТАП 4: Секция Рекомендаций (10 минут)

### Цель:
Добавить 1 секцию "Популярные объявления" внизу страницы

### Шаг 4.1: Копирование RecommendedSection (3 минуты)

```bash
# Копируем виджет из архива
cp -r "E:/www.spa.com 26.09/resources/js/src/widgets/recommended-section" \
      "C:/www.spa.com/resources/js/src/widgets/"
```

---

### Шаг 4.2: Адаптация RecommendedSection.vue (5 минут)

**Файл:** `resources/js/src/widgets/recommended-section/RecommendedSection.vue`

**Найти props:**
```typescript
interface Props {
  masters: Master[]  // ← ПРОБЛЕМА
  title: string
  subtitle?: string
}
```

**Заменить на:**
```typescript
interface Props {
  items: Ad[]  // ← ИСПРАВЛЕНО
  title: string
  subtitle?: string
}
```

**Найти все `master.` в template и заменить на `item.`**

**Найти v-for:**
```vue
<div v-for="master in masters" :key="master.id">
  <MasterCard :master="master" />
</div>
```

**Заменить на:**
```vue
<div v-for="item in items" :key="item.id">
  <ItemCard :item="item" mode="grid" />
</div>
```

**Итого изменений:** ~10 строк

---

### Шаг 4.3: Добавление в Home.vue (2 минуты)

**Добавить импорт:**
```typescript
import { RecommendedSection } from '@/src/widgets/recommended-section'
```

**Добавить секцию перед QuickViewModal (строка ~115):**
```vue
<!-- Популярные объявления -->
<RecommendedSection
  v-if="ads.length > 0"
  :items="ads.slice(0, 8)"
  title="Популярные объявления"
  subtitle="Выбор наших пользователей"
  @favorite="toggleFavorite"
  @quick-view="openQuickView"
/>
```

**Итого изменений:** ~10 строк

---

### ✅ Результат Этапа 4:

**Что работает:**
- ✅ Секция "Популярные объявления" внизу страницы
- ✅ Горизонтальная карусель с 8 объявлениями
- ✅ Кнопки навигации (← →)
- ✅ Клик на карточку → Quick View или переход на /ads/{id}

**Время:** 10 минут
**Изменённых строк:** ~20

---

## 📊 Итоговая Статистика Плана 3

### Время выполнения:
- Этап 1 (QuickView): 15 минут
- Этап 2 (AdsCatalog): 15 минут
- Этап 3 (Фильтры): 10 минут
- Этап 4 (Рекомендации): 10 минут

**ИТОГО: 50 минут** (вместо 1ч 10м в Плане 2)

---

### Строки кода:

| Тип | Количество |
|-----|------------|
| Новый код | ~140 (AdsCatalog) |
| Адаптированный код | ~50 (QuickView + RecommendedSection) |
| Изменённый код | ~79 (Home.vue) |
| **ВСЕГО** | **~269 строк** |

**Сравнение:**
- План 1: 0 строк (уже выполнен)
- План 2: 2613 строк
- **План 3: 269 строк** (в 10 раз меньше!)

---

### Переиспользование кода:

| Компонент | Действие | Изменений |
|-----------|----------|-----------|
| QuickViewModal | Адаптирован | ~15 строк |
| RecommendedSection | Адаптирован | ~10 строк |
| AdsCatalog | **Создан с нуля** | 130 строк |
| Home.vue | Обновлён | ~79 строк |
| ItemCard | Уже есть | 0 строк |

---

## ✅ Финальный Результат Плана 3

### Backend (БЕЗ изменений):
```php
// HomeController.php - остаётся как в Плане 1
return Inertia::render('Home', [
    'ads' => AdResource::collection($ads),
    // ...
]);
```

**Преимущество:** Честная работа с Ads, без костылей!

---

### Frontend (После всех этапов):

```vue
<template>
  <div>
    <Head :title="`Массаж в ${currentCity || 'городе'} — найти мастера`" />

    <div class="py-6 lg:py-8">
      <!-- Заголовок -->
      <div class="mb-6">
        <h1>Массаж в {{ currentCity || 'городе' }}</h1>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
        <!-- Фильтры слева -->
        <div class="lg:col-span-1">
          <FilterPanel
            @apply="handleFiltersApplied"
            @reset="handleFiltersReset"
          >
            <FilterCategory title="Категории услуг">
              <BaseCheckbox
                v-for="category in categories"
                :key="category.id"
                :model-value="selectedCategories.includes(String(category.id))"
                :label="category.name"
                @update:modelValue="handleCategoryToggle(String(category.id), $event)"
              />
            </FilterCategory>
          </FilterPanel>
        </div>

        <!-- Каталог справа -->
        <div class="lg:col-span-3">
          <!-- Карта (заглушка) -->
          <div class="mb-6">
            <div class="h-96 bg-gray-200 rounded-lg">
              🗺️ Карта временно недоступна
            </div>
          </div>

          <!-- AdsCatalog с сортировкой и видами -->
          <AdsCatalog
            :ads="ads"
            @favorite="toggleFavorite"
            @quick-view="openQuickView"
            @sort-change="handleSortChange"
          >
            <template #item="{ ad }">
              <ItemCard
                :item="ad"
                @favorite="toggleFavorite"
                @quick-view="openQuickView"
              />
            </template>
          </AdsCatalog>
        </div>
      </div>

      <!-- Популярные объявления -->
      <RecommendedSection
        v-if="ads.length > 0"
        :items="ads.slice(0, 8)"
        title="Популярные объявления"
        subtitle="Выбор наших пользователей"
        @favorite="toggleFavorite"
        @quick-view="openQuickView"
      />

      <!-- Quick View Modal -->
      <QuickViewModal
        :is-open="quickView.isOpen.value"
        :item="quickView.currentItem.value"
        @close="quickView.closeQuickView"
        @toggle-favorite="toggleFavorite"
      />
    </div>
  </div>
</template>

<script setup lang="ts">
import { router } from '@inertiajs/vue3'
import { ref, computed } from 'vue'

import { AdsCatalog } from '@/src/widgets/ads-catalog'
import { RecommendedSection } from '@/src/widgets/recommended-section'
import { QuickViewModal, useQuickView } from '@/src/features/quick-view'
import { ItemCard } from '@/src/entities/ad/ui/ItemCard'
import { FilterPanel, FilterCategory } from '@/src/features/masters-filter'
import { BaseCheckbox } from '@/src/shared/ui/atoms'

const quickView = useQuickView()
const selectedCategories = ref<string[]>([])
const isLoading = ref(false)

const openQuickView = (ad: Ad) => {
  quickView.openQuickView(ad)
}

const handleFiltersApplied = (filters: any) => {
  router.get(route('home'), filters, {
    preserveState: true,
    preserveScroll: true,
    only: ['ads']
  })
}

const handleSortChange = (sortType: string) => {
  logger.info('Sort changed:', sortType)
}
</script>
```

---

### Что РАБОТАЕТ после Плана 3:

#### Базовый функционал (из Плана 1):
- ✅ Простая сетка карточек ItemCard
- ✅ Базовые фильтры слева
- ✅ Клик на карточку → `/ads/{id}` (без 404!)

#### Новый функционал (добавлен в Плане 3):
- ✅ **QuickView** - быстрый просмотр в модалке
- ✅ **AdsCatalog** - сортировка (новые, цена ↑↓, популярные)
- ✅ **Переключение видов** - сетка / список
- ✅ **Рабочие фильтры** - через Inertia.js
- ✅ **Рекомендации** - секция "Популярные объявления"
- ✅ **Счётчик** - количество найденных объявлений
- ✅ **localStorage** - сохранение предпочтений

#### Чего НЕТ (и не надо):
- ❌ AdTransformService - костыль не нужен!
- ❌ MastersCatalog - слишком тяжёлый
- ❌ Виртуальный скроллинг - для 12 объявлений не нужен
- ❌ Панель на карте - карта всё равно заглушка

---

## 📋 Пошаговый Чек-лист Выполнения

### Подготовка:
- [ ] Убедиться что План 1 выполнен (база работает)
- [ ] Создать резервную копию Home.vue

### Этап 1: QuickView
- [ ] Скопировать `quick-view/` из архива
- [ ] Адаптировать QuickViewModal.vue (Master → Ad)
- [ ] Обновить useQuickView.ts (Master → Ad)
- [ ] Добавить QuickView в Home.vue
- [ ] Проверить: клик "Быстрый просмотр" → модалка

### Этап 2: AdsCatalog
- [ ] Создать `ads-catalog/AdsCatalog.vue`
- [ ] Создать `ads-catalog/index.ts`
- [ ] Интегрировать в Home.vue
- [ ] Проверить: сортировка работает
- [ ] Проверить: переключение видов работает

### Этап 3: Фильтры
- [ ] Обновить handleFiltersApplied (Inertia router)
- [ ] Обновить handleFiltersReset
- [ ] Обновить handleCategoryToggle
- [ ] Проверить: фильтр категорий → перезагрузка данных

### Этап 4: Рекомендации
- [ ] Скопировать `recommended-section/` из архива
- [ ] Адаптировать RecommendedSection.vue (Master → Ad)
- [ ] Добавить секцию в Home.vue
- [ ] Проверить: секция отображается внизу

### Финальная проверка:
- [ ] `npm run build` - успешно
- [ ] Открыть http://localhost:8000
- [ ] Проверить все фичи (список выше)

---

## 🎯 Преимущества Плана 3 над Планом 2

| Критерий | План 2 (restore) | План 3 (hybrid) | Выигрыш |
|----------|------------------|------------------|---------|
| **Время** | 1ч 10м | 50 мин | -29% |
| **Строк кода** | 2613 | 269 | -90% |
| **Сложность** | Высокая | Средняя | ✅ |
| **Костыли** | AdTransformService | Нет | ✅ |
| **Честность** | Ads → "Masters" | Ads = Ads | ✅ |
| **Поддержка** | Сложная | Простая | ✅ |
| **Функционал** | 100% | 80% | ⚠️ -20% |

**Вывод:** План 3 даёт 80% функционала за 20% усилий (правило Парето!)

---

## 🔧 Troubleshooting

### Проблема 1: "Cannot find module '@/src/widgets/ads-catalog'"

**Решение:**
```bash
# Проверить что создали файл
ls "C:/www.spa.com/resources/js/src/widgets/ads-catalog/AdsCatalog.vue"

# Проверить что создали index.ts
ls "C:/www.spa.com/resources/js/src/widgets/ads-catalog/index.ts"

# Пересобрать
npm run build
```

---

### Проблема 2: TypeScript ошибка в QuickViewModal

**Решение:**
```typescript
// Проверить что Ad экспортируется
// resources/js/src/entities/ad/model/types.ts

export interface Ad {
  id: number
  title: string
  photos: string[] | null
  prices: {
    min: number
    max: number
  } | null
  // ... остальные поля
}
```

---

### Проблема 3: Фильтры не обновляют данные

**Решение:**
```bash
# Проверить что роут 'home' существует
php artisan route:list | grep home

# Должно быть:
# GET /     home     App\Application\Http\Controllers\HomeController@index

# Если нет - добавить в routes/web.php:
Route::get('/', [HomeController::class, 'index'])->name('home');
```

---

## 📚 Связанные Документы

- `homepage-ads-display-fix.md` - План 1 (база)
- `restore-homepage-functionality.md` - План 2 (полное восстановление)
- `CLAUDE.md` - принципы YAGNI, KISS, DRY
- `QUICK_REFERENCE.md` - быстрый справочник

---

## 🎓 Выводы

### Что мы узнали:

1. **Правило Парето работает**: 80% функционала за 20% усилий
2. **KISS > "правильная архитектура"**: Проще = лучше
3. **Избегать костылей**: AdTransformService не нужен
4. **Честность в коде**: Ads = Ads, а не "fake Masters"
5. **Постепенное улучшение**: Добавляем фичи по одной

### Принципы План 3:

- ✅ **Работающая база** (План 1)
- ✅ **Постепенное добавление** фич
- ✅ **Минимум изменений** (269 строк vs 2613)
- ✅ **Без костылей** (честная работа с Ads)
- ✅ **Легко поддерживать** (простой код)

---

**Автор:** Claude Code
**Дата:** 2025-10-01
**Статус:** ✅ Готов к выполнению
**Время:** 50 минут
**Сложность:** 🟡 Средняя (4 этапа, постепенно)

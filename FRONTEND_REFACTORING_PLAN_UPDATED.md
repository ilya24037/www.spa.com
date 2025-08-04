# 🎯 План рефакторинга Frontend (с учетом существующих страниц)

## 📊 Анализ существующих страниц

### 1. **Главная страница** (`Home.vue`)
**Используемые компоненты:**
- `Breadcrumbs` - хлебные крошки
- `SidebarWrapper` - обертка для боковой панели с фильтрами
- `ContentCard` - карточка контента
- `Filters` - панель фильтров
- `Cards` - список карточек мастеров
- `UniversalMap` - карта с метками

**Структура страницы:**
```
┌─────────────────────────────────────┐
│ Breadcrumbs (хлебные крошки)        │
├─────────────────────────────────────┤
│ Заголовок + счетчик                 │
├──────────┬──────────────────────────┤
│ Sidebar  │ Карточки мастеров        │
│ Filters  │ + Карта                  │
└──────────┴──────────────────────────┘
```

### 2. **Страница мастера** (`Masters/Show.vue`)
**Используемые компоненты:**
- `BookingModal` - модальное окно записи
- Галерея фото (встроенная)
- Информация о мастере (встроенная)
- Услуги и цены (встроенная)
- Отзывы (встроенная)

**Структура страницы:**
```
┌─────────────────────────┬─────────────┐
│ Фото галерея           │ Контактная  │
│ + Основная информация  │ карточка    │
├─────────────────────────┤ + Кнопка    │
│ Услуги и цены          │ записи      │
├─────────────────────────┤             │
│ Отзывы                 │             │
└─────────────────────────┴─────────────┘
```

### 3. **Личный кабинет** (`Dashboard.vue`)
**Используемые компоненты:**
- `ProfileSidebar` - боковая панель профиля
- `ItemCard` - карточка объявления
- `Toast` - уведомления

**Структура страницы:**
```
┌──────────┬──────────────────────────┐
│ Profile  │ Вкладки                  │
│ Sidebar  │ ├─ Ждут действий        │
│          │ ├─ Активные             │
│          │ └─ Завершенные          │
│          ├──────────────────────────┤
│          │ Список объявлений        │
└──────────┴──────────────────────────┘
```

## 🏗️ Обновленный план миграции

### 📦 Этап 1: Миграция Shared компонентов (10 часов)

#### 1.1 Layout компоненты → shared/layouts

```bash
# Создать структуру
mkdir -p resources/js/src/shared/layouts/{MainLayout,ProfileLayout,components}

# Перенести layout компоненты
mv resources/js/Components/Layout/SidebarWrapper.vue resources/js/src/shared/layouts/components/SidebarWrapper.vue
mv resources/js/Components/Layout/ContentCard.vue resources/js/src/shared/layouts/components/ContentCard.vue
mv resources/js/Components/Layout/ProfileSidebar.vue resources/js/src/shared/layouts/ProfileLayout/ProfileSidebar.vue
mv resources/js/Components/Layout/PageHeader.vue resources/js/src/shared/layouts/components/PageHeader.vue
mv resources/js/Components/Layout/PageSection.vue resources/js/src/shared/layouts/components/PageSection.vue
```

#### 1.2 Common компоненты → shared/ui/molecules

```bash
# Перенести общие компоненты
mkdir -p resources/js/src/shared/ui/molecules/{Breadcrumbs,BackButton}

mv resources/js/Components/Common/Breadcrumbs.vue resources/js/src/shared/ui/molecules/Breadcrumbs/Breadcrumbs.vue
mv resources/js/Components/Layout/BackButton.vue resources/js/src/shared/ui/molecules/BackButton/BackButton.vue
```

#### 1.3 UI компоненты → shared/ui/atoms

```bash
# Базовые компоненты уже есть
mv resources/js/Components/UI/Toast.vue resources/js/src/shared/ui/molecules/Toast/Toast.vue
mv resources/js/Components/UI/Modal.vue resources/js/src/shared/ui/organisms/Modal/Modal.vue
mv resources/js/Components/UI/ConfirmModal.vue resources/js/src/shared/ui/organisms/ConfirmModal/ConfirmModal.vue
```

### 🏢 Этап 2: Entities с учетом реальных компонентов (10 часов)

#### 2.1 Entity Master

```bash
# Структура для мастера
mkdir -p resources/js/src/entities/master/ui/{MasterCard,MasterGallery,MasterInfo,MasterServices,MasterReviews,MasterContact}

# Перенести компоненты мастера
mv resources/js/Components/Cards/Cards.vue resources/js/src/entities/master/ui/MasterCard/MasterCardList.vue
mv resources/js/Components/Masters/MasterGallery resources/js/src/entities/master/ui/MasterGallery/
mv resources/js/Components/Masters/MasterInfo resources/js/src/entities/master/ui/MasterInfo/
mv resources/js/Components/Masters/MasterServices resources/js/src/entities/master/ui/MasterServices/
mv resources/js/Components/Masters/MasterReviews resources/js/src/entities/master/ui/MasterReviews/
mv resources/js/Components/Masters/MasterContactCard resources/js/src/entities/master/ui/MasterContact/
```

#### 2.2 Entity Ad (объявления)

```bash
# Структура для объявлений
mkdir -p resources/js/src/entities/ad/ui/{AdCard,AdForm,AdStatus}

# Перенести компоненты объявлений
mv resources/js/Components/Profile/ItemCard.vue resources/js/src/entities/ad/ui/AdCard/AdCard.vue
# Разделить большую AdForm на части
```

#### 2.3 Entity Booking

```bash
mkdir -p resources/js/src/entities/booking/ui/{BookingModal,BookingWidget,BookingCalendar}

mv resources/js/Components/Booking/BookingModal.vue resources/js/src/entities/booking/ui/BookingModal/BookingModal.vue
mv resources/js/Components/Masters/BookingWidget resources/js/src/entities/booking/ui/BookingWidget/
```

### 🎯 Этап 3: Features для конкретных страниц (12 часов)

#### 3.1 Feature Masters Filter (для главной страницы)

```bash
mkdir -p resources/js/src/features/masters-filter/ui/{FilterPanel,FilterCategory,FilterPrice,FilterLocation,FilterRating}

# Перенести фильтры
mv resources/js/Components/Filters/Filters.vue resources/js/src/features/masters-filter/ui/FilterPanel/FilterPanel.vue
```

**Store для фильтров:**
```typescript
// resources/js/src/features/masters-filter/model/filter.store.ts
export const useFilterStore = defineStore('masters-filter', {
  state: () => ({
    searchTerm: '',
    category: null,
    priceMin: 0,
    priceMax: 100000,
    district: null,
    metro: null,
    rating: null,
    serviceType: null
  })
})
```

#### 3.2 Feature Map (для главной страницы)

```bash
mkdir -p resources/js/src/features/map/ui/{UniversalMap,MapMarker,MapControls}

mv resources/js/Components/Map/UniversalMap.vue resources/js/src/features/map/ui/UniversalMap/UniversalMap.vue
```

#### 3.3 Feature Gallery (для страницы мастера)

```bash
mkdir -p resources/js/src/features/gallery/ui/{PhotoGallery,PhotoViewer,PhotoThumbnails}

mv resources/js/Components/Gallery/PhotoGallery.vue resources/js/src/features/gallery/ui/PhotoGallery/PhotoGallery.vue
```

#### 3.4 Feature Profile Navigation (для личного кабинета)

```bash
mkdir -p resources/js/src/features/profile-navigation/ui/{ProfileTabs,ProfileStats}

mv resources/js/Components/Profile/ProfileNavigation.vue resources/js/src/features/profile-navigation/ui/ProfileTabs/ProfileTabs.vue
```

### 🧩 Этап 4: Widgets - композиция для страниц (8 часов)

#### 4.1 Widget для главной страницы

```typescript
// resources/js/src/widgets/masters-catalog/MastersCatalog.vue
<template>
  <div class="flex gap-6">
    <!-- Боковая панель с фильтрами -->
    <SidebarWrapper v-model="showFilters">
      <template #header>
        <h2 class="font-semibold text-lg">Фильтры</h2>
      </template>
      <MastersFilter @update="handleFilterUpdate" />
    </SidebarWrapper>

    <!-- Основной контент -->
    <div class="flex-1">
      <ContentCard>
        <MasterCardList :masters="filteredMasters" />
        <UniversalMap :markers="mapMarkers" />
      </ContentCard>
    </div>
  </div>
</template>

<script setup>
import { SidebarWrapper, ContentCard } from '@/shared/layouts/components'
import { MastersFilter } from '@/features/masters-filter'
import { MasterCardList } from '@/entities/master'
import { UniversalMap } from '@/features/map'
</script>
```

#### 4.2 Widget для страницы мастера

```typescript
// resources/js/src/widgets/master-profile/MasterProfile.vue
<template>
  <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Левая колонка -->
    <div class="lg:col-span-2 space-y-6">
      <MasterGallery :photos="master.photos" />
      <MasterInfo :master="master" />
      <MasterServices :services="master.services" />
      <MasterReviews :reviews="master.reviews" />
    </div>

    <!-- Правая колонка -->
    <div>
      <MasterContact :master="master" />
      <BookingWidget :master="master" />
    </div>
  </div>
</template>
```

#### 4.3 Widget для личного кабинета

```typescript
// resources/js/src/widgets/profile-dashboard/ProfileDashboard.vue
<template>
  <div class="flex gap-6">
    <ProfileSidebar :counts="counts" :stats="stats" />
    
    <div class="flex-1">
      <ProfileTabs :active-tab="activeTab" />
      <AdList :ads="userAds" :status="activeTab" />
    </div>
  </div>
</template>
```

### 📄 Этап 5: Обновление страниц (6 часов)

#### 5.1 Обновить Home.vue

```typescript
// resources/js/Pages/Home.vue
<template>
  <MainLayout>
    <Breadcrumbs :items="breadcrumbs" />
    
    <PageHeader 
      :title="`Массажисты в ${currentCity}`"
      :subtitle="`Найдено: ${filteredCount} мастеров`"
    />
    
    <MastersCatalog 
      :masters="masters"
      :filters="filters"
      :city="currentCity"
    />
  </MainLayout>
</template>

<script setup>
import { MainLayout } from '@/shared/layouts'
import { Breadcrumbs, PageHeader } from '@/shared/ui'
import { MastersCatalog } from '@/widgets/masters-catalog'
</script>
```

#### 5.2 Обновить Masters/Show.vue

```typescript
// resources/js/Pages/Masters/Show.vue
<template>
  <MainLayout>
    <MasterProfile :master="master" />
  </MainLayout>
</template>

<script setup>
import { MainLayout } from '@/shared/layouts'
import { MasterProfile } from '@/widgets/master-profile'
</script>
```

#### 5.3 Обновить Dashboard.vue

```typescript
// resources/js/Pages/Dashboard.vue
<template>
  <ProfileLayout>
    <ProfileDashboard 
      :ads="ads"
      :counts="counts"
      :stats="userStats"
    />
  </ProfileLayout>
</template>

<script setup>
import { ProfileLayout } from '@/shared/layouts'
import { ProfileDashboard } from '@/widgets/profile-dashboard'
</script>
```

## 📊 Итоговая структура компонентов по страницам

### Главная страница
```
src/
├── shared/
│   ├── layouts/MainLayout/
│   └── ui/molecules/Breadcrumbs/
├── features/
│   ├── masters-filter/
│   └── map/
├── entities/
│   └── master/ui/MasterCard/
└── widgets/
    └── masters-catalog/
```

### Страница мастера
```
src/
├── shared/
│   └── layouts/MainLayout/
├── features/
│   ├── gallery/
│   └── booking-form/
├── entities/
│   ├── master/ui/
│   └── booking/ui/
└── widgets/
    └── master-profile/
```

### Личный кабинет
```
src/
├── shared/
│   └── layouts/ProfileLayout/
├── features/
│   └── profile-navigation/
├── entities/
│   └── ad/ui/AdCard/
└── widgets/
    └── profile-dashboard/
```

## 🚨 Важные изменения в плане

1. **Учтены реальные компоненты** из существующих страниц
2. **Layout компоненты** выделены в отдельную категорию в shared
3. **Widgets** теперь соответствуют реальным страницам
4. **Features** привязаны к конкретному функционалу страниц
5. **Entities** организованы вокруг реальных бизнес-сущностей

## ✅ Чеклист соответствия

- [x] Главная страница - все компоненты учтены
- [x] Страница мастера - галерея, информация, услуги, отзывы
- [x] Личный кабинет - боковая панель, вкладки, карточки объявлений
- [x] Общие layout компоненты - SidebarWrapper, ContentCard, ProfileSidebar
- [x] Фильтры и карта для главной страницы
- [x] Модальные окна и формы бронирования
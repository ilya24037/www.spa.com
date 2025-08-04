# 🏗️ Идеальная структура проекта "Мини Авито"

## 📁 Структура папок

```
project-root/
├── src/
│   ├── shared/                     # 🔄 Переиспользуемые компоненты
│   │   ├── ui/                     # Атомарные UI компоненты
│   │   │   ├── atoms/              # Базовые элементы
│   │   │   │   ├── Button/
│   │   │   │   │   ├── Button.vue
│   │   │   │   │   ├── Button.types.ts
│   │   │   │   │   ├── Button.test.ts
│   │   │   │   │   └── index.ts
│   │   │   │   ├── Input/
│   │   │   │   ├── Icon/
│   │   │   │   ├── Badge/
│   │   │   │   ├── Avatar/
│   │   │   │   ├── Spinner/
│   │   │   │   └── Typography/
│   │   │   │
│   │   │   ├── molecules/          # Составные компоненты
│   │   │   │   ├── Card/
│   │   │   │   ├── FormField/
│   │   │   │   ├── SearchBar/
│   │   │   │   ├── PriceDisplay/
│   │   │   │   ├── Rating/
│   │   │   │   ├── PhoneInput/
│   │   │   │   └── ImageUploader/
│   │   │   │
│   │   │   └── organisms/          # Сложные компоненты
│   │   │       ├── Navigation/
│   │   │       ├── FilterPanel/
│   │   │       ├── PhotoGallery/
│   │   │       └── ContactForm/
│   │   │
│   │   ├── layouts/                # 📐 Макеты страниц
│   │   │   ├── MainLayout/
│   │   │   │   ├── MainLayout.vue
│   │   │   │   ├── Header/
│   │   │   │   │   ├── Header.vue
│   │   │   │   │   ├── HeaderNav.vue
│   │   │   │   │   ├── HeaderActions.vue
│   │   │   │   │   └── HeaderUser.vue
│   │   │   │   ├── Sidebar/
│   │   │   │   │   ├── Sidebar.vue
│   │   │   │   │   └── SidebarFilter.vue
│   │   │   │   ├── Footer/
│   │   │   │   │   ├── Footer.vue
│   │   │   │   │   ├── FooterLinks.vue
│   │   │   │   │   └── FooterContacts.vue
│   │   │   │   └── index.ts
│   │   │   │
│   │   │   ├── AuthLayout/
│   │   │   └── EmptyLayout/
│   │   │
│   │   ├── api/                    # 📡 API клиенты
│   │   │   ├── http/
│   │   │   │   ├── client.ts
│   │   │   │   ├── interceptors.ts
│   │   │   │   └── types.ts
│   │   │   └── endpoints/
│   │   │       ├── base.api.ts
│   │   │       └── types.ts
│   │   │
│   │   ├── lib/                    # 🛠️ Утилиты
│   │   │   ├── utils/
│   │   │   │   ├── format.ts       # Форматирование данных
│   │   │   │   ├── validation.ts   # Валидация
│   │   │   │   ├── storage.ts      # LocalStorage
│   │   │   │   └── helpers.ts      # Общие хелперы
│   │   │   │
│   │   │   ├── hooks/              # Vue композиции
│   │   │   │   ├── useAuth.ts
│   │   │   │   ├── useApi.ts
│   │   │   │   ├── useForm.ts
│   │   │   │   ├── usePagination.ts
│   │   │   │   └── useDebounce.ts
│   │   │   │
│   │   │   └── constants/
│   │   │       ├── routes.ts
│   │   │       ├── api.ts
│   │   │       └── app.ts
│   │   │
│   │   └── types/                  # 📝 Общие типы
│   │       ├── models.ts
│   │       ├── api.ts
│   │       └── ui.ts
│   │
│   ├── entities/                   # 📦 Бизнес-сущности
│   │   ├── master/
│   │   │   ├── model/
│   │   │   │   ├── types.ts
│   │   │   │   └── master.ts
│   │   │   ├── api/
│   │   │   │   └── master.api.ts
│   │   │   ├── ui/
│   │   │   │   ├── MasterCard/
│   │   │   │   ├── MasterInfo/
│   │   │   │   └── MasterBadge/
│   │   │   └── lib/
│   │   │       └── master.utils.ts
│   │   │
│   │   ├── service/
│   │   │   ├── model/
│   │   │   ├── api/
│   │   │   └── ui/
│   │   │       ├── ServiceCard/
│   │   │       └── ServicePrice/
│   │   │
│   │   ├── user/
│   │   │   ├── model/
│   │   │   ├── api/
│   │   │   └── ui/
│   │   │       └── UserAvatar/
│   │   │
│   │   ├── ad/
│   │   │   ├── model/
│   │   │   ├── api/
│   │   │   └── ui/
│   │   │       └── AdForm/
│   │   │
│   │   └── booking/
│   │       ├── model/
│   │       ├── api/
│   │       └── ui/
│   │
│   ├── features/                   # 🎯 Функциональности
│   │   ├── auth/
│   │   │   ├── ui/
│   │   │   │   ├── LoginForm/
│   │   │   │   └── RegisterForm/
│   │   │   ├── model/
│   │   │   │   └── auth.store.ts
│   │   │   └── api/
│   │   │       └── auth.api.ts
│   │   │
│   │   ├── masters-filter/
│   │   │   ├── ui/
│   │   │   │   ├── FilterPanel/
│   │   │   │   ├── FilterCategory/
│   │   │   │   ├── FilterPrice/
│   │   │   │   └── FilterLocation/
│   │   │   ├── model/
│   │   │   │   └── filter.store.ts
│   │   │   └── lib/
│   │   │       └── filter.utils.ts
│   │   │
│   │   ├── masters-list/
│   │   │   ├── ui/
│   │   │   │   ├── MastersList/
│   │   │   │   └── MastersGrid/
│   │   │   └── model/
│   │   │       └── masters.store.ts
│   │   │
│   │   ├── favorites/
│   │   │   ├── ui/
│   │   │   │   └── FavoriteButton/
│   │   │   ├── model/
│   │   │   │   └── favorites.store.ts
│   │   │   └── api/
│   │   │
│   │   ├── compare/
│   │   │   ├── ui/
│   │   │   │   ├── CompareButton/
│   │   │   │   └── CompareTable/
│   │   │   └── model/
│   │   │       └── compare.store.ts
│   │   │
│   │   └── ad-create/
│   │       ├── ui/
│   │       │   ├── StepProgress/
│   │       │   ├── CategoryStep/
│   │       │   ├── InfoStep/
│   │       │   ├── PhotoStep/
│   │       │   ├── PriceStep/
│   │       │   └── ContactsStep/
│   │       └── model/
│   │           └── ad-form.store.ts
│   │
│   ├── widgets/                    # 🧩 Виджеты (композиция features)
│   │   ├── header/
│   │   │   └── HeaderWidget.vue
│   │   ├── masters-catalog/
│   │   │   └── MastersCatalog.vue
│   │   ├── master-profile/
│   │   │   ├── ProfileHeader/
│   │   │   ├── ProfileGallery/
│   │   │   ├── ProfileServices/
│   │   │   └── ProfileContacts/
│   │   └── ad-form/
│   │       └── AdFormWidget.vue
│   │
│   ├── pages/                      # 📄 Страницы
│   │   ├── home/
│   │   │   └── HomePage.vue
│   │   ├── master/
│   │   │   └── [id]/
│   │   │       └── MasterPage.vue
│   │   ├── ad/
│   │   │   ├── create/
│   │   │   │   └── AdCreatePage.vue
│   │   │   └── edit/
│   │   │       └── [id]/
│   │   │           └── AdEditPage.vue
│   │   ├── profile/
│   │   │   ├── ProfilePage.vue
│   │   │   ├── ads/
│   │   │   ├── settings/
│   │   │   └── bookings/
│   │   ├── favorites/
│   │   │   └── FavoritesPage.vue
│   │   └── compare/
│   │       └── ComparePage.vue
│   │
│   └── app/                        # 🚀 Инициализация приложения
│       ├── providers/
│       │   ├── RouterProvider.vue
│       │   ├── StoreProvider.vue
│       │   └── UIProvider.vue
│       ├── styles/
│       │   ├── global.css
│       │   ├── variables.css
│       │   └── reset.css
│       ├── router/
│       │   └── index.ts
│       ├── store/
│       │   └── index.ts
│       └── App.vue
│
├── public/                         # 📁 Статические файлы
│   ├── images/
│   ├── icons/
│   └── fonts/
│
├── tests/                          # 🧪 Тесты
│   ├── unit/
│   ├── integration/
│   └── e2e/
│
├── docs/                           # 📚 Документация
│   ├── components/
│   ├── api/
│   └── architecture/
│
└── config/                         # ⚙️ Конфигурация
    ├── vite.config.ts
    ├── tsconfig.json
    └── tailwind.config.js
```

## 🎯 Принципы организации

### 1. **Feature-Sliced Design**
- `shared/` - переиспользуемые компоненты без бизнес-логики
- `entities/` - бизнес-сущности (master, service, user)
- `features/` - функциональности (auth, filter, favorites)
- `widgets/` - композиция features в блоки
- `pages/` - страницы приложения
- `app/` - инициализация

### 2. **Atomic Design для UI**
- `atoms/` - Button, Input, Icon
- `molecules/` - Card, FormField, SearchBar
- `organisms/` - Navigation, FilterPanel

### 3. **Модульная структура**
Каждый модуль содержит:
- `model/` - бизнес-логика, store
- `ui/` - компоненты
- `api/` - работа с API
- `lib/` - утилиты

### 4. **Переиспользование**
- Общие компоненты в `shared/ui/`
- Общие хуки в `shared/lib/hooks/`
- Общие типы в `shared/types/`

## 📝 Примеры файлов

### Button (Атом)
```typescript
// shared/ui/atoms/Button/Button.vue
<template>
  <button 
    :class="buttonClasses"
    :disabled="disabled"
    @click="$emit('click', $event)"
  >
    <slot />
  </button>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import type { ButtonProps } from './Button.types'

const props = defineProps<ButtonProps>()
const emit = defineEmits<{
  click: [event: MouseEvent]
}>()

const buttonClasses = computed(() => [
  'btn',
  `btn--${props.variant}`,
  `btn--${props.size}`,
  { 'btn--disabled': props.disabled }
])
</script>
```

### MasterCard (Молекула)
```typescript
// entities/master/ui/MasterCard/MasterCard.vue
<template>
  <Card class="master-card" @click="handleClick">
    <template #image>
      <img :src="master.avatar" :alt="master.name" />
    </template>
    
    <template #content>
      <h3>{{ master.name }}</h3>
      <Rating :value="master.rating" />
      <PriceDisplay :price="master.priceFrom" />
    </template>
    
    <template #actions>
      <FavoriteButton :id="master.id" />
      <CompareButton :id="master.id" />
    </template>
  </Card>
</template>

<script setup lang="ts">
import { Card, Rating, PriceDisplay } from '@/shared/ui'
import { FavoriteButton } from '@/features/favorites'
import { CompareButton } from '@/features/compare'
import type { Master } from '../../model/types'

defineProps<{
  master: Master
}>()
</script>
```

### FilterPanel (Организм)
```typescript
// features/masters-filter/ui/FilterPanel/FilterPanel.vue
<template>
  <div class="filter-panel">
    <FilterCategory v-model="filters.category" />
    <FilterPrice 
      v-model:min="filters.priceMin"
      v-model:max="filters.priceMax"
    />
    <FilterLocation v-model="filters.location" />
    
    <Button @click="applyFilters">
      Применить
    </Button>
    
    <Button variant="text" @click="resetFilters">
      Сбросить
    </Button>
  </div>
</template>

<script setup lang="ts">
import { Button } from '@/shared/ui'
import { useFilterStore } from '../../model/filter.store'
import FilterCategory from '../FilterCategory/FilterCategory.vue'
import FilterPrice from '../FilterPrice/FilterPrice.vue'
import FilterLocation from '../FilterLocation/FilterLocation.vue'

const filterStore = useFilterStore()
const { filters, applyFilters, resetFilters } = filterStore
</script>
```

### HomePage (Страница)
```typescript
// pages/home/HomePage.vue
<template>
  <MainLayout>
    <template #sidebar>
      <MastersFilter />
    </template>
    
    <template #content>
      <MastersCatalog />
    </template>
  </MainLayout>
</template>

<script setup lang="ts">
import { MainLayout } from '@/shared/layouts'
import { MastersFilter } from '@/features/masters-filter'
import { MastersCatalog } from '@/widgets/masters-catalog'
</script>
```

## 🔧 Конфигурация путей

### tsconfig.json
```json
{
  "compilerOptions": {
    "paths": {
      "@/*": ["./src/*"],
      "@shared/*": ["./src/shared/*"],
      "@entities/*": ["./src/entities/*"],
      "@features/*": ["./src/features/*"],
      "@widgets/*": ["./src/widgets/*"],
      "@pages/*": ["./src/pages/*"],
      "@app/*": ["./src/app/*"]
    }
  }
}
```

### vite.config.ts
```typescript
import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import path from 'path'

export default defineConfig({
  plugins: [vue()],
  resolve: {
    alias: {
      '@': path.resolve(__dirname, './src'),
      '@shared': path.resolve(__dirname, './src/shared'),
      '@entities': path.resolve(__dirname, './src/entities'),
      '@features': path.resolve(__dirname, './src/features'),
      '@widgets': path.resolve(__dirname, './src/widgets'),
      '@pages': path.resolve(__dirname, './src/pages'),
      '@app': path.resolve(__dirname, './src/app'),
    }
  }
})
```

## 📋 Преимущества структуры

1. **Максимальная переиспользуемость** - каждый компонент независим
2. **Четкая иерархия** - от атомов до страниц
3. **Изоляция бизнес-логики** - в entities и features
4. **Простота тестирования** - каждый модуль изолирован
5. **Масштабируемость** - легко добавлять новые features
6. **Понятность для ИИ** - четкая структура и naming

## 🚀 Команды для создания структуры

```bash
# Создать базовую структуру
mkdir -p src/{shared/{ui/{atoms,molecules,organisms},layouts,api,lib,types},entities,features,widgets,pages,app}

# Создать компонент
mkdir -p src/shared/ui/atoms/Button
touch src/shared/ui/atoms/Button/{Button.vue,Button.types.ts,Button.test.ts,index.ts}

# Создать feature
mkdir -p src/features/auth/{ui,model,api}

# Создать entity
mkdir -p src/entities/master/{model,api,ui,lib}
```
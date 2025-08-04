# 🎯 План рефакторинга Frontend к Feature-Sliced Design

## 📊 Исходные данные
- **Всего компонентов**: 233 Vue файла
- **Текущая структура**: традиционная компонентная
- **Целевая архитектура**: Feature-Sliced Design + Atomic Design
- **Оценка времени**: 40-50 часов

## 🏗️ Этапы рефакторинга

### 📁 Этап 0: Подготовка структуры (2 часа)

#### Задача 1: Создать базовую структуру FSD

```bash
# Создать основные директории
mkdir -p resources/js/src/{shared,entities,features,widgets,pages,app}

# Создать структуру shared слоя
mkdir -p resources/js/src/shared/{ui/{atoms,molecules,organisms},layouts,api,lib/{hooks,utils,constants},types}

# Создать структуру для каждой entity
mkdir -p resources/js/src/entities/{master,service,user,ad,booking}/{model,api,ui,lib}

# Создать структуру для features
mkdir -p resources/js/src/features/{auth,masters-filter,masters-list,favorites,compare,ad-create,booking-create}/{ui,model,api,lib}

# Создать структуру для widgets
mkdir -p resources/js/src/widgets/{header,footer,masters-catalog,master-profile,ad-form}

# Создать app структуру
mkdir -p resources/js/src/app/{providers,styles,router,store}
```

#### Задача 2: Создать конфигурационные файлы

**Файл**: `resources/js/src/shared/config/index.ts`
```typescript
// Создать файл с путями и алиасами
export const PATHS = {
  shared: '@/src/shared',
  entities: '@/src/entities',
  features: '@/src/features',
  widgets: '@/src/widgets',
  pages: '@/src/pages',
  app: '@/src/app'
} as const
```

**Обновить**: `vite.config.js`
```javascript
// Добавить алиасы в resolve.alias
'@shared': path.resolve(__dirname, './resources/js/src/shared'),
'@entities': path.resolve(__dirname, './resources/js/src/entities'),
'@features': path.resolve(__dirname, './resources/js/src/features'),
'@widgets': path.resolve(__dirname, './resources/js/src/widgets'),
'@pages': path.resolve(__dirname, './resources/js/src/pages'),
'@app': path.resolve(__dirname, './resources/js/src/app'),
```

### 📦 Этап 1: Миграция Shared слоя (8 часов)

#### Задача 1.1: Перенос UI компонентов (4 часа)

**1. Атомарные компоненты** (atoms)

```bash
# Перенести базовые компоненты
mv resources/js/Components/UI/BaseInput.vue resources/js/src/shared/ui/atoms/Input/Input.vue
mv resources/js/Components/UI/BaseButton.vue resources/js/src/shared/ui/atoms/Button/Button.vue
mv resources/js/Components/UI/BaseCheckbox.vue resources/js/src/shared/ui/atoms/Checkbox/Checkbox.vue
mv resources/js/Components/UI/BaseRadio.vue resources/js/src/shared/ui/atoms/Radio/Radio.vue
mv resources/js/Components/UI/BaseSelect.vue resources/js/src/shared/ui/atoms/Select/Select.vue
mv resources/js/Components/UI/BaseTextarea.vue resources/js/src/shared/ui/atoms/Textarea/Textarea.vue
```

**Для каждого атома создать**:
```typescript
// Пример для Button
// resources/js/src/shared/ui/atoms/Button/Button.types.ts
export interface ButtonProps {
  variant?: 'primary' | 'secondary' | 'outline' | 'text'
  size?: 'sm' | 'md' | 'lg'
  disabled?: boolean
  loading?: boolean
  fullWidth?: boolean
}

// resources/js/src/shared/ui/atoms/Button/index.ts
export { default as Button } from './Button.vue'
export type { ButtonProps } from './Button.types'
```

**2. Молекулярные компоненты** (molecules)

```bash
# Перенести составные компоненты
mkdir -p resources/js/src/shared/ui/molecules/{Card,FormField,SearchBar,PriceDisplay,Rating,PhoneInput,ImageUploader}

# Переместить и переименовать
mv resources/js/Components/Cards/BaseCard.vue resources/js/src/shared/ui/molecules/Card/Card.vue
mv resources/js/Components/Form/FormField.vue resources/js/src/shared/ui/molecules/FormField/FormField.vue
mv resources/js/Components/Common/SearchBar.vue resources/js/src/shared/ui/molecules/SearchBar/SearchBar.vue
```

**3. Организмы** (organisms)

```bash
# Перенести сложные компоненты
mkdir -p resources/js/src/shared/ui/organisms/{Navigation,FilterPanel,PhotoGallery,ContactForm}

# Переместить
mv resources/js/Components/Header/Navigation.vue resources/js/src/shared/ui/organisms/Navigation/Navigation.vue
mv resources/js/Components/Gallery resources/js/src/shared/ui/organisms/PhotoGallery/
```

#### Задача 1.2: Перенос layouts (1 час)

```bash
# Перенести layouts
mv resources/js/Layouts/AppLayout.vue resources/js/src/shared/layouts/MainLayout/MainLayout.vue
mv resources/js/Layouts/AuthLayout.vue resources/js/src/shared/layouts/AuthLayout/AuthLayout.vue
mv resources/js/Layouts/GuestLayout.vue resources/js/src/shared/layouts/GuestLayout/GuestLayout.vue
```

#### Задача 1.3: Создать shared/lib (2 часа)

**1. Перенести composables в hooks**
```bash
# Перенести и переименовать
mv resources/js/Composables/useAuth.js resources/js/src/shared/lib/hooks/useAuth.ts
mv resources/js/Composables/useForm.js resources/js/src/shared/lib/hooks/useForm.ts
```

**2. Создать утилиты**
```typescript
// resources/js/src/shared/lib/utils/format.ts
export const formatPrice = (price: number): string => {
  return new Intl.NumberFormat('ru-RU', {
    style: 'currency',
    currency: 'RUB'
  }).format(price)
}

export const formatDate = (date: Date | string): string => {
  // Логика форматирования
}

// resources/js/src/shared/lib/utils/validation.ts
export const validators = {
  required: (value: any) => !!value || 'Обязательное поле',
  email: (value: string) => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value) || 'Неверный email',
  phone: (value: string) => /^\+7\d{10}$/.test(value) || 'Неверный формат телефона'
}
```

#### Задача 1.4: Создать общие типы (1 час)

```typescript
// resources/js/src/shared/types/models.ts
export interface BaseModel {
  id: number
  created_at: string
  updated_at: string
}

// resources/js/src/shared/types/api.ts
export interface ApiResponse<T> {
  data: T
  message?: string
  errors?: Record<string, string[]>
}

// resources/js/src/shared/types/ui.ts
export interface SelectOption {
  value: string | number
  label: string
  disabled?: boolean
}
```

### 🏢 Этап 2: Создание Entities слоя (8 часов)

#### Задача 2.1: Entity Master (2 часа)

**1. Создать модель**
```typescript
// resources/js/src/entities/master/model/types.ts
export interface Master {
  id: number
  name: string
  avatar: string
  rating: number
  reviews_count: number
  price_from: number
  description: string
  services: Service[]
  // ... остальные поля
}

// resources/js/src/entities/master/model/master.ts
export const createMaster = (data: Partial<Master>): Master => {
  return {
    id: 0,
    name: '',
    rating: 0,
    // ... дефолтные значения
    ...data
  }
}
```

**2. Создать API**
```typescript
// resources/js/src/entities/master/api/master.api.ts
import { api } from '@/shared/api'
import type { Master } from '../model/types'

export const masterApi = {
  getById: (id: number) => api.get<Master>(`/masters/${id}`),
  getList: (params?: any) => api.get<Master[]>('/masters', { params }),
  update: (id: number, data: Partial<Master>) => api.put<Master>(`/masters/${id}`, data)
}
```

**3. Перенести UI компоненты**
```bash
# Переместить компоненты мастера
mv resources/js/Components/Masters/MasterCard.vue resources/js/src/entities/master/ui/MasterCard/MasterCard.vue
mv resources/js/Components/Masters/MasterInfo resources/js/src/entities/master/ui/MasterInfo/
mv resources/js/Components/Masters/MasterHeader resources/js/src/entities/master/ui/MasterBadge/
```

#### Задача 2.2: Entity Service (1.5 часа)

```typescript
// resources/js/src/entities/service/model/types.ts
export interface Service {
  id: number
  name: string
  category_id: number
  price: number
  duration: number
  description?: string
}

// Создать UI компоненты
// resources/js/src/entities/service/ui/ServiceCard/ServiceCard.vue
// resources/js/src/entities/service/ui/ServicePrice/ServicePrice.vue
```

#### Задача 2.3: Entity User (1.5 часа)

```bash
# Перенести компоненты пользователя
mv resources/js/Components/Profile/UserAvatar.vue resources/js/src/entities/user/ui/UserAvatar/UserAvatar.vue
```

#### Задача 2.4: Entity Ad (2 часа)

```bash
# Перенести компоненты объявлений (самая большая папка - 73 файла)
# Разобрать AdForm на части
mv resources/js/Components/AdForm/modules/* resources/js/src/entities/ad/ui/
```

#### Задача 2.5: Entity Booking (1 час)

```bash
mv resources/js/Components/Booking/* resources/js/src/entities/booking/ui/
```

### 🎯 Этап 3: Создание Features слоя (10 часов)

#### Задача 3.1: Feature Auth (2 часа)

```bash
# Создать структуру
mkdir -p resources/js/src/features/auth/{ui/{LoginForm,RegisterForm,ForgotPasswordForm},model,api}

# Перенести компоненты
mv resources/js/Components/Auth/LoginForm.vue resources/js/src/features/auth/ui/LoginForm/LoginForm.vue
mv resources/js/Components/Auth/RegisterForm.vue resources/js/src/features/auth/ui/RegisterForm/RegisterForm.vue
```

**Создать store**
```typescript
// resources/js/src/features/auth/model/auth.store.ts
import { defineStore } from 'pinia'
import { authApi } from '../api/auth.api'

export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: null,
    isAuthenticated: false
  }),
  actions: {
    async login(credentials: LoginCredentials) {
      const { data } = await authApi.login(credentials)
      this.user = data.user
      this.isAuthenticated = true
    }
  }
})
```

#### Задача 3.2: Feature Masters Filter (2 часа)

```bash
# Перенести фильтры
mkdir -p resources/js/src/features/masters-filter/ui/{FilterPanel,FilterCategory,FilterPrice,FilterLocation}

mv resources/js/Components/Filters/* resources/js/src/features/masters-filter/ui/
```

**Создать store для фильтров**
```typescript
// resources/js/src/features/masters-filter/model/filter.store.ts
export const useFilterStore = defineStore('filter', {
  state: () => ({
    category: null,
    priceMin: 0,
    priceMax: 100000,
    location: null,
    rating: null
  }),
  actions: {
    applyFilters() {
      // Логика применения
    },
    resetFilters() {
      this.$reset()
    }
  }
})
```

#### Задача 3.3: Feature Favorites (1.5 часа)

```bash
# Создать компоненты
mkdir -p resources/js/src/features/favorites/ui/FavoriteButton

# Перенести store
mv resources/js/stores/favorites.js resources/js/src/features/favorites/model/favorites.store.ts
```

#### Задача 3.4: Feature Compare (1.5 часа)

```bash
mkdir -p resources/js/src/features/compare/ui/{CompareButton,CompareTable}
```

#### Задача 3.5: Feature Ad Create (3 часа)

```bash
# Разобрать большую форму на шаги
mkdir -p resources/js/src/features/ad-create/ui/{StepProgress,CategoryStep,InfoStep,PhotoStep,PriceStep,ContactsStep}

# Перенести логику из AdForm
mv resources/js/Components/AdForm/stores/* resources/js/src/features/ad-create/model/
```

### 🧩 Этап 4: Создание Widgets слоя (6 часов)

#### Задача 4.1: Widget Header (1 час)

```typescript
// resources/js/src/widgets/header/HeaderWidget.vue
<template>
  <Header>
    <HeaderLogo />
    <HeaderNav />
    <HeaderSearch />
    <HeaderActions>
      <FavoriteButton />
      <CompareButton />
      <UserMenu v-if="isAuth" />
      <LoginButton v-else />
    </HeaderActions>
  </Header>
</template>

<script setup>
import { Header, HeaderLogo, HeaderNav } from '@/shared/ui/organisms/Header'
import { HeaderSearch } from '@/features/search'
import { FavoriteButton } from '@/features/favorites'
import { CompareButton } from '@/features/compare'
import { UserMenu, LoginButton } from '@/features/auth'
</script>
```

#### Задача 4.2: Widget Masters Catalog (2 часа)

```bash
# Композиция фильтров и списка
mkdir -p resources/js/src/widgets/masters-catalog
```

#### Задача 4.3: Widget Master Profile (2 часа)

```bash
mkdir -p resources/js/src/widgets/master-profile/{ProfileHeader,ProfileGallery,ProfileServices,ProfileContacts}

# Перенести и разделить
mv resources/js/Components/Masters/Master*.vue resources/js/src/widgets/master-profile/
```

#### Задача 4.4: Widget Ad Form (1 час)

```bash
# Объединить все части формы
mkdir -p resources/js/src/widgets/ad-form
```

### 📄 Этап 5: Миграция Pages (4 часов)

#### Задача 5.1: Обновить импорты в страницах

```typescript
// Пример для HomePage
// resources/js/Pages/Home.vue
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

<script setup>
// Старые импорты
// import AppLayout from '@/Layouts/AppLayout.vue'
// import MastersList from '@/Components/Masters/MastersList.vue'

// Новые импорты
import { MainLayout } from '@/shared/layouts'
import { MastersFilter } from '@/features/masters-filter'
import { MastersCatalog } from '@/widgets/masters-catalog'
</script>
```

**Обновить все страницы:**
```bash
# Список страниц для обновления
- Home.vue
- Masters/Show.vue  
- Ads/Create.vue
- Profile/Dashboard.vue
- Favorites/Index.vue
- Compare/Index.vue
```

### 🚀 Этап 6: Финальная интеграция (4 часа)

#### Задача 6.1: Обновить app.js

```typescript
// resources/js/app.js
import { createApp, h } from 'vue'
import { createInertiaApp } from '@inertiajs/vue3'
import { createPinia } from 'pinia'

// Импорты из новой структуры
import '@/src/app/styles/global.css'

createInertiaApp({
  resolve: name => {
    // Обновить пути
    const pages = import.meta.glob('./src/pages/**/*.vue', { eager: true })
    return pages[`./src/pages/${name}.vue`]
  },
  setup({ el, App, props, plugin }) {
    createApp({ render: () => h(App, props) })
      .use(plugin)
      .use(createPinia())
      .mount(el)
  }
})
```

#### Задача 6.2: Создать индексные файлы

```typescript
// resources/js/src/shared/ui/atoms/index.ts
export * from './Button'
export * from './Input'
export * from './Checkbox'
// ... остальные

// resources/js/src/shared/ui/index.ts
export * from './atoms'
export * from './molecules'
export * from './organisms'
```

#### Задача 6.3: Настроить автоимпорты

```javascript
// vite.config.js
import Components from 'unplugin-vue-components/vite'

export default {
  plugins: [
    Components({
      dirs: [
        'resources/js/src/shared/ui',
        'resources/js/src/entities/*/ui',
        'resources/js/src/features/*/ui',
        'resources/js/src/widgets'
      ],
      dts: 'resources/js/components.d.ts'
    })
  ]
}
```

### 🧹 Этап 7: Очистка (2 часа)

#### Задача 7.1: Удалить старые папки

```bash
# После проверки что все работает
rm -rf resources/js/Components
rm -rf resources/js/Composables
rm -rf resources/js/stores

# Переместить оставшиеся файлы
mv resources/js/utils/* resources/js/src/shared/lib/utils/
```

#### Задача 7.2: Обновить тесты

```bash
# Обновить пути в тестах
find tests -name "*.spec.js" -o -name "*.test.js" | xargs sed -i 's|@/Components|@/shared/ui|g'
```

## 📋 Чеклист для проверки

### После каждого этапа проверить:

1. **Компиляция без ошибок**
   ```bash
   npm run build
   ```

2. **Работа в dev режиме**
   ```bash
   npm run dev
   ```

3. **Проверка импортов**
   ```bash
   # Поиск старых импортов
   grep -r "from '@/Components" resources/js/
   ```

4. **Тесты проходят**
   ```bash
   npm run test
   ```

## 🎯 Результат

После выполнения всех этапов:

```
resources/js/
├── src/
│   ├── shared/          ✅ Переиспользуемые компоненты
│   ├── entities/        ✅ Бизнес-сущности
│   ├── features/        ✅ Функциональности
│   ├── widgets/         ✅ Композиция features
│   ├── pages/           ✅ Страницы
│   └── app/             ✅ Инициализация
├── app.js               ✅ Обновлен
└── bootstrap.js         ✅ Без изменений
```

## 🚨 Важные моменты для ИИ помощника

1. **Делать резервные копии** перед каждым этапом
2. **Тестировать после каждого переноса** компонента
3. **Не удалять старые файлы** до полной проверки
4. **Коммитить изменения** после каждого успешного этапа
5. **Следить за импортами** - основная причина ошибок

## 📊 Метрики успеха

- ✅ Все 233 компонента перенесены и работают
- ✅ Структура соответствует FSD
- ✅ Нет дублирования кода
- ✅ Улучшена типизация
- ✅ Упрощено добавление новых features
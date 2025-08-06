# 📋 ПЛАН МИГРАЦИИ HEADER КОМПОНЕНТОВ

## 🎯 ЦЕЛЬ: Постепенная миграция 21 legacy компонента в FSD архитектуру

### ✅ УЖЕ СОЗДАНО:
- `shared/ui/organisms/Header/Header.vue` - новый базовый Header с slot-архитектурой

---

## 🔄 ЭТАПЫ МИГРАЦИИ (по приоритету использования):

### **ЭТАП 1: Критически важные компоненты (используются на всех страницах)**

#### 1. **AuthBlock.vue** → `features/auth/ui/AuthWidget/`
```bash
mkdir -p C:\www.spa.com\resources\js\src\features\auth\ui\AuthWidget
```
**Приоритет:** 🔥 Высокий - используется везде

#### 2. **Navbar.vue** → `shared/ui/organisms/Header/` (интегрировать в основной Header)
**Статус:** ✅ Уже интегрирован в новый Header.vue

#### 3. **UserMenu.vue** → `features/auth/ui/UserDropdown/`
```bash
mkdir -p C:\www.spa.com\resources\js\src\features\auth\ui\UserDropdown
```
**Приоритет:** 🔥 Высокий - для авторизованных пользователей

---

### **ЭТАП 2: Feature-специфичные компоненты**

#### 4. **SearchBar.vue** → `features/search/ui/GlobalSearch/`
```bash
mkdir -p C:\www.spa.com\resources\js\src\features\search\ui\GlobalSearch
```
**Приоритет:** 🔥 Высокий - основной поиск

#### 5. **CitySelector.vue + CityModal.vue** → `features/city-selector/ui/`
```bash
mkdir -p C:\www.spa.com\resources\js\src\features\city-selector\ui\CityPicker
mkdir -p C:\www.spa.com\resources\js\src\features\city-selector\ui\CityModal
```
**Приоритет:** 🔥 Высокий - геолокация критична

#### 6. **FavoritesButton.vue** → `features/favorites/ui/FavoritesCounter/`
```bash
mkdir -p C:\www.spa.com\resources\js\src\features\favorites\ui\FavoritesCounter
```
**Приоритет:** 🟡 Средний

#### 7. **CompareButton.vue** → `features/compare/ui/CompareCounter/`
```bash
mkdir -p C:\www.spa.com\resources\js\src\features\compare\ui\CompareCounter
```
**Приоритет:** 🟡 Средний

---

### **ЭТАП 3: Структурные компоненты**

#### 8. **Logo.vue** → `shared/ui/atoms/Logo/AppLogo.vue`
```bash
mkdir -p C:\www.spa.com\resources\js\src\shared\ui\atoms\Logo
```
**Приоритет:** 🔥 Высокий - брендинг

#### 9. **MobileMenu.vue** → `shared/ui/organisms/Header/components/MobileHeader.vue`
```bash
mkdir -p C:\www.spa.com\resources\js\src\shared\ui\organisms\Header\components
```
**Приоритет:** 🔥 Высокий - мобильные пользователи

#### 10. **QuickLinks.vue** → `shared/ui/molecules/Navigation/QuickNavigation.vue`
```bash
mkdir -p C:\www.spa.com\resources\js\src\shared\ui\molecules\Navigation
```
**Приоритет:** 🟡 Средний

---

### **ЭТАП 4: Дополнительные компоненты**

#### 11. **CatalogDropdown.vue** → `features/catalog/ui/CatalogDropdown/`
```bash
mkdir -p C:\www.spa.com\resources\js\src\features\catalog\ui\CatalogDropdown
```
**Приоритет:** 🟢 Низкий - дополнительный функционал

---

## 🔄 **ПОСТЕПЕННАЯ МИГРАЦИЯ СТРАТЕГИЯ:**

### **ФАЗА 1: Slot Integration (1-2 дня)**
1. ✅ Создать базовый Header с slot-архитектурой
2. 🔄 Интегрировать существующие legacy компоненты через slots
3. ✅ Проверить работоспособность

### **ФАЗА 2: Component by Component (по 1 компоненту в день)**
1. Создать новый FSD компонент
2. Переписать с TypeScript + Composition API
3. Обновить импорты в Header
4. Тестирование
5. Удалить legacy компонент

### **ФАЗА 3: Clean Up (1 день)**
1. Удалить оставшиеся legacy файлы
2. Обновить все импорты в проекте
3. Финальное тестирование

---

## 📝 **ПРИМЕР МИГРАЦИИ - AuthBlock.vue:**

### **Шаг 1:** Создать новую структуру
```typescript
// features/auth/ui/AuthWidget/AuthWidget.vue
<template>
  <div class="auth-widget">
    <!-- Для авторизованных -->
    <UserDropdown v-if="isAuthenticated" :user="user" />
    
    <!-- Для неавторизованных -->
    <div v-else class="flex gap-2">
      <button @click="$emit('show-login')" class="btn-secondary">
        Войти
      </button>
      <button @click="$emit('show-register')" class="btn-primary">
        Регистрация
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import type { User } from '@/types'
import { UserDropdown } from '../UserDropdown'

interface Props {
  user?: User | null
  isAuthenticated: boolean
}

const props = defineProps<Props>()
const emit = defineEmits<{
  'show-login': []
  'show-register': []
}>()
</script>
```

### **Шаг 2:** Обновить использование в Header
```vue
<!-- В Header.vue -->
<template slot="auth">
  <AuthWidget 
    :user="user"
    :is-authenticated="isAuthenticated"
    @show-login="showLoginModal = true"
    @show-register="showRegisterModal = true"
  />
</template>
```

### **Шаг 3:** Обновить импорты
```typescript
// Заменить старый импорт
// import AuthBlock from '@/Components/Header/AuthBlock.vue'
import { AuthWidget } from '@/src/features/auth/ui/AuthWidget'
```

### **Шаг 4:** Удалить legacy файл
```bash
rm C:\www.spa.com\resources\js\Components\Header\AuthBlock.vue
```

---

## 🎯 **КРИТЕРИИ УСПЕШНОЙ МИГРАЦИИ:**

### ✅ **Обязательные требования:**
- TypeScript типизация всех props/emits
- Composition API вместо Options API
- Accessibility атрибуты (aria-*)
- Loading/Error/Empty состояния
- Мобильная адаптивность
- Семантическая разметка

### ✅ **Качество кода:**
- Максимум 200 строк на компонент
- Все стили через Tailwind
- Обработка всех edge cases
- Proper error boundaries

### ✅ **Интеграция:**
- Работает с существующим кодом
- Совместим с Inertia.js
- Не ломает существующий функционал
- Плавная замена без даунтайма

---

## ⚡ **ПЛАН ВЫПОЛНЕНИЯ (7-10 дней):**

### **День 1:** AuthWidget + UserDropdown (критично)
### **День 2:** GlobalSearch (критично)
### **День 3:** CityPicker + CityModal (критично)
### **День 4:** AppLogo + MobileHeader (критично)
### **День 5:** FavoritesCounter + CompareCounter
### **День 6:** QuickNavigation + CatalogDropdown
### **День 7:** Тестирование + исправление багов
### **День 8:** Обновление всех импортов в проекте
### **День 9:** Удаление legacy файлов
### **День 10:** Финальное тестирование

---

## 🚀 **ОЖИДАЕМЫЙ РЕЗУЛЬТАТ:**

После завершения миграции:
- ✅ 0 legacy Header компонентов
- ✅ 100% FSD архитектура в Header
- ✅ TypeScript покрытие Header компонентов
- ✅ Современный, maintainable код
- ✅ Отличная производительность

**Готовность к production:** ✅ 100%
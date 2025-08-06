# 🔍 АНАЛИЗ ОСТАВШЕЙСЯ LEGACY ЛОГИКИ

## 📊 СТАТУС НА 06.08.2025
**Мигрировано Header:** ✅ 100%  
**Осталось legacy компонентов:** 📋 Много (см. детали ниже)

---

## 🚨 КРИТИЧЕСКИЕ LEGACY КОМПОНЕНТЫ ДЛЯ МИГРАЦИИ

### 📁 1. Components/Header/ - ❌ МИГРИРОВАНЫ, НО НЕ УДАЛЕНЫ
```bash
# ЭТИ ФАЙЛЫ НУЖНО УДАЛИТЬ (legacy дубликаты):
Components/Header/AuthBlock.vue          # ✅ Мигрирован → features/auth/ui/AuthWidget
Components/Header/CityModal.vue          # ✅ Мигрирован → features/city-selector/ui/CityModal  
Components/Header/CitySelector.vue       # ✅ Мигрирован → features/city-selector/ui/CityPicker
Components/Header/CompareButton.vue      # ✅ Мигрирован → features/compare/ui/CompareCounter
Components/Header/FavoritesButton.vue    # ✅ Мигрирован → features/favorites/ui/FavoritesCounter
Components/Header/Logo.vue               # ✅ Мигрирован → shared/ui/atoms/Logo/AppLogo
Components/Header/MobileMenu.vue         # ✅ Мигрирован → shared/ui/organisms/Header/components/MobileHeader
Components/Header/QuickLinks.vue         # ✅ Мигрирован → shared/ui/molecules/Navigation/QuickNavigation
Components/Header/SearchBar.vue          # ✅ Мигрирован → features/search/ui/GlobalSearch

# ЭТИ ФАЙЛЫ ОСТАЛИСЬ БЕЗ МИГРАЦИИ:
Components/Header/Navbar.vue             # ⚠️ Обновлен импорты, но сам файл legacy
Components/Header/UserMenu.vue           # ❌ НЕ МИГРИРОВАН! Критично для auth
Components/Header/CatalogDropdown.vue    # ❌ НЕ МИГРИРОВАН! Важен для навигации
```

---

## 🔥 ПРИОРИТЕТ 1: КРИТИЧНЫЕ К МИГРАЦИИ

### 🎯 Components/Header/UserMenu.vue
**Статус:** ❌ НЕ МИГРИРОВАН  
**Использование:** Активно используется в Navbar.vue  
**Проблема:** Остался старый компонент, не интегрирован с новой auth системой  
**Решение:** Мигрировать в `features/auth/ui/UserDropdown` (УЖЕ СОЗДАН!)

### 🎯 Components/Header/CatalogDropdown.vue  
**Статус:** ❌ НЕ МИГРИРОВАН  
**Использование:** Каталог услуг в header  
**Решение:** Создать `features/catalog/ui/CatalogDropdown` или `shared/ui/molecules/Catalog`

### 🎯 Components/Header/Navbar.vue
**Статус:** ⚠️ ЧАСТИЧНО ОБНОВЛЕН  
**Проблема:** Сам файл остался в legacy папке, только импорты FSD  
**Решение:** Полностью мигрировать в `shared/ui/organisms/Header` или удалить

---

## 📋 ПРИОРИТЕТ 2: ОСНОВНЫЕ LEGACY КОМПОНЕНТЫ

### 🏗️ Layout система
```
Layouts/AppLayout.vue                    # ⚠️ Использует новые компоненты, но сам legacy
Layouts/AuthLayout.vue                   # ❌ Не проверен
```

### 🎨 UI компоненты
```
Components/UI/ConfirmModal.vue           # ❌ Не в FSD, нужен в shared/ui/molecules
Components/Footer/Footer.vue             # ❌ Должен быть в shared/ui/organisms
```

### 📱 Основные Features
```
Components/Booking/Calendar.vue          # ❌ Должен быть в features/booking
Components/Form/Sections/               # ❌ Формы не в FSD структуре
Components/Features/                    # ❌ Смешанная архитектура
```

---

## 📊 ПРИОРИТЕТ 3: STORES И STATE MANAGEMENT

### 🏪 Дублированные Stores
```bash
# LEGACY STORES (нужно удалить после проверки):
stores/authStore.js                     # vs features/auth/model/auth.store.ts
stores/masterStore.js                   # vs entities/master/model/masterStore.ts
stores/bookingStore.ts                  # vs entities/booking/model/bookingStore.ts
stores/favorites.js                     # vs features/favorites/model/favorites.store.ts
stores/projectStore.js                  # Не ясно, что это
stores/servicesSelectionStore.js        # Возможно, нужен в features/
```

### 🔧 Utils и Helpers
```bash
utils/adApi.js                          # Должно быть в entities/ad/api/
utils/formOptions.js                    # Должно быть в shared/lib/
utils/formValidators.js                 # Должно быть в shared/lib/
utils/helpers.js                        # Должно быть в shared/lib/
```

---

## 🎯 ПЛАН ДЕЙСТВИЙ ПО УСТРАНЕНИЮ LEGACY

### 🚀 Фаза 1: Завершение Header миграции (НЕМЕДЛЕННО)
1. **Удалить дублированные Header файлы** после проверки использования
2. **Мигрировать UserMenu** → features/auth/ui/UserDropdown (заменить на существующий)
3. **Мигрировать CatalogDropdown** → создать новый FSD компонент
4. **Полностью перенести Navbar в FSD** или реструктурировать

### 🚀 Фаза 2: Основные компоненты (1-2 недели)
1. **Footer** → shared/ui/organisms/Footer
2. **ConfirmModal** → shared/ui/molecules/Modal  
3. **Booking/Calendar** → features/booking/ui/Calendar
4. **Form components** → shared/ui/molecules/Forms

### 🚀 Фаза 3: Stores и API (1 неделя)
1. **Очистить дублированные stores**
2. **Мигрировать utils** → shared/lib/
3. **API клиенты** → entities/*/api/

### 🚀 Фаза 4: Layout система (3-5 дней)
1. **AppLayout** → shared/layouts/AppLayout
2. **AuthLayout** → shared/layouts/AuthLayout
3. **Composables** → shared/composables/

---

## 🔢 КОЛИЧЕСТВЕННЫЙ АНАЛИЗ

### 📊 Header компоненты
- ✅ **Мигрировано:** 9/12 (75%)
- ❌ **Осталось:** 3/12 (25%) - UserMenu, CatalogDropdown, Navbar

### 📊 Общая картина
- 🏗️ **Legacy Components папка:** ~50+ файлов
- 🏪 **Legacy Stores:** 6 файлов  
- 🔧 **Legacy Utils:** 4 файла
- 📱 **Legacy Layouts:** 2 файла
- 📋 **Legacy Composables:** ~10 файлов

### 📊 Оценка объема работ
- **Критичные (Header довершить):** 1-2 дня
- **Основные компоненты:** 1-2 недели  
- **Полная миграция:** 3-4 недели
- **Cleanup и тестирование:** 1 неделя

---

## ⚠️ РИСКИ И ПРОБЛЕМЫ

### 🚨 Высокие риски
1. **UserMenu** используется активно, но не мигрирован
2. **Дублированные stores** могут конфликтовать
3. **Mixed архитектура** затрудняет поддержку

### ⚠️ Средние риски  
1. **Legacy utils** могут содержать важную логику
2. **Старые composables** могут иметь зависимости
3. **Layout компоненты** влияют на всё приложение

### ℹ️ Низкие риски
1. Большинство **FSD структуры уже создано**
2. **Header миграция на 75% завершена**
3. **TypeScript покрытие** хорошее для новых компонентов

---

## 🎯 КРИТИЧЕСКАЯ ЗАДАЧА

**САМОЕ ВАЖНОЕ:** Завершить Header миграцию (3 компонента) и удалить дублированные файлы.

**СЛЕДУЮЩИЙ ПРИОРИТЕТ:** Системная миграция основных компонентов на FSD.

---

## 🏆 ЗАКЛЮЧЕНИЕ

Header миграция успешна на **75%**, но остались **критичные компоненты**:
1. **UserMenu** - должен использовать новый UserDropdown
2. **CatalogDropdown** - нужен новый FSD компонент  
3. **Navbar cleanup** - финализировать структуру

**Общий legacy код:** Ещё много работы для полной FSD архитектуры (~3-4 недели).

---

*Анализ создан: 06.08.2025*
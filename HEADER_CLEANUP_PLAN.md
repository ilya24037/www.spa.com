# 🧹 ПЛАН CLEANUP LEGACY HEADER ФАЙЛОВ

## 📋 АНАЛИЗ БЕЗОПАСНОСТИ УДАЛЕНИЯ

### ✅ БЕЗОПАСНО УДАЛИТЬ (мигрированы на FSD):
- `Components/Header/AuthBlock.vue` → `features/auth/ui/AuthWidget`
- `Components/Header/CityModal.vue` → `features/city-selector/ui/CityModal`  
- `Components/Header/CitySelector.vue` → `features/city-selector/ui/CityPicker`
- `Components/Header/CompareButton.vue` → `features/compare/ui/CompareCounter`
- `Components/Header/FavoritesButton.vue` → `features/favorites/ui/FavoritesCounter`
- `Components/Header/Logo.vue` → `shared/ui/atoms/Logo/AppLogo`
- `Components/Header/MobileMenu.vue` → `shared/ui/organisms/Header/components/MobileHeader`
- `Components/Header/QuickLinks.vue` → `shared/ui/molecules/Navigation/QuickNavigation`
- `Components/Header/SearchBar.vue` → `features/search/ui/GlobalSearch`
- `Components/Header/CatalogDropdown.vue` → `features/catalog/ui/CatalogDropdown`

### ⚠️ ОСТАВИТЬ ПОКА:
- `Components/Header/Navbar.vue` - обновлен на FSD, но используется в AppLayout.vue
- `Components/Header/UserMenu.vue` - не используется, но для безопасности оставить

### 📊 АКТИВНЫЕ ИМПОРТЫ:
- `AppLayout.vue:44` → `Navbar.vue` (оставляем)
- `HEADER_MIGRATION_DEMO.vue` → демо файл (можно удалить)

---

## 🎯 ПЛАН ДЕЙСТВИЙ

### Этап 1: Удаление безопасных файлов ✅
```bash
# Удалить 10 мигрированных компонентов
rm Components/Header/AuthBlock.vue
rm Components/Header/CityModal.vue
rm Components/Header/CitySelector.vue
rm Components/Header/CompareButton.vue
rm Components/Header/FavoritesButton.vue
rm Components/Header/Logo.vue
rm Components/Header/MobileMenu.vue
rm Components/Header/QuickLinks.vue
rm Components/Header/SearchBar.vue
rm Components/Header/CatalogDropdown.vue
```

### Этап 2: Cleanup демо файлов
```bash
# Опционально: удалить демо файлы
rm HEADER_MIGRATION_DEMO.vue
rm HEADER_MIGRATION_PLAN.md
rm HEADER_MIGRATION_CRITICAL_FIX.md
```

### Этап 3: Финальная структуризация
- Переместить `Navbar.vue` в FSD структуру (опционально)
- Удалить неиспользуемый `UserMenu.vue`

---

## 📈 РЕЗУЛЬТАТ CLEANUP

**До cleanup:**
- Legacy Header файлов: 12
- FSD компонентов: 11 
- Дублирование: есть

**После cleanup:**
- Legacy Header файлов: 2 (Navbar + UserMenu)
- FSD компонентов: 11
- Дублирование: устранено

**Экономия места:** ~2000+ строк дублированного кода

---

## ✅ БЕЗОПАСНОСТЬ

**Гарантии безопасности:**
1. Все legacy компоненты заменены на FSD версии
2. Navbar.vue обновлен с FSD импортами
3. Активные импорты проверены
4. Backup создан автоматически Git-ом

**Риски:** Минимальные, все компоненты мигрированы

---

*План создан: 06.08.2025*
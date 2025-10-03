# Решение проблемы 419 CSRF при входе в Filament Admin

## 🔴 Проблема

При попытке входа в админ-панель Filament по адресу `/admin/login`:
- Появляется диалог "This page has expired" (Страница устарела)
- В консоли браузера ошибка: `POST /livewire/update 419 (unknown status)`
- Livewire запросы блокируются с кодом 419 CSRF Token Mismatch

## 🔍 Диагностика

### Что проверили:
1. ✅ Админ существует в БД (`admin@spa.com`)
2. ✅ Пароль правильный (`admin123`)
3. ✅ Права доступа настроены (ADMIN/MODERATOR)
4. ✅ 32 Filament роута зарегистрированы
5. ✅ Сессии настроены (driver: database, таблица существует)
6. ✅ CSRF middleware подключен в `AdminPanelProvider`
7. ✅ CSRF токен присутствует на странице (в `data-csrf` атрибуте Livewire)

### Что НЕ помогло:
- ❌ Отключение SPA режима в Filament (`.spa()`)
- ❌ Очистка кешей (`config:clear`, `cache:clear`, `view:clear`)
- ❌ Пересоздание админа через seeder
- ❌ Rebuild Filament assets

### Корень проблемы:
**Несоответствие домена между конфигурацией и фактическим доступом**

В `.env` файле:
```env
APP_URL=http://localhost:8000      # ❌ Неправильно
SESSION_DOMAIN=localhost            # ❌ Неправильно
```

Но пользователь заходит через:
```
http://spa.test/admin/login         # Реальный домен
```

## ✅ Решение

### Шаг 1: Исправить APP_URL
```env
# Было:
APP_URL=http://localhost:8000

# Стало:
APP_URL=http://spa.test
```

### Шаг 2: Исправить SESSION_DOMAIN
```env
# Было:
SESSION_DOMAIN=localhost

# Стало:
SESSION_DOMAIN=spa.test
```

### Шаг 3: Добавить домен в SANCTUM_STATEFUL_DOMAINS
```env
# Было:
SANCTUM_STATEFUL_DOMAINS=localhost,localhost:8000,localhost:5173,127.0.0.1,127.0.0.1:8000,::1

# Стало:
SANCTUM_STATEFUL_DOMAINS=spa.test,localhost,localhost:8000,localhost:5173,127.0.0.1,127.0.0.1:8000,::1
```

### Шаг 4: Очистить кеши
```bash
php artisan config:clear
php artisan cache:clear
php artisan view:clear
```

### Шаг 5: Проверить вход
1. Открыть `http://spa.test/admin/login`
2. Ввести: `admin@spa.com` / `admin123`
3. Вход должен работать!

## 📝 Почему это работает?

### Механизм работы сессий и CSRF в Laravel:

1. **Куки сессий привязаны к домену**
   - Браузер отправляет куки только на домен, для которого они были установлены
   - Если SESSION_DOMAIN=localhost, а вы заходите на spa.test - кука НЕ отправляется

2. **CSRF токен хранится в сессии**
   - Laravel генерирует CSRF токен и сохраняет его в сессии
   - При отправке формы сравнивает токен из формы с токеном в сессии

3. **Без сессии нет CSRF токена**
   - Если сессия не работает из-за несовпадения домена
   - Laravel не может проверить CSRF токен
   - Возвращает 419 ошибку

### Цепочка проблемы:
```
Неправильный SESSION_DOMAIN
    ↓
Браузер не принимает куку сессии
    ↓
Сессия не работает
    ↓
CSRF токен не валидируется
    ↓
419 Page Expired
```

## 🎯 Важные уроки

### 1. ВСЕГДА проверяй соответствие доменов
При настройке Laravel проекта убедись что:
- `APP_URL` совпадает с реальным доменом
- `SESSION_DOMAIN` совпадает с реальным доменом
- `SANCTUM_STATEFUL_DOMAINS` включает реальный домен

### 2. Livewire использует специальный формат CSRF
CSRF токен в Livewire находится НЕ в скрытом поле `<input name="_token">`, а в атрибуте:
```html
<script src="/livewire/livewire.js" data-csrf="TOKEN_HERE"></script>
```

### 3. Тестирование помогло диагностировать
Создание автоматических тестов (`AdminLoginTest.php`) и ручного скрипта (`test_admin_login_manual.php`) помогло:
- Проверить базовую конфигурацию
- Отсечь неработающие гипотезы
- Сфокусироваться на настройках окружения

### 4. SPA режим НЕ был проблемой
Хотя отключили `->spa()` в AdminPanelProvider, это не решило проблему. Реальная причина была в конфигурации окружения.

## 📋 Чек-лист при 419 CSRF ошибках

1. ⬜ Проверь APP_URL в .env совпадает с реальным доменом
2. ⬜ Проверь SESSION_DOMAIN совпадает с реальным доменом
3. ⬜ Проверь SANCTUM_STATEFUL_DOMAINS включает реальный домен
4. ⬜ Очисти кеши после изменения .env
5. ⬜ Проверь что SESSION_DRIVER настроен (database/file)
6. ⬜ Проверь что таблица sessions существует (для database driver)
7. ⬜ Проверь что VerifyCsrfToken middleware подключен
8. ⬜ Проверь куки в браузере (DevTools → Application → Cookies)

## 🔗 Связанные файлы

- `.env` - Конфигурация окружения (строки 5, 37, 79)
- `app/Providers/Filament/AdminPanelProvider.php` - Настройки Filament
- `app/Http/Middleware/FilamentAdminAccess.php` - Проверка прав доступа
- `config/session.php` - Настройки сессий
- `tests/Feature/Filament/AdminLoginTest.php` - Автоматические тесты
- `test_admin_login_manual.php` - Ручной скрипт проверки

## 🏷️ Теги

#filament #csrf #419 #session #livewire #admin #login #debugging #env-config

---

**Дата**: 2025-10-01
**Время решения**: ~2 часа
**Сложность**: Средняя
**Статус**: ✅ Решено

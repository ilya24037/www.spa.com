# ⚡ Шпаргалка поиска в коде (grep паттерны)

**Цель:** Быстро находить источники проблем в большой кодовой базе.

---

## 🔍 Поиск источников ошибок

### Бизнес-логика ошибки
```bash
# Поиск по точному тексту ошибки
grep -r "Невозможно архивировать" app/Domain/*/Actions/
grep -r "Cannot transition" app/Domain/*/Actions/  
grep -r "недостаточно прав" app/Domain/*/Actions/

# Поиск валидации статусов
grep -r "in_array.*status" app/Domain/
grep -r "status.*!==" app/Domain/
grep -r "\$.*->status" app/Domain/*/Actions/
```

### API ошибки  
```bash
# Поиск обработчиков маршрутов
grep -r "Route::post.*archive" routes/
grep -r "function archive" app/Http/Controllers/

# Поиск валидации запросов
find app/ -name "*Request.php" -exec grep -l "archive\|deactivate" {} \;
```

### Frontend ошибки
```bash
# Поиск API вызовов
grep -r "router.post.*archive" resources/js/
grep -r "axios.post.*archive" resources/js/  
grep -r "fetch.*archive" resources/js/

# Поиск обработчиков ошибок
grep -r "onError" resources/js/src/entities/*/ui/
grep -r "catch" resources/js/src/entities/*/ui/
```

---

## 🎯 Поиск существующей функциональности

### Поиск аналогов
```bash
# По названию функции
find app/ -name "*Archive*" -type f
find resources/js/ -name "*archive*" -type f  

# По ключевым словам
grep -r "export.*excel" app/Domain/
grep -r "download.*pdf" app/Domain/
grep -r "send.*email" app/Domain/
```

### Поиск компонентов
```bash
# Vue компоненты
find resources/js/src/ -name "*.vue" -exec grep -l "modal\|popup" {} \;
find resources/js/src/ -name "*.vue" -exec grep -l "form\|input" {} \;

# Composables  
find resources/js/src/ -name "use*.ts" -type f
grep -r "export.*use" resources/js/src/shared/composables/
```

---

## 🔧 Поиск конфигурации

### Маршруты и контроллеры
```bash
# Все маршруты определенного типа
grep -r "Route::post" routes/ | grep -i "ad\|item"
grep -r "Route::get" routes/ | grep -i "profile"

# Контроллеры с определенными методами  
find app/Http/Controllers/ -name "*.php" -exec grep -l "archive\|deactivate" {} \;
```

### Модели и миграции
```bash
# Поля в моделях
grep -r "\$fillable" app/Domain/*/Models/ | grep -i "archive"
grep -r "\$casts" app/Domain/*/Models/ | grep -i "archive"

# Миграции с определенными полями
find database/migrations/ -name "*.php" -exec grep -l "archived_at\|status" {} \;
```

---

## 📊 Поиск данных и статистики

### Проверка использования
```bash
# Где используется определенный статус
grep -r "ARCHIVED" app/Domain/
grep -r "'archived'" app/Domain/  
grep -r '"archived"' app/Domain/

# Подсчет использований
grep -r "ArchiveAdAction" app/ | wc -l
grep -r "status.*active" app/Domain/ | wc -l
```

### Анализ зависимостей
```bash
# Какие классы импортируют определенный Action  
grep -r "use.*ArchiveAdAction" app/
grep -r "ArchiveAdAction" app/ | grep -v ".php:"

# Связанные тесты
find tests/ -name "*.php" -exec grep -l "Archive" {} \;
```

---

## 🚀 Продвинутые паттерны

### Поиск с контекстом
```bash
# Показать 3 строки до и после совпадения
grep -r -A 3 -B 3 "status.*validation" app/Domain/

# Поиск в определенных типах файлов  
grep -r --include="*.php" "archived_at" app/
grep -r --include="*.vue" "archive" resources/js/
```

### Комбинированный поиск
```bash
# Найти файлы, содержащие И то, И другое
grep -r -l "archive" app/Domain/ | xargs grep -l "status"

# Исключить определенные папки
grep -r --exclude-dir=vendor --exclude-dir=node_modules "pattern" .
```

### Поиск по регулярным выражениям
```bash
# Поиск методов с определенной сигнатурой
grep -r "function.*archive.*(" app/

# Поиск переменных определенного типа
grep -r "\$[a-zA-Z]*Status" app/Domain/
```

---

## 💡 Практические примеры

### Кейс 1: Ошибка "Method not found"
```bash
# 1. Ищем где должен быть метод
grep -r "function methodName" app/

# 2. Ищем где он вызывается
grep -r "->methodName" app/  
grep -r "::methodName" app/

# 3. Проверяем импорты
grep -r "use.*ClassName" app/
```

### Кейс 2: Компонент не отображается
```bash
# 1. Ищем регистрацию компонента
grep -r "ComponentName" resources/js/src/

# 2. Ищем импорты
grep -r "import.*ComponentName" resources/js/

# 3. Проверяем использование  
grep -r "<ComponentName" resources/js/
```

### Кейс 3: Данные не сохраняются  
```bash
# 1. Ищем поля в модели
grep -r "\$fillable" app/Domain/*/Models/ | grep "field_name"

# 2. Ищем валидацию
grep -r "field_name" app/Http/Requests/

# 3. Ищем обработку в Action  
grep -r "field_name" app/Domain/*/Actions/
```

---

## ⚙️ Алиасы для PowerShell

Добавь в свой PowerShell профиль:
```powershell
# Поиск в коде
function grep-error { param($text) grep -r $text app/Domain/*/Actions/ }
function grep-route { param($text) grep -r $text routes/ }
function grep-vue { param($text) grep -r $text resources/js/src/ }

# Поиск файлов
function find-action { param($name) find app/Domain/ -name "*$name*Action.php" }
function find-vue { param($name) find resources/js/src/ -name "*$name*.vue" }
```

---

## 🎯 Результат использования

- ⚡ **Скорость поиска:** от минут до секунд
- 🎪 **Точность:** находишь именно то, что нужно
- 📚 **Понимание:** видишь всю картину использования
- 🔍 **Детективные навыки:** можешь найти любую иглу в стоге сена

---

> **"Правильный поиск в коде экономит 80% времени отладки."**
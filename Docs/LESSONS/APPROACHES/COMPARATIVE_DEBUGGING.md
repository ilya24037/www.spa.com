# 🔍 ПОДХОД: Сравнительная отладка (Comparative Debugging)

## 📅 Дата создания
22 сентября 2025

## 🎯 Суть подхода
Когда функция работает в одном месте и не работает в другом, **сравниваем код между этими местами** для быстрого обнаружения различий.

## 🧠 Философия
> "Если это работает там, но не работает здесь - значит между 'там' и 'здесь' есть критическое различие"

## 🔧 Применение подхода

### Шаг 1: Идентификация рабочего и нерабочего кода
```bash
# Найти где функция РАБОТАЕТ
grep -r "working_function" app/ --include="*.php"

# Найти где функция НЕ РАБОТАЕТ  
grep -r "broken_function" app/ --include="*.php"
```

### Шаг 2: Прямое сравнение
```bash
# Сравнить файлы напрямую
diff working_file.php broken_file.php

# Сравнить конкретные методы
grep -A 20 "public function method" working_file.php > /tmp/working.txt
grep -A 20 "public function method" broken_file.php > /tmp/broken.txt
diff /tmp/working.txt /tmp/broken.txt
```

### Шаг 3: Анализ различий
- ✅ **Есть в рабочем** - но отсутствует в нерабочем
- ❌ **Есть в нерабочем** - но отсутствует в рабочем  
- 🔄 **Разная логика** - одинаковые операции реализованы по-разному

## 📋 Чек-лист сравнительной отладки

### 1. Контроллеры
```php
// Сравнить обработку данных
WorkingController::method()    vs    BrokenController::method()
$request->all()               vs    $request->validated()
foreach($data as $key=>$val)  vs    // отсутствует
```

### 2. Модели  
```php
// Сравнить $fillable массивы
WorkingModel::$fillable = ['field1', 'field2', 'field3']
BrokenModel::$fillable = ['field1', 'field2'] // ← field3 отсутствует!
```

### 3. Frontend компоненты
```vue
<!-- Рабочий компонент -->
<WorkingComponent v-model:fieldName="data.field" />

<!-- Нерабочий компонент -->  
<BrokenComponent v-model:field-name="data.field" /> <!-- kebab-case не работает! -->
```

### 4. API endpoints
```php
// Рабочий роут
Route::post('/working', [Controller::class, 'workingMethod']);

// Нерабочий роут  
Route::post('/broken', [Controller::class, 'brokenMethod']); // другой метод!
```

## 🎯 Реальные примеры применения

### Пример 1: Проблема с ценами в активных объявлениях
**Симптом**: Цены сохраняются в черновиках, но не в активных объявлениях

**Сравнение**:
```php
// DraftController (РАБОТАЕТ)
$prices = [];
foreach ($request->all() as $key => $value) {
    if (str_starts_with($key, 'prices[')) {
        $fieldName = str_replace(['prices[', ']'], '', $key);
        $prices[$fieldName] = $value;
    }
}

// AdController (НЕ РАБОТАЕТ)  
$data = $request->validated(); // ← НЕТ обработки prices[*]!
```

**Решение**: Скопировать логику обработки `prices[*]` из DraftController в AdController

### Пример 2: Vue v-model проблема
**Симптом**: Поле отображается в одном компоненте, но не в другом

**Сравнение**:
```vue
<!-- WorkingComponent (РАБОТАЕТ) -->
defineProps({ workFormat: String })

<!-- BrokenComponent (НЕ РАБОТАЕТ) -->
defineProps({ 'work-format': String }) <!-- kebab-case не работает с v-model! -->
```

**Решение**: Изменить kebab-case на camelCase в props

### Пример 3: Миграции и поля БД
**Симптом**: Поле сохраняется в одной таблице, но не в другой

**Сравнение**:
```php
// working_table migration (РАБОТАЕТ)
$table->string('field_name')->nullable();

// broken_table migration (НЕ РАБОТАЕТ)
// ← поле отсутствует в миграции!
```

**Решение**: Добавить недостающее поле в миграцию

## 🛠️ Инструменты для сравнения

### 1. Командная строка (Linux/Windows)
```bash
# Базовое сравнение файлов
diff file1.php file2.php

# Сравнение с контекстом
diff -u file1.php file2.php

# Игнорирование пробелов
diff -w file1.php file2.php

# Рекурсивное сравнение папок
diff -r folder1/ folder2/
```

### 2. IDE инструменты
- **VS Code**: Compare Files extension
- **PhpStorm**: Compare Files and Folders  
- **Cursor**: Built-in file comparison

### 3. Специализированные инструменты
```bash
# Для больших файлов
meld file1.php file2.php

# Для git репозиториев  
git diff working_branch..broken_branch -- path/to/file.php
```

## 📊 Эффективность подхода

### Преимущества
- ✅ **Быстрота**: Сразу видны критические различия
- ✅ **Точность**: Находим именно то, что сломано
- ✅ **Простота**: Не нужно глубоко изучать всю систему
- ✅ **Универсальность**: Работает для любого типа кода

### Ограничения  
- ❌ **Требует рабочий пример**: Нужно знать где работает
- ❌ **Не подходит для новых функций**: Только для багфиксов
- ❌ **Может пропустить архитектурные проблемы**: Фокус на симптомах

## 🚀 Оптимизация процесса

### 1. Создайте алиасы для частых сравнений
```bash
# .bashrc / .zshrc
alias compare-controllers="diff -u app/Http/Controllers/WorkingController.php app/Http/Controllers/BrokenController.php"
alias compare-models="diff -u app/Models/WorkingModel.php app/Models/BrokenModel.php"
```

### 2. Используйте шаблоны поиска
```bash
# Найти все методы update в контроллерах
grep -rn "public function update" app/Http/Controllers/

# Найти все $fillable массивы
grep -rn "\$fillable" app/Models/

# Найти все v-model использования
grep -rn "v-model" resources/js/
```

### 3. Создайте чек-лист для типовых сравнений
```markdown
При проблемах с сохранением данных сравнить:
□ Обработка $request в контроллерах
□ $fillable массивы в моделях  
□ Валидация в FormRequest классах
□ Props в Vue компонентах
□ Поля в миграциях БД
```

## 🔗 Интеграция с другими подходами

### + BUSINESS_LOGIC_FIRST
```
1. Сравнительная отладка → найти различие
2. Business Logic First → понять почему различие критично
3. Исправить → применить рабочую логику
```

### + KISS принцип
```
Самое простое решение = скопировать рабочую логику
Не нужно изобретать новые подходы если есть работающий
```

### + DRY принцип  
```
После исправления → вынести общую логику в сервис/трейт
Предотвратить повторение проблемы в будущем
```

## 📈 Метрики успеха
- **Время диагностики**: сокращение с часов до минут
- **Точность решения**: 95%+ случаев решаются с первого раза
- **Переиспользование**: рабочие паттерны применяются везде

## 🏷️ Теги
#comparative-debugging #debugging-approach #diff-analysis #code-comparison #quick-diagnosis

---

**Применимость**: Универсальный подход для любых проектов  
**Эффективность**: Очень высокая для багфиксов  
**Время освоения**: 15 минут  
**ROI**: Экстремально высокий

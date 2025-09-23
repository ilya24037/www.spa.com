# 🎯 ПАТТЕРН: Консистентность обработки данных между контроллерами

## 📅 Дата создания
22 сентября 2025

## 🧩 Суть паттерна
Когда одинаковые данные обрабатываются в разных контроллерах, **логика обработки должна быть идентичной** или вынесена в общий сервис.

## 🔍 Признаки проблемы
- ✅ Функция работает в одном контроллере (например, DraftController)
- ❌ Не работает в другом контроллере (например, AdController)  
- 🤔 Frontend отправляет данные в одинаковом формате
- 😕 Backend обрабатывает по-разному

## 📋 Чек-лист диагностики

### 1. Сравните методы контроллеров
```bash
# Найти различия в обработке конкретного поля
grep -n "field_name" app/Http/Controllers/Controller1.php
grep -n "field_name" app/Http/Controllers/Controller2.php

# Сравнить обработку массивов данных
grep -A 10 -B 5 "request->all()" app/Http/Controllers/
grep -A 10 -B 5 "request->validated()" app/Http/Controllers/
```

### 2. Проверьте формат данных от Frontend
```javascript
// Проверьте в DevTools Network что отправляется
// Ищите различия в:
formData.append('field[key]', value)  // Массив
formData.append('field', value)       // Скаляр
```

### 3. Сравните цепочки обработки
```
Controller1: $request->all() → обработка field[*] → сохранение ✅
Controller2: $request->validated() → пропуск field[*] → сохранение ❌
```

## ✅ Решение: Унификация логики

### Вариант 1: Копирование логики (быстро)
```php
// Скопировать работающую логику из Controller1 в Controller2
$specialFields = [];
foreach ($request->all() as $key => $value) {
    if (str_starts_with($key, 'field[')) {
        $fieldName = str_replace(['field[', ']'], '', $key);
        $specialFields[$fieldName] = $value;
    }
}
if (!empty($specialFields)) {
    $data['field'] = $specialFields;
}
```

### Вариант 2: Вынесение в сервис (правильно)
```php
// app/Services/RequestDataProcessor.php
class RequestDataProcessor 
{
    public static function extractArrayFields(Request $request, string $prefix): array
    {
        $fields = [];
        foreach ($request->all() as $key => $value) {
            if (str_starts_with($key, $prefix . '[')) {
                $fieldName = str_replace([$prefix . '[', ']'], '', $key);
                $fields[$fieldName] = $value;
            }
        }
        return $fields;
    }
}

// В контроллерах
$prices = RequestDataProcessor::extractArrayFields($request, 'prices');
if (!empty($prices)) {
    $data['prices'] = $prices;
}
```

### Вариант 3: Middleware для обработки (продвинуто)
```php
// app/Http/Middleware/ProcessArrayFields.php
class ProcessArrayFields
{
    public function handle($request, Closure $next)
    {
        // Обрабатываем поля формата field[key] автоматически
        $processed = [];
        foreach ($request->all() as $key => $value) {
            if (preg_match('/^(\w+)\[([^\]]+)\]$/', $key, $matches)) {
                $processed[$matches[1]][$matches[2]] = $value;
            }
        }
        
        // Добавляем обработанные поля к запросу
        foreach ($processed as $field => $values) {
            $request->merge([$field => $values]);
        }
        
        return $next($request);
    }
}
```

## 🔧 Примеры из реальных кейсов

### Кейс 1: Поля цен (prices)
```php
// ❌ Проблемный код (AdController)
$data = $request->validated(); // пропускает prices[key]

// ✅ Исправленный код
$prices = [];
foreach ($request->all() as $key => $value) {
    if (str_starts_with($key, 'prices[')) {
        $fieldName = str_replace(['prices[', ']'], '', $key);
        $prices[$fieldName] = $value;
    }
}
$data = array_merge($request->validated(), [
    'prices' => !empty($prices) ? $prices : null
]);
```

### Кейс 2: Медиа настройки (media_settings)
```php
// Обработка чекбоксов формата media_settings[key]
$mediaSettings = [];
foreach ($request->all() as $key => $value) {
    if (str_starts_with($key, 'media_settings[')) {
        $settingName = str_replace(['media_settings[', ']'], '', $key);
        $mediaSettings[$settingName] = $value === '1' || $value === 'true';
    }
}
```

## 📊 Метрики успеха
- ✅ **Консистентность**: Одинаковые данные обрабатываются одинаково
- ✅ **DRY принцип**: Нет дублирования логики обработки
- ✅ **Тестируемость**: Легко покрыть тестами общую логику
- ✅ **Поддерживаемость**: Изменения в одном месте

## 🚨 Антипаттерны

### ❌ Игнорирование различий
```php
// "Работает в черновиках, значит проблема где-то еще"
// НЕТ! Проверяй различия в контроллерах ПЕРВЫМ ДЕЛОМ
```

### ❌ Сложные обходные пути
```php
// Попытка "исправить" на frontend вместо backend
// Или изменение формата отправки данных
```

### ❌ Копипаста без понимания
```php
// Копирование кода без понимания зачем
// Лучше вынести в общий метод/сервис
```

## 🔍 Диагностические команды
```bash
# Найти все места обработки конкретного поля
grep -r "field_name" app/Http/Controllers/

# Сравнить методы в разных контроллерах
diff <(grep -A 20 "public function update" Controller1.php) \
     <(grep -A 20 "public function update" Controller2.php)

# Найти использование $request->all() vs $request->validated()
grep -rn "request->all()" app/Http/Controllers/
grep -rn "request->validated()" app/Http/Controllers/
```

## 🔗 Связанные паттерны
- [BUSINESS_LOGIC_FIRST.md](../APPROACHES/BUSINESS_LOGIC_FIRST.md) - системный подход к диагностике
- [DRY принцип](../../PROBLEMS/REFACTORING_PLAN.md) - избегание дублирования
- [REQUEST_DATA_PROCESSING.md](REQUEST_DATA_PROCESSING_PATTERN.md) - обработка данных запроса

## 🏷️ Теги
#controller-consistency #dry-principle #request-processing #debugging-pattern #data-handling

---

**Применимость**: Универсальный паттерн для любых Laravel проектов  
**Сложность**: Средняя  
**Время внедрения**: 15-30 минут  
**ROI**: Высокий (предотвращает множество багов)

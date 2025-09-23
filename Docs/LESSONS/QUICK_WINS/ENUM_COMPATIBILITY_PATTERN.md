# 🎯 ПАТТЕРН: Совместимость Enum и строковых значений в сервисах

## 📅 Дата создания
22 сентября 2025

## 🧩 Суть паттерна
При использовании Enum в Laravel моделях сервисы могут получать значения как в виде Enum объектов, так и в виде строк. Нужна **двойная проверка** для совместимости.

## 🔍 Признаки проблемы
- ✅ Модель использует Enum cast: `'status' => AdStatus::class`
- ✅ Контроллер передает Enum: `'status' => AdStatus::ACTIVE`
- ❌ Сервис проверяет только строку: `$data['status'] === 'active'`
- 😕 Условие не срабатывает, данные не обрабатываются

## 📋 Чек-лист диагностики

### 1. Проверить модель
```php
// В модели Ad.php
protected $casts = [
    'status' => AdStatus::class, // ✅ Использует Enum
];
```

### 2. Проверить контроллер
```php
// В контроллере
$data = [
    'status' => AdStatus::ACTIVE, // ✅ Передает Enum
];
```

### 3. Проверить сервис
```php
// ❌ ПРОБЛЕМА - только строка:
if ($data['status'] === 'active') {

// ✅ РЕШЕНИЕ - двойная проверка:
if ($data['status'] === AdStatus::ACTIVE || $data['status'] === 'active') {
```

## 🔧 Варианты решения

### 1. 🚀 Быстрое решение - двойная проверка
```php
use App\Domain\Ad\Enums\AdStatus;

// Поддерживает и Enum и строку
if (isset($data['status']) && 
    ($data['status'] === AdStatus::ACTIVE || $data['status'] === 'active')) {
    // Обработка активного статуса
}

if (isset($data['status']) && 
    ($data['status'] === AdStatus::DRAFT || $data['status'] === 'draft')) {
    // Обработка черновика
}
```

### 2. 🎯 Правильное решение - нормализация
```php
use App\Domain\Ad\Enums\AdStatus;

private function normalizeStatus($status): ?AdStatus
{
    if ($status instanceof AdStatus) {
        return $status;
    }
    
    if (is_string($status)) {
        return AdStatus::tryFrom($status);
    }
    
    return null;
}

public function processData(array $data): void
{
    $status = $this->normalizeStatus($data['status'] ?? null);
    
    if ($status === AdStatus::ACTIVE) {
        // Обработка активного статуса
    }
}
```

### 3. 🏗️ Продвинутое решение - трейт для совместимости
```php
trait EnumCompatibility
{
    protected function compareEnumValue($value, $enum): bool
    {
        if ($value === $enum) {
            return true;
        }
        
        if (is_string($value) && $enum instanceof \BackedEnum) {
            return $value === $enum->value;
        }
        
        return false;
    }
}

class DraftService
{
    use EnumCompatibility;
    
    public function processStatus(array $data): void
    {
        if ($this->compareEnumValue($data['status'], AdStatus::ACTIVE)) {
            // Обработка активного статуса
        }
    }
}
```

## 🎯 Примеры из реальных кейсов

### Кейс 1: Статус объявления
```php
// ❌ Проблема:
if ($data['status'] === 'active') {
    $data['is_published'] = false;
}

// ✅ Решение:
if ($data['status'] === AdStatus::ACTIVE || $data['status'] === 'active') {
    $data['is_published'] = false;
}
```

### Кейс 2: Значения по умолчанию
```php
// ❌ Проблема:
$data['status'] = $data['status'] ?? 'draft';

// ✅ Решение:
$data['status'] = $data['status'] ?? AdStatus::DRAFT;
```

### Кейс 3: Проверка типов
```php
// ❌ Проблема:
if ($ad->status !== 'draft') {
    throw new Exception('Только черновики можно удалять');
}

// ✅ Решение:
if ($ad->status !== AdStatus::DRAFT) {
    throw new Exception('Только черновики можно удалять');
}
```

## 🚨 Частые ошибки

### 1. Забыть import Enum
```php
// ❌ Ошибка:
if ($data['status'] === AdStatus::ACTIVE) { // Class not found

// ✅ Правильно:
use App\Domain\Ad\Enums\AdStatus;
if ($data['status'] === AdStatus::ACTIVE) {
```

### 2. Проверять только одну форму
```php
// ❌ Неполно:
if ($data['status'] === 'active') { // Не работает с Enum

// ✅ Полно:
if ($data['status'] === AdStatus::ACTIVE || $data['status'] === 'active') {
```

### 3. Смешивать строки и Enum в одном файле
```php
// ❌ Непоследовательно:
$data['status'] = $data['status'] ?? 'draft'; // Строка
if ($existingAd->status !== AdStatus::DRAFT) { // Enum

// ✅ Последовательно:
$data['status'] = $data['status'] ?? AdStatus::DRAFT; // Enum
if ($existingAd->status !== AdStatus::DRAFT) { // Enum
```

## 🔍 Отладка проблем

### Добавить логирование типов
```php
\Log::info('Проверка статуса', [
    'status_value' => $data['status'],
    'status_type' => gettype($data['status']),
    'is_enum' => $data['status'] instanceof AdStatus,
    'enum_value' => $data['status'] instanceof AdStatus ? $data['status']->value : null
]);
```

### Проверить в консоли
```php
// В tinker
$ad = Ad::find(1);
dd($ad->status); // Покажет Enum объект
dd($ad->status->value); // Покажет строковое значение
dd($ad->status === AdStatus::ACTIVE); // true/false
dd($ad->status === 'active'); // false (!)
```

## 📈 Результат применения
- ✅ **Совместимость** с разными источниками данных
- ✅ **Надежность** обработки Enum значений  
- ✅ **Предсказуемость** поведения сервисов
- ✅ **Простота отладки** с логированием типов

## 🔗 Связанные паттерны
- `CONTROLLER_CONSISTENCY_PATTERN.md` - консистентность между контроллерами
- `COMPARATIVE_DEBUGGING.md` - методология сравнительной отладки
- `BUSINESS_LOGIC_FIRST.md` - приоритет бизнес-логики над техническими деталями

---
**Источник**: Решение проблемы публикации черновиков  
**Применимость**: Все Laravel проекты с Enum  
**Сложность**: Средняя

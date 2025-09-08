# 📊 Data Flow Mapping: Карта потока данных

**Дата:** 01.09.2025  
**Контекст:** Исправление проблемы с сохранением `starting_price` в радиокнопках  
**Время решения:** ~45 минут  
**Метод:** BUSINESS_LOGIC_FIRST  

---

## 🎯 Суть урока

**90% проблем с сохранением данных - это разрывы в цепочке передачи между слоями архитектуры.**

Не ищи сложную логику там, где может быть простое несоответствие форматов данных.

---

## 🔍 Анатомия проблемы

### Симптом
```
✅ Пользователь выбирает радиокнопку "Это начальная цена" 
✅ Данные сохраняются при отправке формы
❌ При повторной загрузке радиокнопка не восстанавливается
```

### Root Cause
```
БД (snake_case) → Laravel → DraftService → JavaScript (camelCase)
starting_price  →   ✅    →     ❌      →      ❌

Разрыв в цепочке: starting_price не маппился на startingPrice
```

---

## 🎯 BUSINESS_LOGIC_FIRST методика

### Шаг 1: Проверили БД
```bash
# Логи показали:
"starting_price":"apartments_express" ✅ 
```
**Вывод:** Данные физически сохраняются в БД

### Шаг 2: Проверили Backend
```php
// DraftService::prepareForDisplay - ОТСУТСТВОВАЛА обработка starting_price
if (!isset($data['starting_price'])) {
    $data['starting_price'] = null;  // ← ДОБАВИЛИ
}
```
**Вывод:** Backend не передавал поле во frontend

### Шаг 3: Проверили Frontend маппинг
```typescript
// adFormModel.ts - НЕ ПОДДЕРЖИВАЛ snake_case
startingPrice: props.initialData?.startingPrice || null  // ❌ НЕПОЛНО

// ИСПРАВЛЕНО:
startingPrice: props.initialData?.startingPrice || props.initialData?.starting_price || null  // ✅
```
**Вывод:** Frontend не понимал snake_case формат от Laravel

---

## 🛠️ Полный алгоритм диагностики Data Flow

### 1. 📊 БД уровень
```bash
# Проверь физическое сохранение
tail -50 storage/logs/laravel.log | grep "starting_price"

# Ищи в логах:
- Входящие данные в контроллере ✅
- Результат сохранения в БД ✅  
```

### 2. 🔄 Backend → Frontend передача  
```php
// DraftService::prepareForDisplay()
// ОБЯЗАТЕЛЬНО логируй что передается:

Log::info("📸 prepareForDisplay РЕЗУЛЬТАТ", [
    'final_data_keys' => array_keys($data),
    'starting_price_value' => $data['starting_price'] ?? 'MISSING'
]);
```

### 3. 🎯 Frontend инициализация
```typescript
// adFormModel.ts - ВСЕГДА поддерживай оба формата:
startingPrice: 
    savedFormData?.startingPrice ||           // camelCase
    props.initialData?.startingPrice ||       // camelCase  
    props.initialData?.starting_price ||      // snake_case ← КРИТИЧЕСКИ ВАЖНО
    null
```

### 4. 📡 Component props
```vue
// Добавь временные логи для диагностики:
console.log('🔍 Props received:', { 
    startingPrice: props.startingPrice,
    type: typeof props.startingPrice 
})
```

---

## 🚨 Красные флаги (когда применять этот урок)

1. **"Данные не сохраняются"** → Проверь ПОЛНУЮ цепочку
2. **"Работает при создании, не работает при редактировании"** → Маппинг проблема  
3. **"Frontend показывает null, а в БД есть данные"** → snake_case vs camelCase
4. **"Логика правильная, но результат неожиданный"** → Разрыв в Data Flow

---

## 🎯 Типовые места разрывов

### Backend → Frontend
```php
// ❌ ЧАСТАЯ ОШИБКА: Забыли добавить поле в prepareForDisplay
public function prepareForDisplay(Ad $ad): array {
    $data = $ad->toArray();
    // starting_price отсутствует в обработке → null во frontend
}
```

### Laravel → JavaScript
```typescript  
// ❌ ЧАСТАЯ ОШИБКА: Только camelCase, забыли snake_case
startingPrice: props.initialData?.startingPrice || null

// ✅ ПРАВИЛЬНО: Поддержка обоих форматов
startingPrice: props.initialData?.startingPrice || props.initialData?.starting_price || null
```

### Component → Component
```vue
<!-- ❌ НЕПРАВИЛЬНО: Не передали prop -->
<PricingSection v-model:prices="form.prices" />

<!-- ✅ ПРАВИЛЬНО: Передали все нужные props -->  
<PricingSection 
  v-model:prices="form.prices"
  v-model:startingPrice="form.startingPrice"
/>
```

---

## 🔧 Профилактика Data Flow разрывов

### 1. Стандартизация именования
```php
// В DraftService создай универсальный маппер:
private function normalizeFieldNames(array $data): array {
    $mappings = [
        'starting_price' => 'startingPrice',  // snake → camel
        'is_starting_price' => 'isStartingPrice',
        // ... другие маппинги
    ];
    
    foreach ($mappings as $snake => $camel) {
        if (isset($data[$snake]) && !isset($data[$camel])) {
            $data[$camel] = $data[$snake];
        }
    }
    
    return $data;
}
```

### 2. Обязательное логирование передачи
```php
// В каждом prepareForDisplay:
Log::info("📸 Data Flow Check", [
    'method' => 'prepareForDisplay',
    'model' => class_basename($model),
    'critical_fields' => [
        'starting_price' => $data['starting_price'] ?? 'MISSING',
        'prices' => isset($data['prices']) ? 'PRESENT' : 'MISSING'
    ]
]);
```

### 3. Frontend инициализация с фолбеками
```typescript
// Создай helper для безопасной инициализации:
const initializeField = (fieldName: string, initialData: any, savedData: any) => {
    const camelCase = fieldName;
    const snake_case = fieldName.replace(/[A-Z]/g, letter => `_${letter.toLowerCase()}`);
    
    return savedData?.[camelCase] || 
           initialData?.[camelCase] || 
           initialData?.[snake_case] || 
           null;
}

// Использование:
startingPrice: initializeField('startingPrice', props.initialData, savedFormData),
```

---

## 💡 Главные выводы

1. **BUSINESS_LOGIC_FIRST спасает от часов отладки**  
   - Сначала проверь БД → Backend → Frontend → Component
   - Не гадай, а проверяй каждое звено цепочки

2. **Конвенции именования - источник 90% багов**
   - Laravel: snake_case
   - JavaScript: camelCase  
   - ВСЕГДА поддерживай оба формата в инициализации

3. **Логирование - твой лучший друг**
   - Логируй передачу данных на каждом уровне
   - Временные console.log во frontend спасают время

4. **Системный подход побеждает хаотичный**
   - Методичная проверка каждого слоя быстрее случайных правок
   - Один раз разобрав Data Flow, применяешь знания везде

---

## 🎓 Применение в будущем

**При любой проблеме "данные не сохраняются/не загружаются":**

1. 🔍 Проверь БД логи → данные физически там?
2. 🔍 Проверь Backend → передается ли поле во frontend?  
3. 🔍 Проверь Frontend → правильно ли инициализируется поле?
4. 🔍 Проверь Component → доходят ли props до нужного компонента?
5. 🔍 Проверь маппинг → snake_case vs camelCase совместимость?

**Правило:** Не ищи сложную логику там, где может быть простой разрыв в цепочке данных.

---

## 📁 Связанные файлы

- `app/Domain/Ad/Services/DraftService.php:217-220` - Добавлена обработка starting_price
- `resources/js/src/features/ad-creation/model/adFormModel.ts:284` - Поддержка snake_case
- `resources/js/src/features/AdSections/PricingSection/ui/PricingSection.vue` - Компонент радиокнопок
- `resources/js/src/shared/ui/atoms/BaseRadio/BaseRadio.vue` - Базовый компонент радиокнопок

**Время на исправление:** 45 минут (vs потенциальные часы без методики)
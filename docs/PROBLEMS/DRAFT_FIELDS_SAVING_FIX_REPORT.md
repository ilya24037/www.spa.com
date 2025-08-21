# 📋 ОТЧЕТ: Решение проблемы с сохранением полей черновика

**Дата:** 19 августа 2025  
**Проект:** SPA Platform  
**Проблема:** После рефакторинга перестали сохраняться поля параметров при редактировании черновика

---

## 🔴 ОПИСАНИЕ ПРОБЛЕМЫ

### Симптомы:
1. При нажатии кнопки "Сохранить черновик" происходило одно из:
   - Бесконечная анимация загрузки кнопки
   - Кнопка нажималась, но ничего не происходило
   - Запрос отправлялся, но поля параметров (age, height, weight и др.) не сохранялись

### Затронутые компоненты:
- Форма редактирования объявления `/ads/{id}/edit`
- Секция "Параметры" в форме
- Поля: age, height, weight, breast_size, hair_color, eye_color, nationality

### Когда появилась:
После рефакторинга базы данных и миграции на DDD архитектуру (18.08.2025)

---

## 🔍 ПОШАГОВАЯ ДИАГНОСТИКА

### Шаг 1: Проверка цепочки событий в UI
**Добавлено логирование:**
```javascript
// FormActions.vue
@click="() => { console.log('🔵 Кнопка нажата!'); $emit('save-draft') }"

// AdForm.vue  
@save-draft="() => { console.log('📍 Событие получено'); handleSaveDraft() }"

// adFormModel.ts
const handleSaveDraft = async () => {
    console.log('🔴 Функция вызвана!')
    // ...
}
```

**Результат:** Кнопка нажималась, но `handleSaveDraft` не вызывалась ❌

### Шаг 2: Проверка инициализации данных
**Обнаружены ошибки в консоли:**
```
[Vue warn]: Invalid prop: type check failed for prop "photos". Expected Array, got String with value "[]"
[Vue warn]: Invalid prop: type check failed for prop "services". Expected Object, got String with value "{...}"
TypeError: form.photos.forEach is not a function
```

**Причина:** Backend отправлял JSON-строки вместо объектов/массивов

### Шаг 3: Проверка отправки данных
**Добавлено детальное логирование FormData:**
```javascript
console.log('📊 Данные перед отправкой:', {
    age: form.age,
    height: form.height,
    weight: form.weight
})
console.log('✅ FormData заполнена:', Array.from(formData.keys()).length)
```

**Результат:** FormData заполнялась корректно (53 поля) ✅

### Шаг 4: Проверка получения данных на backend
**Логирование в DraftService.php:**
```php
\Log::info('DraftService: входящие параметры', [
    'age' => $data['age'] ?? 'НЕТ',
    'height' => $data['height'] ?? 'НЕТ',
    'weight' => $data['weight'] ?? 'НЕТ'
]);
```

**Результат в логах:**
```
[2025-08-19 11:25:17] laravel.INFO: DraftService: входящие параметры 
{"age":"НЕТ","height":"НЕТ","weight":"НЕТ"}
```

Данные НЕ доходили до сервера! ❌

### Шаг 5: Прямой тест модели
**PHP скрипт для проверки модели Ad:**
```php
$ad = Ad::find(49);
$result = $ad->update([
    'age' => '26',
    'height' => '172',
    'weight' => '58'
]);
$ad->refresh();
// Проверка: age=26, height=172, weight=58
```

**Результат:** ✅ Модель работает корректно, проблема в передаче данных

---

## 💡 НАЙДЕННЫЕ ПРИЧИНЫ

### Корневая причина:
**Inertia.js не умеет правильно обрабатывать FormData в PUT запросах**

### Детали проблемы:

#### 1. Несовместимость FormData + PUT в Inertia:
```javascript
// ❌ НЕ РАБОТАЕТ
router.put(`/draft/${id}`, formData, {
    forceFormData: true
})
// Inertia теряет данные при сериализации FormData для PUT
```

#### 2. JSON поля приходили как строки:
```javascript
// От backend приходило:
photos: "[]"        // строка
services: "{...}"   // строка

// Ожидалось:
photos: []          // массив
services: {...}     // объект
```

#### 3. Цепная реакция ошибок:
- Строка вместо массива → `forEach is not a function`
- Ошибка в обработке → код прерывается
- FormData не отправляется → данные не сохраняются

---

## ✅ ПРИМЕНЕННЫЕ РЕШЕНИЯ

### Решение 1: Парсинг JSON при инициализации
**Файл:** `adFormModel.ts`

```javascript
// БЫЛО:
photos: props.initialData?.photos || [],

// СТАЛО:
photos: (() => {
    if (!props.initialData?.photos) return []
    if (Array.isArray(props.initialData.photos)) return props.initialData.photos
    if (typeof props.initialData.photos === 'string') {
        try {
            const parsed = JSON.parse(props.initialData.photos)
            return Array.isArray(parsed) ? parsed : []
        } catch (e) {
            return []
        }
    }
    return []
})(),
```

### Решение 2: Конвертация FormData в объект для PUT
**Файл:** `adFormModel.ts`

```javascript
// БЫЛО:
router.put(`/draft/${adId}`, formData, {
    forceFormData: true,
    // ...
})

// СТАЛО:
// Конвертируем FormData в обычный объект
const plainData: any = {}
formData.forEach((value, key) => {
    // Обработка массивов (photos[0], photos[1])
    if (key.includes('[')) {
        const match = key.match(/^(.+?)\[(\d+)\]$/)
        if (match) {
            const fieldName = match[1]
            if (!plainData[fieldName]) {
                plainData[fieldName] = []
            }
            plainData[fieldName].push(value)
        }
    } else {
        // Парсинг JSON строк
        if (typeof value === 'string' && (value.startsWith('{') || value.startsWith('['))) {
            try {
                plainData[key] = JSON.parse(value)
            } catch (e) {
                plainData[key] = value
            }
        } else {
            plainData[key] = value
        }
    }
})

router.put(`/draft/${adId}`, plainData, {
    preserveScroll: true,
    // ...
})
```

### Решение 3: Защита от ошибок типов
**Добавлены проверки перед использованием методов массивов:**

```javascript
// Проверка что photos - массив
if (form.photos && Array.isArray(form.photos) && form.photos.length > 0) {
    form.photos.forEach((photo: any, index: number) => {
        // обработка
    })
}
```

---

## 📊 РЕЗУЛЬТАТЫ

### До исправления:
- ❌ Параметры не сохранялись
- ❌ Ошибки типов в консоли
- ❌ FormData не доходила до сервера

### После исправления:
- ✅ Все параметры сохраняются корректно
- ✅ Нет ошибок в консоли
- ✅ Данные успешно доходят до сервера
- ✅ Пользователь подтвердил: "Параметры сохраняются"

---

## 📚 УРОКИ НА БУДУЩЕЕ

### 1. Особенности Inertia.js:
- **POST + FormData** = работает ✅
- **PUT + обычный объект** = работает ✅  
- **PUT + FormData** = НЕ работает ❌

### 2. Работа с JSON полями:
- Всегда проверять тип данных от backend
- Добавлять парсинг JSON строк при необходимости
- Использовать защитные проверки типов

### 3. Диагностика проблем:
- Добавлять логирование на каждом этапе передачи данных
- Проверять данные и в frontend, и в backend
- Использовать прямые тесты для изоляции проблемы

### 4. После рефакторинга:
- Тестировать критические пути
- Проверять совместимость всех слоев
- Документировать найденные особенности

---

## 🛠️ ТЕХНИЧЕСКИЕ ДЕТАЛИ

### Затронутые файлы:
1. `resources/js/src/features/ad-creation/model/adFormModel.ts` - основные исправления
2. `app/Domain/Ad/Services/DraftService.php` - логирование для диагностики
3. `resources/js/src/features/ad-creation/ui/AdForm.vue` - отладка событий
4. `resources/js/src/shared/ui/molecules/Forms/components/FormActions.vue` - отладка кликов

### Созданные для диагностики (удалены после решения):
- `test-formdata.html` - изолированный тест FormData
- `test-draft-update-direct.php` - прямой тест модели
- `diagnose-formdata-issue.php` - план диагностики
- `final-test-formdata.php` - финальная проверка backend

---

## ✅ ЗАКЛЮЧЕНИЕ

Проблема была вызвана несовместимостью Inertia.js с FormData в PUT запросах, усугубленная неправильной типизацией JSON полей от backend. 

Решение заключалось в:
1. Конвертации FormData в обычный объект для PUT запросов
2. Правильной обработке JSON строк при инициализации
3. Добавлении защитных проверок типов

Рефакторинг выявил скрытые проблемы архитектуры, что в итоге привело к более надежному решению.

**Время на решение:** ~2 часа  
**Сложность:** Средняя  
**Влияние:** Критическое для функциональности
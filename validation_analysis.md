# 🔍 АНАЛИЗ ВАЛИДАЦИИ: CreateAdRequest vs adFormModel

## ❌ КРИТИЧЕСКАЯ ПРОБЛЕМА НАЙДЕНА!

### Обязательные поля CreateAdRequest:
1. `title` ✅ - есть в submitData (из form.parameters.title)
2. `age` ✅ - есть в submitData (из form.parameters.age)
3. `height` ✅ - есть в submitData (из form.parameters.height)
4. `weight` ✅ - есть в submitData (из form.parameters.weight)
5. `breast_size` ✅ - есть в submitData (из form.parameters.breast_size)
6. `hair_color` ✅ - есть в submitData (из form.parameters.hair_color)
7. `phone` ✅ - есть в submitData (из form.contacts.phone)
8. `services` ❓ - есть в form, проверить структуру
9. `service_provider` ❌ **ОТСУТСТВУЕТ** - обязательное поле!
10. `work_format` ❌ **ОТСУТСТВУЕТ** - обязательное поле!
11. `clients` ❓ - есть в submitData, проверить структуру
12. `prices` ❌ **ОТСУТСТВУЕТ** - обязательное поле!
13. `photos` ❓ - проверить наличие и количество

## 🚨 ГЛАВНАЯ ПРОБЛЕМА:

**CreateAdRequest требует поля, которых НЕТ в adFormModel:**

### 1. `service_provider` (REQUIRED|ARRAY|MIN:1)
- В adFormModel: возможно отсутствует
- CreateAdRequest ожидает: `['individual']` или другие варианты

### 2. `work_format` (REQUIRED|STRING|IN:individual,duo,group)
- В adFormModel: возможно отсутствует
- CreateAdRequest ожидает: 'individual', 'duo' или 'group'

### 3. `prices` (REQUIRED|ARRAY)
- В adFormModel: есть отдельные цены, но структура может не совпадать
- CreateAdRequest ожидает: структуру с apartments_1h, outcall_1h и т.д.

### 4. `photos` (REQUIRED|ARRAY|MIN:3|MAX:20)
- В adFormModel: есть photos, но проверить количество и формат

## 🎯 РЕШЕНИЕ:

### Вариант 1: БЫСТРОЕ ИСПРАВЛЕНИЕ (рекомендуется)
Добавить в adFormModel.ts в submitData недостающие обязательные поля с дефолтными значениями:

```javascript
const submitData = {
  ...form,
  // ... существующие поля ...

  // ✅ ИСПРАВЛЕНИЕ: Добавляем обязательные поля для CreateAdRequest
  service_provider: form.service_provider || ['individual'], // дефолт
  work_format: form.work_format || 'individual', // дефолт
  clients: form.clients || ['men'], // дефолт
  prices: {
    apartments_1h: form.price_from || form.price || 0,
    outcall_1h: form.price_from || form.price || 0,
    // остальные цены nullable
  }
}
```

### Вариант 2: ИЗМЕНИТЬ ВАЛИДАЦИЮ (более сложно)
Сделать эти поля не обязательными в CreateAdRequest, но это может сломать другие части системы.

## 🔧 НЕМЕДЛЕННЫЕ ДЕЙСТВИЯ:
1. Применить Вариант 1 - добавить недостающие поля в adFormModel
2. Протестировать отправку формы
3. Проверить успешное создание объявления
4. Удалить debug endpoint после исправления
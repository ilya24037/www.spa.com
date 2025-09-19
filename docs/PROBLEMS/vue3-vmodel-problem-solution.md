# Проблема "Формат работы не отображается" в Vue 3 - Подробное описание

## 📋 ОПИСАНИЕ ПРОБЛЕМЫ

**Что было:** Пользователь создавал новое объявление, сохранял его как "Активное", но при редактировании поле **"Формат работы"** не отображалось корректно. Хотя данные сохранялись в базе данных, в форме редактирования радио-кнопка не была выбрана.

## 🔍 ДИАГНОСТИКА ПРОБЛЕМЫ

### 1. Проверили сохранение в БД
```php
// Создали скрипт check_ad_fields.php
$fieldsToCheck = [
    'work_format' => 'Формат работы',
    // ... другие поля
];
```
**Результат:** ✅ Поле `work_format` корректно сохранялось в БД со значением `"duo"`

### 2. Проверили передачу данных через API
```php
// В AdResource.php добавили логирование
\Log::info('🔍 AdResource::toArray ВЫЗВАН', [
    'ad_id' => $this->id,
    'work_format' => $this->work_format
]);
```
**Результат:** ✅ Данные корректно передавались через API

### 3. Проверили инициализацию в frontend
```typescript
// В adFormModel.ts добавили детальное логирование
work_format: (() => {
  const value = savedFormData?.work_format || props.initialData?.work_format || '';
  console.log('🔍 adFormModel: work_format инициализация');
  console.log('  savedFormData_work_format:', savedFormData?.work_format);
  console.log('  initialData_work_format:', props.initialData?.work_format);
  console.log('  final_value:', value);
  return value;
})(),
```
**Результат:** ✅ `adFormModel` корректно инициализировал `form.work_format = "duo"`

### 4. Проверили передачу в компонент
```vue
<!-- В AdForm.vue добавили логирование -->
<WorkFormatSection
  v-model:work-format="form.work_format"
  :errors="errors"
  :forceValidation="forceValidation.work_format"
  @clearForceValidation="forceValidation.work_format = false"
/>
```
**Результат:** ✅ `AdForm` имел правильное значение `form.work_format = "duo"`

### 5. Проверили получение в дочернем компоненте
```vue
<!-- В WorkFormatSection.vue добавили логирование -->
const props = defineProps({
  'work-format': { type: String, default: '' },
  // ...
})

console.log('🔍 WorkFormatSection: инициализация');
console.log('  props_work_format:', props['work-format']);
```
**Результат:** ❌ `WorkFormatSection` получал `undefined` вместо `"duo"`

## 🎯 КОРЕНЬ ПРОБЛЕМЫ

**Проблема была в несоответствии именования props в Vue 3 Composition API:**

1. **В родительском компоненте (AdForm.vue):**
   ```vue
   <WorkFormatSection v-model:work-format="form.work_format" />
   ```
   Использовался **kebab-case**: `work-format`

2. **В дочернем компоненте (WorkFormatSection.vue):**
   ```typescript
   const props = defineProps({
     'work-format': { type: String, default: '' }
   })
   ```
   Также использовался **kebab-case**: `'work-format'`

3. **НО!** В Vue 3 с Composition API при использовании `v-model` с кастомными аргументами:
   - `v-model:work-format` автоматически преобразуется в `update:work-format`
   - Но `defineProps` ожидает **camelCase** имена для корректной работы с `v-model`

## ✅ РЕШЕНИЕ

### 1. Изменили props в WorkFormatSection.vue
```typescript
// ❌ БЫЛО (kebab-case)
const props = defineProps({
  'work-format': { type: String, default: '' }
})

// ✅ СТАЛО (camelCase)
const props = defineProps({
  workFormat: { type: String, default: '' }
})
```

### 2. Изменили emits в WorkFormatSection.vue
```typescript
// ❌ БЫЛО
const emit = defineEmits(['update:work-format', 'clearForceValidation'])

// ✅ СТАЛО
const emit = defineEmits(['update:workFormat', 'clearForceValidation'])
```

### 3. Изменили v-model в AdForm.vue
```vue
<!-- ❌ БЫЛО -->
<WorkFormatSection v-model:work-format="form.work_format" />

<!-- ✅ СТАЛО -->
<WorkFormatSection v-model:workFormat="form.work_format" />
```

### 4. Обновили все ссылки в WorkFormatSection.vue
```typescript
// Обновили инициализацию
const localWorkFormat = ref(props.workFormat) // было props['work-format']

// Обновили watch
watch(() => props.workFormat, val => { // было props['work-format']
  localWorkFormat.value = val
})

// Обновили emit
const emitWorkFormat = (value) => {
  emit('update:workFormat', value) // было 'update:work-format'
}
```

## 🎉 РЕЗУЛЬТАТ

**После исправления логи показали:**
```
🔍 WorkFormatSection: инициализация
  props_workFormat: duo          // ✅ Теперь получает правильное значение!
  props_workFormat_type: string  // ✅ Правильный тип
  props_workFormat_empty: false  // ✅ Не пустое
  localWorkFormat_value: duo     // ✅ Локальное значение установлено
  localWorkFormat_type: string   // ✅ Правильный тип
```

**Поле "Формат работы" теперь:**
- ✅ Корректно отображается в форме редактирования
- ✅ Радио-кнопка "В паре" выбрана
- ✅ Значение загружается из БД
- ✅ Изменения сохраняются при редактировании

## 🔑 КЛЮЧЕВОЕ ПРАВИЛО

**В Vue 3 Composition API с v-model:**
- ❌ **НЕ РАБОТАЕТ**: `v-model:work-format` + `defineProps({ 'work-format': ... })`
- ✅ **РАБОТАЕТ**: `v-model:workFormat` + `defineProps({ workFormat: ... })`

**Почему:** Vue 3 автоматически преобразует kebab-case в camelCase для props, но `v-model` с кастомными аргументами требует точного соответствия имен.

## 📋 АЛГОРИТМ ДИАГНОСТИКИ

1. **Проверить БД** - сохраняются ли данные
2. **Проверить API** - передаются ли данные через ресурсы
3. **Проверить frontend модель** - инициализируются ли данные
4. **Проверить родительский компонент** - передаются ли данные
5. **Проверить дочерний компонент** - получаются ли данные
6. **Проверить соответствие имен** - camelCase vs kebab-case

## 🚀 ПРИМЕНЕНИЕ

Этот опыт поможет быстро решать аналогичные проблемы с `v-model` в Vue 3:

- При проблемах с отображением данных в дочерних компонентах
- При использовании `v-model` с кастомными аргументами
- При несоответствии имен props между родительским и дочерним компонентами

## 📅 ДАТА РЕШЕНИЯ
19 сентября 2025 года

## 👨‍💻 РАЗРАБОТЧИК
SPA Platform - платформа услуг массажа

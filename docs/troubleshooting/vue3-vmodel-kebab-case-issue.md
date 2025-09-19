# Troubleshooting: Vue 3 v-model с kebab-case props

## 🐛 Проблема
Поле формы не отображает сохранённые данные при редактировании, хотя данные корректно сохраняются в БД и передаются через API.

## 📋 Симптомы
- Данные сохраняются в базе данных ✅
- API возвращает правильные данные ✅
- Родительский компонент имеет правильные данные ✅
- Дочерний компонент получает `undefined` ❌
- Поле формы пустое при редактировании ❌

## 🔍 Диагностика

### Шаг 1: Проверить данные в БД
```php
// check_field.php
$ad = Ad::find($id);
echo "work_format: " . $ad->work_format; // Должно показать значение
```

### Шаг 2: Проверить API response
```javascript
// В браузере, Network tab
// Проверить response от /api/ads/{id}
{
  "work_format": "duo" // Должно быть значение
}
```

### Шаг 3: Проверить родительский компонент
```vue
<script setup>
// В AdForm.vue
console.log('form.work_format:', form.work_format) // Должно показать значение

// Проверить v-model binding
<WorkFormatSection
  v-model:work-format="form.work_format"  <!-- kebab-case -->
/>
</script>
```

### Шаг 4: Проверить дочерний компонент
```vue
<script setup>
// В WorkFormatSection.vue
const props = defineProps({
  'work-format': { type: String }  // kebab-case
})
console.log('props work-format:', props['work-format']) // Показывает undefined ❌
</script>
```

## ✅ Решение

### Изменить на camelCase в дочернем компоненте:

```vue
<!-- WorkFormatSection.vue -->
<script setup>
// ❌ НЕПРАВИЛЬНО - kebab-case
const props = defineProps({
  'work-format': { type: String, default: '' }
})
const emit = defineEmits(['update:work-format'])

// ✅ ПРАВИЛЬНО - camelCase
const props = defineProps({
  workFormat: { type: String, default: '' }
})
const emit = defineEmits(['update:workFormat'])

// Обновить все ссылки
const localValue = ref(props.workFormat) // было props['work-format']
watch(() => props.workFormat, val => {   // было props['work-format']
  localValue.value = val
})
emit('update:workFormat', value)          // было 'update:work-format'
</script>
```

### Изменить на camelCase в родительском компоненте:

```vue
<!-- AdForm.vue -->
<!-- ❌ НЕПРАВИЛЬНО -->
<WorkFormatSection v-model:work-format="form.work_format" />

<!-- ✅ ПРАВИЛЬНО -->
<WorkFormatSection v-model:workFormat="form.work_format" />
```

## 🎯 Правило для Vue 3

**При использовании v-model с кастомными аргументами:**

| Компонент | Неправильно ❌ | Правильно ✅ |
|-----------|---------------|-------------|
| Родитель | `v-model:work-format` | `v-model:workFormat` |
| Ребёнок | `defineProps({ 'work-format': ... })` | `defineProps({ workFormat: ... })` |
| Emit | `emit('update:work-format')` | `emit('update:workFormat')` |

## 🔧 Быстрая проверка

Добавить логирование в дочерний компонент:
```javascript
onMounted(() => {
  console.log('Component mounted with props:', {
    workFormat: props.workFormat,
    workFormat_type: typeof props.workFormat,
    workFormat_empty: !props.workFormat
  })
})
```

Если видите `undefined` - проблема в именовании props.

## 📚 Почему это происходит

Vue 3 автоматически преобразует kebab-case атрибуты в camelCase props, но при использовании `v-model` с кастомными аргументами требуется точное соответствие имен между:
1. Аргументом v-model
2. Именем prop в defineProps
3. Именем события в emit

## 🔗 Связанные документы
- [Полное описание проблемы](../vue3-vmodel-problem-solution.md)
- [Антипаттерн отладки](../antipatterns/vue-vmodel-debugging-antipattern.md)
- [Официальная документация Vue 3 v-model](https://vuejs.org/guide/components/v-model.html)

## 🏷️ Теги
#vue3 #vmodel #troubleshooting #props #kebab-case #camelCase
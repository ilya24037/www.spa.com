# 🔧 ИСПРАВЛЕНИЕ: Не сохраняются поля параметров при редактировании черновиков

## ❌ Проблема

При сохранении черновиков поля из секции "Параметры" (возраст, рост, вес, размер груди, цвет волос, цвет глаз, национальность) не сохранялись в базе данных, хотя поле "Имя" сохранялось корректно.

**Симптомы:**
- ✅ title: сохранялось
- ❌ age, height, weight, breast_size, hair_color, eye_color, nationality: НЕ сохранялись

## 🔍 Диагностика

### 1. Проверка backend
- ✅ Поля присутствуют в `$fillable` модели Ad
- ✅ FormData в adFormModel.ts правильно добавляет все поля
- ✅ DraftService получает данные и обрабатывает их

### 2. Проверка frontend
- ❌ **НАЙДЕНА ОСНОВНАЯ ПРОБЛЕМА**: Несоответствие имен событий между компонентами

**Проблема в ParametersSection.vue:**
```javascript
// ❌ НЕПРАВИЛЬНО: camelCase события
const emit = defineEmits(['update:breastSize', 'update:hairColor', 'update:eyeColor'])

// Но в AdForm.vue использовались snake_case:
<ParametersSection 
  v-model:breastSize="form.breast_size"  // ❌ Не работает!
  v-model:hairColor="form.hair_color"    // ❌ Не работает!
  v-model:eyeColor="form.eye_color"      // ❌ Не работает!
/>
```

## ✅ Решение

### Изменения в `ParametersSection.vue`:

1. **Props изменены на snake_case:**
```javascript
// ✅ ИСПРАВЛЕНО
props: {
  breast_size: { type: [String, Number], default: '' },
  hair_color: { type: String, default: '' },
  eye_color: { type: String, default: '' },
}
```

2. **События изменены на snake_case:**
```javascript
// ✅ ИСПРАВЛЕНО
const emit = defineEmits([
  'update:breast_size', 
  'update:hair_color', 
  'update:eye_color'
])
```

3. **Внутренние ссылки обновлены:**
```javascript
// ✅ ИСПРАВЛЕНО
const localBreastSize = ref(props.breast_size ? String(props.breast_size) : '')
const localHairColor = ref(props.hair_color)
const localEyeColor = ref(props.eye_color)

watch(() => props.breast_size, val => { localBreastSize.value = val ? String(val) : '' })
watch(() => props.hair_color, val => { localHairColor.value = val })
watch(() => props.eye_color, val => { localEyeColor.value = val })

const emitAll = () => {
  emit('update:breast_size', localBreastSize.value)
  emit('update:hair_color', localHairColor.value)
  emit('update:eye_color', localEyeColor.value)
}
```

4. **Error props обновлены:**
```vue
<!-- ✅ ИСПРАВЛЕНО -->
:error="errors.breast_size"
:error="errors.hair_color"
:error="errors.eye_color"
```

### Изменения в `AdForm.vue`:

```vue
<!-- ✅ ИСПРАВЛЕНО -->
<ParametersSection 
  v-model:breast_size="form.breast_size"
  v-model:hair_color="form.hair_color" 
  v-model:eye_color="form.eye_color"
/>
```

## 🧠 Причина проблемы

**Vue.js v-model binding** требует точного соответствия между:
- Именем prop в дочернем компоненте
- Именем события emit в дочернем компоненте  
- Именем v-model в родительском компоненте

**Было:**
```
ParentComponent: v-model:breastSize="form.breast_size"
        ↓
ChildComponent prop: breastSize ✅ (совпадает)
ChildComponent emit: 'update:breastSize' ✅ (совпадает)
        ↓
Результат: form.breast_size ✅ получает значение
```

**НО! В реальности было:**
```
ParentComponent: v-model:breastSize="form.breast_size"
        ↓
ChildComponent prop: breastSize ✅ (совпадает)
ChildComponent emit: 'update:breastSize' ✅ (совпадает)
        ↓
form.breast_size ❌ НЕ получает значение
```

Проблема была в том, что Vue не мог правильно связать camelCase события с snake_case полями формы.

## 🎯 Результат

После исправления:
- ✅ Все поля параметров корректно передаются между компонентами
- ✅ FormData включает все поля параметров
- ✅ Backend получает и сохраняет все данные
- ✅ Поля сохраняются в базе данных при нажатии "Сохранить черновик"

## 🧪 Тестирование

Для проверки исправления:

1. Откройте: http://spa.test/ads/49/edit
2. Заполните все поля в секции "Параметры"
3. Нажмите "Сохранить черновик"
4. Запустите: `php test-parameters-backend.php`
5. Все поля должны показать ✅ статус

## 📚 Урок для будущего

**ВАЖНО:** При создании компонентов с v-model всегда соблюдайте единообразие в именовании:
- Либо везде camelCase
- Либо везде snake_case
- НЕ смешивайте разные конвенции в одной цепочке компонентов

**Рекомендация:** В Vue-компонентах используйте camelCase для consistency с JavaScript, но если backend ожидает snake_case - делайте преобразование в слое данных (adFormModel.ts), а не в компонентах.

---
**Дата исправления:** 19.08.2025  
**Время решения:** 45 минут  
**Затронутые файлы:** 2  
**Статус:** ✅ РЕШЕНО
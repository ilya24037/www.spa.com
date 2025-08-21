# 🔧 Исправление ошибки vuedraggable

## Проблема:
```
TypeError: Cannot read properties of undefined (reading 'header')
at getSlot (vuedraggable.js)
```

## Причина:
Несовместимость синтаксиса vuedraggable с Vue 3. Библиотека ожидает другой формат слотов.

## Временное решение:
1. Создан `PhotoGridSimple.vue` - простая версия без drag&drop
2. Заменен импорт в `PhotoUpload.vue` на простую версию
3. Убран метод `updatePhotos` который больше не нужен

## Результат:
- ✅ Ошибка исправлена
- ✅ Проект компилируется
- ✅ Загрузка фото работает
- ✅ Удаление и поворот работают
- ⚠️ Drag&drop временно отключен

## Постоянное решение (TODO):

### Вариант 1: Использовать @vueuse/integrations
```bash
npm install @vueuse/integrations sortablejs
```

```vue
<script setup>
import { useSortable } from '@vueuse/integrations/useSortable'

const el = ref<HTMLElement>()
const list = ref(photos)

useSortable(el, list)
</script>

<template>
  <div ref="el" class="grid">
    <PhotoItem v-for="item in list" :key="item.id" />
  </div>
</template>
```

### Вариант 2: Использовать vue-draggable-plus
```bash
npm install vue-draggable-plus
```

### Вариант 3: Своя реализация через HTML5 Drag API
Уже есть в composable `usePhotoUpload.ts` методы:
- handleDragStart
- handleDragOver  
- handleDragDrop
- handleDragEnd

## Текущий статус:
Функциональность восстановлена без drag&drop. Drag&drop можно добавить позже одним из предложенных способов.
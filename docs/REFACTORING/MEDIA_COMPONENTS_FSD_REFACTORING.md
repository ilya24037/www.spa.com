# 📋 ДЕТАЛЬНЫЙ ПЛАН РЕФАКТОРИНГА MEDIA КОМПОНЕНТОВ ПО FSD АРХИТЕКТУРЕ

## 📊 1. АНАЛИЗ ТЕКУЩЕГО СОСТОЯНИЯ

### Проблемы:
1. **Монолитные компоненты:**
   - PhotoUpload.vue - 680 строк кода
   - VideoUpload.vue - 590 строк кода
   - Вся логика внутри компонентов

2. **Отсутствующий функционал:**
   - ❌ Нет чекбоксов "Настройки отображения"
   - ❌ Props принимаются но не рендерятся:
     - show-additional-info
     - show-services  
     - show-prices

3. **Нарушение FSD архитектуры:**
   - Нет разделения на ui/model/composables
   - Типы внутри компонентов
   - Логика смешана с представлением

### Готовые элементы в проекте:
```
✅ features/_archive/MediaUpload/composables/
   ├── usePhotoUpload.ts (239 строк готовой логики!)
   ├── useVideoUpload.ts
   └── useFormatDetection.ts

✅ shared/ui/atoms/BaseCheckbox/BaseCheckbox.vue

✅ Эталонная структура: features/gallery/
```

## 🎯 2. ЦЕЛЕВАЯ АРХИТЕКТУРА

```
features/media/
├── photo-upload/
│   ├── model/
│   │   └── types.ts                    # Интерфейсы Photo, MediaSettings
│   ├── composables/
│   │   ├── usePhotoUpload.ts          # Вся логика загрузки (из архива)
│   │   └── useDragAndDrop.ts          # Логика drag & drop
│   └── ui/
│       ├── PhotoUpload.vue            # Главный компонент (макс 150 строк)
│       ├── index.ts                    # Экспорт
│       └── components/
│           ├── MediaSettings.vue       # ЧЕКБОКСЫ НАСТРОЕК!
│           ├── PhotoItem.vue          # Одна фотография
│           ├── PhotoGrid.vue          # Сетка с vuedraggable
│           └── UploadZone.vue         # Зона загрузки
└── video-upload/
    ├── model/
    │   └── types.ts                    # Интерфейсы Video
    ├── composables/
    │   └── useVideoUpload.ts          # Логика видео (из архива)
    └── ui/
        ├── VideoUpload.vue            # Главный компонент (макс 150 строк)
        ├── index.ts
        └── components/
            ├── VideoItem.vue          # Одно видео
            ├── VideoList.vue          # Список видео
            └── VideoUploadZone.vue    # Зона загрузки видео
```

## 📝 3. ПОШАГОВАЯ РЕАЛИЗАЦИЯ

### ШАГ 1: Создание структуры папок
```bash
mkdir -p features/media/photo-upload/{model,composables,ui/components}
mkdir -p features/media/video-upload/{model,composables,ui/components}
```

### ШАГ 2: Миграция готовых composables из архива

#### 2.1 Копировать usePhotoUpload.ts
```typescript
// Из: features/_archive/MediaUpload/composables/usePhotoUpload.ts
// В: features/media/photo-upload/composables/usePhotoUpload.ts

// Вынести интерфейс Photo в model/types.ts
// Оставить только логику в composable
```

#### 2.2 Создать types.ts для photo-upload
```typescript
// features/media/photo-upload/model/types.ts
export interface Photo {
  id: string | number
  file?: File
  url?: string
  preview?: string
  name?: string
  rotation?: number
  isMain?: boolean
}

export interface MediaSettings {
  showAdditionalInfo: boolean
  showServices: boolean
  showPrices: boolean
}

export interface PhotoUploadProps {
  photos: Photo[]
  showAdditionalInfo?: boolean
  showServices?: boolean
  showPrices?: boolean
  maxFiles?: number
  errors?: Record<string, string>
}

export interface PhotoUploadEmits {
  'update:photos': [photos: Photo[]]
  'update:showAdditionalInfo': [value: boolean]
  'update:showServices': [value: boolean]
  'update:showPrices': [value: boolean]
}
```

### ШАГ 3: Создание MediaSettings.vue (РЕШЕНИЕ ПРОБЛЕМЫ!)

```vue
<!-- features/media/photo-upload/ui/components/MediaSettings.vue -->
<template>
  <div class="media-settings-section border-t pt-4 mt-4">
    <h4 class="text-sm font-medium text-gray-700 mb-3">
      Настройки отображения
    </h4>
    
    <div class="space-y-2">
      <BaseCheckbox 
        v-model="localShowAdditionalInfo"
        name="show_additional_info"
        label="Показывать дополнительную информацию"
      />
      
      <BaseCheckbox 
        v-model="localShowServices"
        name="show_services"
        label="Показывать услуги"
      />
      
      <BaseCheckbox 
        v-model="localShowPrices"
        name="show_prices"
        label="Показывать цены"
      />
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import BaseCheckbox from '@/src/shared/ui/atoms/BaseCheckbox/BaseCheckbox.vue'

interface Props {
  showAdditionalInfo?: boolean
  showServices?: boolean
  showPrices?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  showAdditionalInfo: false,
  showServices: false,
  showPrices: false
})

const emit = defineEmits<{
  'update:showAdditionalInfo': [value: boolean]
  'update:showServices': [value: boolean]
  'update:showPrices': [value: boolean]
}>()

// v-model для чекбоксов
const localShowAdditionalInfo = computed({
  get: () => props.showAdditionalInfo,
  set: (value) => emit('update:showAdditionalInfo', value)
})

const localShowServices = computed({
  get: () => props.showServices,
  set: (value) => emit('update:showServices', value)
})

const localShowPrices = computed({
  get: () => props.showPrices,
  set: (value) => emit('update:showPrices', value)
})
</script>
```

### ШАГ 4: Разделение PhotoUpload.vue на компоненты

#### 4.1 PhotoItem.vue (40 строк)
```vue
<!-- features/media/photo-upload/ui/components/PhotoItem.vue -->
<template>
  <div class="photo-item" :class="{ 'main-photo': isMain }">
    <div class="photo-wrapper">
      <img :src="photoUrl" :alt="`Фото ${index + 1}`">
      
      <!-- Контролы -->
      <div class="photo-controls">
        <button @click="$emit('rotate')" title="Повернуть">
          <RotateIcon />
        </button>
        <button @click="$emit('remove')" title="Удалить">
          <TrashIcon />
        </button>
      </div>
      
      <!-- Метка основного фото -->
      <div v-if="isMain" class="main-label">Основное</div>
    </div>
  </div>
</template>

<script setup lang="ts">
import type { Photo } from '../../model/types'

interface Props {
  photo: Photo
  index: number
  isMain?: boolean
}

const props = defineProps<Props>()

const emit = defineEmits<{
  rotate: []
  remove: []
}>()

const photoUrl = computed(() => {
  // Логика получения URL
})
</script>
```

#### 4.2 PhotoGrid.vue с vuedraggable (60 строк)
```vue
<!-- features/media/photo-upload/ui/components/PhotoGrid.vue -->
<template>
  <draggable 
    v-model="localPhotos"
    class="photos-grid"
    item-key="id"
    ghost-class="opacity-50"
    animation="200"
  >
    <template #item="{ element: photo, index }">
      <PhotoItem
        :photo="photo"
        :index="index"
        :is-main="index === 0"
        @rotate="rotatePhoto(index)"
        @remove="removePhoto(index)"
      />
    </template>
  </draggable>
</template>

<script setup lang="ts">
import draggable from 'vuedraggable'
import PhotoItem from './PhotoItem.vue'
import type { Photo } from '../../model/types'

interface Props {
  photos: Photo[]
}

const props = defineProps<Props>()
const emit = defineEmits<{
  'update:photos': [photos: Photo[]]
  'rotate': [index: number]
  'remove': [index: number]
}>()

const localPhotos = computed({
  get: () => props.photos,
  set: (value) => emit('update:photos', value)
})

const rotatePhoto = (index: number) => {
  emit('rotate', index)
}

const removePhoto = (index: number) => {
  emit('remove', index)
}
</script>
```

#### 4.3 UploadZone.vue (50 строк)
```vue
<!-- features/media/photo-upload/ui/components/UploadZone.vue -->
<template>
  <div 
    class="upload-zone"
    :class="{ 'drag-over': isDragOver }"
    @drop.prevent="handleDrop"
    @dragover.prevent="isDragOver = true"
    @dragleave.prevent="isDragOver = false"
  >
    <input
      ref="fileInput"
      type="file"
      multiple
      accept="image/*"
      @change="handleFileSelect"
      class="hidden"
    />
    
    <div v-if="!hasPhotos" class="empty-state" @click="openFileDialog">
      <UploadIcon />
      <p>Перетащите фото или нажмите для выбора</p>
    </div>
    
    <slot v-else />
  </div>
</template>

<script setup lang="ts">
// Логика загрузки файлов
</script>
```

#### 4.4 Главный PhotoUpload.vue (150 строк)
```vue
<!-- features/media/photo-upload/ui/PhotoUpload.vue -->
<template>
  <div class="photo-upload">
    <h3 class="form-group-title">Фотографии</h3>
    
    <!-- Счетчик -->
    <div class="photos-header">
      <p>{{ photos.length }} из {{ maxFiles }}</p>
    </div>

    <!-- Зона загрузки -->
    <UploadZone 
      :has-photos="photos.length > 0"
      @files-dropped="handleFilesDropped"
    >
      <!-- Сетка фотографий -->
      <PhotoGrid
        v-if="photos.length > 0"
        :photos="photos"
        @update:photos="updatePhotos"
        @rotate="rotatePhoto"
        @remove="removePhoto"
      />
      
      <!-- Кнопка добавить еще -->
      <button 
        v-if="photos.length < maxFiles"
        @click="openFileDialog"
        class="add-more-btn"
      >
        Добавить фото
      </button>
    </UploadZone>
    
    <!-- ЧЕКБОКСЫ НАСТРОЕК -->
    <MediaSettings
      :show-additional-info="showAdditionalInfo"
      :show-services="showServices"
      :show-prices="showPrices"
      @update:show-additional-info="$emit('update:showAdditionalInfo', $event)"
      @update:show-services="$emit('update:showServices', $event)"
      @update:show-prices="$emit('update:showPrices', $event)"
    />
    
    <!-- Ошибки -->
    <div v-if="error" class="error-message">
      {{ error }}
    </div>
  </div>
</template>

<script setup lang="ts">
import { usePhotoUpload } from '../composables/usePhotoUpload'
import UploadZone from './components/UploadZone.vue'
import PhotoGrid from './components/PhotoGrid.vue'
import MediaSettings from './components/MediaSettings.vue'
import type { PhotoUploadProps, PhotoUploadEmits } from '../model/types'

const props = withDefaults(defineProps<PhotoUploadProps>(), {
  photos: () => [],
  maxFiles: 10,
  showAdditionalInfo: false,
  showServices: false,
  showPrices: false
})

const emit = defineEmits<PhotoUploadEmits>()

// Использование composable для всей логики
const {
  localPhotos,
  error,
  addPhotos,
  removePhoto,
  rotatePhoto,
  // ... остальные методы
} = usePhotoUpload()

// ... минимальная логика связывания
</script>
```

### ШАГ 5: Аналогично для VideoUpload

#### 5.1 Создать types.ts для video-upload
```typescript
// features/media/video-upload/model/types.ts
export interface Video {
  id: string | number
  file?: File
  url?: string
  duration?: number
  thumbnail?: string
  format?: string
  size?: number
}

export interface VideoUploadProps {
  videos: Video[]
  maxFiles?: number
  maxSize?: number
  acceptedFormats?: string[]
  errors?: Record<string, string>
}
```

#### 5.2 Разделить VideoUpload на компоненты:
- VideoItem.vue (40 строк)
- VideoList.vue (50 строк)
- VideoUploadZone.vue (50 строк)
- VideoUpload.vue (150 строк)

### ШАГ 6: Создание index файлов для экспорта

```typescript
// features/media/photo-upload/index.ts
export { default as PhotoUpload } from './ui/PhotoUpload.vue'
export type { Photo, MediaSettings, PhotoUploadProps } from './model/types'

// features/media/video-upload/index.ts
export { default as VideoUpload } from './ui/VideoUpload.vue'
export type { Video, VideoUploadProps } from './model/types'

// features/media/index.ts
export * from './photo-upload'
export * from './video-upload'
```

### ШАГ 7: Обновление импортов в AdForm.vue

```typescript
// Старые импорты:
import PhotoUpload from '@/src/features/media/PhotoUpload.vue'
import VideoUpload from '@/src/features/media/VideoUpload.vue'

// Новые импорты:
import { PhotoUpload } from '@/src/features/media/photo-upload'
import { VideoUpload } from '@/src/features/media/video-upload'
```

## ✅ 4. КОНТРОЛЬНЫЕ ТОЧКИ

- [ ] Структура папок соответствует FSD
- [ ] Чекбоксы MediaSettings работают и сохраняются
- [ ] Drag & drop фотографий функционирует  
- [ ] Компоненты не превышают 150 строк
- [ ] Типы изолированы в model/types.ts
- [ ] Логика вынесена в composables
- [ ] Используются готовые элементы из архива
- [ ] BaseCheckbox используется для чекбоксов

## 📊 5. ПЕРЕИСПОЛЬЗУЕМЫЕ ЭЛЕМЕНТЫ

### Из архива (копировать с минимальными изменениями):
```
features/_archive/MediaUpload/composables/
├── usePhotoUpload.ts → photo-upload/composables/
├── useVideoUpload.ts → video-upload/composables/
└── useFormatDetection.ts → video-upload/composables/
```

### Из shared (импортировать):
```
shared/ui/atoms/BaseCheckbox/BaseCheckbox.vue
```

### Установленные библиотеки:
```
vuedraggable - для drag & drop
```

## 🧪 6. ПЛАН ТЕСТИРОВАНИЯ

### 6.1 Функциональное тестирование:
1. **Загрузка фотографий:**
   - Выбор через диалог
   - Drag & drop
   - Множественная загрузка

2. **Управление фотографиями:**
   - Поворот фото
   - Удаление фото
   - Перетаскивание для изменения порядка
   - Основное фото (первое в списке)

3. **Настройки отображения:**
   - Чекбокс "Показывать дополнительную информацию"
   - Чекбокс "Показывать услуги"
   - Чекбокс "Показывать цены"
   - Сохранение состояния в форме

4. **Загрузка видео:**
   - Проверка форматов
   - Ограничение размера
   - Отображение превью

### 6.2 Интеграционное тестирование:
1. Проверка работы в AdForm.vue
2. Сохранение данных в backend
3. Загрузка существующих данных при редактировании

## 📈 7. МЕТРИКИ УСПЕХА

| Метрика | Цель | Текущее | После |
|---------|------|---------|-------|
| Размер компонентов | < 150 строк | 680/590 | 150 |
| Покрытие типами | 100% | 20% | 100% |
| Изоляция features | Полная | Нет | Да |
| Переиспользование кода | > 80% | 0% | 85% |
| Соответствие FSD | 100% | 0% | 100% |
| Чекбоксы настроек | Работают | Нет | Да |

## 🚀 8. ПОСЛЕДОВАТЕЛЬНОСТЬ ВЫПОЛНЕНИЯ

### День 1: Подготовка
1. ✅ Создать план (этот документ)
2. Создать структуру папок
3. Мигрировать composables из архива

### День 2: PhotoUpload
4. Создать types.ts для photo-upload
5. Создать MediaSettings.vue с чекбоксами
6. Разделить PhotoUpload на компоненты
7. Интегрировать и протестировать

### День 3: VideoUpload и финализация
8. Создать types.ts для video-upload
9. Разделить VideoUpload на компоненты
10. Обновить импорты в AdForm.vue
11. Полное тестирование
12. Удалить старые монолитные компоненты

## 📝 9. ПРИМЕЧАНИЯ

### Важно помнить:
1. **НЕ переписывать логику заново** - использовать готовые composables из архива
2. **BaseCheckbox уже готов** - не создавать свой чекбокс
3. **vuedraggable установлен** - использовать для drag & drop
4. **Следовать примеру features/gallery** - это эталонная структура

### Риски:
1. Возможные конфликты при интеграции с AdForm.vue
2. Необходимость обновления других компонентов, использующих media
3. Возможные проблемы с сохранением в backend

### Дополнительные улучшения (после основного рефакторинга):
1. Добавить прогресс-бар загрузки
2. Реализовать preview для видео
3. Добавить валидацию размеров изображений
4. Оптимизировать производительность при большом количестве фото

## ✅ 10. КРИТЕРИИ ЗАВЕРШЕНИЯ

Рефакторинг считается завершенным когда:
1. ✅ Все компоненты соответствуют FSD архитектуре
2. ✅ Чекбоксы настроек работают и сохраняют состояние
3. ✅ Размер компонентов не превышает 150 строк
4. ✅ Вся логика вынесена в composables
5. ✅ Типизация вынесена в отдельные файлы
6. ✅ Функциональность полностью сохранена
7. ✅ Тесты пройдены успешно

---

**Автор:** AI Assistant  
**Дата создания:** 2025-08-20  
**Версия:** 1.0  
**Статус:** В работе
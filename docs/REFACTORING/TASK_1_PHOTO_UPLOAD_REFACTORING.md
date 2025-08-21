# 📸 ЗАДАЧА 1: РЕФАКТОРИНГ PHOTO UPLOAD КОМПОНЕНТА

## 👤 Исполнитель: ИИ Помощник #1

## 📊 КОНТЕКСТ
- **Текущий файл:** `resources/js/src/features/media/PhotoUpload.vue` (680 строк)
- **Проблема:** Монолитный компонент, нет чекбоксов настроек, нарушение FSD
- **Готовые ресурсы:** 
  - `features/_archive/MediaUpload/composables/usePhotoUpload.ts` (готовая логика!)
  - `shared/ui/atoms/BaseCheckbox/BaseCheckbox.vue` (готовый чекбокс)

## 🎯 ЦЕЛЬ
Разделить PhotoUpload.vue на модульные компоненты согласно FSD архитектуре с добавлением чекбоксов настроек.

## 📁 СОЗДАТЬ СТРУКТУРУ
```
resources/js/src/features/media/photo-upload/
├── model/
│   └── types.ts                    # Типы для Photo и MediaSettings
├── composables/
│   └── usePhotoUpload.ts          # Скопировать из архива
└── ui/
    ├── PhotoUpload.vue            # Главный (макс 150 строк)
    ├── index.ts                    # export { default as PhotoUpload }
    └── components/
        ├── MediaSettings.vue       # НОВЫЙ! Чекбоксы настроек
        ├── PhotoItem.vue          # Одно фото (40 строк)
        ├── PhotoGrid.vue          # Сетка с vuedraggable (60 строк)
        └── UploadZone.vue         # Зона загрузки (50 строк)
```

## 📝 ПОШАГОВАЯ ИНСТРУКЦИЯ

### ШАГ 1: Создание папок
```bash
mkdir -p resources/js/src/features/media/photo-upload/model
mkdir -p resources/js/src/features/media/photo-upload/composables  
mkdir -p resources/js/src/features/media/photo-upload/ui/components
```

### ШАГ 2: Создать types.ts
```typescript
// resources/js/src/features/media/photo-upload/model/types.ts

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

### ШАГ 3: Копировать и адаптировать usePhotoUpload.ts
```bash
# Копировать из архива
cp features/_archive/MediaUpload/composables/usePhotoUpload.ts \
   features/media/photo-upload/composables/usePhotoUpload.ts

# Изменить импорт типов на:
import type { Photo } from '../model/types'
```

### ШАГ 4: СОЗДАТЬ MediaSettings.vue (КРИТИЧЕСКИ ВАЖНО!)
```vue
<!-- features/media/photo-upload/ui/components/MediaSettings.vue -->
<template>
  <div class="media-settings-section border-t pt-4 mt-4">
    <h4 class="text-sm font-medium text-gray-700 mb-3">
      Настройки отображения
    </h4>
    
    <div class="space-y-2">
      <BaseCheckbox 
        id="show-additional-info"
        v-model="localShowAdditionalInfo"
        name="show_additional_info"
        label="Показывать дополнительную информацию"
      />
      
      <BaseCheckbox 
        id="show-services"
        v-model="localShowServices"
        name="show_services"
        label="Показывать услуги"
      />
      
      <BaseCheckbox 
        id="show-prices"
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

### ШАГ 5: Создать PhotoItem.vue
```vue
<!-- features/media/photo-upload/ui/components/PhotoItem.vue -->
<template>
  <div class="photo-item relative group" :class="{ 'ring-2 ring-blue-500': isMain }">
    <div class="aspect-square rounded-lg overflow-hidden bg-gray-100">
      <img 
        :src="photoUrl" 
        :alt="`Фото ${index + 1}`"
        class="w-full h-full object-cover"
        :style="{ transform: `rotate(${photo.rotation || 0}deg)` }"
      />
      
      <!-- Контролы -->
      <div class="absolute top-2 right-2 flex gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
        <button 
          @click="$emit('rotate')" 
          class="p-1.5 bg-white rounded shadow hover:bg-gray-100"
          title="Повернуть"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                  d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
          </svg>
        </button>
        
        <button 
          @click="$emit('remove')" 
          class="p-1.5 bg-white rounded shadow hover:bg-red-50"
          title="Удалить"
        >
          <svg class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                  d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
          </svg>
        </button>
      </div>
      
      <!-- Метка основного фото -->
      <div v-if="isMain" class="absolute bottom-2 left-2 bg-blue-500 text-white px-2 py-1 rounded text-xs font-medium">
        Основное
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
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
  if (props.photo.preview) return props.photo.preview
  if (props.photo.url) return props.photo.url
  if (props.photo.file) return URL.createObjectURL(props.photo.file)
  return ''
})
</script>
```

### ШАГ 6: Создать PhotoGrid.vue
```vue
<!-- features/media/photo-upload/ui/components/PhotoGrid.vue -->
<template>
  <draggable 
    v-model="localPhotos"
    class="grid grid-cols-3 gap-3 sm:grid-cols-4 md:grid-cols-5"
    item-key="id"
    ghost-class="opacity-50"
    drag-class="scale-105"
    animation="200"
    @start="$emit('dragstart')"
    @end="$emit('dragend')"
  >
    <template #item="{ element: photo, index }">
      <PhotoItem
        :photo="photo"
        :index="index"
        :is-main="index === 0"
        @rotate="$emit('rotate', index)"
        @remove="$emit('remove', index)"
      />
    </template>
  </draggable>
</template>

<script setup lang="ts">
import { computed } from 'vue'
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
  'dragstart': []
  'dragend': []
}>()

const localPhotos = computed({
  get: () => props.photos,
  set: (value) => emit('update:photos', value)
})
</script>
```

### ШАГ 7: Создать UploadZone.vue
```vue
<!-- features/media/photo-upload/ui/components/UploadZone.vue -->
<template>
  <div 
    class="upload-zone border-2 border-dashed rounded-lg p-4 transition-colors"
    :class="{ 
      'border-blue-400 bg-blue-50': isDragOver,
      'border-gray-300': !isDragOver
    }"
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
    
    <!-- Пустое состояние -->
    <div v-if="!hasContent" class="text-center py-8" @click="openFileDialog">
      <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
              d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
      </svg>
      <p class="mt-2 text-sm text-gray-600">
        Перетащите фото сюда или нажмите для выбора
      </p>
      <p class="text-xs text-gray-500">PNG, JPG до 10MB</p>
    </div>
    
    <!-- Контент (фотографии) -->
    <slot v-else />
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'

interface Props {
  hasContent?: boolean
}

defineProps<Props>()

const emit = defineEmits<{
  'files-selected': [files: File[]]
}>()

const fileInput = ref<HTMLInputElement>()
const isDragOver = ref(false)

const openFileDialog = () => {
  fileInput.value?.click()
}

const handleFileSelect = (event: Event) => {
  const target = event.target as HTMLInputElement
  const files = Array.from(target.files || [])
  if (files.length > 0) {
    emit('files-selected', files)
  }
  target.value = ''
}

const handleDrop = (event: DragEvent) => {
  isDragOver.value = false
  const files = Array.from(event.dataTransfer?.files || [])
  if (files.length > 0) {
    emit('files-selected', files)
  }
}

defineExpose({ openFileDialog })
</script>
```

### ШАГ 8: Главный PhotoUpload.vue (КОМПОЗИЦИЯ)
```vue
<!-- features/media/photo-upload/ui/PhotoUpload.vue -->
<template>
  <div class="photo-upload space-y-4">
    <div class="flex justify-between items-center">
      <h3 class="text-lg font-medium">Фотографии</h3>
      <span class="text-sm text-gray-500">
        {{ photos.length }} из {{ maxFiles }}
      </span>
    </div>

    <UploadZone 
      ref="uploadZone"
      :has-content="photos.length > 0"
      @files-selected="handleFilesSelected"
    >
      <div class="space-y-3">
        <!-- Сетка фотографий -->
        <PhotoGrid
          v-if="photos.length > 0"
          :photos="localPhotos"
          @update:photos="updatePhotos"
          @rotate="rotatePhoto"
          @remove="removePhoto"
        />
        
        <!-- Кнопка добавить еще -->
        <button 
          v-if="photos.length > 0 && photos.length < maxFiles"
          @click="uploadZone?.openFileDialog()"
          class="w-full py-2 px-4 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50"
        >
          Добавить еще фото
        </button>
      </div>
    </UploadZone>
    
    <!-- ЧЕКБОКСЫ НАСТРОЕК (КРИТИЧЕСКИ ВАЖНО!) -->
    <MediaSettings
      v-if="photos.length > 0"
      :show-additional-info="showAdditionalInfo"
      :show-services="showServices"
      :show-prices="showPrices"
      @update:show-additional-info="$emit('update:showAdditionalInfo', $event)"
      @update:show-services="$emit('update:showServices', $event)"
      @update:show-prices="$emit('update:showPrices', $event)"
    />
    
    <!-- Ошибки -->
    <div v-if="error" class="rounded-md bg-red-50 p-3">
      <p class="text-sm text-red-800">{{ error }}</p>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue'
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

const uploadZone = ref<InstanceType<typeof UploadZone>>()

// Использование composable
const {
  localPhotos,
  error,
  addPhotos,
  removePhoto,
  rotatePhoto,
  initializeFromProps
} = usePhotoUpload()

// Инициализация
watch(() => props.photos, (newPhotos) => {
  if (newPhotos.length > 0 && localPhotos.value.length === 0) {
    initializeFromProps(newPhotos)
  }
}, { immediate: true })

// Обработчики
const handleFilesSelected = async (files: File[]) => {
  await addPhotos(files)
  emit('update:photos', localPhotos.value)
}

const updatePhotos = (photos: Photo[]) => {
  localPhotos.value = photos
  emit('update:photos', photos)
}
</script>
```

### ШАГ 9: Создать index.ts
```typescript
// features/media/photo-upload/index.ts
export { default as PhotoUpload } from './ui/PhotoUpload.vue'
export type { Photo, MediaSettings, PhotoUploadProps, PhotoUploadEmits } from './model/types'
```

### ШАГ 10: Удалить старый файл
```bash
# После успешного тестирования
rm resources/js/src/features/media/PhotoUpload.vue
```

## ✅ КРИТЕРИИ ГОТОВНОСТИ

1. [ ] Структура папок создана согласно FSD
2. [ ] types.ts содержит все интерфейсы
3. [ ] usePhotoUpload.ts скопирован и адаптирован
4. [ ] MediaSettings.vue создан с 3 чекбоксами
5. [ ] PhotoItem.vue не превышает 50 строк
6. [ ] PhotoGrid.vue использует vuedraggable
7. [ ] UploadZone.vue поддерживает drag & drop
8. [ ] PhotoUpload.vue не превышает 150 строк
9. [ ] index.ts экспортирует компонент и типы
10. [ ] Старый монолитный файл удален

## 🧪 ТЕСТИРОВАНИЕ

1. Открыть страницу создания/редактирования объявления
2. Проверить загрузку фото через выбор файлов
3. Проверить drag & drop
4. Проверить поворот и удаление фото
5. Проверить перетаскивание для изменения порядка
6. **ВАЖНО:** Проверить работу всех 3 чекбоксов
7. Сохранить черновик и проверить сохранение настроек

## ⚠️ ВАЖНЫЕ МОМЕНТЫ

1. **НЕ ЗАБЫТЬ** создать MediaSettings.vue - это решает основную проблему!
2. **ИСПОЛЬЗОВАТЬ** BaseCheckbox из shared/ui/atoms
3. **СКОПИРОВАТЬ** usePhotoUpload.ts из архива, не писать заново
4. **ПРОВЕРИТЬ** что все emit'ы правильно пробрасываются
5. **СОХРАНИТЬ** функциональность drag & drop через vuedraggable

## 📞 КООРДИНАЦИЯ

- **Не зависит от:** Задачи Video Upload (можно делать параллельно)
- **Блокирует:** Обновление импортов в AdForm.vue
- **Результат:** Готовая FSD структура photo-upload/

---
**Статус:** Готово к выполнению  
**Приоритет:** Высокий  
**Время:** ~4 часа
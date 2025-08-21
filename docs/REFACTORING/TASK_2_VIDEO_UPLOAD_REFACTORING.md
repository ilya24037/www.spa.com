# 🎥 ЗАДАЧА 2: РЕФАКТОРИНГ VIDEO UPLOAD КОМПОНЕНТА

## 👤 Исполнитель: ИИ Помощник #2

## 📊 КОНТЕКСТ
- **Текущий файл:** `resources/js/src/features/media/VideoUpload.vue` (590 строк)
- **Проблема:** Монолитный компонент, нарушение FSD архитектуры
- **Готовые ресурсы:**
  - `features/_archive/MediaUpload/composables/useVideoUpload.ts` (готовая логика!)
  - `features/_archive/MediaUpload/composables/useFormatDetection.ts` (определение форматов)

## 🎯 ЦЕЛЬ
Разделить VideoUpload.vue на модульные компоненты согласно FSD архитектуре.

## 📁 СОЗДАТЬ СТРУКТУРУ
```
resources/js/src/features/media/video-upload/
├── model/
│   └── types.ts                    # Типы для Video
├── composables/
│   ├── useVideoUpload.ts          # Скопировать из архива
│   └── useFormatDetection.ts      # Скопировать из архива
└── ui/
    ├── VideoUpload.vue            # Главный (макс 150 строк)
    ├── index.ts                    # export { default as VideoUpload }
    └── components/
        ├── VideoItem.vue          # Одно видео (50 строк)
        ├── VideoList.vue          # Список видео (60 строк)
        ├── VideoUploadZone.vue    # Зона загрузки (50 строк)
        └── FormatWarning.vue      # Предупреждение о формате (30 строк)
```

## 📝 ПОШАГОВАЯ ИНСТРУКЦИЯ

### ШАГ 1: Создание папок
```bash
mkdir -p resources/js/src/features/media/video-upload/model
mkdir -p resources/js/src/features/media/video-upload/composables
mkdir -p resources/js/src/features/media/video-upload/ui/components
```

### ШАГ 2: Создать types.ts
```typescript
// resources/js/src/features/media/video-upload/model/types.ts

export interface Video {
  id: string | number
  file?: File
  url?: string
  duration?: number
  thumbnail?: string
  format?: string
  size?: number
  uploadProgress?: number
  isUploading?: boolean
  error?: string
}

export interface VideoFormat {
  extension: string
  mimeType: string
  codec?: string
  supported: boolean
  browserCompatibility?: {
    chrome: boolean
    firefox: boolean
    safari: boolean
    edge: boolean
  }
}

export interface VideoUploadProps {
  videos?: Video[]
  maxFiles?: number
  maxSize?: number // в байтах
  acceptedFormats?: string[]
  errors?: Record<string, string>
}

export interface VideoUploadEmits {
  'update:videos': [videos: Video[]]
  'upload': [video: Video]
  'remove': [id: string | number]
  'error': [error: string]
}
```

### ШАГ 3: Копировать composables из архива
```bash
# Копировать useVideoUpload
cp features/_archive/MediaUpload/composables/useVideoUpload.ts \
   features/media/video-upload/composables/useVideoUpload.ts

# Копировать useFormatDetection
cp features/_archive/MediaUpload/composables/useFormatDetection.ts \
   features/media/video-upload/composables/useFormatDetection.ts

# Изменить импорты типов на:
import type { Video, VideoFormat } from '../model/types'
```

### ШАГ 4: Создать FormatWarning.vue
```vue
<!-- features/media/video-upload/ui/components/FormatWarning.vue -->
<template>
  <div v-if="showWarning" class="rounded-md bg-yellow-50 p-3">
    <div class="flex">
      <div class="flex-shrink-0">
        <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
          <path fill-rule="evenodd" 
                d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" 
                clip-rule="evenodd" />
        </svg>
      </div>
      <div class="ml-3">
        <h3 class="text-sm font-medium text-yellow-800">
          Внимание: формат видео
        </h3>
        <div class="mt-2 text-sm text-yellow-700">
          <p>{{ warningMessage }}</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
interface Props {
  format?: string
  browser?: string
}

const props = defineProps<Props>()

const showWarning = computed(() => {
  return props.browser === 'chromium' && props.format === 'hevc'
})

const warningMessage = computed(() => {
  if (props.format === 'hevc') {
    return 'Формат HEVC может не воспроизводиться в некоторых браузерах. Рекомендуем использовать MP4 H.264.'
  }
  return 'Некоторые браузеры могут не поддерживать этот формат видео.'
})
</script>
```

### ШАГ 5: Создать VideoItem.vue
```vue
<!-- features/media/video-upload/ui/components/VideoItem.vue -->
<template>
  <div class="video-item flex items-center gap-4 p-3 bg-white rounded-lg border">
    <!-- Превью -->
    <div class="flex-shrink-0 w-24 h-16 bg-gray-100 rounded overflow-hidden">
      <img 
        v-if="video.thumbnail" 
        :src="video.thumbnail" 
        :alt="`Видео ${index + 1}`"
        class="w-full h-full object-cover"
      />
      <div v-else class="w-full h-full flex items-center justify-center">
        <svg class="w-8 h-8 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
        </svg>
      </div>
    </div>
    
    <!-- Информация -->
    <div class="flex-1 min-w-0">
      <p class="text-sm font-medium text-gray-900 truncate">
        {{ video.file?.name || `Видео ${index + 1}` }}
      </p>
      <p class="text-xs text-gray-500">
        {{ formatSize(video.size) }}
        <span v-if="video.duration"> • {{ formatDuration(video.duration) }}</span>
        <span v-if="video.format" class="ml-1 uppercase">{{ video.format }}</span>
      </p>
      
      <!-- Прогресс загрузки -->
      <div v-if="video.isUploading" class="mt-2">
        <div class="w-full bg-gray-200 rounded-full h-1.5">
          <div 
            class="bg-blue-500 h-1.5 rounded-full transition-all duration-300"
            :style="{ width: `${video.uploadProgress || 0}%` }"
          />
        </div>
      </div>
      
      <!-- Ошибка -->
      <p v-if="video.error" class="mt-1 text-xs text-red-600">
        {{ video.error }}
      </p>
    </div>
    
    <!-- Действия -->
    <div class="flex-shrink-0">
      <button 
        @click="$emit('remove')"
        :disabled="video.isUploading"
        class="p-1.5 text-gray-400 hover:text-red-500 disabled:opacity-50"
        title="Удалить"
      >
        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
        </svg>
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import type { Video } from '../../model/types'

interface Props {
  video: Video
  index: number
}

defineProps<Props>()

const emit = defineEmits<{
  remove: []
}>()

const formatSize = (bytes?: number): string => {
  if (!bytes) return ''
  const mb = bytes / (1024 * 1024)
  return `${mb.toFixed(1)} MB`
}

const formatDuration = (seconds?: number): string => {
  if (!seconds) return ''
  const mins = Math.floor(seconds / 60)
  const secs = Math.floor(seconds % 60)
  return `${mins}:${secs.toString().padStart(2, '0')}`
}
</script>
```

### ШАГ 6: Создать VideoList.vue
```vue
<!-- features/media/video-upload/ui/components/VideoList.vue -->
<template>
  <div class="video-list space-y-2">
    <TransitionGroup name="list">
      <VideoItem
        v-for="(video, index) in videos"
        :key="video.id"
        :video="video"
        :index="index"
        @remove="$emit('remove', video.id)"
      />
    </TransitionGroup>
    
    <div v-if="videos.length === 0" class="text-center py-4 text-gray-500">
      Видео не загружены
    </div>
  </div>
</template>

<script setup lang="ts">
import VideoItem from './VideoItem.vue'
import type { Video } from '../../model/types'

interface Props {
  videos: Video[]
}

defineProps<Props>()

const emit = defineEmits<{
  remove: [id: string | number]
}>()
</script>

<style scoped>
.list-enter-active,
.list-leave-active {
  transition: all 0.3s ease;
}
.list-enter-from {
  opacity: 0;
  transform: translateX(-30px);
}
.list-leave-to {
  opacity: 0;
  transform: translateX(30px);
}
</style>
```

### ШАГ 7: Создать VideoUploadZone.vue
```vue
<!-- features/media/video-upload/ui/components/VideoUploadZone.vue -->
<template>
  <div 
    class="video-upload-zone"
    :class="{ 
      'border-blue-400 bg-blue-50': isDragOver,
      'border-gray-300 bg-white': !isDragOver
    }"
    @drop.prevent="handleDrop"
    @dragover.prevent="isDragOver = true"
    @dragleave.prevent="isDragOver = false"
  >
    <input
      ref="fileInput"
      type="file"
      multiple
      :accept="acceptedFormats.join(',')"
      @change="handleFileSelect"
      class="hidden"
    />
    
    <div class="text-center py-8 px-4" @click="openFileDialog">
      <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
              d="M7 4v16M17 4v16M3 8h4m10 0h4M3 16h4m10 0h4M4 4h16a1 1 0 011 1v14a1 1 0 01-1 1H4a1 1 0 01-1-1V5a1 1 0 011-1z" />
      </svg>
      
      <p class="mt-2 text-sm text-gray-600">
        {{ isDragOver ? 'Отпустите файлы здесь' : 'Перетащите видео или нажмите для выбора' }}
      </p>
      
      <p class="mt-1 text-xs text-gray-500">
        {{ formatInfo }}
      </p>
      
      <button 
        type="button"
        class="mt-4 px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600"
      >
        Выбрать видео
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'

interface Props {
  maxSize?: number // в байтах
  acceptedFormats?: string[]
}

const props = withDefaults(defineProps<Props>(), {
  maxSize: 100 * 1024 * 1024, // 100MB
  acceptedFormats: () => ['video/mp4', 'video/webm', 'video/ogg']
})

const emit = defineEmits<{
  'files-selected': [files: File[]]
}>()

const fileInput = ref<HTMLInputElement>()
const isDragOver = ref(false)

const formatInfo = computed(() => {
  const formats = props.acceptedFormats.map(f => f.split('/')[1].toUpperCase()).join(', ')
  const maxSizeMB = Math.round(props.maxSize / (1024 * 1024))
  return `${formats} • Максимум ${maxSizeMB}MB`
})

const openFileDialog = () => {
  fileInput.value?.click()
}

const handleFileSelect = (event: Event) => {
  const target = event.target as HTMLInputElement
  const files = Array.from(target.files || [])
  if (files.length > 0) {
    emit('files-selected', validateFiles(files))
  }
  target.value = ''
}

const handleDrop = (event: DragEvent) => {
  isDragOver.value = false
  const files = Array.from(event.dataTransfer?.files || [])
  if (files.length > 0) {
    emit('files-selected', validateFiles(files))
  }
}

const validateFiles = (files: File[]): File[] => {
  return files.filter(file => {
    // Проверка формата
    if (!props.acceptedFormats.some(format => file.type.startsWith(format.split('/*')[0]))) {
      console.warn(`Неподдерживаемый формат: ${file.type}`)
      return false
    }
    // Проверка размера
    if (file.size > props.maxSize) {
      console.warn(`Файл слишком большой: ${file.name}`)
      return false
    }
    return true
  })
}

defineExpose({ openFileDialog })
</script>

<style scoped>
.video-upload-zone {
  @apply border-2 border-dashed rounded-lg transition-all duration-200 cursor-pointer;
}
</style>
```

### ШАГ 8: Главный VideoUpload.vue (КОМПОЗИЦИЯ)
```vue
<!-- features/media/video-upload/ui/VideoUpload.vue -->
<template>
  <div class="video-upload space-y-4">
    <div class="flex justify-between items-center">
      <h3 class="text-lg font-medium">Видео</h3>
      <span class="text-sm text-gray-500">
        {{ videos.length }} из {{ maxFiles }}
      </span>
    </div>

    <!-- Предупреждение о формате для Chromium -->
    <FormatWarning 
      v-if="detectedFormat"
      :format="detectedFormat"
      :browser="currentBrowser"
    />

    <!-- Зона загрузки (показывать всегда если есть место) -->
    <VideoUploadZone
      v-if="videos.length < maxFiles"
      ref="uploadZone"
      :max-size="maxSize"
      :accepted-formats="acceptedFormats"
      @files-selected="handleFilesSelected"
    />

    <!-- Список видео -->
    <VideoList
      v-if="videos.length > 0"
      :videos="localVideos"
      @remove="removeVideo"
    />
    
    <!-- Ошибки -->
    <div v-if="error" class="rounded-md bg-red-50 p-3">
      <p class="text-sm text-red-800">{{ error }}</p>
    </div>
    
    <!-- Информация об ограничениях -->
    <div class="text-xs text-gray-500 space-y-1">
      <p>• Максимум {{ maxFiles }} видео</p>
      <p>• Размер файла до {{ Math.round(maxSize / (1024 * 1024)) }}MB</p>
      <p>• Поддерживаемые форматы: MP4, WebM, OGG</p>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { useVideoUpload } from '../composables/useVideoUpload'
import { useFormatDetection } from '../composables/useFormatDetection'
import VideoUploadZone from './components/VideoUploadZone.vue'
import VideoList from './components/VideoList.vue'
import FormatWarning from './components/FormatWarning.vue'
import type { VideoUploadProps, VideoUploadEmits } from '../model/types'

const props = withDefaults(defineProps<VideoUploadProps>(), {
  videos: () => [],
  maxFiles: 5,
  maxSize: 100 * 1024 * 1024, // 100MB
  acceptedFormats: () => ['video/mp4', 'video/webm', 'video/ogg']
})

const emit = defineEmits<VideoUploadEmits>()

const uploadZone = ref<InstanceType<typeof VideoUploadZone>>()

// Composables
const {
  localVideos,
  error,
  isUploading,
  addVideos,
  removeVideo,
  uploadVideo,
  initializeFromProps
} = useVideoUpload()

const {
  detectedFormat,
  currentBrowser,
  detectVideoFormat
} = useFormatDetection()

// Инициализация
watch(() => props.videos, (newVideos) => {
  if (newVideos && newVideos.length > 0 && localVideos.value.length === 0) {
    initializeFromProps(newVideos)
  }
}, { immediate: true })

// Обработчики
const handleFilesSelected = async (files: File[]) => {
  // Проверка количества
  if (localVideos.value.length + files.length > props.maxFiles) {
    error.value = `Максимум ${props.maxFiles} видео`
    return
  }
  
  // Определение формата первого файла
  if (files.length > 0) {
    detectedFormat.value = await detectVideoFormat(files[0])
  }
  
  // Добавление видео
  await addVideos(files)
  emit('update:videos', localVideos.value)
  
  // Начать загрузку
  for (const video of localVideos.value) {
    if (video.file && !video.isUploading && !video.url) {
      await uploadVideo(video)
      emit('upload', video)
    }
  }
}

const handleRemoveVideo = (id: string | number) => {
  removeVideo(id)
  emit('update:videos', localVideos.value)
  emit('remove', id)
}
</script>
```

### ШАГ 9: Создать index.ts
```typescript
// features/media/video-upload/index.ts
export { default as VideoUpload } from './ui/VideoUpload.vue'
export type { Video, VideoFormat, VideoUploadProps, VideoUploadEmits } from './model/types'
```

### ШАГ 10: Удалить старый файл
```bash
# После успешного тестирования
rm resources/js/src/features/media/VideoUpload.vue
```

## ✅ КРИТЕРИИ ГОТОВНОСТИ

1. [ ] Структура папок создана согласно FSD
2. [ ] types.ts содержит интерфейсы Video и VideoFormat
3. [ ] useVideoUpload.ts скопирован из архива
4. [ ] useFormatDetection.ts скопирован из архива  
5. [ ] FormatWarning.vue показывает предупреждения
6. [ ] VideoItem.vue отображает одно видео с прогрессом
7. [ ] VideoList.vue управляет списком видео
8. [ ] VideoUploadZone.vue поддерживает drag & drop
9. [ ] VideoUpload.vue не превышает 150 строк
10. [ ] index.ts экспортирует компонент и типы
11. [ ] Старый монолитный файл удален

## 🧪 ТЕСТИРОВАНИЕ

1. Открыть страницу создания/редактирования объявления
2. Проверить загрузку видео через выбор файлов
3. Проверить drag & drop видео
4. Проверить отображение превью (если поддерживается)
5. Проверить прогресс загрузки
6. Проверить удаление видео
7. Проверить ограничения по размеру и количеству
8. Проверить предупреждения для HEVC в Chromium
9. Сохранить и проверить сохранение

## ⚠️ ВАЖНЫЕ МОМЕНТЫ

1. **ИСПОЛЬЗОВАТЬ** composables из архива, не писать заново
2. **ПРОВЕРИТЬ** ограничения по размеру файлов
3. **ДОБАВИТЬ** определение формата для совместимости
4. **ПОКАЗЫВАТЬ** прогресс загрузки для UX
5. **ВАЛИДИРОВАТЬ** форматы видео на клиенте

## 📞 КООРДИНАЦИЯ

- **Не зависит от:** Задачи Photo Upload (можно делать параллельно)
- **Блокирует:** Обновление импортов в AdForm.vue
- **Результат:** Готовая FSD структура video-upload/

---
**Статус:** Готово к выполнению  
**Приоритет:** Высокий  
**Время:** ~3 часа
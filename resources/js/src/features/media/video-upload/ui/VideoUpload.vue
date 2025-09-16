<template>
  <!-- 1. Loading state (skeleton) -->
  <VideoUploadSkeleton v-if="isLoading" />

  <!-- 2. Error state -->
  <div v-else-if="hasError" class="video-upload space-y-4">
    <div class="rounded-lg border-2 border-red-200 bg-red-50 p-6">
      <p class="text-red-600 font-medium mb-2">Произошла ошибка</p>
      <p class="text-red-500 text-sm mb-4">{{ error }}</p>
      <button @click="resetError" class="px-4 py-2 bg-red-500 text-white rounded hover:bg-red-600">
        Попробовать снова
      </button>
    </div>
  </div>

  <!-- 3. Content state -->
  <div v-else class="video-upload space-y-4">


    <!-- Предупреждение о формате для Chromium -->
    <FormatWarning 
      v-if="detectedFormat !== null && detectedFormat !== undefined"
      :format="detectedFormat"
      :browser="currentBrowser"
    />

    <!-- Если есть видео - показываем список + доп зону -->
    <div v-if="hasVideos" class="space-y-3">
      <!-- Обертка для списка видео -->
      <div class="border-2 border-dashed border-gray-300 rounded-lg pt-4 px-4 pb-2">
        <!-- Список видео -->
        <VideoList
          :videos="safeVideos"
          :dragged-index="draggedIndex"
          :drag-over-index="dragOverIndex"
          @remove="handleRemoveVideo"
          @dragstart="handleDragStart"
          @dragover="handleDragOver"
          @drop="onDragDrop"
          @dragend="handleDragEnd"
        />
      </div>
      
      <!-- Зона загрузки (ПОСЛЕ видео) -->
      <VideoUploadZone
        v-if="canAddMoreVideos"
        ref="uploadZone"
        :max-size="maxSize"
        :accepted-formats="acceptedFormats"
        @files-selected="handleFilesSelected"
      />
    </div>

    <!-- Empty state (если нет видео) МИНИМАЛИСТИЧНЫЙ -->
    <div 
      v-if="isEmpty" 
      class="border-2 border-dashed rounded-lg transition-colors cursor-pointer"
      :class="{ 
        'border-blue-400 bg-blue-50': isDragOver,
        'border-gray-300': !isDragOver
      }"
      @drop.prevent="handleDrop"
      @dragover.prevent="isDragOver = true"
      @dragleave.prevent="isDragOver = false"
      @click="openFileDialog"
    >
      <input
        ref="fileInput"
        type="file"
        multiple
        :accept="acceptedFormats.join(',')"
        @change="handleFileSelect"
        class="hidden"
      />
      
      <!-- Основная строка: все по центру вертикально -->
      <div class="text-center py-3 px-4">
        <div class="flex items-center justify-center space-x-2 mb-3">
          <svg class="h-5 w-5 text-gray-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                  d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
          </svg>
          <span class="text-sm text-gray-500">
            {{ isDragOver ? 'Отпустите файлы здесь' : 'Перетащите видео в эту область или нажмите выбрать видео' }}
          </span>
        </div>
        <button 
          type="button"
          class="px-3 py-1.5 bg-blue-500 text-white text-sm rounded hover:bg-blue-600"
        >
          Выбрать видео
        </button>
      </div>
    </div>
    
    <!-- Информация об ограничениях -->
    <div class="text-sm text-gray-800 space-y-1">
      <p>• Максимум 3 видео</p>
      <p>• Длительность: от 5 секунд до 60 секунд</p>
      <p>• Не должны содержать водяные знаки других сайтов</p>
      <p>• Видео должны соответствовать описанию услуг</p>
      <p class="text-amber-600 font-medium">• Без проверочной фотографии видеофайл не будет размещен</p>
    </div>
    
    <!-- Ошибки загрузки -->
    <div v-if="error !== null && error !== undefined && error !== ''" class="rounded-md bg-red-50 p-3">
      <p class="text-sm text-red-800">{{ error }}</p>
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
import VideoUploadSkeleton from './components/VideoUploadSkeleton.vue'
import type { VideoUploadProps, VideoUploadEmits } from '../model/types'

const props = withDefaults(defineProps<VideoUploadProps>(), {
  videos: () => [],
  maxFiles: 3,
  maxSize: 50 * 1024 * 1024, // 50MB
  acceptedFormats: () => ['video/mp4', 'video/webm', 'video/ogg', 'video/quicktime', 'video/x-msvideo']
})

const emit = defineEmits<VideoUploadEmits>()

// Refs
const uploadZone = ref<InstanceType<typeof VideoUploadZone>>()
const fileInput = ref<HTMLInputElement>()
const isDragOver = ref(false)
const isLoading = ref(false)
const hasError = ref(false)

// Используем composables для логики
const {
  localVideos,
  error,
  draggedIndex,
  dragOverIndex,
  addVideos,
  removeVideo,
  uploadVideo,
  initializeFromProps,
  handleDragStart,
  handleDragOver,
  handleDragDrop,
  handleDragEnd
} = useVideoUpload()

const {
  detectVideoFormat,
  detectedFormat,
  currentBrowser
} = useFormatDetection()

// Computed properties с защитой от null/undefined (требование CLAUDE.md)
const safeVideos = computed(() => {
  // Явная проверка на null и undefined
  return localVideos.value !== null && localVideos.value !== undefined ? localVideos.value : []
})

const safeVideosCount = computed(() => {
  // Явная проверка на null и undefined
  return safeVideos.value !== null && safeVideos.value !== undefined ? safeVideos.value.length : 0
})

const isEmpty = computed(() => {
  return safeVideosCount.value === 0
})

const hasVideos = computed(() => {
  return safeVideosCount.value > 0
})

const canAddMoreVideos = computed(() => {
  return safeVideosCount.value < props.maxFiles
})

const maxSizeInMB = computed(() => {
  return Math.round(props.maxSize / (1024 * 1024))
})

// Инициализация видео из props (упрощённая логика как в фото)
watch(() => props.videos, (newVideos) => {
  if (newVideos && newVideos.length > 0 && localVideos.value.length === 0) {
    initializeFromProps(newVideos)
  }
}, { immediate: true })

// Обработчики перетаскивания для пустого состояния
const handleFileSelect = (event: Event) => {
  const target = event.target as HTMLInputElement
  const files = Array.from(target.files || [])
  if (files.length > 0) {
    handleFilesSelected(files)
  }
  target.value = ''
}

const handleDrop = (event: DragEvent) => {
  isDragOver.value = false
  const files = Array.from(event.dataTransfer?.files || [])
  if (files.length > 0) {
    handleFilesSelected(files)
  }
}

// Обработчик drag&drop для изменения порядка (как у фото)
const onDragDrop = (index: number) => {
  handleDragDrop(index)
  // Эмитим изменения после drag&drop
  emit('update:videos', safeVideos.value)
}

// Обработчик выбора файлов
const handleFilesSelected = async (files: File[]) => {
  // Упрощённая проверка как в фото
  if (!files || files.length === 0) {
    return
  }

  // Проверка количества
  if (safeVideosCount.value + files.length > props.maxFiles) {
    error.value = `Максимум ${props.maxFiles} видео`
    hasError.value = true
    return
  }
  
  // Определение формата первого файла
  if (files.length > 0 && files[0]) {
    detectedFormat.value = await detectVideoFormat(files[0])
  }
  
  try {
    // Добавление видео
    await addVideos(files)
    
    emit('update:videos', safeVideos.value)
    
    // Начать загрузку для каждого видео
    for (const video of safeVideos.value) {
      if (video && video.file) {
        await uploadVideo(video.file)
      }
    }
    
    hasError.value = false
  } catch (uploadError) {
    console.error('Ошибка загрузки видео:', uploadError)
    hasError.value = true
  }
}

// Обработчик удаления видео
const handleRemoveVideo = (id: string | number) => {
  // Явная проверка на null и undefined
  if (id !== null && id !== undefined) {
    removeVideo(id)
    emit('update:videos', safeVideos.value)
  }
}

// Функция для открытия диалога выбора файлов
const openFileDialog = () => {
  if (isEmpty.value) {
    // Для пустого состояния используем внутренний input
    fileInput.value?.click()
  } else if (uploadZone.value !== null && uploadZone.value !== undefined) {
    // Для состояния с видео используем VideoUploadZone
    uploadZone.value.openFileDialog()
  }
}

// Сброс ошибки
const resetError = () => {
  hasError.value = false
  error.value = null
}

// Expose методы для родительского компонента
defineExpose({
  openFileDialog
})
</script>

<style scoped>
.video-upload {
  width: 100%;
}
</style>

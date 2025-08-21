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
    <!-- Заголовок секции -->
    <div class="flex justify-between items-center">
      <h3 class="text-lg font-medium">Видео</h3>
      <span class="text-sm text-gray-500">
        {{ safeVideosCount }} из {{ maxFiles }}
      </span>
    </div>

    <!-- Предупреждение о формате для Chromium -->
    <FormatWarning 
      v-if="detectedFormat !== null && detectedFormat !== undefined"
      :format="detectedFormat"
      :browser="currentBrowser"
    />

    <!-- Список видео (ПЕРВЫМ) -->
    <VideoList
      v-if="hasVideos"
      :videos="safeVideos"
      @remove="handleRemoveVideo"
    />

    <!-- Зона загрузки (ПОСЛЕ видео) -->
    <VideoUploadZone
      v-if="!isEmpty && canAddMoreVideos"
      ref="uploadZone"
      :max-size="maxSize"
      :accepted-formats="acceptedFormats"
      @files-selected="handleFilesSelected"
    />

    <!-- Empty state (если нет видео) -->
    <div v-if="isEmpty" class="text-center py-12 border-2 border-dashed border-gray-300 rounded-lg">
      <svg class="w-24 h-24 mx-auto mb-4 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
              d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 00-2-2V8a2 2 0 00-2 2v8a2 2 0 002 2z" />
      </svg>
      <p class="text-gray-500 mb-4">Видео не загружены</p>
      <button 
        @click="openFileDialog"
        class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600"
      >
        Загрузить видео
      </button>
    </div>
    
    <!-- Ошибки загрузки -->
    <div v-if="error !== null && error !== undefined && error !== ''" class="rounded-md bg-red-50 p-3">
      <p class="text-sm text-red-800">{{ error }}</p>
    </div>
    
    <!-- Информация об ограничениях -->
    <div class="text-xs text-gray-500 space-y-1">
      <p>• Максимум {{ maxFiles }} видео</p>
      <p>• Размер файла до {{ maxSizeInMB }}MB</p>
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
import VideoUploadSkeleton from './components/VideoUploadSkeleton.vue'
import type { VideoUploadProps, VideoUploadEmits } from '../model/types'

const props = withDefaults(defineProps<VideoUploadProps>(), {
  videos: () => [],
  maxFiles: 5,
  maxSize: 100 * 1024 * 1024, // 100MB
  acceptedFormats: () => ['video/mp4', 'video/webm', 'video/ogg']
})

const emit = defineEmits<VideoUploadEmits>()

// Refs
const uploadZone = ref<InstanceType<typeof VideoUploadZone>>()
const isLoading = ref(false)
const hasError = ref(false)

// Используем composables для логики
const {
  localVideos,
  error,
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

// Computed для защиты от null/undefined (требование CLAUDE.md)
const safeVideos = computed(() => {
  // Явная проверка на null и undefined
  if (localVideos.value === null || localVideos.value === undefined) {
    return []
  }
  return localVideos.value
})

const safeVideosCount = computed(() => {
  return safeVideos.value.length
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

// Инициализация видео из props
watch(() => props.videos, (newVideos) => {
  // Явная проверка на null и undefined
  if (newVideos !== null && 
      newVideos !== undefined && 
      newVideos.length > 0 && 
      localVideos.value !== null &&
      localVideos.value !== undefined &&
      localVideos.value.length === 0) {
    initializeFromProps(newVideos)
  }
}, { immediate: true })

// Обработчик выбора файлов
const handleFilesSelected = async (files: File[]) => {
  // Явная проверка на null и undefined
  if (files === null || files === undefined || files.length === 0) {
    return
  }

  // Проверка количества с явными проверками
  if (safeVideosCount.value + files.length > props.maxFiles) {
    error.value = `Максимум ${props.maxFiles} видео`
    hasError.value = true
    return
  }
  
  // Определение формата первого файла
  if (files.length > 0 && files[0] !== null && files[0] !== undefined) {
    detectedFormat.value = await detectVideoFormat(files[0])
  }
  
  try {
    // Добавление видео
    await addVideos(files)
    emit('update:videos', safeVideos.value)
    
    // Начать загрузку для каждого видео
    for (const video of safeVideos.value) {
      // Явная проверка полей видео
      if (video !== null && 
          video !== undefined && 
          video.file !== null && 
          video.file !== undefined && 
          video.isUploading !== true && 
          (video.url === null || video.url === undefined || video.url === '')) {
        await uploadVideo(video)
        emit('upload', video)
      }
    }
  } catch (err) {
    console.error('Ошибка при загрузке видео:', err)
    hasError.value = true
    error.value = 'Ошибка при загрузке видео'
  }
}

// Обработчик удаления видео
const handleRemoveVideo = (id: string | number) => {
  // Явная проверка ID
  if (id === null || id === undefined) {
    console.error('Попытка удалить видео без ID')
    return
  }
  
  removeVideo(id)
  emit('update:videos', safeVideos.value)
  emit('remove', id)
}

// Сброс ошибки
const resetError = () => {
  hasError.value = false
  error.value = ''
}

// Открытие диалога выбора файлов
const openFileDialog = () => {
  // Создаем временный input для выбора файлов
  const input = document.createElement('input')
  input.type = 'file'
  input.multiple = true
  input.accept = props.acceptedFormats.join(',')
  
  input.onchange = (event) => {
    const target = event.target as HTMLInputElement
    const files = Array.from(target.files || [])
    if (files.length > 0) {
      handleFilesSelected(files)
    }
  }
  
  input.click()
}
</script>
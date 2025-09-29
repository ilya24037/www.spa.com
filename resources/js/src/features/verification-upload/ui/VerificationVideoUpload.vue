<template>
  <div class="verification-video-upload">
    <!-- Инструкции -->
    <div class="instructions-grid grid md:grid-cols-2 gap-4 mb-6">
      <!-- Шаг 1 -->
      <div class="instruction-card bg-gray-50 rounded-lg p-4">
        <div class="flex items-start gap-3">
          <div class="flex-shrink-0 w-8 h-8 bg-blue-500 text-white rounded-full flex items-center justify-center font-bold">
            1
          </div>
          <div class="flex-1">
            <h3 class="font-semibold mb-2">Подготовка</h3>
            <p class="text-sm text-gray-600 mb-2">Запишите видео где вы:</p>
            <ul class="text-sm text-gray-600 space-y-1">
              <li>• Показываете лицо</li>
              <li>• Произносите вслух дату</li>
              <li>• Называете сайт "FEIPITER"</li>
            </ul>
            <div class="bg-white border border-gray-200 rounded p-3 mt-2">
              <p class="text-sm font-medium">Пример фразы:</p>
              <p class="text-sm text-gray-700 italic">
                "{{ currentDate }}, для сайта FEIPITER"
              </p>
            </div>
          </div>
        </div>
      </div>
      
      <!-- Шаг 2 -->
      <div class="instruction-card bg-gray-50 rounded-lg p-4">
        <div class="flex items-start gap-3">
          <div class="flex-shrink-0 w-8 h-8 bg-blue-500 text-white rounded-full flex items-center justify-center font-bold">
            2
          </div>
          <div class="flex-1">
            <h3 class="font-semibold mb-2">Требования к видео</h3>
            <ul class="text-sm text-gray-600 space-y-1">
              <li>• Длительность: 5-30 секунд</li>
              <li>• Формат: MP4, MOV, AVI</li>
              <li>• Размер: до 50MB</li>
              <li>• Качество: HD или выше</li>
              <li>• Звук обязателен</li>
              <li>• Хорошее освещение</li>
            </ul>
            <div class="mt-3 p-2 bg-yellow-50 border border-yellow-200 rounded">
              <p class="text-xs text-yellow-700">
                ⚠️ Видео должно быть записано сегодня
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Зона загрузки -->
    <div v-if="!currentFile && !uploadedPath" class="upload-zone">
      <label 
        class="upload-label"
        :class="{ 'dragging': isDragging }"
        @dragover.prevent="isDragging = true"
        @dragleave.prevent="isDragging = false"
        @drop.prevent="handleDrop"
      >
        <input
          ref="fileInput"
          type="file"
          accept="video/mp4,video/quicktime,video/x-msvideo,video/*"
          class="hidden"
          @change="handleFileSelect"
          :disabled="uploading"
        >
        
        <div class="upload-content">
          <svg class="w-12 h-12 text-gray-400 mb-3"
               fill="none"
               stroke="currentColor"
               viewBox="0 0 24 24">
            <path stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
          </svg>
          
          <p class="text-lg font-medium text-gray-700 mb-1">
            Загрузить проверочное видео
          </p>
          <p class="text-sm text-gray-500">
            MP4, MOV, AVI до 50MB
          </p>
          <p class="text-xs text-gray-400 mt-2">
            Нажмите или перетащите файл
          </p>
        </div>
      </label>
    </div>
    
    <!-- Предпросмотр -->
    <div v-if="currentFile || uploadedPath" class="preview mt-4">
      <div class="relative">
        <!-- Видео плеер -->
        <div class="video-container bg-black rounded-lg overflow-hidden">
          <video 
            ref="videoPlayer"
            :src="previewUrl || uploadedPath"
            controls
            class="w-full max-h-64"
            @loadedmetadata="handleVideoLoad"
          >
            Ваш браузер не поддерживает видео
          </video>
        </div>
        
        <!-- Информация о видео -->
        <div v-if="videoInfo" class="mt-2 p-3 bg-gray-50 rounded-lg">
          <div class="grid grid-cols-2 gap-2 text-sm">
            <div>
              <span class="text-gray-500">Длительность:</span>
              <span class="ml-1 font-medium">{{ formatDuration(videoInfo.duration) }}</span>
            </div>
            <div>
              <span class="text-gray-500">Размер:</span>
              <span class="ml-1 font-medium">{{ formatFileSize(videoInfo.size) }}</span>
            </div>
          </div>
        </div>
        
        <!-- Статус -->
        <div v-if="uploading" class="absolute inset-0 bg-black bg-opacity-50 rounded-lg flex items-center justify-center">
          <div class="text-white text-center">
            <svg class="animate-spin h-8 w-8 mx-auto mb-2" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25"
                      cx="12"
                      cy="12"
                      r="10"
                      stroke="currentColor"
                      stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <div>Загрузка видео...</div>
            <div class="text-sm mt-1">{{ uploadProgress }}%</div>
          </div>
        </div>
        
        <!-- Кнопки действий -->
        <div v-if="!uploading" class="mt-3 flex gap-2">
          <button
            v-if="!uploadedPath"
            @click="uploadVideo"
            :disabled="!isValidVideo"
            :class="[
              'px-3 py-1 rounded transition-colors',
              isValidVideo 
                ? 'bg-blue-500 text-white hover:bg-blue-600' 
                : 'bg-gray-300 text-gray-500 cursor-not-allowed'
            ]"
          >
            Отправить на проверку
          </button>
          <button
            @click="removeVideo"
            class="px-3 py-1 bg-red-500 text-white rounded hover:bg-red-600 transition-colors"
          >
            Удалить
          </button>
        </div>
      </div>
    </div>
    
    <!-- Ошибка -->
    <div v-if="error" class="mt-4 bg-red-50 border border-red-200 rounded-lg p-3">
      <p class="text-sm text-red-600">{{ error }}</p>
    </div>
    
    <!-- Предупреждение о размере -->
    <div v-if="currentFile && videoInfo && videoInfo.size > 30 * 1024 * 1024" 
         class="mt-4 bg-yellow-50 border border-yellow-200 rounded-lg p-3">
      <p class="text-sm text-yellow-800">
        ⚠️ Видео файл большой ({{ formatFileSize(videoInfo.size) }}). Загрузка может занять время.
      </p>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { verificationApi } from '../api/verificationApi'

interface Props {
  adId: number
  currentVideo?: string | null
  status?: string
}

const props = defineProps<Props>()

const emit = defineEmits<{
  uploaded: [path: string]
  deleted: []
}>()

// Состояние
const fileInput = ref<HTMLInputElement>()
const videoPlayer = ref<HTMLVideoElement>()
const currentFile = ref<File | null>(null)
const uploadedPath = ref<string | null>(props.currentVideo || null)
const previewUrl = ref<string | null>(null)
const isDragging = ref(false)
const uploading = ref(false)
const uploadProgress = ref(0)
const error = ref<string | null>(null)

// Информация о видео
const videoInfo = ref<{
  duration: number
  size: number
} | null>(null)

// Вычисляемые
const currentDate = computed(() => {
  const date = new Date()
  return date.toLocaleDateString('ru-RU', {
    day: 'numeric',
    month: 'long',
    year: 'numeric'
  })
})

const isValidVideo = computed(() => {
  if (!videoInfo.value) return false
  
  // Проверка длительности (5-30 секунд)
  if (videoInfo.value.duration < 5 || videoInfo.value.duration > 30) {
    return false
  }
  
  // Проверка размера (до 50MB)
  if (videoInfo.value.size > 50 * 1024 * 1024) {
    return false
  }
  
  return true
})

// Методы
const handleFileSelect = (event: Event) => {
  const target = event.target as HTMLInputElement
  if (target.files && target.files[0]) {
    selectFile(target.files[0])
  }
}

const handleDrop = (event: DragEvent) => {
  isDragging.value = false
  if (event.dataTransfer?.files && event.dataTransfer.files[0]) {
    selectFile(event.dataTransfer.files[0])
  }
}

const selectFile = (file: File) => {
  error.value = null
  
  // Валидация типа
  if (!file.type.startsWith('video/')) {
    error.value = 'Недопустимый формат файла. Загрузите видео файл'
    return
  }
  
  // Валидация размера
  if (file.size > 50 * 1024 * 1024) {
    error.value = 'Файл слишком большой. Максимум 50MB'
    return
  }
  
  currentFile.value = file
  videoInfo.value = {
    duration: 0,
    size: file.size
  }
  
  // Создаем preview
  const url = URL.createObjectURL(file)
  previewUrl.value = url
}

const handleVideoLoad = () => {
  if (videoPlayer.value && videoInfo.value) {
    videoInfo.value.duration = videoPlayer.value.duration
    
    // Проверяем длительность
    if (videoInfo.value.duration < 5) {
      error.value = 'Видео слишком короткое. Минимум 5 секунд'
    } else if (videoInfo.value.duration > 30) {
      error.value = 'Видео слишком длинное. Максимум 30 секунд'
    } else {
      error.value = null
    }
  }
}

const uploadVideo = async () => {
  if (!currentFile.value || !isValidVideo.value) return
  
  uploading.value = true
  uploadProgress.value = 0
  error.value = null
  
  // Симуляция прогресса загрузки
  const progressInterval = setInterval(() => {
    if (uploadProgress.value < 90) {
      uploadProgress.value += 10
    }
  }, 200)
  
  try {
    const result = await verificationApi.uploadVideo(props.adId, currentFile.value)
    
    clearInterval(progressInterval)
    uploadProgress.value = 100
    
    if (result.success) {
      uploadedPath.value = result.path || null
      currentFile.value = null
      if (previewUrl.value) {
        URL.revokeObjectURL(previewUrl.value)
        previewUrl.value = null
      }
      emit('uploaded', result.path!)
    } else {
      error.value = result.message
    }
  } catch (err: any) {
    clearInterval(progressInterval)
    error.value = err.response?.data?.message || 'Ошибка при загрузке видео'
  } finally {
    uploading.value = false
    uploadProgress.value = 0
  }
}

const removeVideo = async () => {
  if (uploadedPath.value) {
    try {
      await verificationApi.deleteFiles(props.adId)
      emit('deleted')
    } catch (err) {
      console.error('Failed to delete video:', err)
    }
  }
  
  currentFile.value = null
  videoInfo.value = null
  uploadedPath.value = null
  error.value = null
  
  if (previewUrl.value) {
    URL.revokeObjectURL(previewUrl.value)
    previewUrl.value = null
  }
  
  if (fileInput.value) {
    fileInput.value.value = ''
  }
}

const formatDuration = (seconds: number): string => {
  const mins = Math.floor(seconds / 60)
  const secs = Math.floor(seconds % 60)
  return `${mins}:${secs.toString().padStart(2, '0')}`
}

const formatFileSize = (bytes: number): string => {
  if (bytes < 1024) return bytes + ' B'
  if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB'
  return (bytes / (1024 * 1024)).toFixed(1) + ' MB'
}

// Watchers
watch(() => props.currentVideo, (newVal) => {
  if (newVal && !currentFile.value) {
    uploadedPath.value = newVal
  }
})

// Cleanup
watch(previewUrl, (newUrl, oldUrl) => {
  if (oldUrl && oldUrl !== newUrl) {
    URL.revokeObjectURL(oldUrl)
  }
})
</script>

<style scoped>
.upload-label {
  @apply block w-full cursor-pointer;
  @apply border-2 border-dashed border-gray-300 rounded-lg;
  @apply hover:border-gray-400 transition-colors;
  @apply p-8 text-center;
}

.upload-label.dragging {
  @apply border-blue-500 bg-blue-50;
}

.upload-content {
  @apply flex flex-col items-center;
}

.video-container {
  @apply relative;
  min-height: 200px;
}
</style>
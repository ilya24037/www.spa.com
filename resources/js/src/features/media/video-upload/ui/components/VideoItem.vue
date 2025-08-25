<template>
  <div class="video-item relative group bg-white rounded-lg border overflow-hidden">
    <!-- Превью видео -->
    <div class="aspect-square bg-gray-100 relative flex items-center justify-center">
      <!-- Для сохраненного видео показываем мини-плеер -->
      <video 
        v-if="safeVideo.url !== null && safeVideo.url !== undefined && safeVideo.url !== ''" 
        :src="safeVideo.url"
        class="w-full h-full object-contain"
        controls
        muted
        preload="metadata"
        playsinline
        disablepictureinpicture
        :title="`Видео ${index + 1}`"
        @error="handleVideoError"
        @loadedmetadata="handleVideoLoaded"
      />
      <!-- Для загружаемого видео показываем превью -->
      <img 
        v-else-if="safeVideo.thumbnail !== null && safeVideo.thumbnail !== undefined && safeVideo.thumbnail !== ''" 
        :src="safeVideo.thumbnail" 
        :alt="`Видео ${index + 1}`"
        class="w-full h-full object-contain"
      />
      <!-- Заглушка когда нет ни url ни thumbnail -->
      <div v-else class="w-full h-full flex items-center justify-center">
        <div class="text-center">
          <svg class="w-12 h-12 text-gray-400 mx-auto mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                  d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
          </svg>
          <p class="text-xs text-gray-500">Видео недоступно</p>
        </div>
      </div>


      
      <!-- Кнопка удаления -->
      <div class="absolute top-2 right-2 opacity-0 group-hover:opacity-100 transition-opacity">
        <button 
          @click="$emit('remove')"
          :disabled="safeVideo.isUploading"
          class="p-1.5 bg-white rounded shadow hover:bg-red-50 disabled:opacity-50"
          title="Удалить"
        >
          <svg class="w-4 h-4 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                  d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
          </svg>
        </button>
      </div>

      <!-- Прогресс загрузки -->
      <div v-if="safeVideo.isUploading === true" class="absolute bottom-0 left-0 right-0 p-2">
        <div class="w-full bg-gray-200 rounded-full h-1.5">
          <div 
            class="bg-blue-500 h-1.5 rounded-full transition-all duration-300"
            :style="{ width: `${safeVideo.uploadProgress || 0}%` }"
          />
        </div>
      </div>
    </div>
    

  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import type { Video } from '../../model/types'

interface Props {
  video: Video
  index: number
}

const props = defineProps<Props>()

const emit = defineEmits<{
  remove: []
}>()

// Computed для защиты от null/undefined (требование CLAUDE.md)
const safeVideo = computed(() => {
  // Явная проверка на null и undefined
  if (props.video === null || props.video === undefined) {
    return {} as Video
  }
  return props.video
})

// Форматирование размера файла
const formatSize = (bytes?: number): string => {
  // Явная проверка на null и undefined
  if (bytes === null || bytes === undefined || bytes === 0) return ''
  const mb = bytes / (1024 * 1024)
  return `${mb.toFixed(1)} MB`
}

// Форматирование длительности видео
const formatDuration = (seconds?: number): string => {
  // Явная проверка на null и undefined
  if (seconds === null || seconds === undefined || seconds === 0) return ''
  const mins = Math.floor(seconds / 60)
  const secs = Math.floor(seconds % 60)
  return `${mins}:${secs.toString().padStart(2, '0')}`
}

// Получение имени видео для отображения
const getVideoDisplayName = (): string => {
  // Если есть файл, показываем его имя
  if (safeVideo.value?.file?.name) {
    return safeVideo.value.file.name
  }
  
  // Если есть URL, извлекаем имя файла из пути
  if (safeVideo.value?.url) {
    const urlParts = safeVideo.value.url.split('/')
    const filename = urlParts[urlParts.length - 1]
    if (filename && filename !== 'undefined') {
      return filename
    }
  }
  
  // Заглушка по умолчанию
  return `Видео ${props.index + 1}`
}



// Обработка ошибок загрузки видео
const handleVideoError = (event: Event) => {
  const video = event.target as HTMLVideoElement
  const error = video.error
  
  console.warn('Ошибка загрузки видео:', {
    url: safeVideo.value.url,
    error: error?.message || 'Неизвестная ошибка',
    code: error?.code
  })
}

// Обработка успешной загрузки метаданных
const handleVideoLoaded = (event: Event) => {
  const video = event.target as HTMLVideoElement
  console.log('Видео успешно загружено:', {
    url: safeVideo.value.url,
    duration: video.duration,
    videoWidth: video.videoWidth,
    videoHeight: video.videoHeight
  })
}
</script>
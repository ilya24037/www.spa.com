<template>
  <div class="video-item relative group bg-white rounded-lg border overflow-hidden cursor-move hover:shadow-md transition-shadow">
    <!-- Превью видео -->
    <div class="aspect-square bg-gray-100 relative flex items-center justify-center">
      <!-- Для сохраненного видео показываем мини-плеер -->
      <video 
        v-if="!videoError && getVideoUrl() !== null && getVideoUrl() !== ''" 
        :src="getVideoUrl()"
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
      <!-- Показываем thumbnail если видео не загрузилось или есть thumbnail -->
      <img 
        v-else-if="safeVideo.thumbnail !== null && safeVideo.thumbnail !== undefined && safeVideo.thumbnail !== ''" 
        :src="safeVideo.thumbnail" 
        :alt="`Видео ${index + 1}`"
        class="w-full h-full object-contain cursor-pointer"
        @click="tryPlayVideo"
      />
      <!-- Заглушка когда нет ни url ни thumbnail -->
      <div v-else class="w-full h-full flex items-center justify-center">
        <div class="text-center">
          <svg class="w-12 h-12 text-gray-400 mx-auto mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                  d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
          </svg>
          <p class="text-xs text-gray-500">
            {{ videoError ? 'Ошибка загрузки видео' : 'Видео недоступно' }}
          </p>
          <button v-if="videoError && getVideoUrl()" @click="retryVideo" class="mt-2 text-xs text-blue-500 hover:text-blue-600">
            Попробовать снова
          </button>
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
import { ref, computed, onUnmounted } from 'vue'
import type { Video } from '../../model/types'

interface Props {
  video: Video
  index: number
}

const props = defineProps<Props>()

const emit = defineEmits<{
  remove: []
}>()

// Состояние для отслеживания ошибок видео
const videoError = ref(false)

// Храним созданный blob URL для очистки при размонтировании
const createdBlobUrl = ref<string | null>(null)

// Computed для защиты от null/undefined (требование CLAUDE.md)
const safeVideo = computed(() => {
  // Явная проверка на null и undefined
  if (props.video === null || props.video === undefined) {
    return {} as Video
  }
  
  // Дополнительная проверка: если video это строка JSON, пытаемся распарсить
  if (typeof props.video === 'string') {
    try {
      const parsed = JSON.parse(props.video)
      console.warn('VideoItem: получена строка вместо объекта, распарсили:', parsed)
      return parsed
    } catch (e) {
      // Если это просто путь к файлу (старый формат), конвертируем в объект
      return {
        id: `video_${props.index}`,
        url: props.video,
        file: null,
        isUploading: false
      } as Video
    }
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

// Получение корректного URL видео
const getVideoUrl = (): string => {
  // ВАЖНО: Сначала проверяем file для локального превью (до сохранения)
  if (safeVideo.value?.file instanceof File) {
    // Если blob URL еще не создан, создаем его
    if (!createdBlobUrl.value) {
      createdBlobUrl.value = URL.createObjectURL(safeVideo.value.file)
      console.log('VideoItem: создан blob URL для превью:', createdBlobUrl.value)
    }
    return createdBlobUrl.value
  }
  
  // Проверяем что есть URL
  if (!safeVideo.value?.url) {
    return ''
  }
  
  const url = safeVideo.value.url
  
  // Проверяем что URL это строка, а не объект
  if (typeof url !== 'string') {
    console.error('VideoItem: URL не строка:', url)
    return ''
  }
  
  // Поддержка blob URL (для локального превью)
  if (url.startsWith('blob:')) {
    console.log('VideoItem: используем существующий blob URL:', url)
    return url
  }
  
  // Игнорируем base64 данные (они слишком большие для браузера)
  if (url.startsWith('data:')) {
    console.error('VideoItem: URL содержит base64 данные, которые нельзя воспроизвести')
    videoError.value = true
    return ''
  }
  
  // Проверяем что URL начинается с /storage/ или http
  if (url.startsWith('/storage/') || url.startsWith('http://') || url.startsWith('https://')) {
    return url
  }
  
  // Если URL выглядит как JSON, значит проблема
  if (url.startsWith('{') || url.startsWith('[')) {
    console.error('VideoItem: URL содержит JSON вместо пути:', url)
    return ''
  }
  
  // Для относительных путей добавляем /storage/
  if (!url.startsWith('/')) {
    return '/storage/' + url
  }
  
  return url
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
  
  // Помечаем что видео не загрузилось
  videoError.value = true
  
  // Если есть thumbnail, показываем его вместо видео
  if (safeVideo.value.thumbnail) {
    console.log('Показываем thumbnail вместо видео')
  }
}

// Обработка успешной загрузки метаданных
const handleVideoLoaded = (event: Event) => {
  const video = event.target as HTMLVideoElement
  
  // Сбрасываем флаг ошибки
  videoError.value = false
}

// Попытка воспроизвести видео при клике на thumbnail
const tryPlayVideo = () => {
  if (getVideoUrl()) {
    // Открываем видео в новой вкладке
    window.open(getVideoUrl(), '_blank')
  }
}

// Попытка перезагрузить видео
const retryVideo = () => {
  videoError.value = false
  // Vue перерендерит компонент и попробует загрузить видео снова
}

// Очистка blob URL при размонтировании для предотвращения утечки памяти
onUnmounted(() => {
  if (createdBlobUrl.value) {
    URL.revokeObjectURL(createdBlobUrl.value)
    createdBlobUrl.value = null
  }
})
</script>
<template>
  <div class="video-upload">
    <h4 class="field-label">Видео презентация</h4>
    <p class="field-hint">
      Добавьте короткое видео (до 50MB) для демонстрации ваших услуг
    </p>

    <!-- Индикатор поддержки форматов -->
    <div v-if="formatSupport.length > 0" class="format-support">
      <h5 class="support-title">Поддержка форматов в вашем браузере:</h5>
      <div class="support-list">
        <div 
          v-for="format in formatSupport" 
          :key="format.name"
          class="support-item"
          :class="{ 
            'supported': format.level === 'probably', 
            'maybe': format.level === 'maybe',
            'unsupported': format.level === '' 
          }"
        >
          <span class="support-icon">{{ format.icon }}</span>
          <span class="support-name">{{ format.name }}</span>
          <span class="support-status">{{ format.status }}</span>
        </div>
      </div>
      
      <!-- Рекомендации для Chromium -->
      <div v-if="isChromiumBased" class="chromium-warning">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor">
          <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
          <line x1="12" y1="9" x2="12" y2="13"/>
          <line x1="12" y1="17" x2="12.01" y2="17"/>
        </svg>
        <span>
          Ваш браузер (Chromium) имеет ограниченную поддержку MP4. 
          Рекомендуем использовать WebM формат или Google Chrome.
        </span>
      </div>
    </div>
    
    <div class="video-container">
      <input
        type="file"
        ref="videoInput"
        accept="video/*"
        @change="handleVideoUpload"
        class="hidden"
      />
      
      <!-- Видео элемент с множественными источниками -->
      <div v-if="localVideos.length > 0" class="video-item">
        <div class="video-wrapper">
          <video 
            :key="videoKey"
            controls
            preload="metadata"
            class="video-element"
            @loadedmetadata="onVideoLoaded"
            @error="onVideoError"
            @canplay="onVideoCanPlay"
          >
            <!-- Множественные источники для кроссбраузерной совместимости -->
            <source 
              v-for="source in videoSources" 
              :key="source.type"
              :src="source.url" 
              :type="source.type"
            />
            Ваш браузер не поддерживает воспроизведение видео
          </video>
          
          <!-- Метаданные видео -->
          <div v-if="videoMetadata" class="video-metadata">
            <div class="metadata-item">
              <span class="metadata-label">Размер:</span>
              <span class="metadata-value">{{ videoMetadata.dimensions }}</span>
            </div>
            <div class="metadata-item">
              <span class="metadata-label">Длительность:</span>
              <span class="metadata-value">{{ videoMetadata.duration }}</span>
            </div>
            <div class="metadata-item">
              <span class="metadata-label">Размер файла:</span>
              <span class="metadata-value">{{ videoMetadata.fileSize }}</span>
            </div>
          </div>
          
          <!-- Контролы видео -->
          <div class="video-controls">
            <button 
              type="button"
              @click="removeVideo"
              class="control-btn delete-btn"
              title="Удалить видео"
            >
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="3,6 5,6 21,6"/>
                <path d="M19,6v14a2,2,0,0,1-2,2H7a2,2,0,0,1-2-2V6m3,0V4a2,2,0,0,1,2-2h4a2,2,0,0,1,2,2v2"/>
              </svg>
            </button>
          </div>
          
          <!-- Статус совместимости -->
          <div v-if="videoCompatibility" class="compatibility-status" :class="videoCompatibility.class">
            <span class="compatibility-icon">{{ videoCompatibility.icon }}</span>
            <span class="compatibility-text">{{ videoCompatibility.text }}</span>
          </div>
        </div>
      </div>
      
      <!-- Кнопка добавления видео -->
      <div 
        v-else
        class="add-video-btn"
        @click="triggerVideoInput"
      >
        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor">
          <polygon points="23,7 16,12 23,17" stroke-width="2"/>
          <rect x="1" y="5" width="15" height="14" rx="2" ry="2" stroke-width="2"/>
        </svg>
        <span>Добавить видео</span>
        <div class="format-hint">
          Поддерживаемые форматы: MP4, WebM, OGV
        </div>
      </div>
    </div>

    <!-- Ошибки -->
    <div v-if="error" class="error-message">{{ error }}</div>
    
    <!-- Логи для отладки (только в development) -->
    <div v-if="isDevelopment && debugLogs.length > 0" class="debug-logs">
      <details>
        <summary>Логи отладки видео</summary>
        <div class="logs-content">
          <div v-for="(log, index) in debugLogs" :key="index" class="log-entry">
            [{{ log.timestamp }}] {{ log.level }}: {{ log.message }}
          </div>
        </div>
      </details>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch, onMounted } from 'vue'

interface Video {
  id: string | number
  file?: File
  url?: string
  preview?: string
  name?: string
  type?: string
}

interface VideoSource {
  url: string
  type: string
}

interface VideoMetadata {
  dimensions: string
  duration: string
  fileSize: string
}

interface FormatSupport {
  name: string
  type: string
  level: string
  status: string
  icon: string
}

interface Props {
  video?: Array<string | Video>
  errors?: Record<string, string>
}

interface Emits {
  (e: 'update:video', videos: Array<File | string>): void
}

const props = withDefaults(defineProps<Props>(), {
  video: () => [],
  errors: () => ({})
})

const emit = defineEmits<Emits>()

// Refs
const videoInput = ref<HTMLInputElement | null>(null)

// State
const localVideos = ref<Video[]>([])
const error = ref('')
const videoKey = ref(0) // Для принудительного обновления video элемента
const videoMetadata = ref<VideoMetadata | null>(null)
const formatSupport = ref<FormatSupport[]>([])
const debugLogs = ref<Array<{timestamp: string, level: string, message: string}>>([])

// Computed
const isDevelopment = computed(() => import.meta.env.DEV)

const isChromiumBased = computed(() => {
  const userAgent = navigator.userAgent
  return userAgent.includes('Chrome') && !userAgent.includes('Edg') && !userAgent.includes('OPR')
})

const videoSources = computed((): VideoSource[] => {
  if (localVideos.value.length === 0) return []
  
  const video = localVideos.value[0]
  const sources: VideoSource[] = []
  
  if (video.url || video.preview) {
    const url = video.url || video.preview!
    
    // Определяем тип по расширению файла или MIME типу
    let type = video.type || ''
    if (!type) {
      if (url.includes('.webm')) type = 'video/webm'
      else if (url.includes('.mp4')) type = 'video/mp4'
      else if (url.includes('.ogv')) type = 'video/ogg'
    }
    
    sources.push({ url, type })
  }
  
  return sources
})

const videoCompatibility = computed(() => {
  if (localVideos.value.length === 0) return null
  
  const video = localVideos.value[0]
  if (!video.file) return null
  
  const mimeType = video.file.type
  const videoElement = document.createElement('video')
  const support = videoElement.canPlayType(mimeType)
  
  if (support === 'probably') {
    return {
      class: 'compatible',
      icon: '✅',
      text: 'Отличная совместимость'
    }
  } else if (support === 'maybe') {
    return {
      class: 'maybe-compatible',
      icon: '⚠️',
      text: 'Возможны проблемы с воспроизведением'
    }
  } else {
    return {
      class: 'incompatible',
      icon: '❌',
      text: 'Формат не поддерживается'
    }
  }
})

// Initialize from props
watch(() => props.video, (newVideos) => {
  if (localVideos.value.length === 0 && newVideos && newVideos.length > 0) {
    localVideos.value = newVideos.map((video, index) => {
      if (typeof video === 'string') {
        return {
          id: `video-${index}`,
          url: video,
          preview: video
        }
      }
      return video
    })
  }
}, { immediate: true })

// Emit changes
watch(localVideos, (newVideos) => {
  const videosToEmit = newVideos.map(video => {
    if (video.file) return video.file
    return video.url || video.preview
  })
  emit('update:video', videosToEmit)
}, { deep: true })

// Methods
const addDebugLog = (level: string, message: string) => {
  if (!isDevelopment.value) return
  
  debugLogs.value.push({
    timestamp: new Date().toLocaleTimeString(),
    level,
    message
  })
  
  // Ограничиваем количество логов
  if (debugLogs.value.length > 20) {
    debugLogs.value = debugLogs.value.slice(-20)
  }
}

const detectFormatSupport = () => {
  const video = document.createElement('video')
  const formats = [
    { name: 'MP4 (H.264)', type: 'video/mp4; codecs="avc1.42E01E"' },
    { name: 'MP4 (H.265)', type: 'video/mp4; codecs="hev1.1.6.L93.B0"' },
    { name: 'WebM (VP8)', type: 'video/webm; codecs="vp8"' },
    { name: 'WebM (VP9)', type: 'video/webm; codecs="vp9"' },
    { name: 'OGG (Theora)', type: 'video/ogg; codecs="theora"' }
  ]

  formatSupport.value = formats.map(format => {
    const support = video.canPlayType(format.type)
    return {
      ...format,
      level: support,
      status: support === 'probably' ? 'Отлично' : 
              support === 'maybe' ? 'Возможно' : 'Не поддерживается',
      icon: support === 'probably' ? '✅' : 
            support === 'maybe' ? '⚠️' : '❌'
    }
  })
  
  addDebugLog('info', `Detected format support: ${formatSupport.value.map(f => f.name + ':' + f.level).join(', ')}`)
}

const triggerVideoInput = () => {
  videoInput.value?.click()
}

const handleVideoUpload = (event: Event) => {
  const target = event.target as HTMLInputElement
  const file = target.files?.[0]
  if (file) {
    processVideo(file)
    target.value = ''
  }
}

const processVideo = (file: File) => {
  error.value = ''
  addDebugLog('info', `Processing video file: ${file.name}, type: ${file.type}, size: ${file.size}`)
  
  if (!file.type.startsWith('video/')) {
    error.value = 'Выберите видео файл'
    addDebugLog('error', 'Invalid file type: ' + file.type)
    return
  }
  
  if (file.size > 50 * 1024 * 1024) {
    error.value = 'Максимальный размер видео 50MB'
    addDebugLog('error', `File too large: ${(file.size / 1024 / 1024).toFixed(2)}MB`)
    return
  }
  
  const reader = new FileReader()
  reader.onload = (e) => {
    localVideos.value = [{
      id: Date.now(),
      file: file,
      preview: e.target?.result as string,
      name: file.name,
      type: file.type
    }]
    
    videoKey.value++ // Принудительно обновляем video элемент
    addDebugLog('success', 'Video file processed successfully')
  }
  
  reader.onerror = () => {
    error.value = 'Ошибка при загрузке файла'
    addDebugLog('error', 'FileReader error')
  }
  
  reader.readAsDataURL(file)
}

const removeVideo = () => {
  localVideos.value = []
  videoMetadata.value = null
  addDebugLog('info', 'Video removed')
}

// Video event handlers
const onVideoLoaded = (event: Event) => {
  const video = event.target as HTMLVideoElement
  const file = localVideos.value[0]?.file
  
  videoMetadata.value = {
    dimensions: `${video.videoWidth} × ${video.videoHeight}`,
    duration: formatDuration(video.duration),
    fileSize: file ? formatFileSize(file.size) : 'Неизвестно'
  }
  
  addDebugLog('info', `Video metadata loaded: ${videoMetadata.value.dimensions}, ${videoMetadata.value.duration}`)
}

const onVideoError = (event: Event) => {
  const video = event.target as HTMLVideoElement
  const errorCode = video.error?.code
  const errorMessage = getVideoErrorMessage(errorCode)
  
  error.value = `Ошибка воспроизведения видео: ${errorMessage}`
  addDebugLog('error', `Video error: code ${errorCode}, ${errorMessage}`)
}

const onVideoCanPlay = () => {
  addDebugLog('success', 'Video can play')
}

// Utility functions
const formatDuration = (seconds: number): string => {
  if (isNaN(seconds)) return 'Неизвестно'
  const minutes = Math.floor(seconds / 60)
  const remainingSeconds = Math.floor(seconds % 60)
  return `${minutes}:${remainingSeconds.toString().padStart(2, '0')}`
}

const formatFileSize = (bytes: number): string => {
  const sizes = ['Bytes', 'KB', 'MB', 'GB']
  if (bytes === 0) return '0 Bytes'
  const i = Math.floor(Math.log(bytes) / Math.log(1024))
  return `${(bytes / Math.pow(1024, i)).toFixed(2)} ${sizes[i]}`
}

const getVideoErrorMessage = (errorCode?: number): string => {
  const messages: Record<number, string> = {
    1: 'Загрузка прервана',
    2: 'Ошибка сети',
    3: 'Ошибка декодирования (формат не поддерживается)',
    4: 'Источник видео не поддерживается'
  }
  return messages[errorCode || 0] || 'Неизвестная ошибка'
}

// Lifecycle
onMounted(() => {
  detectFormatSupport()
  addDebugLog('info', 'VideoUpload component mounted')
})
</script>

<style scoped>
.video-upload {
  background: white;
  border-radius: 8px;
  padding: 24px;
}

.field-label {
  font-size: 16px;
  font-weight: 500;
  color: #333;
  margin-bottom: 8px;
}

.field-hint {
  font-size: 13px;
  color: #666;
  margin-bottom: 16px;
}

.format-support {
  background: #f8f9fa;
  border: 1px solid #e9ecef;
  border-radius: 6px;
  padding: 16px;
  margin-bottom: 16px;
}

.support-title {
  font-size: 14px;
  font-weight: 500;
  color: #495057;
  margin-bottom: 12px;
}

.support-list {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 8px;
}

.support-item {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 8px;
  border-radius: 4px;
  font-size: 13px;
}

.support-item.supported {
  background: #d4edda;
  color: #155724;
}

.support-item.maybe {
  background: #fff3cd;
  color: #856404;
}

.support-item.unsupported {
  background: #f8d7da;
  color: #721c24;
}

.support-icon {
  flex-shrink: 0;
}

.support-name {
  font-weight: 500;
}

.support-status {
  margin-left: auto;
  font-size: 12px;
}

.chromium-warning {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-top: 12px;
  padding: 12px;
  background: #fff3cd;
  border: 1px solid #ffeaa7;
  border-radius: 4px;
  color: #856404;
  font-size: 13px;
}

.chromium-warning svg {
  flex-shrink: 0;
  color: #f39c12;
}

.video-container {
  display: flex;
  gap: 16px;
  margin-top: 16px;
}

.hidden {
  display: none;
}

.video-item {
  position: relative;
  width: 400px;
  min-height: 300px;
  transition: all 0.3s ease;
}

.video-wrapper {
  position: relative;
  width: 100%;
  height: 100%;
  border-radius: 8px;
  overflow: hidden;
  background: #000;
  border: 1px solid #e0e0e0;
}

.video-element {
  width: 100%;
  height: 300px;
  object-fit: contain;
  background: #000;
}

.video-metadata {
  position: absolute;
  bottom: 48px;
  left: 8px;
  right: 8px;
  background: rgba(0, 0, 0, 0.8);
  color: white;
  padding: 8px;
  border-radius: 4px;
  font-size: 12px;
}

.metadata-item {
  display: flex;
  justify-content: space-between;
  margin-bottom: 4px;
}

.metadata-item:last-child {
  margin-bottom: 0;
}

.metadata-label {
  opacity: 0.8;
}

.metadata-value {
  font-weight: 500;
}

.video-controls {
  position: absolute;
  top: 8px;
  right: 8px;
  display: flex;
  gap: 4px;
  opacity: 0;
  transition: opacity 0.3s ease;
}

.video-item:hover .video-controls {
  opacity: 1;
}

.control-btn {
  width: 32px;
  height: 32px;
  border-radius: 6px;
  background: rgba(255, 255, 255, 0.95);
  border: 1px solid #ddd;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.2s ease;
}

.control-btn:hover {
  background: white;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
}

.control-btn svg {
  width: 20px;
  height: 20px;
  color: #666;
}

.delete-btn:hover svg {
  color: #dc3545;
}

.compatibility-status {
  position: absolute;
  top: 8px;
  left: 8px;
  display: flex;
  align-items: center;
  gap: 4px;
  padding: 4px 8px;
  border-radius: 4px;
  font-size: 12px;
  font-weight: 500;
}

.compatibility-status.compatible {
  background: rgba(40, 167, 69, 0.9);
  color: white;
}

.compatibility-status.maybe-compatible {
  background: rgba(255, 193, 7, 0.9);
  color: #212529;
}

.compatibility-status.incompatible {
  background: rgba(220, 53, 69, 0.9);
  color: white;
}

.add-video-btn {
  width: 400px;
  height: 300px;
  border: 2px dashed #ddd;
  border-radius: 8px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 8px;
  cursor: pointer;
  transition: all 0.3s ease;
  background: #fafafa;
}

.add-video-btn:hover {
  border-color: #007bff;
  background: #f0f8ff;
}

.add-video-btn svg {
  color: #666;
}

.add-video-btn span {
  font-size: 14px;
  color: #666;
  font-weight: 500;
}

.format-hint {
  font-size: 12px;
  color: #999;
  text-align: center;
}

.error-message {
  margin-top: 12px;
  padding: 12px;
  background: #fee;
  border: 1px solid #fcc;
  border-radius: 6px;
  color: #c00;
  font-size: 14px;
}

.debug-logs {
  margin-top: 16px;
  padding: 12px;
  background: #f8f9fa;
  border: 1px solid #dee2e6;
  border-radius: 6px;
  font-size: 12px;
}

.debug-logs summary {
  cursor: pointer;
  font-weight: 500;
  color: #495057;
}

.logs-content {
  margin-top: 8px;
  max-height: 200px;
  overflow-y: auto;
}

.log-entry {
  padding: 2px 0;
  font-family: monospace;
  border-bottom: 1px solid #e9ecef;
}

.log-entry:last-child {
  border-bottom: none;
}

/* Мобильная адаптация */
@media (max-width: 640px) {
  .video-container {
    flex-direction: column;
    gap: 12px;
  }
  
  .video-item,
  .add-video-btn {
    width: 100%;
    height: 200px;
  }
  
  .support-list {
    grid-template-columns: 1fr;
  }
  
  .control-btn {
    width: 28px;
    height: 28px;
  }
  
  .control-btn svg {
    width: 16px;
    height: 16px;
  }
}
</style>
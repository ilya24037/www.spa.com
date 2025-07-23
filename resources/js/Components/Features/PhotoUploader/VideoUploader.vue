<!-- Компонент загрузки видео в стиле Авито -->
<template>
  <div class="avito-video-uploader">
    <!-- Заголовок -->
    <div class="video-header">
      <h5 class="video-title">Видео</h5>
    </div>

    <!-- Область загрузки -->
    <div 
      class="upload-zone"
      :class="{ 
        'drag-over': isDragOver,
        'has-video': video
      }"
      @drop="handleDrop"
      @dragover.prevent="handleDragOver"
      @dragleave="handleDragLeave"
    >
      <!-- Скрытый input -->
      <input
        ref="fileInput"
        type="file"
        accept="video/mp4,video/avi,video/webm,video/mov"
        @change="handleFileSelect"
        class="hidden-input"
      />

      <!-- Превью видео -->
      <div v-if="video" class="video-preview">
        <div class="video-container">
          <video 
            :src="video.preview"
            class="video-element"
            controls
            preload="metadata"
          ></video>
          
          <!-- Кнопка удаления -->
          <button 
            type="button"
            class="remove-video-btn"
            @click="removeVideo"
            title="Удалить видео"
          >
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
              <line x1="18" y1="6" x2="6" y2="18"/>
              <line x1="6" y1="6" x2="18" y2="18"/>
            </svg>
          </button>
        </div>
        
        <!-- Информация о видео -->
        <div class="video-info">
          <p class="video-name">{{ video.name }}</p>
          <p class="video-size">{{ formatFileSize(video.size) }}</p>
        </div>
      </div>

      <!-- Пустое состояние -->
      <div v-else class="empty-state" @click="triggerFileInput">
        <div class="empty-content">
          <div class="upload-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
              <polygon points="23,7 16,12 23,17 23,7"/>
              <rect x="1" y="5" width="15" height="14" rx="2" ry="2"/>
            </svg>
          </div>
          <p class="upload-text">Перетащите видео сюда или нажмите для выбора</p>
          <p class="upload-hint">Максимум 50MB, формат MP4, AVI</p>
        </div>
      </div>

      <!-- Drag-and-drop overlay -->
      <div v-if="isDragOver" class="drag-overlay">
        <div class="drag-content">
          <div class="drag-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
              <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
              <polyline points="7,10 12,15 17,10"/>
              <line x1="12" y1="15" x2="12" y2="3"/>
            </svg>
          </div>
          <p class="drag-text">Перетащите сюда видео</p>
        </div>
      </div>
    </div>

    <!-- Подсказки -->
    <div class="upload-hints">
      <p class="hint-text">Добавьте видео презентацию ваших услуг</p>
      <p class="hint-text secondary">Добавьте видео обзор до 50MB</p>
      <p class="hint-text secondary">Первая фотография будет использоваться как превью</p>
    </div>

    <!-- Ошибки -->
    <div v-if="error" class="error-message">{{ error }}</div>

    <!-- Прогресс загрузки -->
    <div v-if="uploading" class="upload-progress">
      <div class="progress-bar">
        <div 
          class="progress-fill"
          :style="{ width: uploadProgress + '%' }"
        ></div>
      </div>
      <p class="progress-text">Загрузка видео... {{ uploadProgress }}%</p>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'

// Props
const props = defineProps({
  modelValue: {
    type: Object,
    default: null
  },
  maxFileSize: {
    type: Number,
    default: 50 * 1024 * 1024 // 50MB
  },
  uploading: {
    type: Boolean,
    default: false
  },
  uploadProgress: {
    type: Number,
    default: 0
  }
})

// Events
const emit = defineEmits(['update:modelValue', 'upload', 'error'])

// State
const fileInput = ref(null)
const isDragOver = ref(false)
const error = ref('')

// Computed
const video = computed({
  get() {
    return props.modelValue
  },
  set(value) {
    emit('update:modelValue', value)
  }
})

// Methods
const triggerFileInput = () => {
  fileInput.value?.click()
}

const handleFileSelect = (event) => {
  const file = event.target.files[0]
  if (file) {
    processFile(file)
  }
  event.target.value = ''
}

const handleDrop = (event) => {
  event.preventDefault()
  isDragOver.value = false
  
  const file = event.dataTransfer.files[0]
  if (file) {
    processFile(file)
  }
}

const handleDragOver = (event) => {
  event.preventDefault()
  isDragOver.value = true
}

const handleDragLeave = (event) => {
  event.preventDefault()
  isDragOver.value = false
}

const processFile = (file) => {
  error.value = ''
  
  if (!file.type.startsWith('video/')) {
    error.value = 'Выберите видео файл'
    return
  }
  
  if (file.size > props.maxFileSize) {
    error.value = `Файл слишком большой (максимум ${props.maxFileSize / (1024 * 1024)}MB)`
    return
  }
  
  // Создаем превью
  const reader = new FileReader()
  reader.onload = (e) => {
    video.value = {
      id: Date.now() + Math.random(),
      file: file,
      preview: e.target.result,
      name: file.name,
      size: file.size
    }
  }
  reader.readAsDataURL(file)
}

const removeVideo = () => {
  video.value = null
}

const formatFileSize = (bytes) => {
  if (bytes === 0) return '0 Bytes'
  const k = 1024
  const sizes = ['Bytes', 'KB', 'MB', 'GB']
  const i = Math.floor(Math.log(bytes) / Math.log(k))
  return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i]
}

// Метод для получения файла
const getFile = () => {
  return video.value?.file || null
}

// Метод для очистки
const clear = () => {
  video.value = null
  error.value = ''
}

// Expose methods
defineExpose({
  getFile,
  clear
})
</script>

<style scoped>
.avito-video-uploader {
  @apply w-full mt-6;
}

.video-header {
  @apply mb-4;
}

.video-title {
  @apply text-lg font-medium text-gray-900;
}

.upload-zone {
  @apply relative border-2 border-dashed border-gray-300 rounded-lg;
  min-height: 200px;
  transition: all 0.2s ease;
}

.upload-zone:hover {
  @apply border-blue-400;
}

.upload-zone.drag-over {
  @apply border-blue-500 bg-blue-50;
}

.upload-zone.has-video {
  @apply border-solid border-gray-200;
}

.hidden-input {
  @apply sr-only;
}

.video-preview {
  @apply p-4;
}

.video-container {
  @apply relative w-full max-w-md mx-auto;
}

.video-element {
  @apply w-full rounded-lg;
}

.remove-video-btn {
  @apply absolute top-2 right-2 w-8 h-8 bg-white bg-opacity-90 hover:bg-opacity-100 rounded-full flex items-center justify-center shadow transition-all text-red-600 hover:text-red-700;
}

.remove-video-btn svg {
  @apply w-4 h-4;
}

.video-info {
  @apply mt-3 text-center;
}

.video-name {
  @apply text-sm font-medium text-gray-700 truncate;
}

.video-size {
  @apply text-xs text-gray-500 mt-1;
}

.empty-state {
  @apply flex items-center justify-center cursor-pointer h-48;
}

.empty-content {
  @apply text-center;
}

.upload-icon {
  @apply w-12 h-12 text-gray-400 mx-auto mb-4;
}

.upload-icon svg {
  @apply w-full h-full;
}

.upload-text {
  @apply text-lg text-gray-700 mb-2;
}

.upload-hint {
  @apply text-sm text-gray-500;
}

.drag-overlay {
  @apply absolute inset-0 bg-blue-50 bg-opacity-95 flex items-center justify-center z-10;
}

.drag-content {
  @apply text-center;
}

.drag-icon {
  @apply w-16 h-16 text-blue-500 mx-auto mb-4;
}

.drag-icon svg {
  @apply w-full h-full;
}

.drag-text {
  @apply text-xl font-medium text-blue-700;
}

.upload-hints {
  @apply mt-4 space-y-1;
}

.hint-text {
  @apply text-sm text-gray-600;
}

.hint-text.secondary {
  @apply text-gray-500;
}

.error-message {
  @apply mt-2 text-sm text-red-600;
}

.upload-progress {
  @apply mt-4;
}

.progress-bar {
  @apply w-full h-2 bg-gray-200 rounded-full overflow-hidden;
}

.progress-fill {
  @apply h-full bg-blue-500 transition-all duration-300;
}

.progress-text {
  @apply mt-2 text-sm text-gray-600;
}

/* Мобильная адаптация */
@media (max-width: 768px) {
  .upload-zone {
    min-height: 150px;
  }
  
  .video-container {
    @apply max-w-full;
  }
}
</style> 
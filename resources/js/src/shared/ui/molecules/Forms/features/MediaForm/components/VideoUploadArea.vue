<template>
  <div 
    class="upload-area videos-upload"
    :class="{ 
      'border-blue-300 bg-blue-50': isDragOver,
      'border-red-300': hasError
    }"
    @dragover.prevent="handleDragOver"
    @dragleave="handleDragLeave"
    @drop.prevent="handleVideoDrop"
  >
    <!-- Загрузка видео -->
    <input
      ref="videoInput"
      type="file"
      multiple
      accept="video/mp4,video/quicktime,video/x-msvideo,video/webm,video/ogg"
      class="hidden"
      @change="handleVideoSelect"
    />
    
    <div class="upload-content">
      <div class="upload-icon">
        <svg class="w-12 h-12 text-gray-400"
             fill="none"
             stroke="currentColor"
             viewBox="0 0 24 24">
          <path stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
        </svg>
      </div>
      
      <div class="upload-text">
        <p class="text-lg font-medium text-gray-900">
          Перетащите видео сюда
        </p>
        <p class="text-sm text-gray-500">
          или 
          <button
            type="button"
            class="text-blue-600 hover:text-blue-700 font-medium"
            @click="openVideoSelector"
          >
            выберите файлы
          </button>
        </p>
        <div class="text-xs text-gray-500 mt-3 space-y-1">
          <p>• Максимум 3 видео</p>
          <p>• Длительность: от 5 секунд до 60 секунд</p>
          <p>• Не должно содержать водяные знаки других сайтов</p>
          <p class="text-amber-600 font-medium">• Без проверочной фотографии видеофайл не будет размещен, поэтому подтверждайте свою анкету.</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'

// Props
interface Props {
  hasError?: boolean
  disabled?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  hasError: false,
  disabled: false
})

// Emits
const emit = defineEmits<{
  'files-selected': [files: File[]]
}>()

// State
const videoInput = ref<HTMLInputElement>()
const isDragOver = ref(false)

// Methods
const openVideoSelector = () => {
  if (props.disabled) return
  videoInput.value?.click()
}

const handleVideoSelect = (event: Event) => {
  const target = event.target as HTMLInputElement
  if (target.files) {
    const files = Array.from(target.files)
    handleFiles(files)
  }
}

const handleVideoDrop = (event: DragEvent) => {
  isDragOver.value = false
  if (props.disabled) return
  
  const files = Array.from(event.dataTransfer?.files || [])
  handleFiles(files)
}

const handleDragOver = () => {
  if (!props.disabled) {
    isDragOver.value = true
  }
}

const handleDragLeave = () => {
  isDragOver.value = false
}

const handleFiles = (files: File[]) => {
  const validFiles = files.filter(file => {
    // Проверка типа файла
    if (!file.type.startsWith('video/')) {
      return false
    }
    
    // Проверка размера (50MB)
    if (file.size > 50 * 1024 * 1024) {
      return false
    }
    
    return true
  })
  
  if (validFiles.length > 0) {
    emit('files-selected', validFiles)
  }
}
</script>

<style scoped>
.upload-area {
  @apply border-2 border-dashed border-gray-300 rounded-lg p-8 text-center transition-colors;
}

.upload-area:hover {
  @apply border-gray-400 bg-gray-50;
}

.upload-content {
  @apply flex flex-col items-center space-y-4;
}

.upload-icon {
  @apply flex items-center justify-center;
}

.upload-text {
  @apply text-center;
}
</style>

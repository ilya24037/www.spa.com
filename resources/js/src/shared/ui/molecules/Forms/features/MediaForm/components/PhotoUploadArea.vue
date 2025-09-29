<template>
  <div 
    class="upload-area photos-upload"
    :class="{ 
      'border-blue-300 bg-blue-50': isDragOver,
      'border-red-300': hasError
    }"
    @dragover.prevent="handleDragOver"
    @dragleave="handleDragLeave"
    @drop.prevent="handlePhotoDrop"
  >
    <!-- Загрузка фотографий -->
    <input
      ref="photoInput"
      type="file"
      multiple
      accept="image/*"
      class="hidden"
      @change="handlePhotoSelect"
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
                d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
        </svg>
      </div>
      
      <div class="upload-text">
        <p class="text-lg font-medium text-gray-900">
          Перетащите фотографии сюда
        </p>
        <p class="text-sm text-gray-500">
          или 
          <button
            type="button"
            class="text-blue-600 hover:text-blue-700 font-medium"
            @click="openPhotoSelector"
          >
            выберите файлы
          </button>
        </p>
        <p class="text-xs text-gray-400 mt-2">
          JPG, PNG, GIF и другие форматы до 10 МБ
        </p>
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
const photoInput = ref<HTMLInputElement>()
const isDragOver = ref(false)

// Methods
const openPhotoSelector = () => {
  if (props.disabled) return
  photoInput.value?.click()
}

const handlePhotoSelect = (event: Event) => {
  const target = event.target as HTMLInputElement
  if (target.files) {
    const files = Array.from(target.files)
    handleFiles(files)
  }
}

const handlePhotoDrop = (event: DragEvent) => {
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
    if (!file.type.startsWith('image/')) {
      return false
    }
    
    // Проверка размера (10MB)
    if (file.size > 10 * 1024 * 1024) {
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

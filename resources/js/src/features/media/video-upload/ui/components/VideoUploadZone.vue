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
    
    <!-- МИНИМАЛИСТИЧНЫЙ ДИЗАЙН: все по центру вертикально -->
    <div class="text-center py-3 px-4" @click="openFileDialog">
      <!-- Иконка и текст над кнопкой -->
      <div class="flex items-center justify-center space-x-2 mb-3">
        <svg class="h-5 w-5 text-gray-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                d="M7 4v16M17 4v16M3 8h4m10 0h4M3 16h4m10 0h4M4 4h16a1 1 0 011 1v14a1 1 0 01-1 1H4a1 1 0 01-1-1V5a1 1 0 011-1z" />
        </svg>
        <span class="text-sm text-gray-500">
          {{ isDragOver ? 'Отпустите файлы здесь' : 'Перетащите видео в эту область или нажмите выбрать видео' }}
        </span>
      </div>
      
      <!-- Кнопка по центру -->
      <button 
        type="button"
        class="px-3 py-1.5 bg-blue-500 text-white text-sm rounded hover:bg-blue-600"
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
  return `${formats} • до ${maxSizeMB}MB`
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
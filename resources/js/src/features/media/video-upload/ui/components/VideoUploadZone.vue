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
    
    <div class="text-center py-8 px-4" @click="openFileDialog">
      <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
              d="M7 4v16M17 4v16M3 8h4m10 0h4M3 16h4m10 0h4M4 4h16a1 1 0 011 1v14a1 1 0 01-1 1H4a1 1 0 01-1-1V5a1 1 0 011-1z" />
      </svg>
      
      <p class="mt-2 text-sm text-gray-600">
        {{ isDragOver ? 'Отпустите файлы здесь' : 'Перетащите видео или нажмите для выбора' }}
      </p>
      
      <p class="mt-1 text-xs text-gray-500">
        {{ formatInfo }}
      </p>
      
      <button 
        type="button"
        class="mt-4 px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600"
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
  return `${formats} • Максимум ${maxSizeMB}MB`
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
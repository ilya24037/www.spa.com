<template>
  <div 
    class="photo-upload-zone"
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
      accept="image/*"
      @change="handleFileSelect"
      class="hidden"
    />
    
    <!-- МИНИМАЛИСТИЧНЫЙ ДИЗАЙН: все по центру вертикально -->
    <div class="text-center py-3 px-4" @click="openFileDialog">
      <!-- Иконка и текст над кнопкой -->
      <div class="flex items-center justify-center space-x-2 mb-3">
        <svg class="h-5 w-5 text-gray-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
        </svg>
        <span class="text-sm text-gray-600">
          {{ isDragOver ? 'Отпустите файлы здесь' : 'Перетащите фото в эту область или нажмите выбрать фото' }}
        </span>
      </div>
      
      <!-- Кнопка по центру -->
      <button 
        type="button"
        class="px-3 py-1.5 bg-blue-500 text-white text-sm rounded hover:bg-blue-600"
      >
        Выбрать фото
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
  maxSize: 10 * 1024 * 1024, // 10MB (унифицировано с backend)
  acceptedFormats: () => ['image/jpeg', 'image/png', 'image/webp']
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
    // ✅ УПРОЩЕННАЯ ВАЛИДАЦИЯ: более читаемый код
    const isValidFormat = props.acceptedFormats.some(format => 
      file.type.startsWith(format.split('/*')[0])
    )
    const isValidSize = file.size <= props.maxSize
    
    // Логируем только ошибки в production
    if (!isValidFormat || !isValidSize) {
      const reason = !isValidFormat ? 'неподдерживаемый формат' : 'слишком большой размер'
      console.warn(`❌ Файл отклонен (${reason}): ${file.name}`)
    }
    
    return isValidFormat && isValidSize
  })
}

defineExpose({ openFileDialog })
</script>

<style scoped>
.photo-upload-zone {
  @apply border-2 border-dashed rounded-lg transition-all duration-200 cursor-pointer;
}
</style>

<!-- Зона загрузки фотографий с drag & drop -->
<template>
  <div 
    class="upload-zone border-2 border-dashed rounded-lg transition-colors"
    :class="{ 
      'border-blue-400 bg-blue-50': isDragOver,
      'border-gray-300': !isDragOver
    }"
    @drop.prevent="hasContent ? null : handleDrop"
    @dragover.prevent="hasContent ? null : handleDragOver"
    @dragleave.prevent="hasContent ? null : (isDragOver = false)"
  >
    <input
      ref="fileInput"
      type="file"
      multiple
      accept="image/*"
      @change="handleFileSelect"
      class="hidden"
    />
    
    <!-- Пустое состояние - как у видео -->
    <div v-if="!hasContent" class="text-center py-3 px-4" @click="openFileDialog">
      <div class="flex items-center justify-center space-x-2 mb-3">
        <svg class="h-5 w-5 text-gray-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
        </svg>
        <span class="text-sm text-gray-600">
          {{ isDragOver ? 'Отпустите файлы здесь' : 'Перетащите фото в эту область или нажмите выбрать фото' }}
        </span>
      </div>
      
      <button 
        type="button"
        class="px-3 py-1.5 bg-blue-500 text-white text-sm rounded hover:bg-blue-600"
      >
        Выбрать фото
      </button>
    </div>
    
    <!-- Контент (фотографии) -->
    <div v-else class="p-4">
      <slot />
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'

interface Props {
  hasContent?: boolean
}

const props = defineProps<Props>()

const emit = defineEmits<{
  'files-selected': [files: File[]]
}>()

const fileInput = ref<HTMLInputElement>()
const isDragOver = ref(false)

const openFileDialog = () => {
  fileInput.value?.click()
}

const handleFileSelect = (event: Event) => {
  console.log('📁 UploadZone: handleFileSelect вызван', { 
    event: event.type,
    target: event.target,
    filesCount: (event.target as HTMLInputElement)?.files?.length
  })
  
  const target = event.target as HTMLInputElement
  const files = Array.from(target.files || [])
  if (files.length > 0) {
    console.log('✅ UploadZone: Файлы выбраны, эмитим событие', { filesCount: files.length })
    emit('files-selected', files)
  }
  target.value = ''
}

const handleDrop = (event: DragEvent) => {
  console.log('📁 UploadZone: handleDrop вызван', { 
    dataTransferTypes: event.dataTransfer?.types,
    hasFiles: event.dataTransfer?.types.includes('Files'),
    typesCount: event.dataTransfer?.types.length
  })
  
  isDragOver.value = false
  
  // Проверяем, что перетаскиваются ТОЛЬКО файлы, без других типов
  const hasFiles = event.dataTransfer?.types.includes('Files')
  const hasOnlyFiles = hasFiles && event.dataTransfer?.types.length === 1
  
  if (hasOnlyFiles) {
    const files = Array.from(event.dataTransfer?.files || [])
    if (files.length > 0) {
      console.log('✅ UploadZone: Файлы перетащены, эмитим событие', { filesCount: files.length })
      emit('files-selected', files)
    }
  } else {
    console.log('❌ UploadZone: Drag & drop пропущен - не файлы или перетаскивание фото', {
      hasFiles,
      hasOnlyFiles,
      typesCount: event.dataTransfer?.types.length,
      types: Array.from(event.dataTransfer?.types || [])
    })
  }
}

const handleDragOver = (event: DragEvent) => {
  console.log('📁 UploadZone: handleDragOver вызван', { 
    event: event.type,
    dataTransferTypes: event.dataTransfer?.types,
    hasContent: props.hasContent,
    typesCount: event.dataTransfer?.types.length
  })
  isDragOver.value = true
}

defineExpose({ openFileDialog })
</script>
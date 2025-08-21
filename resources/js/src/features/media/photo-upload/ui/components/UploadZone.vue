<!-- Зона загрузки фотографий с drag & drop -->
<template>
  <div 
    class="upload-zone border-2 border-dashed rounded-lg p-4 transition-colors"
    :class="{ 
      'border-blue-400 bg-blue-50': isDragOver,
      'border-gray-300': !isDragOver
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
    
    <!-- Пустое состояние -->
    <div v-if="!hasContent" class="text-center py-8" @click="openFileDialog">
      <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
              d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
      </svg>
      <p class="mt-2 text-sm text-gray-600">
        Перетащите фото сюда или нажмите для выбора
      </p>
      <p class="text-xs text-gray-500">PNG, JPG до 10MB</p>
    </div>
    
    <!-- Контент (фотографии) -->
    <slot v-else />
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'

interface Props {
  hasContent?: boolean
}

defineProps<Props>()

const emit = defineEmits<{
  'files-selected': [files: File[]]
}>()

const fileInput = ref<HTMLInputElement>()
const isDragOver = ref(false)

const openFileDialog = () => {
  fileInput.value?.click()
}

const handleFileSelect = (event: Event) => {
  const target = event.target as HTMLInputElement
  const files = Array.from(target.files || [])
  if (files.length > 0) {
    emit('files-selected', files)
  }
  target.value = ''
}

const handleDrop = (event: DragEvent) => {
  isDragOver.value = false
  const files = Array.from(event.dataTransfer?.files || [])
  if (files.length > 0) {
    emit('files-selected', files)
  }
}

defineExpose({ openFileDialog })
</script>
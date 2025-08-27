<template>
  <div class="video-upload space-y-4">
    <!-- Список видео -->
    <VideoList 
      v-if="!isEmpty"
      :videos="safeVideos"
      :dragged-index="draggedIndex"
      :drag-over-index="dragOverIndex"
      @remove="handleRemoveVideo"
      @dragstart="handleDragStart"
      @dragover="handleDragOver"
      @drop="onDragDrop"
      @dragend="handleDragEnd"
    />
    
    <!-- Зона загрузки -->
    <div 
      v-if="canAddMore"
      class="border-2 border-dashed border-gray-300 rounded-lg p-4 text-center cursor-pointer hover:border-blue-400"
      :class="{ 'border-blue-400 bg-blue-50': isDragOver }"
      @click="openFileDialog"
      @drop.prevent="handleDrop"
      @dragover.prevent="isDragOver = true"
      @dragleave.prevent="isDragOver = false"
    >
      <input
        ref="fileInput"
        type="file"
        multiple
        accept="video/*"
        @change="handleFileSelect"
        class="hidden"
      />
      
      <div class="space-y-2">
        <div class="text-gray-600">
          {{ isDragOver ? 'Отпустите видео здесь' : 'Перетащите видео или нажмите для выбора' }}
        </div>
        <div class="text-xs text-gray-500">
          Максимум {{ props.maxFiles }} видео, до {{ maxSizeInfo }}
        </div>
      </div>
    </div>
    
    <!-- Простое отображение ошибок -->
    <div v-if="error" class="text-red-600 text-sm">
      {{ error }}
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { useVideoUpload } from '../composables/useVideoUpload'
import VideoList from './components/VideoList.vue'
import type { VideoUploadProps, VideoUploadEmits } from '../model/types'

// ✅ УПРОЩЕНИЕ: Простые props без acceptedFormats
const props = withDefaults(defineProps<VideoUploadProps>(), {
  videos: () => [],
  maxFiles: 5,
  maxSize: 100 * 1024 * 1024 // 100MB
})

const emit = defineEmits<VideoUploadEmits>()

// ✅ УПРОЩЕНИЕ: Только 3 необходимых refs
const fileInput = ref<HTMLInputElement>()
const isDragOver = ref(false)

// ✅ УПРОЩЕНИЕ: Импорт с drag&drop методами
const {
  localVideos,
  error,
  draggedIndex,
  dragOverIndex,
  addVideos,
  removeVideo,
  initializeFromProps,
  handleDragStart,
  handleDragOver,
  handleDragDrop,
  handleDragEnd
} = useVideoUpload()

// ✅ УПРОЩЕНИЕ: Убрали useFormatDetection согласно плану

// ✅ УПРОЩЕНИЕ: 4 простых computed без избыточных проверок
const safeVideos = computed(() => localVideos.value || [])
const isEmpty = computed(() => safeVideos.value.length === 0)
const canAddMore = computed(() => safeVideos.value.length < props.maxFiles)
const maxSizeInfo = computed(() => `${Math.round(props.maxSize / (1024 * 1024))}MB`)

// ✅ УПРОЩЕНИЕ: Простая синхронизация как в DescriptionSection
watch(() => props.videos, (newVideos) => {
  if (safeVideos.value.length === 0 && (newVideos || []).length > 0) {
    initializeFromProps(newVideos || [])
  }
}, { immediate: true })

// ✅ УПРОЩЕНИЕ: Общий emit как в DescriptionSection
const emitVideos = () => {
  emit('update:videos', safeVideos.value)
}

// Обработчики перетаскивания для пустого состояния
const handleFileSelect = (event: Event) => {
  const target = event.target as HTMLInputElement
  const files = Array.from(target.files || [])
  if (files.length > 0) {
    handleFilesSelected(files)
  }
  target.value = ''
}

const handleDrop = (event: DragEvent) => {
  isDragOver.value = false
  const files = Array.from(event.dataTransfer?.files || [])
  if (files.length > 0) {
    handleFilesSelected(files)
  }
}

// ✅ Обработчик drag&drop для изменения порядка
const onDragDrop = (index: number) => {
  handleDragDrop(index)
  // Эмитим изменения после drag&drop
  emitVideos()
}

// ✅ УПРОЩЕНИЕ: Простая валидация файлов
const validateFiles = (files: File[]): boolean => {
  if (!files?.length) return false
  
  if (safeVideos.value.length + files.length > props.maxFiles) {
    error.value = `Максимум ${props.maxFiles} видео`
    return false
  }
  
  return true
}

// ✅ УПРОЩЕНИЕ: Простой обработчик файлов
const handleFilesSelected = async (files: File[]) => {
  if (!validateFiles(files)) return
  
  try {
    await addVideos(files)
    emitVideos()
  } catch (err) {
    error.value = 'Ошибка загрузки видео'
  }
}

// ✅ УПРОЩЕНИЕ: Простой обработчик без избыточных проверок
const handleRemoveVideo = (id: string | number) => {
  removeVideo(id)
  emitVideos()
}

// ✅ УПРОЩЕНИЕ: Простое открытие файлового диалога
const openFileDialog = () => {
  fileInput.value?.click()
}

// ✅ УПРОЩЕНИЕ: Убрали resetError - не нужен при простой структуре

// Expose методы для родительского компонента
defineExpose({
  openFileDialog
})
</script>

<style scoped>
.video-upload {
  width: 100%;
}
</style>

<template>
  <div class="verification-photo-upload">
    <!-- Инструкции -->
    <div class="instructions-grid grid md:grid-cols-2 gap-4 mb-6">
      <!-- Шаг 1 -->
      <div class="instruction-card bg-gray-50 rounded-lg p-4">
        <div class="flex items-start gap-3">
          <div class="flex-shrink-0 w-8 h-8 bg-blue-500 text-white rounded-full flex items-center justify-center font-bold">
            1
          </div>
          <div class="flex-1">
            <h3 class="font-semibold mb-2">Подготовка</h3>
            <p class="text-sm text-gray-600 mb-2">На листке бумаги напишите от руки:</p>
            <div class="bg-white border border-gray-200 rounded p-3 font-mono text-sm">
              <div class="font-bold">{{ currentDate }}</div>
              <div class="font-bold">для FEIPITER</div>
            </div>
            <p class="text-xs text-gray-500 mt-2">Надписи должны быть написаны от руки</p>
          </div>
        </div>
      </div>
      
      <!-- Шаг 2 -->
      <div class="instruction-card bg-gray-50 rounded-lg p-4">
        <div class="flex items-start gap-3">
          <div class="flex-shrink-0 w-8 h-8 bg-blue-500 text-white rounded-full flex items-center justify-center font-bold">
            2
          </div>
          <div class="flex-1">
            <h3 class="font-semibold mb-2">Съёмка</h3>
            <p class="text-sm text-gray-600 mb-2">Сделайте фото с листком:</p>
            <ul class="text-sm text-gray-600 space-y-1">
              <li>• Лицо и листок должны быть видны</li>
              <li>• Фото в полный рост приветствуется</li>
              <li>• Хорошее освещение</li>
              <li>• Четкое изображение</li>
            </ul>
            <div class="mt-2 text-center">
              <img 
                src="/images/verification-example.jpg" 
                alt="Пример проверочного фото"
                class="inline-block w-24 h-32 object-cover rounded border border-gray-300"
                @error="handleImageError"
              >
            </div>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Зона загрузки -->
    <div v-if="!currentFile && !uploadedPath" class="upload-zone">
      <label 
        class="upload-label"
        :class="{ 'dragging': isDragging }"
        @dragover.prevent="isDragging = true"
        @dragleave.prevent="isDragging = false"
        @drop.prevent="handleDrop"
      >
        <input
          ref="fileInput"
          type="file"
          accept="image/jpeg,image/jpg,image/png"
          class="hidden"
          @change="handleFileSelect"
          :disabled="uploading"
        >
        
        <div class="upload-content">
          <svg class="w-12 h-12 text-gray-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
          </svg>
          
          <p class="text-lg font-medium text-gray-700 mb-1">
            Загрузить проверочное фото
          </p>
          <p class="text-sm text-gray-500">
            JPG, PNG до 10MB
          </p>
          <p class="text-xs text-gray-400 mt-2">
            Нажмите или перетащите файл
          </p>
        </div>
      </label>
    </div>
    
    <!-- Предпросмотр -->
    <div v-if="currentFile || uploadedPath" class="preview mt-4">
      <div class="relative inline-block">
        <img 
          :src="previewUrl || uploadedPath"
          alt="Проверочное фото"
          class="max-h-64 rounded-lg border border-gray-300"
        >
        
        <!-- Статус -->
        <div v-if="uploading" class="absolute inset-0 bg-black bg-opacity-50 rounded-lg flex items-center justify-center">
          <div class="text-white">
            <svg class="animate-spin h-8 w-8 mx-auto mb-2" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            Загрузка...
          </div>
        </div>
        
        <!-- Кнопки действий -->
        <div v-if="!uploading" class="mt-2 flex gap-2">
          <button
            v-if="!uploadedPath"
            @click="uploadPhoto"
            class="px-3 py-1 bg-blue-500 text-white rounded hover:bg-blue-600 transition-colors"
          >
            Отправить на проверку
          </button>
          <button
            @click="removePhoto"
            class="px-3 py-1 bg-red-500 text-white rounded hover:bg-red-600 transition-colors"
          >
            Удалить
          </button>
        </div>
      </div>
    </div>
    
    <!-- Ошибка -->
    <div v-if="error" class="mt-4 bg-red-50 border border-red-200 rounded-lg p-3">
      <p class="text-sm text-red-600">{{ error }}</p>
    </div>
    
    <!-- Важные замечания -->
    <div class="mt-6 bg-yellow-50 border border-yellow-200 rounded-lg p-4">
      <div class="flex">
        <div class="flex-shrink-0">
          <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
          </svg>
        </div>
        <div class="ml-3">
          <h3 class="text-sm font-medium text-yellow-800">Важные замечания</h3>
          <ul class="mt-2 text-sm text-yellow-700 space-y-1">
            <li>• Фотосессия должна быть актуальной</li>
            <li>• Проверочное фото действует 4 месяца</li>
            <li>• Типажные фотографии запрещены</li>
            <li>• Фото не будет показано в анкете</li>
          </ul>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { verificationApi } from '../api/verificationApi'

interface Props {
  adId: number
  currentPhoto?: string | null
  status?: string
}

const props = defineProps<Props>()

const emit = defineEmits<{
  uploaded: [path: string]
  deleted: []
}>()

// Состояние
const fileInput = ref<HTMLInputElement>()
const currentFile = ref<File | null>(null)
const uploadedPath = ref<string | null>(props.currentPhoto || null)
const previewUrl = ref<string | null>(null)
const isDragging = ref(false)
const uploading = ref(false)
const error = ref<string | null>(null)

// Вычисляемые
const currentDate = computed(() => {
  const date = new Date()
  return date.toLocaleDateString('ru-RU', {
    day: 'numeric',
    month: 'long',
    year: 'numeric'
  })
})

// Методы
const handleFileSelect = (event: Event) => {
  const target = event.target as HTMLInputElement
  if (target.files && target.files[0]) {
    selectFile(target.files[0])
  }
}

const handleDrop = (event: DragEvent) => {
  isDragging.value = false
  if (event.dataTransfer?.files && event.dataTransfer.files[0]) {
    selectFile(event.dataTransfer.files[0])
  }
}

const selectFile = (file: File) => {
  error.value = null
  
  // Валидация
  if (!['image/jpeg', 'image/jpg', 'image/png'].includes(file.type)) {
    error.value = 'Недопустимый формат файла. Разрешены JPG, PNG'
    return
  }
  
  if (file.size > 10 * 1024 * 1024) {
    error.value = 'Файл слишком большой. Максимум 10MB'
    return
  }
  
  currentFile.value = file
  
  // Создаем preview
  const reader = new FileReader()
  reader.onload = (e) => {
    previewUrl.value = e.target?.result as string
  }
  reader.readAsDataURL(file)
}

const uploadPhoto = async () => {
  if (!currentFile.value) return
  
  uploading.value = true
  error.value = null
  
  try {
    const result = await verificationApi.uploadPhoto(props.adId, currentFile.value)
    
    if (result.success) {
      uploadedPath.value = result.path || null
      currentFile.value = null
      previewUrl.value = null
      emit('uploaded', result.path!)
    } else {
      error.value = result.message
    }
  } catch (err: any) {
    error.value = err.response?.data?.message || 'Ошибка при загрузке фото'
  } finally {
    uploading.value = false
  }
}

const removePhoto = async () => {
  if (uploadedPath.value) {
    try {
      await verificationApi.deleteFiles(props.adId)
      emit('deleted')
    } catch (err) {
      console.error('Failed to delete photo:', err)
    }
  }
  
  currentFile.value = null
  previewUrl.value = null
  uploadedPath.value = null
  error.value = null
  
  if (fileInput.value) {
    fileInput.value.value = ''
  }
}

const handleImageError = (event: Event) => {
  const target = event.target as HTMLImageElement
  target.style.display = 'none'
}

// Watchers
watch(() => props.currentPhoto, (newVal) => {
  if (newVal && !currentFile.value) {
    uploadedPath.value = newVal
  }
})
</script>

<style scoped>
.upload-label {
  @apply block w-full cursor-pointer;
  @apply border-2 border-dashed border-gray-300 rounded-lg;
  @apply hover:border-gray-400 transition-colors;
  @apply p-8 text-center;
}

.upload-label.dragging {
  @apply border-blue-500 bg-blue-50;
}

.upload-content {
  @apply flex flex-col items-center;
}
</style>
<template>
  <div class="photo-uploader">
    <!-- Загруженные фото -->
    <div v-if="photos.length" class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4">
      <div 
        v-for="(photo, index) in photos" 
        :key="index"
        class="relative group"
      >
        <!-- Превью фото -->
        <div class="aspect-square bg-gray-100 rounded-lg overflow-hidden">
          <img 
            :src="photo.preview || photo.url" 
            :alt="`Фото ${index + 1}`"
            class="w-full h-full object-cover"
          />
        </div>
        
        <!-- Оверлей с действиями -->
        <div class="absolute inset-0 bg-black bg-opacity-50 opacity-0 group-hover:opacity-100 transition-opacity rounded-lg flex items-center justify-center gap-2">
          <!-- Кнопка "Главное фото" -->
          <button
            v-if="!photo.is_main"
            @click="setMainPhoto(index)"
            class="p-2 bg-white text-gray-700 rounded-full hover:bg-gray-100 transition-colors"
            title="Сделать главным"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
            </svg>
          </button>
          
          <!-- Индикатор главного фото -->
          <div 
            v-else
            class="p-2 bg-yellow-500 text-white rounded-full"
            title="Главное фото"
          >
            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
              <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
            </svg>
          </div>
          
          <!-- Кнопка удалить -->
          <button
            @click="removePhoto(index)"
            class="p-2 bg-red-500 text-white rounded-full hover:bg-red-600 transition-colors"
            title="Удалить фото"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
            </svg>
          </button>
        </div>
        
        <!-- Порядковый номер -->
        <div class="absolute top-2 left-2 bg-black bg-opacity-70 text-white text-xs px-2 py-1 rounded">
          {{ index + 1 }}
        </div>
      </div>
    </div>
    
    <!-- Зона загрузки -->
    <div 
      v-if="photos.length < maxPhotos"
      @drop="handleDrop"
      @dragover="handleDragOver"
      @dragleave="handleDragLeave"
      @click="openFileDialog"
      class="border-2 border-dashed rounded-lg p-8 text-center cursor-pointer transition-colors"
      :class="[
        isDragging ? 'border-blue-500 bg-blue-50' : 'border-gray-300 hover:border-gray-400',
        'hover:bg-gray-50'
      ]"
    >
      <div class="flex flex-col items-center">
        <!-- Иконка -->
        <svg class="w-12 h-12 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
        </svg>
        
        <!-- Текст -->
        <h3 class="text-lg font-medium text-gray-900 mb-2">
          Добавить фотографии
        </h3>
        <p class="text-gray-600 mb-4">
          Перетащите файлы сюда или нажмите для выбора
        </p>
        
        <!-- Ограничения -->
        <div class="text-sm text-gray-500">
          <p>Максимум {{ maxPhotos }} фото</p>
          <p>До {{ maxSize }}MB каждое</p>
          <p>Форматы: JPG, PNG, WebP</p>
        </div>
      </div>
    </div>
    
    <!-- Сообщение об ошибке -->
    <div v-if="error" class="mt-4 p-4 bg-red-50 border border-red-200 rounded-lg">
      <div class="flex items-center">
        <svg class="w-5 h-5 text-red-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <span class="text-red-800">{{ error }}</span>
      </div>
    </div>
    
    <!-- Прогресс загрузки -->
    <div v-if="uploading" class="mt-4 p-4 bg-blue-50 border border-blue-200 rounded-lg">
      <div class="flex items-center justify-between mb-2">
        <span class="text-sm font-medium text-blue-700">Загрузка фотографий...</span>
        <span class="text-sm text-blue-600">{{ Math.round(uploadProgress) }}%</span>
      </div>
      <div class="w-full bg-blue-200 rounded-full h-2">
        <div 
          class="bg-blue-600 h-2 rounded-full transition-all duration-300"
          :style="{ width: `${uploadProgress}%` }"
        ></div>
      </div>
    </div>
    
    <!-- Скрытый input -->
    <input 
      ref="fileInput"
      type="file"
      multiple
      accept="image/jpeg,image/jpg,image/png,image/webp"
      @change="handleFileSelect"
      class="hidden"
    />
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'

const props = defineProps({
  modelValue: {
    type: Array,
    default: () => []
  },
  maxPhotos: {
    type: Number,
    default: 8
  },
  maxSize: {
    type: Number,
    default: 5 // MB
  }
})

const emit = defineEmits(['update:modelValue'])

// Состояние
const isDragging = ref(false)
const uploading = ref(false)
const uploadProgress = ref(0)
const error = ref('')
const fileInput = ref(null)

// Вычисления
const photos = computed({
  get: () => props.modelValue,
  set: (value) => emit('update:modelValue', value)
})

// Методы
const openFileDialog = () => {
  fileInput.value?.click()
}

const handleFileSelect = (event) => {
  const files = Array.from(event.target.files)
  processFiles(files)
}

const handleDrop = (event) => {
  event.preventDefault()
  isDragging.value = false
  
  const files = Array.from(event.dataTransfer.files).filter(file => 
    file.type.startsWith('image/')
  )
  
  processFiles(files)
}

const handleDragOver = (event) => {
  event.preventDefault()
  isDragging.value = true
}

const handleDragLeave = () => {
  isDragging.value = false
}

const processFiles = async (files) => {
  error.value = ''
  
  // Проверяем лимиты
  if (photos.value.length + files.length > props.maxPhotos) {
    error.value = `Максимум ${props.maxPhotos} фотографий`
    return
  }
  
  uploading.value = true
  uploadProgress.value = 0
  
  const newPhotos = []
  
  for (let i = 0; i < files.length; i++) {
    const file = files[i]
    
    // Проверяем размер
    if (file.size > props.maxSize * 1024 * 1024) {
      error.value = `Файл ${file.name} слишком большой (максимум ${props.maxSize}MB)`
      continue
    }
    
    // Создаём превью
    const preview = await createPreview(file)
    
    newPhotos.push({
      file,
      preview,
      name: file.name,
      size: file.size,
      is_main: photos.value.length === 0 && newPhotos.length === 0
    })
    
    uploadProgress.value = ((i + 1) / files.length) * 100
  }
  
  photos.value = [...photos.value, ...newPhotos]
  uploading.value = false
  
  // Очищаем input
  if (fileInput.value) {
    fileInput.value.value = ''
  }
}

const createPreview = (file) => {
  return new Promise((resolve) => {
    const reader = new FileReader()
    reader.onload = (e) => resolve(e.target.result)
    reader.readAsDataURL(file)
  })
}

const removePhoto = (index) => {
  const updatedPhotos = [...photos.value]
  const removedPhoto = updatedPhotos.splice(index, 1)[0]
  
  // Если удаляли главное фото, делаем главным первое оставшееся
  if (removedPhoto.is_main && updatedPhotos.length > 0) {
    updatedPhotos[0].is_main = true
  }
  
  photos.value = updatedPhotos
}

const setMainPhoto = (index) => {
  const updatedPhotos = photos.value.map((photo, i) => ({
    ...photo,
    is_main: i === index
  }))
  
  photos.value = updatedPhotos
}
</script>

<style scoped>
.photo-uploader {
  /* Дополнительные стили если нужны */
}
</style>
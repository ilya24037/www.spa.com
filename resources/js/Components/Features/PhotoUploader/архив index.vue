<!-- Модуль загрузки фотографий в стиле Авито -->
<template>
  <div class="avito-photo-uploader">
    <!-- Заголовок секции -->
    <div class="section-header">
      <h2 class="section-title">Фотографии и видео</h2>
    </div>

    <!-- Подзаголовок фотографий -->
    <div class="photos-header">
      <h5 class="photos-title">Фотографии</h5>
      <p class="photos-counter">{{ photos.length }} из {{ maxFiles }}</p>
    </div>

    <!-- Основная область загрузки -->
    <div 
      class="upload-zone"
      :class="{ 
        'drag-over': isDragOver,
        'has-photos': photos.length > 0 
      }"
      @drop="handleDrop"
      @dragover.prevent="handleDragOver"
      @dragleave="handleDragLeave"
    >
      <!-- Скрытый input -->
      <input
        ref="fileInput"
        type="file"
        multiple
        accept="image/gif,image/png,image/jpeg,image/pjpeg,image/heic"
        @change="handleFileSelect"
        class="hidden-input"
      />

      <!-- Список фотографий -->
      <div class="photos-list" v-if="photos.length > 0">
        <!-- Основное фото -->
        <div 
          v-for="(photo, index) in photos" 
          :key="photo.id"
          class="photo-item"
          :class="{ 
            'main-photo': index === 0,
            'drag-over': dragOverIndex === index,
            'being-dragged': draggedIndex === index
          }"
          draggable="true"
          @dragstart="handlePhotoStart($event, index)"
          @dragover.prevent="handleDragOverPhoto($event, index)"
          @drop.prevent="handlePhotoMove($event, index)"
          @dragend="handleDragEnd"
          @dragleave="handleDragLeavePhoto($event, index)"
        >
          <div class="photo-preview">
            <img 
              :src="photo.preview" 
              :alt="`Фото ${index + 1}`"
              class="photo-image"
              :style="{ transform: `rotate(${photo.rotation || 0}deg)` }"
            />
            
            <!-- Кнопки управления -->
            <div class="photo-controls">
              <button 
                type="button"
                class="control-btn rotate-btn"
                @click.stop="rotatePhoto(index)"
                title="Повернуть"
              >
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                  <path d="M1 4v6h6"/>
                  <path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"/>
                </svg>
              </button>
              
              <button 
                type="button"
                class="control-btn delete-btn"
                @click.stop="removePhoto(index)"
                title="Удалить"
              >
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                  <line x1="18" y1="6" x2="6" y2="18"/>
                  <line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
              </button>
            </div>
          </div>
          
          <!-- Пометка основного фото -->
          <span v-if="index === 0" class="main-photo-label">основное фото</span>
        </div>

        <!-- Кнопка добавления -->
        <label 
          v-if="photos.length < maxFiles"
          class="add-photo-btn"
          @click="triggerFileInput"
        >
          <div class="add-photo-content">
            <svg class="add-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor">
              <line x1="12" y1="5" x2="12" y2="19"/>
              <line x1="5" y1="12" x2="19" y2="12"/>
            </svg>
          </div>
        </label>
      </div>

      <!-- Пустое состояние -->
      <div v-else class="empty-state" @click="triggerFileInput">
        <div class="empty-content">
          <div class="upload-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
              <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
              <circle cx="8.5" cy="8.5" r="1.5"/>
              <polyline points="21,15 16,10 5,21"/>
            </svg>
          </div>
          <p class="upload-text">Перетащите фото сюда или нажмите для выбора</p>
          <p class="upload-hint">До {{ maxFiles }} фото, формат JPG, PNG</p>
        </div>
      </div>

      <!-- Drag-and-drop overlay -->
      <div v-if="isDragOver" class="drag-overlay">
        <div class="drag-content">
          <div class="drag-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
              <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
              <polyline points="7,10 12,15 17,10"/>
              <line x1="12" y1="15" x2="12" y2="3"/>
            </svg>
          </div>
          <p class="drag-text">Перетащите сюда изображения</p>
        </div>
      </div>
    </div>

    <!-- Подсказки -->
    <div class="upload-hints">
      <p class="hint-text">Добавьте качественные фото ваших работ</p>
      <p class="hint-text secondary">Перетащите фото сюда или нажмите для выбора</p>
      <p class="hint-text secondary">До {{ maxFiles }} фото, формат JPG, PNG</p>
      <p class="hint-text secondary">Максимум 50 фотографий. Первая фотография будет использоваться как главная</p>
    </div>

    <!-- Ошибки -->
    <div v-if="error" class="error-message">{{ error }}</div>

    <!-- Прогресс загрузки -->
    <div v-if="uploading" class="upload-progress">
      <div class="progress-bar">
        <div 
          class="progress-fill"
          :style="{ width: uploadProgress + '%' }"
        ></div>
      </div>
      <p class="progress-text">Загрузка... {{ uploadProgress }}%</p>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'

// Props
const props = defineProps({
  modelValue: {
    type: Array,
    default: () => []
  },
  maxFiles: {
    type: Number,
    default: 10
  },
  maxFileSize: {
    type: Number,
    default: 5 * 1024 * 1024 // 5MB
  },
  uploading: {
    type: Boolean,
    default: false
  },
  uploadProgress: {
    type: Number,
    default: 0
  }
})

// Events
const emit = defineEmits(['update:modelValue', 'upload', 'error'])

// State
const fileInput = ref(null)
const isDragOver = ref(false)
const error = ref('')
const dragOverIndex = ref(null)
let draggedIndex = null

// Computed
const photos = computed({
  get() {
    return props.modelValue
  },
  set(value) {
    emit('update:modelValue', value)
  }
})

// Methods
const triggerFileInput = () => {
  fileInput.value?.click()
}

const handleFileSelect = (event) => {
  const files = Array.from(event.target.files)
  processFiles(files)
  event.target.value = ''
}

const handleDrop = (event) => {
  event.preventDefault()
  isDragOver.value = false
  
  // Если это перестановка фото - игнорируем
  if (draggedIndex !== null) {
    return
  }
  
  // Иначе обрабатываем как загрузку новых файлов
  const files = Array.from(event.dataTransfer.files)
  if (files.length > 0) {
    processFiles(files)
  }
}

const handleDragOver = (event) => {
  event.preventDefault()
  isDragOver.value = true
}

const handleDragLeave = (event) => {
  event.preventDefault()
  isDragOver.value = false
}

const processFiles = (files) => {
  error.value = ''
  
  const imageFiles = files.filter(file => file.type.startsWith('image/'))
  
  if (imageFiles.length === 0) {
    error.value = 'Выберите изображения'
    return
  }
  
  // Проверяем лимит файлов
  if (photos.value.length + imageFiles.length > props.maxFiles) {
    error.value = `Максимум ${props.maxFiles} фотографий`
    return
  }
  
  // Проверяем размер файлов
  const oversizedFiles = imageFiles.filter(file => file.size > props.maxFileSize)
  if (oversizedFiles.length > 0) {
    error.value = `Некоторые файлы слишком большие (максимум ${props.maxFileSize / (1024 * 1024)}MB)`
    return
  }
  
  // Создаем превью и добавляем файлы
  imageFiles.forEach(file => {
    const reader = new FileReader()
    reader.onload = (e) => {
      const photo = {
        id: Date.now() + Math.random(),
        file: file,
        preview: e.target.result,
        name: file.name,
        size: file.size,
        rotation: 0
      }
      
      photos.value = [...photos.value, photo]
    }
    reader.readAsDataURL(file)
  })
}

const removePhoto = (index) => {
  if (index < 0 || index >= photos.value.length) {
    return
  }
  
  const newPhotos = [...photos.value]
  newPhotos.splice(index, 1)
  photos.value = newPhotos
  
}

const rotatePhoto = (index) => {
  if (index < 0 || index >= photos.value.length) {
    return
  }
  
  const newPhotos = [...photos.value]
  const currentRotation = newPhotos[index].rotation || 0
  newPhotos[index].rotation = (currentRotation + 90) % 360
  photos.value = newPhotos
  
}

// Drag and drop для перестановки фото
const handlePhotoStart = (event, index) => {
  draggedIndex = index
  event.dataTransfer.effectAllowed = 'move'
  event.dataTransfer.setData('text/plain', index.toString())
  
  // Добавляем прозрачность
  setTimeout(() => {
    if (event.target) {
      event.target.style.opacity = '0.5'
    }
  }, 0)
}

const handlePhotoMove = (event, targetIndex) => {
  event.preventDefault()
  event.stopPropagation()
  
  
  if (draggedIndex === null || draggedIndex === targetIndex) {
    return
  }
  
  if (draggedIndex < 0 || draggedIndex >= photos.value.length || 
      targetIndex < 0 || targetIndex >= photos.value.length) {
    return
  }
  
  const newPhotos = [...photos.value]
  
  // Простая логика: меняем местами
  const temp = newPhotos[draggedIndex]
  newPhotos[draggedIndex] = newPhotos[targetIndex]
  newPhotos[targetIndex] = temp
  
  
  photos.value = newPhotos
  
  // Сбрасываем состояние
  draggedIndex = null
  dragOverIndex.value = null
}

// Новые обработчики для лучшего UX
const handleDragOverPhoto = (event, index) => {
  event.preventDefault()
  event.stopPropagation()
  
  if (draggedIndex !== null && draggedIndex !== index) {
    dragOverIndex.value = index
  }
}

const handleDragLeavePhoto = (event, index) => {
  // Сбрасываем только если покидаем именно этот элемент
  if (dragOverIndex.value === index) {
    dragOverIndex.value = null
  }
}

const handleDragEnd = (event) => {
  
  // Возвращаем нормальную прозрачность
  if (event.target) {
    event.target.style.opacity = '1'
  }
  
  // Сбрасываем состояние
  draggedIndex = null
  dragOverIndex.value = null
}

// Метод для получения файлов
const getFiles = () => {
  return photos.value.map(photo => photo.file)
}

// Метод для очистки
const clear = () => {
  photos.value = []
  error.value = ''
}

// Expose methods
defineExpose({
  getFiles,
  clear
})
</script>

<style scoped>
.avito-photo-uploader {
  @apply w-full;
}

.section-header {
  @apply mb-6;
}

.section-title {
  @apply text-2xl font-bold text-gray-900;
}

.photos-header {
  @apply flex items-center justify-between mb-4;
}

.photos-title {
  @apply text-lg font-medium text-gray-900;
}

.photos-counter {
  @apply text-sm text-gray-500;
}

.upload-zone {
  @apply relative border-2 border-dashed border-gray-300 rounded-lg;
  min-height: 200px;
  transition: all 0.2s ease;
}

.upload-zone:hover {
  @apply border-blue-400;
}

.upload-zone.drag-over {
  @apply border-blue-500 bg-blue-50;
}

.upload-zone.has-photos {
  @apply border-solid border-gray-200;
}

.hidden-input {
  @apply sr-only;
}

.photos-list {
  @apply p-4 grid gap-4;
  grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
}

.photo-item {
  @apply relative bg-gray-100 rounded-lg overflow-hidden cursor-move;
  aspect-ratio: 4/3;
}

.photo-item.main-photo .photo-preview::after {
  content: '';
  @apply absolute inset-0 ring-2 ring-blue-500;
  border-radius: inherit;
}

/* Визуальные состояния для drag & drop */
.photo-item.being-dragged {
  @apply opacity-30 scale-90;
  transition: all 0.2s ease;
}

.photo-item.drag-over {
  @apply ring-4 ring-blue-500 bg-blue-50 scale-105;
  transition: all 0.2s ease;
}

.photo-item.drag-over::before {
  content: 'Поменять местами';
  @apply absolute inset-0 bg-blue-500 bg-opacity-20 flex items-center justify-center text-blue-700 font-medium text-sm z-20;
}

.photo-preview {
  @apply relative w-full h-full;
}

.photo-image {
  @apply w-full h-full object-cover;
  transition: transform 0.3s ease;
}

.photo-controls {
  @apply absolute top-2 right-2 flex gap-1 opacity-0;
  transition: opacity 0.2s ease;
  z-index: 10;
}

.photo-item:hover .photo-controls {
  @apply opacity-100;
}

/* Для основного фото всегда показываем кнопки */
.photo-item.main-photo .photo-controls {
  @apply opacity-100;
}

.control-btn {
  @apply w-8 h-8 bg-white bg-opacity-90 hover:bg-opacity-100 rounded-full flex items-center justify-center shadow transition-all;
  cursor: pointer;
  z-index: 11;
}

.control-btn svg {
  @apply w-4 h-4;
}

.rotate-btn {
  @apply text-blue-600 hover:text-blue-700;
}

.delete-btn {
  @apply text-red-600 hover:text-red-700;
}

.main-photo-label {
  @apply absolute bottom-2 left-2 bg-black bg-opacity-70 text-white text-xs px-2 py-1 rounded;
}

.add-photo-btn {
  @apply relative bg-gray-50 border-2 border-dashed border-gray-300 rounded-lg cursor-pointer hover:border-blue-400 hover:bg-blue-50 transition-all;
  aspect-ratio: 4/3;
}

.add-photo-content {
  @apply absolute inset-0 flex items-center justify-center;
}

.add-icon {
  @apply w-8 h-8 text-gray-400;
}

.empty-state {
  @apply flex items-center justify-center cursor-pointer h-48;
}

.empty-content {
  @apply text-center;
}

.upload-icon {
  @apply w-12 h-12 text-gray-400 mx-auto mb-4;
}

.upload-icon svg {
  @apply w-full h-full;
}

.upload-text {
  @apply text-lg text-gray-700 mb-2;
}

.upload-hint {
  @apply text-sm text-gray-500;
}

.drag-overlay {
  @apply absolute inset-0 bg-blue-50 bg-opacity-95 flex items-center justify-center z-10;
}

.drag-content {
  @apply text-center;
}

.drag-icon {
  @apply w-16 h-16 text-blue-500 mx-auto mb-4;
}

.drag-icon svg {
  @apply w-full h-full;
}

.drag-text {
  @apply text-xl font-medium text-blue-700;
}

.upload-hints {
  @apply mt-4 space-y-1;
}

.hint-text {
  @apply text-sm text-gray-600;
}

.hint-text.secondary {
  @apply text-gray-500;
}

.error-message {
  @apply mt-2 text-sm text-red-600;
}

.upload-progress {
  @apply mt-4;
}

.progress-bar {
  @apply w-full h-2 bg-gray-200 rounded-full overflow-hidden;
}

.progress-fill {
  @apply h-full bg-blue-500 transition-all duration-300;
}

.progress-text {
  @apply mt-2 text-sm text-gray-600;
}

/* Мобильная адаптация */
@media (max-width: 768px) {
  .photos-list {
    grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
    @apply gap-2 p-2;
  }
  
  .upload-zone {
    min-height: 150px;
  }
  
  .section-title {
    @apply text-xl;
  }
}
</style> 
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
        'drag-over': isDragOver && draggedIndex === null,
        'has-photos': photos.length > 0 
      }"
      @drop.prevent="handleDrop"
      @dragover.prevent="handleDragOver"
      @dragleave.prevent="handleDragLeave"
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
        <div 
          v-for="(photo, index) in photos" 
          :key="`photo-${photo.id}`"
          class="photo-item"
          :class="{ 
            'main-photo': index === 0,
            'drag-over': dragOverIndex === index && draggedIndex !== null && draggedIndex !== index,
            'being-dragged': draggedIndex === index
          }"
          draggable="true"
          @dragstart="handlePhotoStart($event, index)"
          @dragover.prevent="handlePhotoDragOver($event, index)"
          @drop.prevent="handlePhotoDrop($event, index)"
          @dragend="handleDragEnd"
          @dragleave.prevent="handlePhotoDragLeave($event, index)"
        >
          <div class="photo-preview">
            <img 
              :src="photo.preview" 
              :alt="photo.name"
              class="photo-image"
              :style="{ transform: `rotate(${photo.rotation || 0}deg)` }"
            />
            
            <!-- Контролы фото -->
            <div class="photo-controls">
              <button 
                @click.stop="rotatePhoto(index)" 
                class="control-btn rotate-btn"
                type="button"
                title="Повернуть"
              >
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <polyline points="23,4 23,10 17,10"/>
                  <path d="M20.49,15a9,9,0,1,1-2.12-9.36L23,10"/>
                </svg>
              </button>
              
              <button 
                @click.stop="removePhoto(index)" 
                class="control-btn delete-btn"
                type="button"
                title="Удалить"
              >
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <polyline points="3,6 5,6 21,6"/>
                  <path d="M19,6v14a2,2,0,0,1-2,2H7a2,2,0,0,1-2-2V6m3,0V4a2,2,0,0,1,2-2h4a2,2,0,0,1,2,2v2"/>
                </svg>
              </button>
            </div>
            
            <!-- Метка основного фото -->
            <div v-if="index === 0" class="main-photo-label">
              Основное фото
            </div>
          </div>
        </div>

        <!-- Кнопка добавления фото -->
        <label v-if="photos.length < maxFiles" class="add-photo-btn">
          <input
            type="file"
            multiple
            accept="image/gif,image/png,image/jpeg,image/pjpeg,image/heic"
            @change="handleFileSelect"
            class="hidden-input"
          />
          <div class="add-photo-content">
            <svg class="add-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
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

      <!-- Drag-and-drop overlay для новых файлов -->
      <div v-if="isDragOver && draggedIndex === null" class="drag-overlay">
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
      <p class="hint-text secondary">Перетащите фото для изменения порядка</p>
      <p class="hint-text secondary">До {{ maxFiles }} фото, формат JPG, PNG</p>
      <p class="hint-text secondary">Первая фотография будет использоваться как главная</p>
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
const draggedIndex = ref(null)

// Computed
const photos = computed({
  get() {
    // Безопасное получение массива
    const value = props.modelValue
    return Array.isArray(value) ? value : []
  },
  set(value) {
    // Безопасная отправка массива
    const safeValue = Array.isArray(value) ? value : []
    emit('update:modelValue', safeValue)
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
  event.stopPropagation()
  
  // Если это не перетаскивание фото (draggedIndex === null), обрабатываем файлы
  if (draggedIndex.value === null) {
    isDragOver.value = false
    const files = Array.from(event.dataTransfer.files)
    if (files.length > 0) {
      processFiles(files)
    }
  }
}

const handleDragOver = (event) => {
  event.preventDefault()
  // Показываем оверлей только если не перетаскиваем существующее фото
  if (draggedIndex.value === null) {
    isDragOver.value = true
  }
}

const handleDragLeave = (event) => {
  event.preventDefault()
  // Убираем оверлей только если покидаем всю зону
  if (event.currentTarget === event.target) {
    isDragOver.value = false
  }
}

const processFiles = (files) => {
  error.value = ''
  
  const imageFiles = files.filter(file => file.type.startsWith('image/'))
  
  if (imageFiles.length === 0) {
    error.value = 'Выберите изображения'
    return
  }
  
  // Проверяем лимит файлов
  const currentPhotosCount = Array.isArray(photos.value) ? photos.value.length : 0
  if (currentPhotosCount + imageFiles.length > props.maxFiles) {
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
  const currentPhotos = Array.isArray(photos.value) ? photos.value : []
  if (index < 0 || index >= currentPhotos.length) {
    return
  }
  
  const newPhotos = [...currentPhotos]
  newPhotos.splice(index, 1)
  photos.value = newPhotos
}

const rotatePhoto = (index) => {
  const currentPhotos = Array.isArray(photos.value) ? photos.value : []
  if (index < 0 || index >= currentPhotos.length) {
    return
  }
  
  const newPhotos = [...currentPhotos]
  const currentRotation = newPhotos[index].rotation || 0
  newPhotos[index] = {
    ...newPhotos[index],
    rotation: (currentRotation + 90) % 360
  }
  photos.value = newPhotos
}

// Drag and drop для перестановки фото
const handlePhotoStart = (event, index) => {
  draggedIndex.value = index
  event.dataTransfer.effectAllowed = 'move'
  event.dataTransfer.setData('text/plain', index.toString())
  
  // Добавляем класс для визуального эффекта
  event.target.classList.add('opacity-50')
}

const handlePhotoDragOver = (event, index) => {
  event.preventDefault()
  event.stopPropagation()
  
  if (draggedIndex.value !== null && draggedIndex.value !== index) {
    dragOverIndex.value = index
  }
}

const handlePhotoDrop = (event, targetIndex) => {
  event.preventDefault()
  event.stopPropagation()
  
  const sourceIndex = draggedIndex.value
  
  if (sourceIndex === null || sourceIndex === targetIndex) {
    return
  }
  
  const currentPhotos = Array.isArray(photos.value) ? photos.value : []
  if (sourceIndex < 0 || sourceIndex >= currentPhotos.length || 
      targetIndex < 0 || targetIndex >= currentPhotos.length) {
    return
  }
  
  // Создаем новый массив для перестановки
  const newPhotos = [...currentPhotos]
  const [movedPhoto] = newPhotos.splice(sourceIndex, 1)
  newPhotos.splice(targetIndex, 0, movedPhoto)
  
  photos.value = newPhotos
  
  // Сбрасываем состояние
  draggedIndex.value = null
  dragOverIndex.value = null
}

const handlePhotoDragLeave = (event, index) => {
  // Сбрасываем только если действительно покидаем элемент
  if (!event.currentTarget.contains(event.relatedTarget)) {
    if (dragOverIndex.value === index) {
      dragOverIndex.value = null
    }
  }
}

const handleDragEnd = (event) => {
  // Убираем визуальные эффекты
  event.target.classList.remove('opacity-50')
  
  // Сбрасываем состояние
  draggedIndex.value = null
  dragOverIndex.value = null
  isDragOver.value = false
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
  transition: all 0.2s ease;
}

.photo-item.main-photo {
  @apply ring-2 ring-blue-500;
}

.photo-item.being-dragged {
  @apply opacity-30 scale-95;
}

.photo-item.drag-over {
  @apply ring-4 ring-blue-400 bg-blue-50 scale-105;
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

.control-btn {
  @apply w-8 h-8 bg-white bg-opacity-90 hover:bg-opacity-100 rounded-full flex items-center justify-center shadow transition-all;
  cursor: pointer;
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

/* Индикатор места при перетаскивании */
.photo-item.drag-over::before {
  content: '';
  @apply absolute inset-0 bg-blue-500 bg-opacity-20 z-10;
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
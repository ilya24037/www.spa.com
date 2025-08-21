<template>
  <div class="photos-section">
    <h2 class="form-group-title">Фотографии и видео</h2>
    
    <!-- Подзаголовок фотографий -->
    <div class="photos-header">
      <h5 class="photos-subtitle">Фотографии</h5>
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
          :key="`photo-${photo.id || index}`"
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
              :src="photoUrl(photo)" 
              :alt="photo.name || 'Фото'"
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
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="20" height="20">
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
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="20" height="20">
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
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" width="48" height="48">
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
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" width="48" height="48">
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
      <p class="hint-text secondary">Первая фотография будет использоваться как главная</p>
    </div>

    <!-- Ошибки -->
    <div v-if="error" class="error-message">{{ error }}</div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'

// Props
const props = defineProps({
  photos: {
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
  errors: { 
    type: Object, 
    default: () => ({}) 
  }
})

// Events
const emit = defineEmits(['update:photos'])

// State
const fileInput = ref(null)
const isDragOver = ref(false)
const error = ref('')
const dragOverIndex = ref(null)
const draggedIndex = ref(null)
const photos = ref([])

// Инициализация фотографий из props
watch(() => props.photos, (newPhotos) => {
  // Преобразуем фотографии в нужный формат
  photos.value = newPhotos.map((photo, index) => {
    if (typeof photo === 'string') {
      return {
        id: `existing-${index}`,
        preview: photo,
        name: `photo-${index}`,
        rotation: 0
      }
    }
    return {
      ...photo,
      id: photo.id || `photo-${index}`,
      rotation: photo.rotation || 0
    }
  })
}, { immediate: true })

// Emit changes
watch(photos, (newPhotos) => {
  // Передаем массив файлов или URL обратно
  const photosToEmit = newPhotos.map(photo => {
    if (photo.file) {
      return photo.file
    }
    return photo.preview
  })
  emit('update:photos', photosToEmit)
}, { deep: true })

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
  
  // Перемещаем фото
  const newPhotos = [...photos.value]
  const [movedPhoto] = newPhotos.splice(sourceIndex, 1)
  newPhotos.splice(targetIndex, 0, movedPhoto)
  
  photos.value = newPhotos
  
  // Сбрасываем состояние
  draggedIndex.value = null
  dragOverIndex.value = null
}

const handleDragEnd = (event) => {
  event.target.classList.remove('opacity-50')
  draggedIndex.value = null
  dragOverIndex.value = null
}

const handlePhotoDragLeave = (event, index) => {
  if (dragOverIndex.value === index) {
    dragOverIndex.value = null
  }
}

const photoUrl = (photo) => {
  if (!photo) return ''
  if (typeof photo === 'string') return photo
  if (photo.preview) return photo.preview
  if (photo.file && photo.file instanceof File) {
    return URL.createObjectURL(photo.file)
  }
  return ''
}
</script>

<style scoped>
.photos-section {
  background: white;
  border-radius: 8px;
  padding: 24px;
}

.form-group-title {
  font-size: 20px;
  font-weight: 600;
  color: #000;
  margin-bottom: 20px;
}

.photos-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 16px;
}

.photos-subtitle {
  font-size: 16px;
  font-weight: 500;
  color: #333;
  margin: 0;
}

.photos-counter {
  font-size: 14px;
  color: #666;
  margin: 0;
}

.upload-zone {
  border: 2px dashed #ddd;
  border-radius: 8px;
  min-height: 200px;
  position: relative;
  transition: all 0.3s ease;
}

.upload-zone.drag-over {
  border-color: #007bff;
  background-color: #f0f8ff;
}

.upload-zone.has-photos {
  border-style: solid;
  padding: 16px;
}

.hidden-input {
  display: none;
}

.photos-list {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
  gap: 12px;
}

.photo-item {
  position: relative;
  aspect-ratio: 1;
  cursor: move;
  transition: all 0.3s ease;
}

.photo-item.being-dragged {
  opacity: 0.5;
}

.photo-item.drag-over {
  transform: scale(1.05);
}

.photo-preview {
  position: relative;
  width: 100%;
  height: 100%;
  border-radius: 8px;
  overflow: hidden;
  background: #f5f5f5;
}

.photo-image {
  width: 100%;
  height: 100%;
  object-fit: cover;
  transition: transform 0.3s ease;
}

.photo-controls {
  position: absolute;
  top: 8px;
  right: 8px;
  display: flex;
  gap: 4px;
  opacity: 0;
  transition: opacity 0.3s ease;
}

.photo-item:hover .photo-controls {
  opacity: 1;
}

.control-btn {
  width: 32px;
  height: 32px;
  border-radius: 6px;
  background: rgba(255, 255, 255, 0.9);
  border: 1px solid #ddd;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.2s ease;
}

.control-btn:hover {
  background: white;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
}

.control-btn svg {
  color: #666;
}

.rotate-btn:hover svg {
  color: #007bff;
}

.delete-btn:hover svg {
  color: #dc3545;
}

.main-photo-label {
  position: absolute;
  bottom: 8px;
  left: 8px;
  right: 8px;
  background: rgba(0, 123, 255, 0.9);
  color: white;
  padding: 4px 8px;
  border-radius: 4px;
  font-size: 12px;
  font-weight: 500;
  text-align: center;
}

.add-photo-btn {
  aspect-ratio: 1;
  border: 2px dashed #ddd;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.3s ease;
  background: #fafafa;
}

.add-photo-btn:hover {
  border-color: #007bff;
  background: #f0f8ff;
}

.add-photo-content {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 8px;
}

.add-icon {
  width: 32px;
  height: 32px;
  color: #666;
}

.empty-state {
  display: flex;
  align-items: center;
  justify-content: center;
  min-height: 200px;
  cursor: pointer;
}

.empty-content {
  text-align: center;
  padding: 32px;
}

.upload-icon {
  display: flex;
  justify-content: center;
  margin-bottom: 16px;
}

.upload-icon svg {
  color: #999;
}

.upload-text {
  font-size: 16px;
  color: #333;
  margin-bottom: 8px;
}

.upload-hint {
  font-size: 14px;
  color: #666;
}

.drag-overlay {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 123, 255, 0.1);
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  pointer-events: none;
}

.drag-content {
  text-align: center;
}

.drag-icon {
  display: flex;
  justify-content: center;
  margin-bottom: 16px;
}

.drag-icon svg {
  color: #007bff;
}

.drag-text {
  font-size: 18px;
  font-weight: 500;
  color: #007bff;
}

.upload-hints {
  margin-top: 16px;
}

.hint-text {
  font-size: 14px;
  color: #666;
  margin: 4px 0;
}

.hint-text.secondary {
  font-size: 13px;
  color: #999;
}

.error-message {
  margin-top: 12px;
  padding: 12px;
  background: #fee;
  border: 1px solid #fcc;
  border-radius: 6px;
  color: #c00;
  font-size: 14px;
}

/* Мобильная адаптация */
@media (max-width: 640px) {
  .photos-list {
    grid-template-columns: repeat(auto-fill, minmax(80px, 1fr));
  }
  
  .control-btn {
    width: 28px;
    height: 28px;
  }
  
  .control-btn svg {
    width: 16px;
    height: 16px;
  }
}
</style>
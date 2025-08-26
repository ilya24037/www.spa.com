<template>
  <div class="media-section">
    <h2 class="form-group-title">Фотографии и видео</h2>
    <p class="section-description">
      Добавьте качественные фото и видео ваших работ. Это поможет привлечь больше клиентов.
    </p>
    
    <!-- Фотографии -->
    <div class="photos-subsection">
      <h4 class="field-label">Фотографии работ</h4>
      <p class="field-hint">
        Добавьте до 10 качественных фотографий ваших работ. Рекомендуемый формат 4:3
      </p>
      
      <!-- Область загрузки с drag-and-drop -->
      <div 
        class="upload-zone"
        :class="{ 
          'drag-over': isDragOver && draggedIndex === null,
          'has-photos': localPhotos.length > 0 
        }"
        @drop.prevent="handleDrop"
        @dragover.prevent="handleDragOver"
        @dragleave.prevent="handleDragLeave"
      >
        <!-- Скрытый input -->
        <input
          ref="photoInput"
          type="file"
          multiple
          accept="image/*"
          @change="handlePhotoUpload"
          class="hidden"
        />

        <!-- Кнопка удалить все фото -->
        <div v-if="localPhotos.length > 0" class="photos-header">
          <span class="photos-count">Фотографий: {{ localPhotos.length }}</span>
          <button 
            type="button"
            @click="removeAllPhotos"
            class="remove-all-btn"
            title="Удалить все фотографии"
          >
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <polyline points="3,6 5,6 21,6"/>
              <path d="M19,6v14a2,2,0,0,1-2,2H7a2,2,0,0,1-2-2V6m3,0V4a2,2,0,0,1,2-2h4a2,2,0,0,1,2,2v2"/>
            </svg>
            Удалить все
          </button>
        </div>
        
        <!-- Список фотографий с возможностью перетаскивания -->
        <div v-if="localPhotos.length > 0" class="photo-grid">
          <div 
            v-for="(photo, index) in localPhotos" 
            :key="photo.id || index"
            class="photo-item"
            :class="{ 
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
            <div class="photo-wrapper">
              <img 
                :src="getPhotoUrl(photo)" 
                :alt="`Фото ${index + 1}`"
                class="photo-preview"
                :style="{ transform: `rotate(${photo.rotation || 0}deg)` }"
              />
              
              <!-- Контролы фото -->
              <div class="photo-controls">
                <button 
                  type="button"
                  @click.stop="rotatePhoto(index)"
                  class="control-btn rotate-btn"
                  title="Повернуть"
                >
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="23,4 23,10 17,10"/>
                    <path d="M20.49,15a9,9,0,1,1-2.12-9.36L23,10"/>
                  </svg>
                </button>
                
                <button 
                  type="button"
                  @click.stop="removePhoto(index)"
                  class="control-btn delete-btn"
                  title="Удалить"
                >
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="3,6 5,6 21,6"/>
                    <path d="M19,6v14a2,2,0,0,1-2,2H7a2,2,0,0,1-2-2V6m3,0V4a2,2,0,0,1,2-2h4a2,2,0,0,1,2,2v2"/>
                  </svg>
                </button>
              </div>
              
              <!-- Метка основного фото -->
              <div v-if="index === 0" class="main-photo-badge">
                Основное
              </div>
            </div>
          </div>
          
          <!-- Кнопка добавить еще -->
          <div 
            v-if="localPhotos.length < 10" 
            class="add-photo-btn"
            @click="triggerPhotoInput"
          >
            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor">
              <line x1="12" y1="5" x2="12" y2="19" stroke-width="2"/>
              <line x1="5" y1="12" x2="19" y2="12" stroke-width="2"/>
            </svg>
            <span>Добавить фото</span>
          </div>
        </div>

        <!-- Пустое состояние -->
        <div v-else class="empty-upload" @click="triggerPhotoInput">
          <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor">
            <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
            <circle cx="8.5" cy="8.5" r="1.5"/>
            <polyline points="21,15 16,10 5,21"/>
          </svg>
          <p class="upload-text">Перетащите фото сюда или нажмите для выбора</p>
          <p class="upload-hint">До 10 фото, формат JPG, PNG</p>
        </div>

        <!-- Оверлей для drag-and-drop -->
        <div v-if="isDragOver && draggedIndex === null" class="drag-overlay">
          <div class="drag-content">
            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor">
              <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
              <polyline points="7,10 12,15 17,10"/>
              <line x1="12" y1="15" x2="12" y2="3"/>
            </svg>
            <p class="drag-text">Перетащите сюда изображения</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Видео презентация -->
    <div class="video-subsection">
      <h4 class="field-label">Видео презентация</h4>
      <p class="field-hint">Добавьте короткое видео (до 50MB) для демонстрации ваших услуг</p>
      
      <div class="video-grid">
        <input
          type="file"
          ref="videoInput"
          accept="video/*"
          @change="handleVideoUpload"
          class="hidden"
        />
        
        <!-- Видео элемент в стиле фото -->
        <div v-if="localVideos.length > 0" class="video-item">
          <div class="video-wrapper">
            <video 
              v-for="(video, index) in localVideos" 
              :key="index"
              :src="video.preview || video.url" 
              controls
              class="video-element"
            />
            
            <!-- Контролы видео -->
            <div class="video-controls">
              <button 
                type="button"
                @click="removeVideo(0)"
                class="control-btn delete-btn"
                title="Удалить видео"
              >
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="20" height="20">
                  <polyline points="3,6 5,6 21,6"/>
                  <path d="M19,6v14a2,2,0,0,1-2,2H7a2,2,0,0,1-2-2V6m3,0V4a2,2,0,0,1,2-2h4a2,2,0,0,1,2,2v2"/>
                </svg>
              </button>
            </div>
          </div>
        </div>
        
        <!-- Кнопка добавления видео -->
        <div 
          v-else
          class="add-video-btn"
          @click="triggerVideoInput"
        >
          <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor">
            <polygon points="23,7 16,12 23,17" stroke-width="2"/>
            <rect x="1" y="5" width="15" height="14" rx="2" ry="2" stroke-width="2"/>
          </svg>
          <span>Добавить видео</span>
        </div>
      </div>
    </div>

    <!-- Настройки отображения -->
    <div class="media-settings">
      <h4 class="field-label">Настройки отображения</h4>
      <div class="space-y-3">
        <BaseCheckbox
          v-for="setting in allMediaSettings"
          :key="setting.id"
          :model-value="isMediaSettingSelected(setting.id)"
          :label="setting.label"
          @update:modelValue="toggleMediaSetting(setting.id)"
        />
      </div>
    </div>

    <!-- Ошибки -->
    <div v-if="error" class="error-message">{{ error }}</div>
  </div>
</template>

<script setup>
import { ref, watch, nextTick } from 'vue'
import BaseCheckbox from '@/src/shared/ui/atoms/BaseCheckbox/BaseCheckbox.vue'

const props = defineProps({
  photos: { type: Array, default: () => [] },
  video: { type: Array, default: () => [] },
  mediaSettings: { type: Array, default: () => ['show_photos_in_gallery'] },
  errors: { type: Object, default: () => ({}) }
})

const emit = defineEmits(['update:photos', 'update:video', 'update:media-settings'])

// Refs
const photoInput = ref(null)
const videoInput = ref(null)

// Media settings definitions (скопировано из рабочего FeaturesSection)
const allMediaSettings = [
  { id: 'show_photos_in_gallery', label: 'Показывать фото в галерее на странице объявления' },
  { id: 'allow_download_photos', label: 'Разрешить клиентам скачивать фотографии' },
  { id: 'watermark_photos', label: 'Добавить водяной знак на фотографии' }
]

// State
const localPhotos = ref([])
const localVideos = ref([])
const localMediaSettings = ref([...props.mediaSettings])  // Скопировано из FeaturesSection
const error = ref('')

// Drag and drop state
const isDragOver = ref(false)
const draggedIndex = ref(null)
const dragOverIndex = ref(null)

// Initialize from props only once
watch(() => props.photos, (newPhotos) => {
  // Only update if localPhotos is empty (initial load)
  if (localPhotos.value.length === 0 && newPhotos && newPhotos.length > 0) {
    localPhotos.value = newPhotos.map((photo, index) => {
      if (typeof photo === 'string') {
        return {
          id: `existing-${index}`,
          url: photo,
          preview: photo,
          rotation: 0
        }
      }
      return {
        ...photo,
        id: photo.id || `photo-${index}`,
        rotation: photo.rotation || 0
      }
    })
  }
}, { immediate: true })

watch(() => props.video, (newVideos) => {
  // Only update if localVideos is empty (initial load)
  if (localVideos.value.length === 0 && newVideos && newVideos.length > 0) {
    localVideos.value = newVideos.map((video, index) => {
      if (typeof video === 'string') {
        return {
          id: `video-${index}`,
          url: video,
          preview: video
        }
      }
      return video
    })
  }
}, { immediate: true })

// Watch для синхронизации с props (скопировано из FeaturesSection)
watch(() => props.mediaSettings, (val) => {
  localMediaSettings.value = [...(val || [])]
}, { deep: true, immediate: true })

// ОТКЛЮЧЕНО: Emit делается вручную в каждой функции изменения
// watch(localPhotos, (newPhotos) => {
//   const photosToEmit = newPhotos.map(photo => {
//     if (photo.file) return photo.file
//     return photo.url || photo.preview || photo
//   })
//   emit('update:photos', photosToEmit)
// }, { deep: true })

watch(localVideos, (newVideos) => {
  const videosToEmit = newVideos.map(video => {
    if (video.file) return video.file
    return video.url || video.preview
  })
  emit('update:video', videosToEmit)
}, { deep: true })

// Methods
const triggerPhotoInput = () => {
  photoInput.value?.click()
}

const triggerVideoInput = () => {
  videoInput.value?.click()
}

const handlePhotoUpload = (event) => {
  const files = Array.from(event.target.files)
  processPhotos(files)
  event.target.value = ''
}

const handleVideoUpload = (event) => {
  const file = event.target.files[0]
  if (file) {
    processVideo(file)
    event.target.value = ''
  }
}

const processPhotos = (files) => {
  error.value = ''
  
  const imageFiles = files.filter(file => file.type.startsWith('image/'))
  
  if (imageFiles.length === 0) {
    error.value = 'Выберите изображения'
    return
  }
  
  if (localPhotos.value.length + imageFiles.length > 10) {
    error.value = 'Максимум 10 фотографий'
    return
  }
  
  let loadedCount = 0
  imageFiles.forEach(file => {
    const reader = new FileReader()
    reader.onload = (e) => {
      const photo = {
        id: Date.now() + Math.random(),
        file: file,
        preview: e.target.result,
        name: file.name,
        rotation: 0
      }
      localPhotos.value = [...localPhotos.value, photo]
      
      loadedCount++
      // Эмитим после загрузки всех файлов
      if (loadedCount === imageFiles.length) {
        const photosToEmit = localPhotos.value.map(p => {
          if (p.file) return p.file
          return p.url || p.preview || p
        })
        emit('update:photos', photosToEmit)
      }
    }
    reader.readAsDataURL(file)
  })
}

const processVideo = (file) => {
  error.value = ''
  
  if (!file.type.startsWith('video/')) {
    error.value = 'Выберите видео файл'
    return
  }
  
  if (file.size > 50 * 1024 * 1024) {
    error.value = 'Максимальный размер видео 50MB'
    return
  }
  
  const reader = new FileReader()
  reader.onload = (e) => {
    localVideos.value = [{
      id: Date.now(),
      file: file,
      preview: e.target.result,
      name: file.name
    }]
  }
  reader.readAsDataURL(file)
}

const removePhoto = (index) => {
  
  // KISS: простое удаление
  const newPhotos = [...localPhotos.value]
  newPhotos.splice(index, 1)
  localPhotos.value = newPhotos
  
  
  // Эмитим изменения немедленно для надежности
  const photosToEmit = newPhotos.map(photo => {
    if (photo.file) return photo.file
    return photo.url || photo.preview || photo
  })
  
  emit('update:photos', photosToEmit)
}

const removeAllPhotos = () => {
  
  // Очищаем локальный массив
  localPhotos.value = []
  
  // Эмитим пустой массив
  emit('update:photos', [])
}

const removeVideo = (index) => {
  
  // Правильно удаляем только одно видео по индексу
  const newVideos = [...localVideos.value]
  newVideos.splice(index, 1)
  localVideos.value = newVideos
  
  
  // Эмитим обновленный массив
  const videosToEmit = newVideos.map(video => {
    if (video.file) return video.file
    return video.url || video.preview || video
  })
  
  emit('update:video', videosToEmit)
}

const rotatePhoto = (index) => {
  if (index < 0 || index >= localPhotos.value.length) return
  
  const newPhotos = [...localPhotos.value]
  const currentRotation = newPhotos[index].rotation || 0
  newPhotos[index] = {
    ...newPhotos[index],
    rotation: (currentRotation + 90) % 360
  }
  localPhotos.value = newPhotos
  
  // Эмитим изменения
  const photosToEmit = newPhotos.map(photo => {
    if (photo.file) return photo.file
    return photo.url || photo.preview || photo
  })
  emit('update:photos', photosToEmit)
}

// Drag and drop handlers
const handleDrop = (event) => {
  event.preventDefault()
  event.stopPropagation()
  
  // Only process files if we're not dragging a photo
  if (draggedIndex.value === null) {
    isDragOver.value = false
    const files = Array.from(event.dataTransfer.files)
    if (files.length > 0) {
      processPhotos(files)
    }
  }
}

const handleDragOver = (event) => {
  event.preventDefault()
  // Only show drag over if we're not dragging existing photos
  if (draggedIndex.value === null && event.dataTransfer.types.includes('Files')) {
    isDragOver.value = true
  }
}

const handleDragLeave = (event) => {
  event.preventDefault()
  // Only reset if leaving the entire zone
  if (event.currentTarget === event.target) {
    isDragOver.value = false
  }
}

// Photo reordering
const handlePhotoStart = (event, index) => {
  draggedIndex.value = index
  event.dataTransfer.effectAllowed = 'move'
  // Store index in dataTransfer to prevent duplicates
  event.dataTransfer.setData('photoIndex', index.toString())
  event.target.classList.add('dragging')
}

const handlePhotoDragOver = (event, index) => {
  event.preventDefault()
  event.stopPropagation()
  
  // Only show drag over effect when dragging between photos
  if (draggedIndex.value !== null && draggedIndex.value !== index) {
    dragOverIndex.value = index
  }
}

const handlePhotoDrop = (event, targetIndex) => {
  event.preventDefault()
  event.stopPropagation()
  
  const sourceIndex = draggedIndex.value
  
  // Don't process if dropping on the same position or if not dragging a photo
  if (sourceIndex === null || sourceIndex === targetIndex) {
    draggedIndex.value = null
    dragOverIndex.value = null
    return
  }
  
  // Only reorder if we're dragging a photo (not files from outside)
  if (event.dataTransfer.getData('photoIndex')) {
    const newPhotos = [...localPhotos.value]
    const [movedPhoto] = newPhotos.splice(sourceIndex, 1)
    newPhotos.splice(targetIndex, 0, movedPhoto)
    localPhotos.value = newPhotos
    
    // Эмитим изменения после перетаскивания
    const photosToEmit = newPhotos.map(photo => {
      if (photo.file) return photo.file
      return photo.url || photo.preview || photo
    })
    emit('update:photos', photosToEmit)
  }
  
  // Reset drag state
  draggedIndex.value = null
  dragOverIndex.value = null
}

const handleDragEnd = (event) => {
  event.target.classList.remove('dragging')
  draggedIndex.value = null
  dragOverIndex.value = null
}

const handlePhotoDragLeave = (event, index) => {
  if (dragOverIndex.value === index) {
    dragOverIndex.value = null
  }
}

const getPhotoUrl = (photo) => {
  if (!photo) return ''
  if (typeof photo === 'string') return photo
  if (photo.preview) return photo.preview
  if (photo.url) return photo.url
  if (photo.file && photo.file instanceof File) {
    return URL.createObjectURL(photo.file)
  }
  return ''
}

// Логика чекбоксов (скопировано из FeaturesSection - ТОЧНАЯ РАБОЧАЯ ВЕРСИЯ)
const isMediaSettingSelected = (settingId) => {
  return localMediaSettings.value.includes(settingId)
}

const toggleMediaSetting = (settingId) => {
  const index = localMediaSettings.value.indexOf(settingId)
  if (index > -1) {
    localMediaSettings.value.splice(index, 1)
  } else {
    localMediaSettings.value.push(settingId)
  }
  emitMediaSettings()
}

const emitMediaSettings = () => {
  emit('update:media-settings', [...localMediaSettings.value])
}
</script>

<style scoped>
.media-section {
  background: white;
  border-radius: 8px;
  padding: 24px;
}

.form-group-title {
  font-size: 20px;
  font-weight: 600;
  color: #000;
  margin-bottom: 8px;
}

.section-description {
  font-size: 14px;
  color: #666;
  margin-bottom: 24px;
}

.photos-subsection,
.video-subsection {
  margin-bottom: 32px;
}

.field-label {
  font-size: 16px;
  font-weight: 500;
  color: #333;
  margin-bottom: 8px;
}

.field-hint {
  font-size: 13px;
  color: #666;
  margin-bottom: 16px;
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

.hidden {
  display: none;
}

.photo-grid {
  display: flex;
  flex-wrap: wrap;
  gap: 16px;
}

.photo-item {
  position: relative;
  width: 266px;
  height: 200px;
  cursor: move;
  transition: all 0.3s ease;
}

.photo-item.being-dragged {
  opacity: 0.5;
}

.photo-item.drag-over {
  transform: scale(1.05);
}

.photo-item.dragging {
  opacity: 0.5;
}

.photo-wrapper {
  position: relative;
  width: 100%;
  height: 100%;
  border-radius: 8px;
  overflow: hidden;
  background: #f5f5f5;
  border: 1px solid #e0e0e0;
}

.photo-item:first-child .photo-wrapper {
  border: 2px solid #007bff;
}

.photo-preview {
  width: 100%;
  height: 100%;
  object-fit: contain;
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
  background: rgba(255, 255, 255, 0.95);
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
  width: 18px;
  height: 18px;
  color: #666;
}

.rotate-btn:hover svg {
  color: #007bff;
}

.delete-btn:hover svg {
  color: #dc3545;
}

.main-photo-badge {
  position: absolute;
  bottom: 8px;
  left: 8px;
  background: #007bff;
  color: white;
  padding: 6px 12px;
  border-radius: 4px;
  font-size: 12px;
  font-weight: 500;
  z-index: 2;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.add-photo-btn {
  width: 266px;
  height: 200px;
  border: 2px dashed #ddd;
  border-radius: 8px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 8px;
  cursor: pointer;
  transition: all 0.3s ease;
  background: #fafafa;
}

.add-photo-btn:hover {
  border-color: #007bff;
  background: #f0f8ff;
}

.add-photo-btn svg {
  color: #666;
}

.add-photo-btn span {
  font-size: 14px;
  color: #666;
}

.empty-upload {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  min-height: 200px;
  cursor: pointer;
  padding: 32px;
}

.empty-upload svg {
  color: #999;
  margin-bottom: 16px;
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

.drag-content svg {
  color: #007bff;
  margin-bottom: 16px;
}

.drag-text {
  font-size: 18px;
  font-weight: 500;
  color: #007bff;
}



.video-grid {
  display: flex;
  gap: 16px;
  margin-top: 16px;
}

.video-item {
  position: relative;
  width: 400px;
  height: 300px;
  transition: all 0.3s ease;
}

.video-wrapper {
  position: relative;
  width: 100%;
  height: 100%;
  border-radius: 8px;
  overflow: hidden;
  background: #000;
  border: 1px solid #e0e0e0;
}

.video-element {
  width: 100%;
  height: 100%;
  object-fit: contain;
  background: #000;
}

.video-controls {
  position: absolute;
  top: 8px;
  right: 8px;
  display: flex;
  gap: 4px;
  opacity: 0;
  transition: opacity 0.3s ease;
}

.video-item:hover .video-controls {
  opacity: 1;
}

.add-video-btn {
  width: 400px;
  height: 300px;
  border: 2px dashed #ddd;
  border-radius: 8px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 8px;
  cursor: pointer;
  transition: all 0.3s ease;
  background: #fafafa;
}

.add-video-btn:hover {
  border-color: #007bff;
  background: #f0f8ff;
}

.add-video-btn svg {
  color: #666;
}

.add-video-btn span {
  font-size: 14px;
  color: #666;
}


.media-settings {
  margin-top: 24px;
  padding-top: 24px;
  border-top: 1px solid #e0e0e0;
}

.space-y-3 > * + * {
  margin-top: 12px;
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
  .photo-grid,
  .video-grid {
    flex-direction: column;
    gap: 12px;
  }
  
  .photo-item,
  .add-photo-btn {
    width: 100%;
    height: 150px;
  }
  
  .video-item,
  .add-video-btn {
    width: 100%;
    height: 200px;
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
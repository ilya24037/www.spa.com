<template>
  <div class="media-section">
    <h2 class="form-group-title">Фотографии и видео</h2>
    <p class="section-description">
      Добавьте качественные фото и видео ваших работ. Это поможет привлечь больше клиентов.
    </p>
    
    <div class="media-fields">
      <!-- Загрузка фотографий -->
      <div class="photos-upload">
        <h4 class="field-label">Фотографии работ</h4>
        <p class="field-hint">Добавьте до 10 качественных фотографий ваших работ. Рекомендуемый формат 4:3</p>
        
        <div class="upload-area">
          <input
            type="file"
            ref="photoInput"
            multiple
            accept="image/*"
            @change="handlePhotoUpload"
            class="hidden"
          />
          
          <!-- Превью загруженных фото -->
          <div v-if="localPhotos.length > 0" class="photo-grid">
            <div 
              v-for="(photo, index) in localPhotos" 
              :key="photo.id || index"
              class="photo-item"
            >
              <img 
                :src="photo.preview || photo.url" 
                :alt="`Фото ${index + 1}`"
                class="photo-preview"
              />
              <button 
                type="button"
                @click="removePhoto(index)"
                class="photo-remove"
                title="Удалить фото"
              >
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                  <line x1="18" y1="6" x2="6" y2="18" stroke-width="2"/>
                  <line x1="6" y1="6" x2="18" y2="18" stroke-width="2"/>
                </svg>
              </button>
            </div>
            
            <!-- Кнопка добавить еще -->
            <div 
              v-if="localPhotos.length < 10"
              @click="photoInput?.click()"
              class="add-photo-btn"
            >
              <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <line x1="12" y1="5" x2="12" y2="19" stroke-width="2"/>
                <line x1="5" y1="12" x2="19" y2="12" stroke-width="2"/>
              </svg>
              <span>Добавить фото</span>
            </div>
          </div>
          
          <!-- Пустое состояние -->
          <div 
            v-else
            @click="photoInput?.click()"
            class="empty-upload-area"
          >
            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" class="mx-auto mb-2">
              <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
              <circle cx="8.5" cy="8.5" r="1.5"/>
              <polyline points="21 15 16 10 5 21"/>
            </svg>
            <p class="text-gray-600">Нажмите для загрузки фотографий</p>
            <p class="text-sm text-gray-500">или перетащите файлы сюда</p>
          </div>
        </div>
        
        <span v-if="errors?.photos" class="error-message">
          {{ errors.photos[0] }}
        </span>
      </div>

      <!-- Загрузка видео -->
      <div class="video-upload">
        <h4 class="field-label">Видео презентация</h4>
        <p class="field-hint">Добавьте короткое видео (до 50MB) для демонстрации ваших услуг</p>
        
        <div class="upload-area">
          <input
            type="file"
            ref="videoInput"
            accept="video/*"
            @change="handleVideoUpload"
            class="hidden"
          />
          
          <!-- Превью загруженных видео -->
          <div v-if="localVideos.length > 0" class="video-list">
            <div 
              v-for="(video, index) in localVideos" 
              :key="video.id || index"
              class="video-item"
            >
              <div class="video-info">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                  <polygon points="5 3 19 12 5 21 5 3"/>
                </svg>
                <span>{{ video.name || `Видео ${index + 1}` }}</span>
                <span class="video-size">{{ formatFileSize(video.size) }}</span>
              </div>
              <button 
                type="button"
                @click="removeVideo(index)"
                class="btn-remove"
                title="Удалить видео"
              >
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                  <line x1="18" y1="6" x2="6" y2="18" stroke-width="2"/>
                  <line x1="6" y1="6" x2="18" y2="18" stroke-width="2"/>
                </svg>
              </button>
            </div>
          </div>
          
          <!-- Кнопка добавить видео -->
          <button
            v-if="localVideos.length < 3"
            type="button"
            @click="videoInput?.click()"
            class="btn-upload-video"
          >
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor">
              <polygon points="5 3 19 12 5 21 5 3"/>
            </svg>
            {{ localVideos.length > 0 ? 'Добавить еще видео' : 'Загрузить видео' }}
          </button>
        </div>
        
        <span v-if="errors?.videos" class="error-message">
          {{ errors.videos[0] }}
        </span>
      </div>

      <!-- Настройки медиа -->
      <div class="media-settings">
        <h4 class="field-label">Настройки отображения</h4>
        
        <div class="settings-options">
          <label class="checkbox-option">
            <input
              type="checkbox"
              value="show_photos_in_gallery"
              v-model="localMediaSettings"
              class="checkbox-input"
            />
            <span class="checkbox-label">Показывать фото в галерее на странице объявления</span>
          </label>
          
          <label class="checkbox-option">
            <input
              type="checkbox"
              value="allow_download_photos"
              v-model="localMediaSettings"
              class="checkbox-input"
            />
            <span class="checkbox-label">Разрешить клиентам скачивать фотографии</span>
          </label>
          
          <label class="checkbox-option">
            <input
              type="checkbox"
              value="watermark_photos"
              v-model="localMediaSettings"
              class="checkbox-input"
            />
            <span class="checkbox-label">Добавить водяной знак на фотографии</span>
          </label>
        </div>
      </div>

      <!-- Советы по медиа -->
      <div class="media-tips">
        <h4 class="tips-title">💡 Советы по качественным фото и видео</h4>
        <ul class="tips-list">
          <li>Используйте хорошее освещение</li>
          <li>Делайте фото в высоком разрешении</li>
          <li>Показывайте процесс работы</li>
          <li>Добавляйте фото до и после</li>
          <li>Видео должно быть не более 2-3 минут</li>
        </ul>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, watch, nextTick } from 'vue'

// Types
interface MediaFile {
  id?: number
  name?: string
  size?: number
  url?: string
  preview?: string
  file?: File
}

interface Props {
  photos?: MediaFile[]
  videos?: MediaFile[]
  mediaSettings?: string[]
  errors?: Record<string, string[]>
}

// Props
const props = withDefaults(defineProps<Props>(), {
  photos: () => [],
  videos: () => [],
  mediaSettings: () => ['show_photos_in_gallery'],
  errors: () => ({})
})

// Emits
const emit = defineEmits<{
  'update:photos': [value: MediaFile[]]
  'update:videos': [value: MediaFile[]]
  'update:mediaSettings': [value: string[]]
}>()

// Refs
const photoInput = ref<HTMLInputElement>()
const videoInput = ref<HTMLInputElement>()

// Local state
const localPhotos = ref<MediaFile[]>([...props.photos])
const localVideos = ref<MediaFile[]>([...props.videos])
const localMediaSettings = ref<string[]>([...props.mediaSettings])

// Watch for prop changes only
watch(() => props.photos, (newVal) => {
  localPhotos.value = [...newVal]
}, { deep: true })

watch(() => props.videos, (newVal) => {
  localVideos.value = [...newVal]
}, { deep: true })

watch(() => props.mediaSettings, (newVal) => {
  localMediaSettings.value = [...newVal]
}, { deep: true })

// Watch local mediaSettings changes only (for checkboxes)
watch(localMediaSettings, (newVal) => {
  emit('update:mediaSettings', newVal)
}, { deep: true })

// Methods
const handlePhotoUpload = async (event: Event) => {
  const input = event.target as HTMLInputElement
  const files = input.files
  
  if (!files) return
  
  const remainingSlots = 10 - localPhotos.value.length
  const filesToAdd = Math.min(files.length, remainingSlots)
  
  // Собираем все новые фото в временный массив
  const newPhotos: MediaFile[] = []
  const promises: Promise<void>[] = []
  
  Array.from(files).slice(0, filesToAdd).forEach((file, index) => {
    const promise = new Promise<void>((resolve, reject) => {
      const reader = new FileReader()
      
      reader.onload = (e) => {
        const uniqueId = Date.now() + Math.random() * 1000 + index
        newPhotos.push({
          id: uniqueId,
          name: file.name,
          size: file.size,
          preview: e.target?.result as string,
          file: file
        })
        resolve()
      }
      
      reader.onerror = (error) => {
        console.error('Ошибка чтения файла:', error)
        reject(error)
      }
      
      reader.readAsDataURL(file)
    })
    
    promises.push(promise)
  })
  
  // Ждем загрузки всех файлов
  try {
    await Promise.all(promises)
    
    // Обновляем массив и эмитим изменения
    const updatedPhotos = [...localPhotos.value, ...newPhotos]
    localPhotos.value = updatedPhotos
    emit('update:photos', updatedPhotos)
  } catch (error) {
    console.error('Ошибка при загрузке файлов:', error)
  }
  
  // Clear input
  input.value = ''
}

const handleVideoUpload = (event: Event) => {
  const input = event.target as HTMLInputElement
  const files = input.files
  
  if (!files || files.length === 0) return
  
  const file = files[0]
  
  // Check file size (50MB limit)
  if (file.size > 50 * 1024 * 1024) {
    alert('Видео не должно превышать 50MB')
    input.value = ''
    return
  }
  
  const uniqueId = Date.now() + Math.random() * 1000
  const newVideo = {
    id: uniqueId,
    name: file.name,
    size: file.size,
    file: file
  }
  
  // Обновляем массив и эмитим изменения
  const updatedVideos = [...localVideos.value, newVideo]
  localVideos.value = updatedVideos
  emit('update:videos', updatedVideos)
  
  // Clear input
  input.value = ''
}

const removePhoto = (index: number) => {
  // Создаем новый массив без удаленного элемента
  const updatedPhotos = localPhotos.value.filter((_, i) => i !== index)
  localPhotos.value = updatedPhotos
  emit('update:photos', updatedPhotos)
}

const removeVideo = (index: number) => {
  // Создаем новый массив без удаленного элемента
  const updatedVideos = localVideos.value.filter((_, i) => i !== index)
  localVideos.value = updatedVideos
  emit('update:videos', updatedVideos)
}

const formatFileSize = (bytes?: number): string => {
  if (!bytes) return '0 KB'
  
  const sizes = ['Bytes', 'KB', 'MB', 'GB']
  const i = Math.floor(Math.log(bytes) / Math.log(1024))
  
  return Math.round(bytes / Math.pow(1024, i) * 100) / 100 + ' ' + sizes[i]
}
</script>

<style scoped>
.media-section {
  @apply space-y-4;
}

.form-group-title {
  @apply text-xl font-semibold text-gray-900 mb-2;
}

.section-description {
  @apply text-gray-600 mb-4;
}

.media-fields {
  @apply space-y-6;
}

.field-label {
  @apply block text-sm font-medium text-gray-700 mb-2;
}

.field-hint {
  @apply text-sm text-gray-500 mb-3;
}

.error-message {
  @apply text-red-500 text-sm mt-1 block;
}

/* Photos upload */
.photos-upload {
  @apply space-y-3;
}

.upload-area {
  @apply space-y-4;
}

.photo-grid {
  @apply grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4;
}

.photo-item {
  @apply relative;
}

.photo-preview {
  @apply w-full h-32 object-cover rounded-lg border border-gray-200;
}

.photo-remove {
  @apply absolute top-2 right-2 p-1 bg-red-500 text-white rounded transition-opacity;
  opacity: 0;
}

.photo-item:hover .photo-remove {
  opacity: 1;
}

.add-photo-btn {
  @apply flex flex-col items-center justify-center h-32 border-2 border-dashed border-gray-300 rounded-lg cursor-pointer hover:border-gray-400 transition-colors;
}

.empty-upload-area {
  @apply flex flex-col items-center justify-center p-8 border-2 border-dashed border-gray-300 rounded-lg cursor-pointer hover:border-gray-400 transition-colors;
}

/* Video upload */
.video-upload {
  @apply space-y-3;
}

.video-list {
  @apply space-y-2;
}

.video-item {
  @apply flex items-center justify-between p-3 bg-gray-50 rounded-lg;
}

.video-info {
  @apply flex items-center gap-3;
}

.video-size {
  @apply text-sm text-gray-500;
}

.btn-upload-video {
  @apply flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors;
}

.btn-remove {
  @apply p-1 text-red-500 hover:bg-red-50 rounded transition-colors;
}

/* Settings */
.media-settings {
  @apply p-4 bg-gray-50 rounded-lg;
}

.settings-options {
  @apply space-y-2;
}

.checkbox-option {
  @apply flex items-start space-x-2 cursor-pointer;
}

.checkbox-input {
  @apply w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500 mt-0.5;
}

.checkbox-label {
  @apply text-sm text-gray-700;
}

/* Tips */
.media-tips {
  @apply p-4 bg-blue-50 rounded-lg;
}

.tips-title {
  @apply text-sm font-medium text-blue-900 mb-2;
}

.tips-list {
  @apply list-disc list-inside space-y-1 text-sm text-blue-800;
}

.hidden {
  @apply sr-only;
}
</style>
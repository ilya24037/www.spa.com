<template>
  <div class="media-section">
    <h2 class="form-group-title">Фотографии и видео</h2>
    <p class="section-description">
      Добавьте качественные фото и видео ваших работ. Это поможет привлечь больше клиентов.
    </p>
    
    <!-- Фотографии -->
    <div class="photos-subsection">
      <PhotoGallery
        :photos="photos"
        :errors="errors"
        @update:photos="handlePhotosUpdate"
      />
    </div>

    <!-- Видео презентация -->
    <div class="video-subsection">
      <VideoUpload
        :video="video"
        :errors="errors"
        @update:video="handleVideoUpdate"
      />
    </div>

    <!-- Настройки отображения -->
    <div class="media-settings">
      <h4 class="field-label">Настройки отображения</h4>
      <div class="space-y-3">
        <BaseCheckbox
          v-model="showInGallery"
          label="Показывать фото в галерее на странице объявления"
          @update:modelValue="updateSettings"
        />
        <BaseCheckbox
          v-model="allowDownload"
          label="Разрешить клиентам скачивать фотографии"
          @update:modelValue="updateSettings"
        />
        <BaseCheckbox
          v-model="addWatermark"
          label="Добавить водяной знак на фотографии"
          @update:modelValue="updateSettings"
        />
      </div>
    </div>

    <!-- Ошибки -->
    <div v-if="globalError" class="error-message">{{ globalError }}</div>
  </div>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue'
import PhotoGallery from './PhotoGallery/PhotoGallery.vue'
import VideoUpload from './VideoUpload/VideoUpload.vue'
import BaseCheckbox from '@/src/shared/ui/atoms/BaseCheckbox/BaseCheckbox.vue'

interface Props {
  photos?: Array<string | any>
  video?: Array<string | any>
  mediaSettings?: Array<string>
  errors?: Record<string, string>
}

interface Emits {
  (e: 'update:photos', photos: Array<File | string>): void
  (e: 'update:video', video: Array<File | string>): void
  (e: 'update:mediaSettings', settings: Array<string>): void
}

const props = withDefaults(defineProps<Props>(), {
  photos: () => [],
  video: () => [],
  mediaSettings: () => ['show_photos_in_gallery'],
  errors: () => ({})
})

const emit = defineEmits<Emits>()

// State
const showInGallery = ref(true)
const allowDownload = ref(false)
const addWatermark = ref(true)
const globalError = ref('')

// Initialize media settings from props
watch(() => props.mediaSettings, (settings) => {
  showInGallery.value = settings.includes('show_photos_in_gallery')
  allowDownload.value = settings.includes('allow_download_photos')
  addWatermark.value = settings.includes('watermark_photos')
}, { immediate: true })

// Event handlers
const handlePhotosUpdate = (photos: Array<File | string>) => {
  emit('update:photos', photos)
  globalError.value = '' // Clear global error when photos are updated
}

const handleVideoUpdate = (video: Array<File | string>) => {
  emit('update:video', video)
  globalError.value = '' // Clear global error when video is updated
}

const updateSettings = () => {
  const settings: string[] = []
  if (showInGallery.value) settings.push('show_photos_in_gallery')
  if (allowDownload.value) settings.push('allow_download_photos')
  if (addWatermark.value) settings.push('watermark_photos')
  emit('update:mediaSettings', settings)
}

// Watch for errors
watch(() => props.errors, (errors) => {
  // Set global error if there are validation errors
  if (errors.photos || errors.video || errors.mediaSettings) {
    globalError.value = errors.photos || errors.video || errors.mediaSettings || 'Ошибка в медиа файлах'
  } else {
    globalError.value = ''
  }
}, { immediate: true })
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

.media-settings {
  margin-top: 24px;
  padding-top: 24px;
  border-top: 1px solid #e0e0e0;
}

.field-label {
  font-size: 16px;
  font-weight: 500;
  color: #333;
  margin-bottom: 8px;
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
  .media-section {
    padding: 16px;
  }
  
  .photos-subsection,
  .video-subsection {
    margin-bottom: 24px;
  }
}
</style>
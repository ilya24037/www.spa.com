<template>
  <div class="media-modules">
    <!-- Фотографии -->
    <FormSection
      title="Фотографии"
      hint="Добавьте до 10 фотографий. Первая фотография будет главной."
      :errors="errors"
      :error-keys="['photos']"
    >
      <PhotoUploader 
        :model-value="photos"
        @update:model-value="updatePhotos"
        :max-files="10"
        :max-file-size="5242880"
        :uploading="uploading"
        :upload-progress="uploadProgress"
        @error="handlePhotoError"
      />
    </FormSection>

    <!-- Видео -->
    <FormSection
      title="Видео"
      hint="Добавьте одно видео для привлечения внимания (максимум 50 МБ)"
      :errors="errors"
      :error-keys="['video']"
    >
      <VideoUploader 
        :model-value="video"
        @update:model-value="updateVideo"
        :max-file-size="50 * 1024 * 1024"
        :uploading="uploadingVideo"
        :upload-progress="videoUploadProgress"
        @error="handleVideoError"
      />
    </FormSection>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import FormSection from '@/Components/UI/Forms/FormSection.vue'
import PhotoUploader from '@/Components/Features/PhotoUploader/index.vue'
import VideoUploader from '@/Components/Features/PhotoUploader/VideoUploader.vue'
import { useAdFormStore } from '../../stores/adFormStore'

// Используем централизованный store (как в Clients и Parameters)
const store = useAdFormStore()

// Props только для UI состояния и ошибок
const props = defineProps({
  uploading: {
    type: Boolean,
    default: false
  },
  uploadProgress: {
    type: Number,
    default: 0
  },
  uploadingVideo: {
    type: Boolean,
    default: false
  },
  videoUploadProgress: {
    type: Number,
    default: 0
  },
  errors: {
    type: Object,
    default: () => ({})
  }
})

// Emit только для ошибок
const emit = defineEmits(['photo-error', 'video-error'])

// Читаем данные ТОЛЬКО из store (как в Clients/Parameters)
const photos = computed(() => {
  console.log('MediaModule photos from store:', store.formData.photos)
  return store.formData.photos || []
})

const video = computed(() => {
  console.log('MediaModule video from store:', store.formData.video)
  return store.formData.video || null
})

// Обновляем ТОЛЬКО store (без emit, как в Clients/Parameters)
const updatePhotos = (newPhotos) => {
  console.log('MediaModule updatePhotos:', newPhotos)
  store.updateField('photos', newPhotos)
}

const updateVideo = (newVideo) => {
  console.log('MediaModule updateVideo:', newVideo)
  store.updateField('video', newVideo)
}

// Обработчики ошибок
const handlePhotoError = (error) => {
  console.error('Photo error:', error)
  emit('photo-error', error)
}

const handleVideoError = (error) => {
  console.error('Video error:', error)
  emit('video-error', error)
}
</script>

<style scoped>
.media-modules {
  display: flex;
  flex-direction: column;
  gap: 24px;
}
</style>
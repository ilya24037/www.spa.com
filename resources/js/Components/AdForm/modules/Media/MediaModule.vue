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
        @error="$emit('photo-error', $event)"
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
        @error="$emit('video-error', $event)"
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

// AVITO-STYLE: Используем централизованный store
const store = useAdFormStore()

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

const emit = defineEmits([
  'photo-error', 
  'video-error'
])

// Читаем данные ТОЛЬКО из store (как на Avito)
const photos = computed(() => {
  const storePhotos = store.formData.photos
  return Array.isArray(storePhotos) ? storePhotos : []
})
const video = computed(() => store.formData.video || null)

// Методы обновляют ТОЛЬКО store (как на Avito/Ozon)
const updatePhotos = (newPhotos) => {
  console.log('updatePhotos called:', newPhotos)
  store.updateField('photos', newPhotos)
}

const updateVideo = (newVideo) => {
  console.log('updateVideo called:', newVideo)
  store.updateField('video', newVideo)
}
</script>

<style scoped>
.media-modules {
  display: flex;
  flex-direction: column;
  gap: 24px;
}
</style>
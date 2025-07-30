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
        v-model="localPhotos"
        :max-files="10"
        :max-file-size="5242880"
        :uploading="uploading"
        :upload-progress="uploadProgress"
        @error="$emit('photo-error', $event)"
        @update:model-value="updatePhotos"
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
        v-model="localVideo"
        :max-file-size="50 * 1024 * 1024"
        :uploading="uploadingVideo"
        :upload-progress="videoUploadProgress"
        @error="$emit('video-error', $event)"
        @update:model-value="updateVideo"
      />
    </FormSection>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue'
import FormSection from '@/Components/UI/Forms/FormSection.vue'
import PhotoUploader from '@/Components/Features/PhotoUploader/index.vue'
import VideoUploader from '@/Components/Features/PhotoUploader/VideoUploader.vue'

const props = defineProps({
  photos: {
    type: Array,
    default: () => []
  },
  video: {
    type: Object,
    default: null
  },
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
  'update:photos', 
  'update:video', 
  'photo-error', 
  'video-error'
])

// Локальное состояние
const localPhotos = ref([...props.photos])
const localVideo = ref(props.video)

// Отслеживание изменений пропсов
watch(() => props.photos, (newValue) => {
  localPhotos.value = [...newValue]
}, { deep: true })

watch(() => props.video, (newValue) => {
  localVideo.value = newValue
})

// Методы обновления
const updatePhotos = (photos) => {
  localPhotos.value = photos
  emit('update:photos', photos)
}

const updateVideo = (video) => {
  localVideo.value = video
  emit('update:video', video)
}
</script>

<style scoped>
.media-modules {
  display: flex;
  flex-direction: column;
  gap: 24px;
}
</style>
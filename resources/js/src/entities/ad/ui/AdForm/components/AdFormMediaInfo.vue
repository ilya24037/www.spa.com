<!-- resources/js/src/entities/ad/ui/AdForm/components/AdFormMediaInfo.vue -->
<template>
  <div :class="SECTION_CLASSES">
    <h3 :class="TITLE_CLASSES">Фотографии и видео</h3>
    <p :class="SUBTITLE_CLASSES">Добавьте фотографии и видео для привлечения клиентов</p>
    
    <div :class="FIELDS_CONTAINER_CLASSES">
      <!-- Медиа загрузка -->
      <AdFormMediaUpload 
        :uploading="uploading"
        :upload-progress="uploadProgress"
        :uploading-video="uploadingVideo"
        :video-upload-progress="videoUploadProgress"
        :errors="errors"
        @photo-error="$emit('photo-error', $event)"
        @video-error="$emit('video-error', $event)"
      />
    </div>
  </div>
</template>

<script setup>
// Модули медиа
import AdFormMediaUpload from '../modules/Media/AdFormMediaUpload.vue'

// 🎯 Стили согласно дизайн-системе
const SECTION_CLASSES = 'space-y-6'
const TITLE_CLASSES = 'text-xl font-semibold text-gray-900'
const SUBTITLE_CLASSES = 'text-sm text-gray-600'
const FIELDS_CONTAINER_CLASSES = 'space-y-6'

defineProps({
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

defineEmits(['photo-error', 'video-error'])
</script>
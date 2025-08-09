<template>
  <div class="media-uploader">
    <!-- Загрузка фотографий -->
    <PhotoUploader 
      :master-id="masterId"
      @uploaded="$emit('photos-uploaded', $event)"
      @error="$emit('error', $event)"
    />

    <!-- Загрузка видео -->
    <VideoUploader 
      :master-id="masterId"
      @uploaded="$emit('video-uploaded', $event)"
      @error="$emit('error', $event)"
    />

    <!-- Предпросмотр загруженных файлов -->
    <MediaPreview 
      :photos="photos"
      :videos="videos"
      @delete="$emit('delete', $event)"
    />
  </div>
</template>

<script setup>
import PhotoUploader from './PhotoUploader.vue'
import VideoUploader from './VideoUploader.vue'
import MediaPreview from './MediaPreview.vue'

// Пропсы
defineProps({
  masterId: {
    type: Number,
    required: true
  },
  photos: {
    type: Array,
    default: () => []
  },
  videos: {
    type: Array,
    default: () => []
  }
})

// События
defineEmits(['photos-uploaded', 'video-uploaded', 'delete', 'error'])
</script> 
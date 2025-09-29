<template>
  <div class="media-preview-grid">
    <!-- Фотографии -->
    <div v-if="photos.length > 0" class="photos-section">
      <h4 class="text-sm font-medium text-gray-900 mb-3">
        Загруженные фотографии ({{ photos.length }})
      </h4>
      <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
        <div
          v-for="(photo, index) in photos"
          :key="photo.id || index"
          class="media-item photo-item"
        >
          <div class="relative group">
            <img
              :src="getPhotoUrl(photo)"
              :alt="`Фото ${index + 1}`"
              class="w-full h-32 object-cover rounded-lg"
              loading="lazy"
            />
            
            <!-- Overlay с действиями -->
            <div class="media-overlay">
              <div class="overlay-actions">
                <button
                  type="button"
                  class="action-btn view-btn"
                  @click="viewPhoto(photo, index)"
                  title="Просмотр"
                >
                  <svg class="w-4 h-4"
                       fill="none"
                       stroke="currentColor"
                       viewBox="0 0 24 24">
                    <path stroke-linecap="round"
                          stroke-linejoin="round"
                          stroke-width="2"
                          d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    <path stroke-linecap="round"
                          stroke-linejoin="round"
                          stroke-width="2"
                          d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                  </svg>
                </button>
                
                <button
                  v-if="!readonly"
                  type="button"
                  class="action-btn delete-btn"
                  @click="removePhoto(index)"
                  title="Удалить"
                >
                  <svg class="w-4 h-4"
                       fill="none"
                       stroke="currentColor"
                       viewBox="0 0 24 24">
                    <path stroke-linecap="round"
                          stroke-linejoin="round"
                          stroke-width="2"
                          d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                  </svg>
                </button>
              </div>
            </div>
            
            <!-- Индикатор загрузки -->
            <div v-if="photo.uploading" class="upload-progress">
              <div class="progress-bar">
                <div 
                  class="progress-fill"
                  :style="{ width: `${photo.progress || 0}%` }"
                />
              </div>
              <span class="progress-text">{{ photo.progress || 0 }}%</span>
            </div>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Видео -->
    <div v-if="videos.length > 0" class="videos-section mt-6">
      <h4 class="text-sm font-medium text-gray-900 mb-3">
        Загруженные видео ({{ videos.length }})
      </h4>
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <div
          v-for="(video, index) in videos"
          :key="video.id || index"
          class="media-item video-item"
        >
          <div class="relative group">
            <video
              :src="getVideoUrl(video)"
              class="w-full h-48 object-cover rounded-lg"
              preload="metadata"
              muted
            />
            
            <!-- Overlay с действиями -->
            <div class="media-overlay">
              <div class="overlay-actions">
                <button
                  type="button"
                  class="action-btn view-btn"
                  @click="playVideo(video, index)"
                  title="Воспроизвести"
                >
                  <svg class="w-4 h-4"
                       fill="none"
                       stroke="currentColor"
                       viewBox="0 0 24 24">
                    <path stroke-linecap="round"
                          stroke-linejoin="round"
                          stroke-width="2"
                          d="M14.828 14.828a4 4 0 01-5.656 0M9 10h1.586a1 1 0 01.707.293l2.414 2.414a1 1 0 00.707.293H15M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                  </svg>
                </button>
                
                <button
                  v-if="!readonly"
                  type="button"
                  class="action-btn delete-btn"
                  @click="removeVideo(index)"
                  title="Удалить"
                >
                  <svg class="w-4 h-4"
                       fill="none"
                       stroke="currentColor"
                       viewBox="0 0 24 24">
                    <path stroke-linecap="round"
                          stroke-linejoin="round"
                          stroke-width="2"
                          d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                  </svg>
                </button>
              </div>
            </div>
            
            <!-- Индикатор загрузки -->
            <div v-if="video.uploading" class="upload-progress">
              <div class="progress-bar">
                <div 
                  class="progress-fill"
                  :style="{ width: `${video.progress || 0}%` }"
                />
              </div>
              <span class="progress-text">{{ video.progress || 0 }}%</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
// Props
interface MediaItem {
  id?: string | number
  url?: string
  file?: File
  uploading?: boolean
  progress?: number
}

interface Props {
  photos: MediaItem[]
  videos: MediaItem[]
  readonly?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  photos: () => [],
  videos: () => [],
  readonly: false
})

// Emits
const emit = defineEmits<{
  'remove-photo': [index: number]
  'remove-video': [index: number]
  'view-photo': [photo: MediaItem, index: number]
  'play-video': [video: MediaItem, index: number]
}>()

// Methods
const getPhotoUrl = (photo: MediaItem): string => {
  if (photo.url) return photo.url
  if (photo.file) return URL.createObjectURL(photo.file)
  return ''
}

const getVideoUrl = (video: MediaItem): string => {
  if (video.url) return video.url
  if (video.file) return URL.createObjectURL(video.file)
  return ''
}

const removePhoto = (index: number) => {
  emit('remove-photo', index)
}

const removeVideo = (index: number) => {
  emit('remove-video', index)
}

const viewPhoto = (photo: MediaItem, index: number) => {
  emit('view-photo', photo, index)
}

const playVideo = (video: MediaItem, index: number) => {
  emit('play-video', video, index)
}
</script>

<style scoped>
.media-preview-grid {
  @apply space-y-6;
}

.media-item {
  @apply relative;
}

.media-overlay {
  @apply absolute inset-0 bg-black bg-opacity-50 opacity-0 group-hover:opacity-100 transition-opacity duration-200 rounded-lg flex items-center justify-center;
}

.overlay-actions {
  @apply flex space-x-2;
}

.action-btn {
  @apply p-2 rounded-full transition-colors duration-200;
}

.view-btn {
  @apply bg-blue-600 hover:bg-blue-700 text-white;
}

.delete-btn {
  @apply bg-red-600 hover:bg-red-700 text-white;
}

.upload-progress {
  @apply absolute inset-x-0 bottom-0 p-2 bg-black bg-opacity-75 rounded-b-lg;
}

.progress-bar {
  @apply w-full bg-gray-600 rounded-full h-2 mb-1;
}

.progress-fill {
  @apply bg-blue-500 h-2 rounded-full transition-all duration-300;
}

.progress-text {
  @apply text-white text-xs text-center;
}
</style>

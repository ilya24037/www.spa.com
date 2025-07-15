<template>
  <div class="media-gallery">
    <!-- Главное изображение -->
    <div class="main-image">
      <img 
        :src="currentMedia?.url || '/images/no-photo.jpg'"
        :alt="currentMedia?.alt || 'Фото мастера'"
        class="w-full h-96 object-cover rounded-lg"
        @click="openLightbox"
      >
      
      <!-- Кнопки управления -->
      <div class="image-controls">
        <button 
          @click="previousMedia"
          :disabled="!hasPrevious"
          class="control-btn"
        >
          <ChevronLeftIcon class="w-6 h-6" />
        </button>
        
        <button 
          @click="nextMedia"
          :disabled="!hasNext"
          class="control-btn"
        >
          <ChevronRightIcon class="w-6 h-6" />
        </button>
      </div>
    </div>

    <!-- Миниатюры -->
    <GalleryThumbnails 
      :media="allMedia"
      :current-index="currentIndex"
      @select="selectMedia"
    />

    <!-- Полноэкранный просмотр -->
    <GalleryLightbox 
      v-if="showLightbox"
      :media="allMedia"
      :current-index="currentIndex"
      @close="closeLightbox"
      @next="nextMedia"
      @previous="previousMedia"
    />
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { ChevronLeftIcon, ChevronRightIcon } from '@heroicons/vue/24/outline'
import GalleryThumbnails from './GalleryThumbnails.vue'
import GalleryLightbox from './GalleryLightbox.vue'

const props = defineProps({
  photos: {
    type: Array,
    default: () => []
  },
  videos: {
    type: Array,
    default: () => []
  }
})

// Объединяем фото и видео в один массив
const allMedia = computed(() => {
  const media = []
  
  // Добавляем фото
  props.photos.forEach(photo => {
    media.push({
      ...photo,
      type: 'photo',
      url: photo.thumb_url || photo.url
    })
  })
  
  // Добавляем видео
  props.videos.forEach(video => {
    media.push({
      ...video,
      type: 'video',
      url: video.thumb_url || video.url
    })
  })
  
  return media
})

const currentIndex = ref(0)
const showLightbox = ref(false)

// Текущее медиа
const currentMedia = computed(() => 
  allMedia.value[currentIndex.value] || null
)

// Навигация
const hasPrevious = computed(() => currentIndex.value > 0)
const hasNext = computed(() => currentIndex.value < allMedia.value.length - 1)

const selectMedia = (index) => {
  currentIndex.value = index
}

const nextMedia = () => {
  if (hasNext.value) {
    currentIndex.value++
  }
}

const previousMedia = () => {
  if (hasPrevious.value) {
    currentIndex.value--
  }
}

const openLightbox = () => {
  if (allMedia.value.length > 0) {
    showLightbox.value = true
  }
}

const closeLightbox = () => {
  showLightbox.value = false
}
</script>

<style scoped>
.media-gallery {
  @apply space-y-4;
}

.main-image {
  @apply relative;
}

.image-controls {
  @apply absolute inset-0 flex items-center justify-between p-4;
}

.control-btn {
  @apply bg-black bg-opacity-50 text-white p-2 rounded-full hover:bg-opacity-70 transition-all disabled:opacity-50 disabled:cursor-not-allowed;
}
</style> 
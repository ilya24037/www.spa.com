<template>
  <div class="lightbox-overlay" @click="$emit('close')">
    <div class="lightbox-content" @click.stop>
      <!-- Кнопка закрытия -->
      <button @click="$emit('close')" class="close-btn">
        <XMarkIcon class="w-8 h-8" />
      </button>
      
      <!-- Главное изображение -->
      <div class="main-image-container">
        <img 
          :src="currentPhoto?.url || '/images/no-photo.jpg'"
          :alt="`${masterName} - фото ${currentIndex + 1}`"
          class="main-image"
        >
      </div>
      
      <!-- Навигация -->
      <button 
        v-if="hasPrevious"
        @click="$emit('previous')"
        class="nav-btn nav-btn-left"
      >
        <ChevronLeftIcon class="w-8 h-8" />
      </button>
      
      <button 
        v-if="hasNext"
        @click="$emit('next')"
        class="nav-btn nav-btn-right"
      >
        <ChevronRightIcon class="w-8 h-8" />
      </button>
      
      <!-- Счетчик -->
      <div class="photo-counter">
        {{ currentIndex + 1 }} из {{ photos.length }}
      </div>
      
      <!-- Миниатюры внизу -->
      <div class="thumbnails-bar">
        <button
          v-for="(photo, index) in photos"
          :key="photo.id"
          @click="$emit('select', index)"
          :class="[
            'thumbnail-btn',
            { 'active': currentIndex === index }
          ]"
        >
          <img 
            :src="photo.thumb_url || photo.url"
            :alt="`${masterName} - фото ${index + 1}`"
            class="thumbnail-img"
          >
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { 
  XMarkIcon, 
  ChevronLeftIcon, 
  ChevronRightIcon 
} from '@heroicons/vue/24/outline'

const props = defineProps({
  photos: {
    type: Array,
    default: () => []
  },
  currentIndex: {
    type: Number,
    default: 0
  },
  masterName: {
    type: String,
    required: true
  }
})

defineEmits(['close', 'next', 'previous', 'select'])

const currentPhoto = computed(() => 
  props.photos[props.currentIndex] || null
)

const hasPrevious = computed(() => props.currentIndex > 0)
const hasNext = computed(() => props.currentIndex < props.photos.length - 1)
</script>

<style scoped>
.lightbox-overlay {
  @apply fixed inset-0 bg-black bg-opacity-90 flex items-center justify-center z-50;
}

.lightbox-content {
  @apply relative w-full h-full flex flex-col;
}

.close-btn {
  @apply absolute top-4 right-4 z-10 bg-black bg-opacity-50 text-white p-2 rounded-full hover:bg-opacity-70 transition-all;
}

.main-image-container {
  @apply flex-1 flex items-center justify-center p-4;
}

.main-image {
  @apply max-w-full max-h-full object-contain;
}

.nav-btn {
  @apply absolute top-1/2 -translate-y-1/2 bg-black bg-opacity-50 text-white p-3 rounded-full hover:bg-opacity-70 transition-all z-10;
}

.nav-btn-left {
  @apply left-4;
}

.nav-btn-right {
  @apply right-4;
}

.photo-counter {
  @apply absolute top-4 left-4 bg-black bg-opacity-50 text-white px-3 py-1 rounded-full text-sm;
}

.thumbnails-bar {
  @apply absolute bottom-4 left-1/2 -translate-x-1/2 flex gap-2 bg-black bg-opacity-50 p-2 rounded-lg;
}

.thumbnail-btn {
  @apply w-16 h-16 rounded-lg overflow-hidden border-2 border-transparent hover:border-white transition-colors;
}

.thumbnail-btn.active {
  @apply border-white;
}

.thumbnail-img {
  @apply w-full h-full object-cover;
}

/* Мобильная адаптация */
@media (max-width: 768px) {
  .nav-btn {
    @apply p-2;
  }
  
  .nav-btn-left {
    @apply left-2;
  }
  
  .nav-btn-right {
    @apply right-2;
  }
  
  .thumbnails-bar {
    @apply bottom-2 gap-1;
  }
  
  .thumbnail-btn {
    @apply w-12 h-12;
  }
}
</style> 
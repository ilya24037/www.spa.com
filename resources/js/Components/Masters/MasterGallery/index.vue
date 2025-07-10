<!-- resources/js/Components/Masters/MasterGallery/index.vue -->
<template>
  <div class="master-gallery">
    <div class="relative">
      <!-- Основное изображение -->
      <GalleryImage 
        :src="currentPhoto"
        :alt="master.name"
        :is-premium="master.is_premium"
        :is-verified="master.is_verified"
        @click="openFullscreen"
        @error="handleImageError"
      />
      
      <!-- Кнопки навигации (для множества фото) -->
      <div v-if="photos.length > 1" class="absolute inset-0 flex items-center justify-between px-4 pointer-events-none">
        <button
          v-show="currentIndex > 0"
          @click="previousPhoto"
          class="pointer-events-auto bg-black/50 hover:bg-black/70 text-white p-2 rounded-full transition-colors"
          aria-label="Предыдущее фото"
        >
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
          </svg>
        </button>
        
        <button
          v-show="currentIndex < photos.length - 1"
          @click="nextPhoto"
          class="pointer-events-auto bg-black/50 hover:bg-black/70 text-white p-2 rounded-full transition-colors"
          aria-label="Следующее фото"
        >
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
          </svg>
        </button>
      </div>
      
      <!-- Индикатор текущего фото -->
      <div v-if="photos.length > 1" class="absolute bottom-4 left-1/2 -translate-x-1/2 bg-black/50 text-white px-3 py-1 rounded-full text-sm">
        {{ currentIndex + 1 }} / {{ photos.length }}
      </div>
    </div>
    
    <!-- Миниатюры -->
    <ThumbnailList
      v-if="photos.length > 1"
      :photos="photos"
      :current-index="currentIndex"
      @select="currentIndex = $event"
    />
    
    <!-- Модальное окно для полноэкранного просмотра -->
    <Teleport to="body">
      <ImageGalleryModal
        v-if="showFullscreen"
        v-model="showFullscreen"
        :images="photos"
        :initial-index="currentIndex"
        @close="showFullscreen = false"
      />
    </Teleport>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import GalleryImage from './GalleryImage.vue'
import ThumbnailList from './ThumbnailList.vue'
import ImageGalleryModal from '@/Components/Common/ImageGalleryModal.vue'
import { useGallery } from './useGallery'

const props = defineProps({
  master: {
    type: Object,
    required: true
  }
})

// Composable для логики галереи
const { 
  photos, 
  currentIndex, 
  currentPhoto,
  nextPhoto,
  previousPhoto,
  setPhoto
} = useGallery(props.master)

// Локальное состояние
const showFullscreen = ref(false)

// Методы
const openFullscreen = () => {
  showFullscreen.value = true
}

const handleImageError = (event) => {
  event.target.src = '/images/placeholder-master.jpg'
}

// Предзагрузка соседних изображений для плавности
watch(currentIndex, (newIndex) => {
  // Предзагружаем следующее и предыдущее изображение
  if (photos.value[newIndex + 1]) {
    const img = new Image()
    img.src = photos.value[newIndex + 1]
  }
  if (photos.value[newIndex - 1]) {
    const img = new Image()
    img.src = photos.value[newIndex - 1]
  }
})
</script>

<style scoped>
/* Плавные переходы для галереи */
.master-gallery {
  @apply bg-gray-50 rounded-lg overflow-hidden;
}
</style>
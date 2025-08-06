<!-- resources/js/src/features/gallery/ui/PhotoGallery/PhotoViewer.vue -->
<template>
  <Teleport to="body">
    <div 
      v-if="isOpen" 
      class="fixed inset-0 z-50 bg-black bg-opacity-90 flex items-center justify-center"
      @click="close"
      @keyup.esc="close"
      tabindex="0"
    >
      <!-- Overlay -->
      <div class="absolute inset-0 bg-black opacity-90"></div>
      
      <!-- Viewer Content -->
      <div class="relative z-10 w-full h-full flex items-center justify-center p-4">
        <!-- Close Button -->
        <button
          @click="close"
          class="absolute top-4 right-4 text-white hover:text-gray-300 z-20"
          aria-label="Закрыть просмотр"
        >
          <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>

        <!-- Navigation Arrows -->
        <button
          v-if="hasPrev"
          @click.stop="prevImage"
          class="absolute left-4 text-white hover:text-gray-300 z-20"
          aria-label="Предыдущее изображение"
        >
          <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
          </svg>
        </button>

        <button
          v-if="hasNext"
          @click.stop="nextImage"
          class="absolute right-4 text-white hover:text-gray-300 z-20"
          aria-label="Следующее изображение"
        >
          <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
          </svg>
        </button>

        <!-- Current Image -->
        <div class="max-w-full max-h-full flex items-center justify-center">
          <img
            v-if="currentImage"
            :src="currentImage.url"
            :alt="currentImage.alt || 'Изображение'"
            class="max-w-full max-h-full object-contain"
            @click.stop
          >
        </div>

        <!-- Image Counter -->
        <div class="absolute bottom-4 left-1/2 transform -translate-x-1/2 text-white text-sm">
          {{ currentIndex + 1 }} из {{ totalImages }}
        </div>

        <!-- Image Info -->
        <div v-if="currentImage?.description" class="absolute bottom-12 left-1/2 transform -translate-x-1/2 text-white text-center max-w-md">
          {{ currentImage.description }}
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup lang="ts">
import { computed, onMounted, onUnmounted } from 'vue'
import { useGalleryStore } from '@/src/features/gallery/model/gallery.store'

// Store
const galleryStore = useGalleryStore()

// Computed
const isOpen = computed(() => galleryStore.isViewerOpen)
const currentImage = computed(() => galleryStore.currentImage)
const currentIndex = computed(() => galleryStore.currentIndex)
const totalImages = computed(() => galleryStore.images.length)
const hasNext = computed(() => galleryStore.hasNext)
const hasPrev = computed(() => galleryStore.hasPrev)

// Methods
const close = () => {
  galleryStore.closeViewer()
}

const nextImage = () => {
  galleryStore.nextImage()
}

const prevImage = () => {
  galleryStore.prevImage()
}

// Keyboard navigation
const handleKeydown = (event: KeyboardEvent) => {
  if (!isOpen.value) return
  
  switch (event.key) {
    case 'Escape':
      close()
      break
    case 'ArrowLeft':
      prevImage()
      break
    case 'ArrowRight':
      nextImage()
      break
  }
}

onMounted(() => {
  document.addEventListener('keydown', handleKeydown)
})

onUnmounted(() => {
  document.removeEventListener('keydown', handleKeydown)
})
</script>
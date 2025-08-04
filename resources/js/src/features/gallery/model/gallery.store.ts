import { defineStore } from 'pinia'
import { ref, computed } from 'vue'

export interface GalleryImage {
  id: string
  url: string
  thumbnail?: string
  alt?: string
  caption?: string
  type: 'photo' | 'video'
}

export const useGalleryStore = defineStore('gallery', () => {
  // State
  const images = ref<GalleryImage[]>([])
  const currentIndex = ref(0)
  const isViewerOpen = ref(false)
  const isLoading = ref(false)
  
  // Getters
  const currentImage = computed(() => images.value[currentIndex.value])
  const hasNext = computed(() => currentIndex.value < images.value.length - 1)
  const hasPrev = computed(() => currentIndex.value > 0)
  
  // Actions
  const openViewer = (index: number = 0) => {
    currentIndex.value = index
    isViewerOpen.value = true
    document.body.style.overflow = 'hidden'
  }
  
  const closeViewer = () => {
    isViewerOpen.value = false
    document.body.style.overflow = ''
  }
  
  const nextImage = () => {
    if (hasNext.value) {
      currentIndex.value++
    }
  }
  
  const prevImage = () => {
    if (hasPrev.value) {
      currentIndex.value--
    }
  }
  
  const setImages = (newImages: GalleryImage[]) => {
    images.value = newImages
  }
  
  return {
    // State
    images,
    currentIndex,
    isViewerOpen,
    isLoading,
    
    // Getters
    currentImage,
    hasNext,
    hasPrev,
    
    // Actions
    openViewer,
    closeViewer,
    nextImage,
    prevImage,
    setImages
  }
})
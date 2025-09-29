<!-- ItemImage - изображение объявления с прокруткой на hover -->
<template>
  <div class="item-image-section">
    <div class="item-image-container">
      <div 
        class="item-image-wrapper ozon-style"
        @mouseenter="startImagePreview"
        @mouseleave="stopImagePreview"
        @mousemove="handleMouseMove"
      >
        <img 
          :src="currentImageUrl"
          :alt="item.title || 'Изображение'"
          class="item-image"
          @error="handleImageError"
        >
      </div>
    </div>
    
    <!-- Индикаторы слайдера ПОД фото как на Ozon -->
    <div v-if="processedPhotos.length > 1" class="slider-indicators ozon-indicators">
      <div 
        v-for="n in Math.min(processedPhotos.length, 6)" 
        :key="n"
        class="slider-dot ozon-dot"
        :class="{ 'active': n === currentPhotoIndex + 1 }"
      />
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'
import type { AdItem, ItemPhoto } from '../ItemCard.types'

interface Props {
  item: AdItem
  itemUrl: string
}

const props = defineProps<Props>()

// Состояние для прокрутки фото
const isHovering = ref(false)
const currentPhotoIndex = ref(0)
let hoverTimer: ReturnType<typeof setInterval> | null = null

// Обработка фотографий
const processedPhotos = computed(() => {
  const photos: string[] = []
  
  if (props.item.photos && props.item.photos.length) {
    props.item.photos.forEach((photo) => {
      let photoUrl: string | null = null
      
      if (typeof photo === 'string') {
        photoUrl = photo
      } else if (photo && typeof photo === 'object') {
        photoUrl = photo.preview || photo.url || photo.src || photo.path || null
      }
      
      if (photoUrl) {
        if (!photoUrl.startsWith('http') && !photoUrl.startsWith('/')) {
          photoUrl = '/storage/' + photoUrl
        }
        photos.push(photoUrl)
      }
    })
  }
  
  // Если нет фото в массиве, проверяем поле photo
  if (photos.length === 0 && props.item.photo) {
    let photoUrl = props.item.photo
    if (!photoUrl.startsWith('http') && !photoUrl.startsWith('/')) {
      photoUrl = '/storage/' + photoUrl
    }
    photos.push(photoUrl)
  }
  
  // Если вообще нет фото, используем заглушку
  if (photos.length === 0) {
    photos.push('/images/no-photo.jpg')
  }
  
  return photos
})

// Текущее изображение
const currentImageUrl = computed(() => {
  return processedPhotos.value[currentPhotoIndex.value] || '/images/no-photo.jpg'
})

// Методы управления слайдером
const startImagePreview = () => {
  if (processedPhotos.value.length <= 1) return
  
  isHovering.value = true
  currentPhotoIndex.value = 0
  
  hoverTimer = setInterval(() => {
    if (isHovering.value) {
      currentPhotoIndex.value = (currentPhotoIndex.value + 1) % processedPhotos.value.length
    }
  }, 800)
}

const stopImagePreview = () => {
  isHovering.value = false
  currentPhotoIndex.value = 0
  
  if (hoverTimer) {
    clearInterval(hoverTimer)
    hoverTimer = null
  }
}

const handleMouseMove = (event: MouseEvent) => {
  if (processedPhotos.value.length <= 1) return
  
  const element = event.currentTarget as HTMLElement
  const rect = element.getBoundingClientRect()
  const x = event.clientX - rect.left
  const width = rect.width
  const segmentWidth = width / processedPhotos.value.length
  const index = Math.floor(x / segmentWidth)
  
  if (index !== currentPhotoIndex.value && index < processedPhotos.value.length) {
    currentPhotoIndex.value = index
  }
}

const handleImageError = (event: Event) => {
  const img = event.target as HTMLImageElement
  img.src = '/images/no-photo.jpg'
}
</script>

<style scoped>
.item-image-section {
  position: relative;
  margin-bottom: 12px;
}

.item-image-container {
  position: relative;
  width: 100%;
  padding-bottom: 100%;
  overflow: hidden;
  border-radius: 8px;
  background: #f5f5f5;
}

.item-image-wrapper {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
}

.item-image {
  width: 100%;
  height: 100%;
  object-fit: cover;
  transition: transform 0.3s ease;
}

.item-image-wrapper:hover .item-image {
  transform: scale(1.05);
}

/* Индикаторы Ozon стиль */
.ozon-indicators {
  display: flex;
  justify-content: center;
  gap: 4px;
  margin-top: 8px;
  padding: 0 8px;
}

.ozon-dot {
  width: 4px;
  height: 4px;
  border-radius: 50%;
  background: #d4d4d4;
  transition: all 0.3s ease;
}

.ozon-dot.active {
  background: #333;
  width: 16px;
  border-radius: 2px;
}
</style>
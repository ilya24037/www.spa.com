<!-- Ozon-style галерея для профиля мастера -->
<template>
  <div class="master-gallery">
    <!-- Галерея с миниатюрами слева и основным фото справа (как на Ozon) -->
    <div v-if="images && images.length > 0" class="flex gap-4 p-4">
      <!-- Миниатюры слева (вертикальный список) -->
      <div class="flex flex-col gap-2 w-20">
        <div 
          v-for="(image, index) in visibleThumbnails"
          :key="`thumb-${index}`"
          :class="[
            'relative cursor-pointer border-2 rounded-lg overflow-hidden transition-all',
            currentImageIndex === index 
              ? 'border-blue-500 shadow-lg' 
              : 'border-gray-200 hover:border-gray-400'
          ]"
          @click="setActiveImage(index)"
        >
          <img 
            :src="getImageUrl(image)"
            :alt="`Фото ${index + 1}`"
            class="w-full h-16 object-cover"
            loading="lazy"
          >
          
          <!-- Индикатор дополнительных фото на последней миниатюре -->
          <div 
            v-if="index === 5 && images.length > 6" 
            class="absolute inset-0 bg-black/70 flex items-center justify-center text-white text-xs font-medium"
          >
            +{{ images.length - 6 }}
          </div>
        </div>
      </div>
      
      <!-- Основное фото справа -->
      <div class="flex-1 relative">
        <div 
          class="cursor-pointer"
          @click="openPhotoViewer"
        >
          <img 
            :src="currentImage"
            :alt="`Фото ${currentImageIndex + 1}`"
            class="w-full h-96 object-cover rounded-lg transition-opacity duration-300"
          >
          
          <!-- Индикатор количества фото -->
          <div class="absolute bottom-4 right-4 bg-black/50 text-white px-3 py-1 rounded-full text-sm">
            📷 {{ images.length }} {{ getPhotoWord(images.length) }}
          </div>
        </div>
      </div>
    </div>
    
    <!-- Плейсхолдер когда нет фото -->
    <div v-else class="h-96 bg-gray-100 flex items-center justify-center rounded-lg">
      <div class="text-center">
        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
        </svg>
        <p class="mt-2 text-sm text-gray-500">Нет фотографий</p>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'

// TypeScript интерфейсы
interface Photo {
  id?: string | number
  url: string
  thumbnail_url?: string
  alt?: string
  caption?: string
}

interface Props {
  images?: Array<string | Photo> | null
  initialIndex?: number
}

// Props с дефолтами
const props = withDefaults(defineProps<Props>(), {
  images: null,
  initialIndex: 0
})

// Эмиты
const emit = defineEmits<{
  'open-viewer': [index: number]
  'image-change': [index: number]
}>()

// Состояние компонента
const currentImageIndex = ref(props.initialIndex)

// Computed свойства
const visibleThumbnails = computed(() => {
  if (!props.images) return []
  return props.images.slice(0, 6)
})

const currentImage = computed(() => {
  if (!props.images || props.images.length === 0) return ''
  const image = props.images[currentImageIndex.value]
  return getImageUrl(image)
})

// Методы
const getImageUrl = (image: string | Photo): string => {
  if (typeof image === 'string') {
    return image
  }
  return image.url || image.thumbnail_url || ''
}

const setActiveImage = (index: number) => {
  currentImageIndex.value = index
  emit('image-change', index)
}

const openPhotoViewer = () => {
  emit('open-viewer', currentImageIndex.value)
  
  // Также можем использовать глобальное событие для PhotoViewer
  if (typeof window !== 'undefined') {
    window.dispatchEvent(new CustomEvent('open-photo-viewer', {
      detail: {
        images: props.images?.map(img => getImageUrl(img)),
        initialIndex: currentImageIndex.value
      }
    }))
  }
}

const getPhotoWord = (count: number): string => {
  const lastDigit = count % 10
  const lastTwoDigits = count % 100
  
  if (lastTwoDigits >= 11 && lastTwoDigits <= 14) {
    return 'фото'
  }
  
  if (lastDigit === 1) return 'фото'
  if (lastDigit >= 2 && lastDigit <= 4) return 'фото'
  return 'фото'
}
</script>

<style scoped>
.master-gallery {
  @apply max-w-full;
}

/* Анимация переключения изображений */
.master-gallery img {
  transition: opacity 0.3s ease-in-out;
}

/* Hover эффекты для миниатюр */
.master-gallery .cursor-pointer:hover {
  transform: scale(1.02);
  transition: transform 0.2s ease-in-out;
}
</style>
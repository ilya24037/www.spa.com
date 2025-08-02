<!-- resources/js/src/features/gallery/ui/PhotoGallery/PhotoViewer.vue -->
<template>
  <div :class="OVERLAY_CLASSES" @click="handleOverlayClick">
    <div :class="CONTAINER_CLASSES">
      <!-- Заголовок -->
      <div :class="HEADER_CLASSES">
        <h3 :class="TITLE_CLASSES">Просмотр фотографий</h3>
        <button
          @click="$emit('close')"
          :class="CLOSE_BUTTON_CLASSES"
        >
          <svg :class="CLOSE_ICON_CLASSES" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
          </svg>
        </button>
      </div>

      <!-- Основное изображение -->
      <div :class="IMAGE_CONTAINER_CLASSES">
        <img
          :src="currentPhoto.url"
          :alt="currentPhoto.alt || `Фото ${currentIndex + 1}`"
          :class="IMAGE_CLASSES"
        >

        <!-- Навигация -->
        <button
          v-if="photos.length > 1"
          @click="previousPhoto"
          :class="NAV_BUTTON_LEFT_CLASSES"
        >
          <svg :class="NAV_ICON_CLASSES" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
          </svg>
        </button>

        <button
          v-if="photos.length > 1"
          @click="nextPhoto"
          :class="NAV_BUTTON_RIGHT_CLASSES"
        >
          <svg :class="NAV_ICON_CLASSES" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
          </svg>
        </button>
      </div>

      <!-- Нижняя панель -->
      <div :class="BOTTOM_PANEL_CLASSES">
        <!-- Счетчик -->
        <div :class="COUNTER_CLASSES">
          {{ currentIndex + 1 }} из {{ photos.length }}
        </div>

        <!-- Миниатюры -->
        <div :class="THUMBNAILS_CONTAINER_CLASSES">
          <button
            v-for="(photo, index) in photos"
            :key="photo.id || index"
            @click="setCurrentPhoto(index)"
            :class="getThumbnailClasses(index)"
          >
            <img
              :src="getThumbnailUrl(photo)"
              :alt="`Миниатюра ${index + 1}`"
              :class="THUMBNAIL_IMAGE_CLASSES"
            >
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, onUnmounted } from 'vue'

// 🎯 Стили согласно дизайн-системе
const OVERLAY_CLASSES = 'fixed inset-0 z-50 bg-black/90 flex items-center justify-center p-4'
const CONTAINER_CLASSES = 'w-full max-w-6xl h-full max-h-[90vh] bg-white rounded-lg overflow-hidden flex flex-col'
const HEADER_CLASSES = 'flex items-center justify-between p-4 border-b border-gray-200'
const TITLE_CLASSES = 'text-lg font-semibold text-gray-900'
const CLOSE_BUTTON_CLASSES = 'p-2 hover:bg-gray-100 rounded-lg transition-colors'
const CLOSE_ICON_CLASSES = 'w-5 h-5 text-gray-500'
const IMAGE_CONTAINER_CLASSES = 'flex-1 relative bg-gray-100 flex items-center justify-center'
const IMAGE_CLASSES = 'max-w-full max-h-full object-contain'
const NAV_BUTTON_LEFT_CLASSES = 'absolute left-4 top-1/2 -translate-y-1/2 w-12 h-12 bg-black/50 hover:bg-black/70 text-white rounded-full flex items-center justify-center transition-colors'
const NAV_BUTTON_RIGHT_CLASSES = 'absolute right-4 top-1/2 -translate-y-1/2 w-12 h-12 bg-black/50 hover:bg-black/70 text-white rounded-full flex items-center justify-center transition-colors'
const NAV_ICON_CLASSES = 'w-6 h-6'
const BOTTOM_PANEL_CLASSES = 'p-4 border-t border-gray-200 bg-gray-50'
const COUNTER_CLASSES = 'text-center text-sm text-gray-600 mb-3'
const THUMBNAILS_CONTAINER_CLASSES = 'flex gap-2 overflow-x-auto justify-center'
const THUMBNAIL_BASE_CLASSES = 'flex-shrink-0 w-12 h-12 rounded overflow-hidden border-2 transition-colors'
const THUMBNAIL_ACTIVE_CLASSES = 'border-blue-500'
const THUMBNAIL_INACTIVE_CLASSES = 'border-gray-200 hover:border-gray-300'
const THUMBNAIL_IMAGE_CLASSES = 'w-full h-full object-cover'

const props = defineProps({
  photos: {
    type: Array,
    required: true
  },
  currentIndex: {
    type: Number,
    default: 0
  }
})

const emit = defineEmits(['close', 'change'])

// Вычисляемые свойства
const currentPhoto = computed(() => 
  props.photos[props.currentIndex] || { url: '', alt: '' }
)

// Методы
const getThumbnailUrl = (photo) => {
  return photo.thumbnail || photo.small || photo.url
}

const getThumbnailClasses = (index) => {
  return [
    THUMBNAIL_BASE_CLASSES,
    index === props.currentIndex ? THUMBNAIL_ACTIVE_CLASSES : THUMBNAIL_INACTIVE_CLASSES
  ].join(' ')
}

const setCurrentPhoto = (index) => {
  if (index >= 0 && index < props.photos.length) {
    emit('change', index)
  }
}

const nextPhoto = () => {
  const nextIndex = (props.currentIndex + 1) % props.photos.length
  setCurrentPhoto(nextIndex)
}

const previousPhoto = () => {
  const prevIndex = props.currentIndex === 0 ? props.photos.length - 1 : props.currentIndex - 1
  setCurrentPhoto(prevIndex)
}

const handleOverlayClick = (e) => {
  if (e.target === e.currentTarget) {
    emit('close')
  }
}

const handleKeydown = (e) => {
  switch (e.key) {
    case 'Escape':
      emit('close')
      break
    case 'ArrowLeft':
      previousPhoto()
      break
    case 'ArrowRight':
      nextPhoto()
      break
  }
}

// Жизненный цикл
onMounted(() => {
  document.addEventListener('keydown', handleKeydown)
  document.body.style.overflow = 'hidden'
})

onUnmounted(() => {
  document.removeEventListener('keydown', handleKeydown)
  document.body.style.overflow = ''
})
</script>
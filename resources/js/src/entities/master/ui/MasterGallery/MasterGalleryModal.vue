<!-- resources/js/src/entities/master/ui/MasterGallery/MasterGalleryModal.vue -->
<template>
  <div :class="MODAL_OVERLAY_CLASSES" @click="handleOverlayClick">
    <div :class="MODAL_CONTAINER_CLASSES">
      <!-- Заголовок -->
      <div :class="MODAL_HEADER_CLASSES">
        <h3 :class="MODAL_TITLE_CLASSES">{{ masterName }} - Фотографии</h3>
        <button
          @click="$emit('close')"
          :class="CLOSE_BUTTON_CLASSES"
          :aria-label="'Закрыть'"
        >
          <svg :class="CLOSE_ICON_CLASSES" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>

      <!-- Основное изображение -->
      <div :class="IMAGE_CONTAINER_CLASSES">
        <img
          :src="currentPhotoUrl"
          :alt="`${masterName} - фото ${currentIndex + 1}`"
          :class="IMAGE_CLASSES"
          @load="handleImageLoad"
          @error="handleImageError"
        >

        <!-- Навигационные кнопки -->
        <template v-if="photos.length > 1">
          <button
            @click="prevPhoto"
            :class="PREV_BUTTON_CLASSES"
            :aria-label="'Предыдущее фото'"
          >
            <svg :class="NAV_ICON_CLASSES" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
          </button>

          <button
            @click="nextPhoto"
            :class="NEXT_BUTTON_CLASSES"
            :aria-label="'Следующее фото'"
          >
            <svg :class="NAV_ICON_CLASSES" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
          </button>
        </template>

        <!-- Индикатор загрузки -->
        <div v-if="imageLoading" :class="LOADING_CLASSES">
          <svg :class="LOADING_ICON_CLASSES" fill="none" viewBox="0 0 24 24">
            <circle
              class="opacity-25"
              cx="12"
              cy="12"
              r="10"
              stroke="currentColor"
              stroke-width="4"
            />
            <path
              class="opacity-75"
              fill="currentColor"
              d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
            />
          </svg>
        </div>
      </div>

      <!-- Нижняя панель -->
      <div v-if="photos.length > 1" :class="BOTTOM_PANEL_CLASSES">
        <!-- Счетчик -->
        <div :class="COUNTER_CLASSES">
          {{ currentIndex + 1 }} из {{ photos.length }}
        </div>

        <!-- Миниатюры -->
        <div :class="THUMBNAILS_CONTAINER_CLASSES">
          <button
            v-for="(photo, index) in photos"
            :key="`modal-thumb-${index}`"
            @click="goToPhoto(index)"
            :class="getThumbnailClasses(index)"
          >
            <img
              :src="getPhotoUrl(photo, 'thumbnail')"
              :alt="`Миниатюра ${index + 1}`"
              :class="THUMBNAIL_IMAGE_CLASSES"
              loading="lazy"
            >
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'

// 🎯 Стили согласно дизайн-системе
const MODAL_OVERLAY_CLASSES = 'fixed inset-0 z-50 bg-black/90 flex items-center justify-center p-4'
const MODAL_CONTAINER_CLASSES = 'w-full max-w-6xl h-full max-h-[90vh] bg-white rounded-lg overflow-hidden flex flex-col'
const MODAL_HEADER_CLASSES = 'flex items-center justify-between p-4 border-b border-gray-200'
const MODAL_TITLE_CLASSES = 'text-lg font-semibold text-gray-900'
const CLOSE_BUTTON_CLASSES = 'p-2 hover:bg-gray-100 rounded-lg transition-colors'
const CLOSE_ICON_CLASSES = 'w-5 h-5 text-gray-500'
const IMAGE_CONTAINER_CLASSES = 'flex-1 relative bg-gray-100 flex items-center justify-center'
const IMAGE_CLASSES = 'max-w-full max-h-full object-contain'
const PREV_BUTTON_CLASSES = 'absolute left-4 top-1/2 -translate-y-1/2 w-12 h-12 bg-black/50 hover:bg-black/70 text-white rounded-full flex items-center justify-center transition-all'
const NEXT_BUTTON_CLASSES = 'absolute right-4 top-1/2 -translate-y-1/2 w-12 h-12 bg-black/50 hover:bg-black/70 text-white rounded-full flex items-center justify-center transition-all'
const NAV_ICON_CLASSES = 'w-6 h-6'
const LOADING_CLASSES = 'absolute inset-0 flex items-center justify-center bg-gray-100'
const LOADING_ICON_CLASSES = 'w-8 h-8 text-gray-400 animate-spin'
const BOTTOM_PANEL_CLASSES = 'p-4 border-t border-gray-200 bg-gray-50'
const COUNTER_CLASSES = 'text-center text-sm text-gray-600 mb-3'
const THUMBNAILS_CONTAINER_CLASSES = 'flex gap-2 overflow-x-auto justify-center'
const THUMBNAIL_BASE_CLASSES = 'flex-shrink-0 w-12 h-12 rounded overflow-hidden border-2 transition-all'
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
  },
  masterName: {
    type: String,
    default: 'Мастер'
  }
})

const emit = defineEmits(['close', 'change'])

// Состояние
const localCurrentIndex = ref(props.currentIndex)
const imageLoading = ref(true)

// Вычисляемые свойства
const currentPhotoUrl = computed(() => {
  const photo = props.photos[localCurrentIndex.value]
  if (!photo) return ''
  
  return getPhotoUrl(photo, 'large')
})

// Методы
const getPhotoUrl = (photo, size = 'medium') => {
  if (typeof photo === 'string') return photo
  
  if (photo.sizes && photo.sizes[size]) {
    return photo.sizes[size]
  }
  
  return photo.url || photo.path || photo
}

const getThumbnailClasses = (index) => {
  return [
    THUMBNAIL_BASE_CLASSES,
    localCurrentIndex.value === index ? THUMBNAIL_ACTIVE_CLASSES : THUMBNAIL_INACTIVE_CLASSES
  ].join(' ')
}

const nextPhoto = () => {
  if (props.photos.length <= 1) return
  
  const newIndex = (localCurrentIndex.value + 1) % props.photos.length
  localCurrentIndex.value = newIndex
  emit('change', newIndex)
}

const prevPhoto = () => {
  if (props.photos.length <= 1) return
  
  const newIndex = localCurrentIndex.value === 0 
    ? props.photos.length - 1 
    : localCurrentIndex.value - 1
  
  localCurrentIndex.value = newIndex
  emit('change', newIndex)
}

const goToPhoto = (index) => {
  if (index >= 0 && index < props.photos.length) {
    localCurrentIndex.value = index
    emit('change', index)
  }
}

const handleOverlayClick = (e) => {
  if (e.target === e.currentTarget) {
    emit('close')
  }
}

const handleImageLoad = () => {
  imageLoading.value = false
}

const handleImageError = () => {
  imageLoading.value = false
  // Можно добавить fallback изображение
}

// Клавиатурная навигация
const handleKeydown = (e) => {
  switch (e.key) {
    case 'ArrowLeft':
      e.preventDefault()
      prevPhoto()
      break
    case 'ArrowRight':
      e.preventDefault()
      nextPhoto()
      break
    case 'Escape':
      e.preventDefault()
      emit('close')
      break
  }
}

// Синхронизация с внешним индексом
watch(() => props.currentIndex, (newIndex) => {
  localCurrentIndex.value = newIndex
})

// Отслеживание изменения фото для показа загрузки
watch(currentPhotoUrl, () => {
  imageLoading.value = true
})

// Жизненный цикл
onMounted(() => {
  document.addEventListener('keydown', handleKeydown)
  // Блокируем скролл body
  document.body.style.overflow = 'hidden'
})

onUnmounted(() => {
  document.removeEventListener('keydown', handleKeydown)
  // Восстанавливаем скролл body
  document.body.style.overflow = ''
})
</script>

<style scoped>
/* Анимация появления модального окна */
.modal-overlay {
  animation: fadeIn 0.2s ease-out;
}

.modal-container {
  animation: slideUp 0.3s ease-out;
}

@keyframes fadeIn {
  from {
    opacity: 0;
  }
  to {
    opacity: 1;
  }
}

@keyframes slideUp {
  from {
    opacity: 0;
    transform: translateY(20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

/* Кастомный скроллбар для миниатюр */
.thumbnails-container::-webkit-scrollbar {
  height: 4px;
}

.thumbnails-container::-webkit-scrollbar-track {
  background: #f1f5f9;
  border-radius: 2px;
}

.thumbnails-container::-webkit-scrollbar-thumb {
  background: #cbd5e1;
  border-radius: 2px;
}

.thumbnails-container::-webkit-scrollbar-thumb:hover {
  background: #94a3b8;
}
</style>
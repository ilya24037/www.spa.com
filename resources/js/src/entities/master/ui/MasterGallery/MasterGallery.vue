<!-- resources/js/src/entities/master/ui/MasterGallery/MasterGallery.vue -->
<template>
  <div :class="GALLERY_CONTAINER_CLASSES">
    <!-- Основное изображение -->
    <div :class="MAIN_IMAGE_CONTAINER_CLASSES">
      <!-- Соотношение сторон 4:3 для единообразия -->
      <div :class="ASPECT_RATIO_CLASSES">
        <!-- Индикаторы слайдов для мобильной версии -->
        <div v-if="validPhotos.length > 1" :class="MOBILE_INDICATORS_CLASSES">
          <div :class="INDICATORS_WRAPPER_CLASSES">
            <span
              v-for="(_, index) in validPhotos"
              :key="`indicator-${index}`"
              :class="getIndicatorClasses(index)"
            ></span>
          </div>
        </div>

        <!-- Слайдер изображений -->
        <div :class="SLIDER_CONTAINER_CLASSES">
          <transition-group
            name="slide"
            tag="div"
            :class="TRANSITION_GROUP_CLASSES"
          >
            <div
              v-for="(photo, index) in validPhotos"
              v-show="index === currentIndex"
              :key="`photo-${index}`"
              :class="SLIDE_CLASSES"
            >
              <img 
                :src="getPhotoUrl(photo)"
                :alt="`${masterName} - фото ${index + 1}`"
                :class="IMAGE_CLASSES"
                loading="lazy"
                @error="handleImageError(index)"
              >
            </div>
          </transition-group>

          <!-- Заглушка если нет фото -->
          <div v-if="!hasValidPhotos" :class="PLACEHOLDER_CLASSES">
            <div :class="PLACEHOLDER_CONTENT_CLASSES">
              <svg :class="PLACEHOLDER_ICON_CLASSES" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
              </svg>
              <p :class="PLACEHOLDER_TEXT_CLASSES">Нет фото</p>
            </div>
          </div>
        </div>

        <!-- Кнопки навигации (только десктоп) -->
        <template v-if="validPhotos.length > 1">
          <button
            @click="prevPhoto"
            :class="PREV_BUTTON_CLASSES"
            :aria-label="'Предыдущее фото'"
          >
            <svg :class="ARROW_ICON_CLASSES" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
          </button>
          
          <button
            @click="nextPhoto"
            :class="NEXT_BUTTON_CLASSES"
            :aria-label="'Следующее фото'"
          >
            <svg :class="ARROW_ICON_CLASSES" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
          </button>
        </template>

        <!-- Счетчик фото (десктоп) -->
        <div v-if="validPhotos.length > 1" :class="COUNTER_CLASSES">
          {{ currentIndex + 1 }} / {{ validPhotos.length }}
        </div>
        
        <!-- Кнопка полноэкранного просмотра -->
        <button
          v-if="hasValidPhotos"
          @click="openFullscreen"
          :class="FULLSCREEN_BUTTON_CLASSES"
          :aria-label="'Открыть в полном экране'"
        >
          <svg :class="FULLSCREEN_ICON_CLASSES" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4" />
          </svg>
        </button>
      </div>
    </div>

    <!-- Миниатюры (только десктоп) -->
    <div v-if="validPhotos.length > 1" :class="THUMBNAILS_CONTAINER_CLASSES">
      <div :class="THUMBNAILS_WRAPPER_CLASSES">
        <button
          v-for="(photo, index) in validPhotos"
          :key="`thumb-${index}`"
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

    <!-- Модальное окно полноэкранного просмотра -->
    <MasterGalleryModal
      v-if="showModal"
      :photos="validPhotos"
      :current-index="currentIndex"
      :master-name="masterName"
      @close="closeFullscreen"
      @change="handleModalChange"
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import MasterGalleryModal from './MasterGalleryModal.vue'

// 🎯 Стили согласно дизайн-системе
const GALLERY_CONTAINER_CLASSES = 'bg-white rounded-lg shadow-sm overflow-hidden'
const MAIN_IMAGE_CONTAINER_CLASSES = 'relative bg-gray-100'
const ASPECT_RATIO_CLASSES = 'relative aspect-[4/3]'
const MOBILE_INDICATORS_CLASSES = 'absolute top-4 left-1/2 -translate-x-1/2 z-20 lg:hidden'
const INDICATORS_WRAPPER_CLASSES = 'flex gap-1'
const SLIDER_CONTAINER_CLASSES = 'relative h-full w-full overflow-hidden'
const TRANSITION_GROUP_CLASSES = 'relative h-full w-full'
const SLIDE_CLASSES = 'absolute inset-0'
const IMAGE_CLASSES = 'w-full h-full object-cover'
const PLACEHOLDER_CLASSES = 'absolute inset-0 flex items-center justify-center bg-gray-100'
const PLACEHOLDER_CONTENT_CLASSES = 'text-center'
const PLACEHOLDER_ICON_CLASSES = 'w-16 h-16 mx-auto text-gray-400'
const PLACEHOLDER_TEXT_CLASSES = 'mt-2 text-sm text-gray-500'
const PREV_BUTTON_CLASSES = 'hidden lg:flex absolute left-4 top-1/2 -translate-y-1/2 w-10 h-10 bg-white/80 hover:bg-white rounded-full items-center justify-center shadow-lg transition-all hover:scale-110 z-10'
const NEXT_BUTTON_CLASSES = 'hidden lg:flex absolute right-4 top-1/2 -translate-y-1/2 w-10 h-10 bg-white/80 hover:bg-white rounded-full items-center justify-center shadow-lg transition-all hover:scale-110 z-10'
const ARROW_ICON_CLASSES = 'w-5 h-5 text-gray-700'
const COUNTER_CLASSES = 'hidden lg:block absolute top-4 right-4 bg-black/50 text-white px-3 py-1 rounded-full text-sm'
const FULLSCREEN_BUTTON_CLASSES = 'absolute bottom-4 right-4 w-10 h-10 bg-black/50 hover:bg-black/70 text-white rounded-full flex items-center justify-center transition-all z-10'
const FULLSCREEN_ICON_CLASSES = 'w-5 h-5'
const THUMBNAILS_CONTAINER_CLASSES = 'hidden lg:block p-4 bg-gray-50'
const THUMBNAILS_WRAPPER_CLASSES = 'flex gap-2 overflow-x-auto'
const THUMBNAIL_BASE_CLASSES = 'flex-shrink-0 w-16 h-16 rounded-lg overflow-hidden border-2 transition-all'
const THUMBNAIL_ACTIVE_CLASSES = 'border-blue-500'
const THUMBNAIL_INACTIVE_CLASSES = 'border-transparent hover:border-gray-300'
const THUMBNAIL_IMAGE_CLASSES = 'w-full h-full object-cover'

const props = defineProps({
  photos: {
    type: Array,
    default: () => []
  },
  masterName: {
    type: String,
    default: 'Мастер'
  },
  autoplay: {
    type: Boolean,
    default: false
  },
  autoplayInterval: {
    type: Number,
    default: 5000
  }
})

// Состояние
const currentIndex = ref(0)
const showModal = ref(false)
const autoplayTimer = ref(null)
const erroredIndices = ref(new Set())

// Touch события для мобильной навигации
const touchStart = ref({ x: 0, y: 0 })
const touchEnd = ref({ x: 0, y: 0 })

// Вычисляемые свойства
const validPhotos = computed(() => {
  return props.photos?.filter((photo, index) => 
    photo && !erroredIndices.value.has(index)
  ) || []
})

const hasValidPhotos = computed(() => validPhotos.value.length > 0)

// Методы
const getPhotoUrl = (photo, size = 'medium') => {
  if (typeof photo === 'string') return photo
  
  if (photo.sizes && photo.sizes[size]) {
    return photo.sizes[size]
  }
  
  return photo.url || photo.path || photo
}

const getIndicatorClasses = (index) => {
  const baseClasses = 'block w-6 h-1 rounded-full transition-all'
  return [
    baseClasses,
    currentIndex.value === index ? 'bg-white' : 'bg-white/50'
  ].join(' ')
}

const getThumbnailClasses = (index) => {
  return [
    THUMBNAIL_BASE_CLASSES,
    currentIndex.value === index ? THUMBNAIL_ACTIVE_CLASSES : THUMBNAIL_INACTIVE_CLASSES
  ].join(' ')
}

const nextPhoto = () => {
  if (validPhotos.value.length <= 1) return
  currentIndex.value = (currentIndex.value + 1) % validPhotos.value.length
}

const prevPhoto = () => {
  if (validPhotos.value.length <= 1) return
  currentIndex.value = currentIndex.value === 0 
    ? validPhotos.value.length - 1 
    : currentIndex.value - 1
}

const goToPhoto = (index) => {
  if (index >= 0 && index < validPhotos.value.length) {
    currentIndex.value = index
  }
}

const handleImageError = (index) => {
  erroredIndices.value.add(index)
  
  // Переключаемся на следующее фото если текущее не загрузилось
  if (index === currentIndex.value && validPhotos.value.length > 1) {
    nextPhoto()
  }
}

const openFullscreen = () => {
  showModal.value = true
}

const closeFullscreen = () => {
  showModal.value = false
}

const handleModalChange = (newIndex) => {
  currentIndex.value = newIndex
}

// Touch события
const handleTouchStart = (e) => {
  touchStart.value = {
    x: e.touches[0].clientX,
    y: e.touches[0].clientY
  }
}

const handleTouchEnd = (e) => {
  touchEnd.value = {
    x: e.changedTouches[0].clientX,
    y: e.changedTouches[0].clientY
  }
  
  const deltaX = touchStart.value.x - touchEnd.value.x
  const deltaY = Math.abs(touchStart.value.y - touchEnd.value.y)
  
  // Горизонтальный свайп должен быть больше вертикального
  if (Math.abs(deltaX) > 50 && Math.abs(deltaX) > deltaY) {
    if (deltaX > 0) {
      nextPhoto()
    } else {
      prevPhoto()
    }
  }
}

// Автопроигрывание
const startAutoplay = () => {
  if (!props.autoplay || validPhotos.value.length <= 1) return
  
  autoplayTimer.value = setInterval(() => {
    nextPhoto()
  }, props.autoplayInterval)
}

const stopAutoplay = () => {
  if (autoplayTimer.value) {
    clearInterval(autoplayTimer.value)
    autoplayTimer.value = null
  }
}

// Клавиатурная навигация
const handleKeydown = (e) => {
  if (!hasValidPhotos.value || showModal.value) return
  
  switch (e.key) {
    case 'ArrowLeft':
      prevPhoto()
      break
    case 'ArrowRight':
      nextPhoto()
      break
    case 'Escape':
      closeFullscreen()
      break
  }
}

// Жизненный цикл
onMounted(() => {
  // Добавляем обработчики событий
  document.addEventListener('keydown', handleKeydown)
  
  // Touch события для мобильной навигации
  const container = document.querySelector('[data-gallery-container]')
  if (container) {
    container.addEventListener('touchstart', handleTouchStart, { passive: true })
    container.addEventListener('touchend', handleTouchEnd, { passive: true })
  }
  
  // Запускаем автопроигрывание
  startAutoplay()
})

onUnmounted(() => {
  document.removeEventListener('keydown', handleKeydown)
  stopAutoplay()
})
</script>

<style scoped>
/* Анимации переходов */
.slide-enter-active,
.slide-leave-active {
  transition: all 0.3s ease;
}

.slide-enter-from {
  opacity: 0;
  transform: translateX(30px);
}

.slide-leave-to {
  opacity: 0;
  transform: translateX(-30px);
}

/* Кастомный скроллбар для миниатюр */
.thumbnails-wrapper::-webkit-scrollbar {
  height: 4px;
}

.thumbnails-wrapper::-webkit-scrollbar-track {
  background: #f1f5f9;
  border-radius: 2px;
}

.thumbnails-wrapper::-webkit-scrollbar-thumb {
  background: #cbd5e1;
  border-radius: 2px;
}

.thumbnails-wrapper::-webkit-scrollbar-thumb:hover {
  background: #94a3b8;
}
</style>
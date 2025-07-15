<template>
  <div class="ozon-gallery flex gap-4 bg-white rounded-lg overflow-hidden">
    <!-- Левая колонка - миниатюры (как у Ozon) -->
    <div class="flex-shrink-0 w-20 sm:w-24">
      <div class="sticky top-0 space-y-2 max-h-[600px] overflow-y-auto scrollbar-hide">
        <button
          v-for="(image, index) in images"
          :key="index"
          @click="setCurrentImage(index)"
          class="relative w-full aspect-square rounded-lg overflow-hidden border-2 transition-all duration-200"
          :class="[
            currentIndex === index 
              ? 'border-blue-500 scale-105 shadow-lg' 
              : 'border-gray-200 hover:border-gray-300 hover:scale-102'
          ]"
        >
          <img 
            :src="image"
            :alt="`Фото ${index + 1}`"
            class="w-full h-full object-cover"
            loading="lazy"
          >
          
          <!-- Индикатор активного фото -->
          <div 
            v-if="currentIndex === index"
            class="absolute inset-0 bg-blue-500/10 flex items-center justify-center"
          >
            <div class="w-2 h-2 bg-blue-500 rounded-full"></div>
          </div>
        </button>
      </div>
    </div>

    <!-- Правая колонка - основное изображение -->
    <div class="flex-1 relative">
      <div class="relative aspect-square bg-gray-50 rounded-lg overflow-hidden group">
        <!-- Основное изображение -->
        <img 
          :src="currentImage"
          :alt="masterName"
          class="w-full h-full object-cover cursor-zoom-in transition-transform duration-300"
          @click="openFullscreen"
          @error="handleImageError"
        >

        <!-- Маркетинговые бейджи (как у Ozon) -->
        <div class="absolute top-4 left-4 flex flex-col gap-2">
          <!-- Премиум статус -->
          <div 
            v-if="isPremium"
            class="px-3 py-1 bg-gradient-to-r from-purple-600 to-pink-600 text-white rounded-full text-sm font-medium flex items-center gap-1 shadow-lg"
          >
            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
              <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
            </svg>
            Премиум
          </div>

          <!-- Высокий рейтинг -->
          <div 
            v-if="rating >= 4.8"
            class="px-3 py-1 bg-green-500 text-white rounded-full text-sm font-medium flex items-center gap-1 shadow-lg"
          >
            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
              <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
            </svg>
            Топ рейтинг
          </div>

          <!-- Проверенный мастер -->
          <div 
            v-if="isVerified"
            class="px-3 py-1 bg-blue-500 text-white rounded-full text-sm font-medium flex items-center gap-1 shadow-lg"
          >
            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
            </svg>
            Проверен
          </div>
        </div>

        <!-- Счетчик фотографий -->
        <div class="absolute bottom-4 right-4 px-3 py-1 bg-black/60 text-white rounded-full text-sm backdrop-blur-sm">
          {{ currentIndex + 1 }} / {{ images.length }}
        </div>

        <!-- Кнопки навигации -->
        <div v-if="images.length > 1" class="absolute inset-y-0 left-0 right-0 flex items-center justify-between px-4 pointer-events-none">
          <button
            v-if="currentIndex > 0"
            @click="previousImage"
            class="pointer-events-auto bg-white/80 hover:bg-white text-gray-800 p-2 rounded-full shadow-lg transition-all duration-200 hover:scale-110"
            aria-label="Предыдущее фото"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
          </button>
          
          <button
            v-if="currentIndex < images.length - 1"
            @click="nextImage"
            class="pointer-events-auto bg-white/80 hover:bg-white text-gray-800 p-2 rounded-full shadow-lg transition-all duration-200 hover:scale-110"
            aria-label="Следующее фото"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
          </button>
        </div>

        <!-- Оверлей увеличения при наведении -->
        <div class="absolute inset-0 bg-black/0 group-hover:bg-black/10 transition-colors duration-300 flex items-center justify-center opacity-0 group-hover:opacity-100">
          <div class="bg-white/90 backdrop-blur-sm px-4 py-2 rounded-lg text-gray-800 font-medium flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7" />
            </svg>
            Увеличить
          </div>
        </div>
      </div>
    </div>

    <!-- Модальное окно полноэкранного просмотра -->
    <Teleport to="body">
      <ImageGalleryModal
        v-if="showFullscreen"
        v-model="showFullscreen"
        :images="images"
        :initial-index="currentIndex"
        @close="showFullscreen = false"
      />
    </Teleport>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import ImageGalleryModal from './ImageGalleryModal.vue'

// Пропсы
const props = defineProps({
  images: {
    type: Array,
    required: true,
    validator: (value) => value.length > 0
  },
  masterName: {
    type: String,
    default: 'Мастер'
  },
  isPremium: {
    type: Boolean,
    default: false
  },
  isVerified: {
    type: Boolean,
    default: false
  },
  rating: {
    type: Number,
    default: 0
  }
})

// Состояние
const currentIndex = ref(0)
const showFullscreen = ref(false)

// Вычисляемые свойства
const currentImage = computed(() => props.images[currentIndex.value] || props.images[0])

// Методы
const setCurrentImage = (index) => {
  if (index >= 0 && index < props.images.length) {
    currentIndex.value = index
  }
}

const nextImage = () => {
  if (currentIndex.value < props.images.length - 1) {
    currentIndex.value++
  }
}

const previousImage = () => {
  if (currentIndex.value > 0) {
    currentIndex.value--
  }
}

const openFullscreen = () => {
  showFullscreen.value = true
}

const handleImageError = (event) => {
  event.target.src = '/images/placeholder-master.jpg'
}

// Предзагрузка изображений
watch(currentIndex, (newIndex) => {
  // Предзагружаем соседние изображения
  const preloadIndexes = [newIndex - 1, newIndex + 1]
  preloadIndexes.forEach(index => {
    if (index >= 0 && index < props.images.length) {
      const img = new Image()
      img.src = props.images[index]
    }
  })
})

// Клавиатурная навигация
const handleKeydown = (event) => {
  if (event.key === 'ArrowLeft') {
    previousImage()
  } else if (event.key === 'ArrowRight') {
    nextImage()
  } else if (event.key === 'Escape') {
    showFullscreen.value = false
  }
}

// Добавляем обработчики клавиатуры
import { onMounted, onUnmounted } from 'vue'

onMounted(() => {
  document.addEventListener('keydown', handleKeydown)
})

onUnmounted(() => {
  document.removeEventListener('keydown', handleKeydown)
})
</script>

<style scoped>
/* Скрытие скроллбара */
.scrollbar-hide {
  -ms-overflow-style: none;
  scrollbar-width: none;
}

.scrollbar-hide::-webkit-scrollbar {
  display: none;
}

/* Плавные анимации */
.ozon-gallery img {
  will-change: transform;
}

/* Адаптивность */
@media (max-width: 640px) {
  .ozon-gallery {
    flex-direction: column;
  }
  
  .ozon-gallery .flex-shrink-0 {
    width: 100%;
    max-height: 80px;
  }
  
  .ozon-gallery .flex-shrink-0 .space-y-2 {
    display: flex;
    flex-direction: row;
    gap: 0.5rem;
    overflow-x: auto;
    padding-bottom: 0.5rem;
  }
  
  .ozon-gallery .flex-shrink-0 button {
    flex-shrink: 0;
    width: 60px;
    height: 60px;
  }
}
</style> 
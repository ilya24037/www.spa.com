<!-- resources/js/Components/Masters/MasterGallery/ThumbnailList.vue -->
<template>
  <div class="thumbnail-list p-3 bg-white">
    <div class="relative">
      <!-- Контейнер с горизонтальной прокруткой -->
      <div 
        ref="scrollContainer"
        class="flex gap-2 overflow-x-auto scrollbar-hide scroll-smooth"
        @scroll="handleScroll"
      >
        <button
          v-for="(photo, index) in photos"
          :key="`thumb-${index}`"
          @click="$emit('select', index)"
          class="thumbnail-item flex-shrink-0 relative group"
          :class="{ 'ring-2 ring-indigo-500': currentIndex === index }"
          :aria-label="`Фото ${index + 1}`"
        >
          <div class="w-16 h-16 lg:w-20 lg:h-20 rounded-lg overflow-hidden">
            <img 
              :src="photo"
              :alt="`Фото ${index + 1}`"
              class="w-full h-full object-cover transition-transform duration-200"
              :class="currentIndex === index ? 'scale-110' : 'group-hover:scale-105'"
              @error="handleImageError"
              loading="lazy"
            >
          </div>
          
          <!-- Индикатор активного фото -->
          <div 
            v-if="currentIndex === index"
            class="absolute inset-0 rounded-lg border-2 border-indigo-500 pointer-events-none"
          />
          
          <!-- Оверлей при наведении -->
          <div 
            v-if="currentIndex !== index"
            class="absolute inset-0 rounded-lg bg-black/0 group-hover:bg-black/10 transition-colors pointer-events-none"
          />
        </button>
        
        <!-- Дополнительная кнопка, если фото больше 6 -->
        <button
          v-if="photos.length > 6"
          @click="$emit('show-all')"
          class="flex-shrink-0 w-16 h-16 lg:w-20 lg:h-20 rounded-lg bg-gray-100 hover:bg-gray-200 transition-colors flex items-center justify-center group"
        >
          <div class="text-center">
            <svg class="w-6 h-6 mx-auto text-gray-600 group-hover:text-gray-800" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
            </svg>
            <span class="text-xs text-gray-600 mt-1">+{{ photos.length - 6 }}</span>
          </div>
        </button>
      </div>
      
      <!-- Кнопки прокрутки для десктопа -->
      <button
        v-if="showLeftScroll"
        @click="scrollLeft"
        class="absolute left-0 top-1/2 -translate-y-1/2 -translate-x-1 bg-white shadow-md rounded-full p-1.5 opacity-0 lg:opacity-100 hover:shadow-lg transition-all"
        aria-label="Прокрутить влево"
      >
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
        </svg>
      </button>
      
      <button
        v-if="showRightScroll"
        @click="scrollRight"
        class="absolute right-0 top-1/2 -translate-y-1/2 translate-x-1 bg-white shadow-md rounded-full p-1.5 opacity-0 lg:opacity-100 hover:shadow-lg transition-all"
        aria-label="Прокрутить вправо"
      >
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
        </svg>
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue'

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

defineEmits(['select', 'show-all'])

// Refs
const scrollContainer = ref(null)
const showLeftScroll = ref(false)
const showRightScroll = ref(false)

// Методы прокрутки
const scrollLeft = () => {
  if (scrollContainer.value) {
    scrollContainer.value.scrollBy({ left: -160, behavior: 'smooth' })
  }
}

const scrollRight = () => {
  if (scrollContainer.value) {
    scrollContainer.value.scrollBy({ left: 160, behavior: 'smooth' })
  }
}

// Проверка видимости кнопок прокрутки
const handleScroll = () => {
  if (!scrollContainer.value) return
  
  const { scrollLeft, scrollWidth, clientWidth } = scrollContainer.value
  showLeftScroll.value = scrollLeft > 0
  showRightScroll.value = scrollLeft < scrollWidth - clientWidth - 10
}

// Обработка ошибок загрузки изображений
const handleImageError = (event) => {
  event.target.src = '/images/placeholder-thumb.jpg'
}

// Автоскролл к активному элементу
watch(() => props.currentIndex, (newIndex) => {
  if (!scrollContainer.value) return
  
  const thumbs = scrollContainer.value.querySelectorAll('.thumbnail-item')
  if (thumbs[newIndex]) {
    thumbs[newIndex].scrollIntoView({ 
      behavior: 'smooth', 
      block: 'nearest', 
      inline: 'center' 
    })
  }
})

// Проверка прокрутки при загрузке и изменении размера
onMounted(() => {
  handleScroll()
  window.addEventListener('resize', handleScroll)
})

onUnmounted(() => {
  window.removeEventListener('resize', handleScroll)
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

/* Плавные переходы для миниатюр */
.thumbnail-item {
  transition: all 0.2s ease;
}

.thumbnail-item:active {
  transform: scale(0.95);
}
</style>
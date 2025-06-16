<template>
  <div class="relative group bg-white rounded-lg shadow-sm hover:shadow-md transition-all duration-200 overflow-hidden">
    <!-- Бейджи статуса -->
    <div v-if="card.is_premium || card.is_verified" class="absolute top-2 left-2 z-10 flex flex-col gap-1">
      <span v-if="card.is_premium" class="bg-gradient-to-r from-yellow-400 to-yellow-500 text-white px-2 py-0.5 rounded text-xs font-medium shadow-sm">
        ⭐ ТОП
      </span>
      <span v-if="card.is_verified" class="bg-green-500 text-white px-2 py-0.5 rounded text-xs font-medium shadow-sm">
        ✓ Проверен
      </span>
    </div>

    <!-- Действия -->
    <div class="absolute top-2 right-2 z-10 opacity-0 group-hover:opacity-100 transition-opacity duration-200">
      <button 
        @click.stop="toggleFavorite"
        class="p-1.5 bg-white/90 backdrop-blur rounded-lg hover:bg-white shadow-sm transition-all"
        :class="{ 'text-red-500': isFavorite, 'text-gray-600 hover:text-red-500': !isFavorite }"
      >
        <svg class="w-5 h-5" :fill="isFavorite ? 'currentColor' : 'none'" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
        </svg>
      </button>
    </div>

    <!-- Галерея фото -->
    <div 
      class="relative h-48 bg-gray-100 cursor-pointer overflow-hidden"
      @click="openProfile"
      @mousemove="handleMouseMove"
      @mouseleave="currentImage = 0"
    >
      <!-- Основное изображение -->
      <transition name="fade" mode="out-in">
        <img
          v-if="currentImageUrl"
          :key="currentImageUrl"
          :src="currentImageUrl"
          :alt="card.name"
          class="w-full h-full object-cover"
          loading="lazy"
        />
        <div v-else class="w-full h-full flex items-center justify-center bg-gray-100">
          <svg class="w-16 h-16 text-gray-300" fill="currentColor" viewBox="0 0 24 24">
            <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
          </svg>
        </div>
      </transition>

      <!-- Индикаторы фото -->
      <div 
        v-if="card.images?.length > 1"
        class="absolute bottom-2 left-1/2 -translate-x-1/2 flex gap-1"
      >
        <span
          v-for="(_, index) in card.images"
          :key="index"
          class="block h-1 rounded-full bg-white transition-all duration-200"
          :class="index === currentImage ? 'w-4 opacity-100' : 'w-1 opacity-60'"
        />
      </div>

      <!-- Скидка -->
      <div v-if="card.discount" class="absolute top-2 right-2 bg-red-500 text-white px-2 py-1 rounded-lg text-sm font-bold shadow-lg">
        -{{ card.discount }}%
      </div>
    </div>

    <!-- Информация -->
    <div class="p-4">
      <!-- Имя и рейтинг -->
      <div class="flex items-start justify-between gap-2 mb-2">
        <div class="flex-1 min-w-0">
          <h3 class="font-semibold text-gray-900 truncate">{{ card.name || 'Мастер' }}</h3>
          
          <!-- Рейтинг -->
          <div class="flex items-center gap-1 mt-1">
            <div class="flex items-center">
              <svg class="w-4 h-4 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
              </svg>
              <span class="text-sm font-medium text-gray-900 ml-1">{{ card.rating || '5.0' }}</span>
            </div>
            <span class="text-xs text-gray-500">({{ card.reviews_count || 0 }} отзывов)</span>
          </div>
        </div>
        
        <!-- Цена -->
        <div class="text-right">
          <p class="text-lg font-bold text-gray-900">от {{ formatPrice(card.price || card.price_from || 2000) }}</p>
          <p class="text-xs text-gray-500">/час</p>
        </div>
      </div>

      <!-- Услуги -->
      <p class="text-sm text-gray-600 line-clamp-2 mb-3 min-h-[2.5rem]">
        {{ getServicesText() }}
      </p>

      <!-- Локация и доступность -->
      <div class="flex items-center justify-between text-xs text-gray-500 mb-3">
        <div class="flex items-center gap-1">
          <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
          </svg>
          <span>{{ card.district || card.location || 'Центр города' }}</span>
        </div>
        <span v-if="card.available_today" class="text-green-600 font-medium">
          ✓ Сегодня
        </span>
      </div>

      <!-- Кнопки действий -->
      <div class="flex gap-2">
        <button
          @click.stop="showPhone"
          class="flex-1 bg-green-500 hover:bg-green-600 text-white font-medium py-2 px-3 rounded-lg transition-colors flex items-center justify-center gap-1"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
          </svg>
          <span class="text-sm">Позвонить</span>
        </button>
        
        <button
          @click.stop="openBooking"
          class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-3 rounded-lg transition-colors text-sm"
        >
          Записаться
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'

const props = defineProps({
  card: {
    type: Object,
    required: true
  }
})

// Локальное состояние
const currentImage = ref(0)
const isFavorite = ref(false)

// Computed
const currentImageUrl = computed(() => {
  const images = props.card.images || []
  const photos = props.card.photos || []
  const allImages = [...images, ...photos]
  
  if (allImages.length > 0) {
    return allImages[currentImage.value]
  }
  
  return props.card.avatar || props.card.image || null
})

// Methods
const handleMouseMove = (event) => {
  const images = props.card.images || props.card.photos || []
  if (images.length <= 1) return
  
  const rect = event.currentTarget.getBoundingClientRect()
  const x = event.clientX - rect.left
  const width = rect.width
  const segmentWidth = width / images.length
  const index = Math.floor(x / segmentWidth)
  
  currentImage.value = Math.max(0, Math.min(index, images.length - 1))
}

const toggleFavorite = () => {
  isFavorite.value = !isFavorite.value
  // TODO: Сохранить в localStorage или отправить на сервер
}

const openProfile = () => {
  router.visit(`/masters/${props.card.id}`)
}

const openBooking = () => {
  router.visit(`/masters/${props.card.id}/book`)
}

const showPhone = () => {
  // Показать телефон или открыть звонилку
  if (props.card.phone) {
    window.location.href = `tel:${props.card.phone}`
  } else {
    router.visit(`/masters/${props.card.id}#contacts`)
  }
}

const formatPrice = (price) => {
  if (!price) return '0'
  return new Intl.NumberFormat('ru-RU').format(price)
}

const getServicesText = () => {
  if (props.card.services?.length > 0) {
    if (typeof props.card.services[0] === 'object') {
      return props.card.services.map(s => s.name).join(', ')
    }
    return props.card.services.join(', ')
  }
  return props.card.description || 'Классический массаж, расслабляющий массаж'
}
</script>

<style scoped>
/* Анимация переключения изображений */
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.2s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

/* Ограничение текста на 2 строки */
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>
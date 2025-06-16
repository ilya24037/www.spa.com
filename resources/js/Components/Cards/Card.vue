<!-- resources/js/Components/Cards/Card.vue -->
<template>
  <div class="relative group bg-white rounded-lg shadow-sm hover:shadow-md transition-all duration-200 overflow-hidden">
    <!-- Бейджи статуса (стиль OZON) -->
    <div class="absolute top-2 left-2 z-10 flex flex-col gap-1">
      <!-- Распродажа/Скидка -->
      <span v-if="card.discount" class="bg-[#f91155] text-white px-2 py-0.5 rounded text-xs font-medium">
        Распродажа
      </span>
      
      <!-- Премиум -->
      <span v-if="card.is_premium" class="bg-[#7000ff] text-white px-2 py-0.5 rounded text-xs font-medium">
        Premium
      </span>
      
      <!-- Проверен -->
      <span v-if="card.is_verified" class="bg-green-500 text-white px-2 py-0.5 rounded text-xs font-medium">
        <svg class="w-3 h-3 inline mr-0.5" fill="currentColor" viewBox="0 0 20 20">
          <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
        </svg>
        Проверен
      </span>
    </div>

    <!-- Избранное (стиль OZON - всегда видно) -->
    <button 
      @click.stop="toggleFavorite"
      class="absolute top-2 right-2 z-10 p-2 bg-white/90 backdrop-blur rounded-lg hover:bg-white shadow-sm transition-all"
      :class="{ 'text-[#f91155]': isFavorite, 'text-gray-400 hover:text-[#f91155]': !isFavorite }"
    >
      <svg class="w-5 h-5" :fill="isFavorite ? 'currentColor' : 'none'" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
      </svg>
    </button>

    <!-- Галерея фото (увеличенная высота как OZON) -->
    <div 
      class="relative h-56 bg-gray-100 cursor-pointer overflow-hidden"
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
          :alt="card.name || card.display_name"
          class="w-full h-full object-cover"
          loading="lazy"
        />
        <div v-else class="w-full h-full flex items-center justify-center bg-gray-100">
          <svg class="w-16 h-16 text-gray-300" fill="currentColor" viewBox="0 0 24 24">
            <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
          </svg>
        </div>
      </transition>

      <!-- Индикаторы фото (стиль OZON) -->
      <div 
        v-if="(card.images?.length > 1) || (card.photos?.length > 1)"
        class="absolute bottom-2 left-1/2 -translate-x-1/2 flex gap-1"
      >
        <span
          v-for="(_, index) in allImages"
          :key="index"
          class="block h-1 rounded-full bg-white/80 transition-all duration-200"
          :class="index === currentImage ? 'w-4' : 'w-1'"
        />
      </div>

      <!-- Процент скидки -->
      <div 
        v-if="card.discount || card.discount_percent" 
        class="absolute bottom-2 left-2 bg-[#f91155] text-white px-2 py-0.5 rounded text-xs font-bold"
      >
        -{{ card.discount || card.discount_percent }}%
      </div>
    </div>

    <!-- Информация (стиль OZON) -->
    <div class="p-3">
      <!-- Цена (крупнее, как на OZON) -->
      <div class="mb-2">
        <div class="flex items-baseline gap-2">
          <span class="text-2xl font-bold text-gray-900">
            {{ formatPrice(card.price || card.price_from || 2000) }} ₽
          </span>
          <!-- Старая цена если есть скидка -->
          <span 
            v-if="card.old_price" 
            class="text-base line-through text-gray-400"
          >
            {{ formatPrice(card.old_price) }} ₽
          </span>
          <!-- Процент скидки -->
          <span 
            v-if="card.discount || card.discount_percent" 
            class="text-sm text-[#f91155] font-medium"
          >
            -{{ card.discount || card.discount_percent }}%
          </span>
        </div>
        <span class="text-sm text-gray-500">/час</span>
      </div>

      <!-- Имя мастера -->
      <h3 class="font-medium text-gray-900 mb-1 line-clamp-1">
        {{ card.name || card.display_name || 'Мастер' }}
      </h3>

      <!-- Специализация -->
      <p class="text-sm text-gray-600 line-clamp-2 mb-2 min-h-[2.5rem]">
        {{ getServicesText() }}
      </p>

      <!-- Рейтинг и отзывы (стиль OZON) -->
      <div class="flex items-center gap-2 mb-2">
        <div class="flex items-center">
          <svg class="w-4 h-4 text-yellow-400 fill-current" viewBox="0 0 20 20">
            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
          </svg>
          <span class="text-sm font-medium ml-1">{{ card.rating || '4.9' }}</span>
        </div>
        <span class="text-xs text-gray-500">
          {{ card.reviews_count || 0 }} {{ pluralize(card.reviews_count || 0) }}
        </span>
      </div>

      <!-- Локация с метро -->
      <div class="flex items-center gap-1 text-xs text-gray-600 mb-3">
        <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
        </svg>
        <span>{{ card.district || 'Центр' }}</span>
        <span v-if="card.metro_station" class="text-gray-400">•</span>
        <span v-if="card.metro_station">м. {{ card.metro_station }}</span>
      </div>

      <!-- Доступность -->
      <div class="flex items-center justify-between mb-3">
        <div class="flex items-center gap-2">
          <span 
            v-if="card.home_service" 
            class="inline-flex items-center text-xs text-gray-600"
          >
            <svg class="w-3.5 h-3.5 mr-1 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
            </svg>
            Выезд
          </span>
          <span 
            v-if="card.available_today" 
            class="inline-flex items-center text-xs text-green-600 font-medium"
          >
            <span class="w-1.5 h-1.5 bg-green-500 rounded-full mr-1"></span>
            Свободен сегодня
          </span>
        </div>
      </div>

      <!-- Кнопка (стиль OZON - одна основная) -->
      <button
        @click.stop="openBooking"
        class="w-full bg-[#005bff] hover:bg-[#0051e5] text-white font-medium py-2.5 rounded-lg transition-colors text-sm"
      >
        Записаться
      </button>
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
const allImages = computed(() => {
  const images = props.card.images || []
  const photos = props.card.photos || []
  return [...images, ...photos]
})

const currentImageUrl = computed(() => {
  if (allImages.value.length > 0) {
    return allImages.value[currentImage.value]
  }
  return props.card.avatar || props.card.image || '/images/default-master.jpg'
})

// Methods
const handleMouseMove = (event) => {
  if (allImages.value.length <= 1) return
  
  const rect = event.currentTarget.getBoundingClientRect()
  const x = event.clientX - rect.left
  const width = rect.width
  const segmentWidth = width / allImages.value.length
  const index = Math.floor(x / segmentWidth)
  
  currentImage.value = Math.max(0, Math.min(index, allImages.value.length - 1))
}

const toggleFavorite = () => {
  isFavorite.value = !isFavorite.value
  // TODO: API запрос для сохранения в избранное
}

const openProfile = () => {
  router.visit(`/masters/${props.card.id}`)
}

const openBooking = () => {
  router.visit(`/masters/${props.card.id}/book`)
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
  return props.card.specialization || props.card.description || 'Классический массаж'
}

const pluralize = (count) => {
  const n = Math.abs(count) % 100
  const n1 = n % 10
  if (n > 10 && n < 20) return 'отзывов'
  if (n1 > 1 && n1 < 5) return 'отзыва'
  if (n1 === 1) return 'отзыв'
  return 'отзывов'
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

/* Ограничение текста */
.line-clamp-1 {
  display: -webkit-box;
  -webkit-line-clamp: 1;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>
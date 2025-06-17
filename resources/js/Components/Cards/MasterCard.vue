<!-- resources/js/Components/Cards/MasterCard.vue -->
<template>
  <article 
    class="relative bg-white rounded-lg shadow-sm hover:shadow-md transition-all duration-200 overflow-hidden cursor-pointer"
    @click="goToProfile"
  >
    <!-- Бейджи -->
    <div class="absolute top-2 left-2 z-10 flex flex-col gap-1">
      <span 
        v-if="master.is_premium"
        class="bg-purple-600 text-white px-2 py-0.5 rounded text-xs font-medium"
      >
        Premium
      </span>
      <span 
        v-if="master.is_verified" 
        class="bg-green-500 text-white px-2 py-0.5 rounded text-xs font-medium flex items-center gap-0.5"
      >
        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
          <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
        </svg>
        Проверен
      </span>
    </div>

    <!-- Избранное -->
    <button 
      @click.stop="toggleFavorite"
      class="absolute top-2 right-2 z-10 p-2 bg-white/90 backdrop-blur rounded-lg hover:bg-white shadow-sm transition-all"
      :class="{ 'text-red-500': isFavorite, 'text-gray-400 hover:text-red-500': !isFavorite }"
    >
      <svg class="w-5 h-5" :fill="isFavorite ? 'currentColor' : 'none'" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
            d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
      </svg>
    </button>

    <!-- Изображение -->
    <div class="relative aspect-[4/5] overflow-hidden bg-gray-100">
      <img 
        :src="masterPhoto"
        :alt="master.display_name || 'Мастер массажа'"
        class="w-full h-full object-cover"
        loading="lazy"
        @error="handleImageError"
      >
      
      <!-- Онлайн статус -->
      <div 
        v-if="master.is_online"
        class="absolute bottom-2 left-2 px-2 py-1 bg-green-500 text-white text-xs font-medium rounded-full flex items-center gap-1"
      >
        <span class="w-2 h-2 bg-white rounded-full animate-pulse"></span>
        Онлайн
      </div>
    </div>

    <!-- Контент -->
    <div class="p-4">
      <!-- Цена и рейтинг -->
      <div class="flex items-start justify-between gap-2 mb-2">
        <div>
          <div class="font-bold text-xl text-gray-900">
            от {{ formatPrice(master.price_from || 2000) }} ₽
          </div>
          <div class="text-xs text-gray-500">/час</div>
        </div>
        
        <div class="flex items-center gap-1 text-sm">
          <svg class="w-4 h-4 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
          </svg>
          <span class="font-medium">{{ master.rating || '5.0' }}</span>
          <span class="text-gray-400">({{ master.reviews_count || 0 }})</span>
        </div>
      </div>

      <!-- Имя и специализация -->
      <h3 class="font-semibold text-gray-900 truncate mb-1">
        {{ master.display_name || 'Мастер' }}
      </h3>
      
      <!-- Услуги -->
      <p class="text-sm text-gray-600 line-clamp-2 mb-3">
        {{ getServicesText() }}
      </p>

      <!-- Локация -->
      <div class="flex items-center gap-1 text-xs text-gray-500 mb-3">
        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
              d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
              d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
        </svg>
        <span>{{ master.district || 'Центр' }}</span>
        <span v-if="master.metro_station">• м. {{ master.metro_station }}</span>
      </div>

      <!-- Кнопки действий -->
      <div class="flex gap-2">
        <button
          @click.stop="showPhone"
          class="flex-1 bg-green-500 hover:bg-green-600 text-white font-medium py-2 px-3 rounded-lg transition-colors flex items-center justify-center gap-1"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
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
  </article>
</template>

<script setup>
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'

const props = defineProps({
  master: {
    type: Object,
    required: true
  }
})

// Локальное состояние
const isFavorite = ref(false)
const imageError = ref(false)

// Вычисляемые свойства
const masterPhoto = computed(() => {
  if (imageError.value) {
    return '/images/default-avatar.jpg'
  }
  return props.master.avatar || props.master.photo || '/images/default-avatar.jpg'
})

// Методы
const formatPrice = (price) => {
  return new Intl.NumberFormat('ru-RU').format(price)
}

const getServicesText = () => {
  if (props.master.services?.length > 0) {
    const serviceNames = props.master.services.map(s => s.name || s).slice(0, 3)
    return serviceNames.join(', ') + (props.master.services.length > 3 ? '...' : '')
  }
  return 'Классический массаж'
}

const toggleFavorite = () => {
  isFavorite.value = !isFavorite.value
  // TODO: Сохранить в избранное через API
  router.post('/api/favorites/toggle', { 
    master_id: props.master.id 
  }, {
    preserveState: true,
    preserveScroll: true
  })
}

const goToProfile = () => {
  router.visit(`/masters/${props.master.id}`)
}

const openBooking = () => {
  router.visit(`/masters/${props.master.id}/book`)
}

const showPhone = () => {
  if (props.master.phone) {
    window.location.href = `tel:${props.master.phone}`
  }
}

const handleImageError = () => {
  imageError.value = true
}
</script>

<style scoped>
/* Ограничение текста на 2 строки */
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>
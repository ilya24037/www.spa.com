<!-- resources/js/Components/Masters/MasterHeader/QuickStats.vue -->
<template>
  <div class="quick-stats flex flex-wrap items-center gap-6">
    <!-- Рейтинг -->
    <div class="flex items-center gap-2">
      <div class="flex text-yellow-400">
        <svg 
          v-for="i in 5" 
          :key="i" 
          class="w-5 h-5" 
          :class="i <= Math.round(computedRating) ? 'text-yellow-400' : 'text-gray-300'"
          fill="currentColor" 
          viewBox="0 0 20 20"
        >
          <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
        </svg>
      </div>
      <span class="font-semibold">{{ computedRating }}</span>
      <button 
        class="text-gray-500 hover:text-indigo-600 transition-colors"
        @click="$emit('show-reviews')"
      >
        ({{ reviewsCount || 0 }} {{ reviewsText }})
      </button>
    </div>

    <!-- Статус доступности -->
    <span 
      v-if="isAvailable" 
      class="text-green-600 flex items-center gap-2"
    >
      <span class="relative flex h-3 w-3">
        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
        <span class="relative inline-flex rounded-full h-3 w-3 bg-green-500"></span>
      </span>
      Доступен сейчас
    </span>
    <span 
      v-else 
      class="text-gray-500 flex items-center gap-2"
    >
      <span class="w-3 h-3 bg-gray-400 rounded-full"></span>
      Не в сети
    </span>

    <!-- Просмотры -->
    <span class="text-gray-500 flex items-center gap-1">
      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
      </svg>
      {{ formattedViews }} {{ viewsText }}
    </span>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  rating: {
    type: Number,
    default: 5.0
  },
  reviewsCount: {
    type: Number,
    default: 0
  },
  isAvailable: {
    type: Boolean,
    default: false
  },
  viewsCount: {
    type: Number,
    default: 0
  }
})

defineEmits(['show-reviews'])

// Вычисляемые свойства
const computedRating = computed(() => props.rating || 5.0)

// Форматирование больших чисел
const formattedViews = computed(() => {
  const count = props.viewsCount || 0
  if (count >= 1000) {
    return (count / 1000).toFixed(1).replace('.0', '') + 'K'
  }
  return count
})

// Склонение слов для отзывов
const reviewsText = computed(() => {
  const count = props.reviewsCount
  const lastDigit = count % 10
  const lastTwoDigits = count % 100
  
  if (lastTwoDigits >= 11 && lastTwoDigits <= 14) return 'отзывов'
  if (lastDigit === 1) return 'отзыв'
  if (lastDigit >= 2 && lastDigit <= 4) return 'отзыва'
  return 'отзывов'
})

// Склонение слов для просмотров
const viewsText = computed(() => {
  const count = props.viewsCount
  const lastDigit = count % 10
  const lastTwoDigits = count % 100
  
  if (lastTwoDigits >= 11 && lastTwoDigits <= 14) return 'просмотров'
  if (lastDigit === 1) return 'просмотр'
  if (lastDigit >= 2 && lastDigit <= 4) return 'просмотра'
  return 'просмотров'
})
</script>

<style scoped>
/* Анимация пульсации для статуса */
@keyframes ping {
  75%, 100% {
    transform: scale(2);
    opacity: 0;
  }
}
</style>
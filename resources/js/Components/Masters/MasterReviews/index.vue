<template>
  <div class="master-reviews">
    <div class="reviews-header">
      <h2 class="section-title">Отзывы</h2>
      <div class="rating-summary">
        <div class="stars">
          <StarIcon 
            v-for="i in 5" 
            :key="i"
            :class="[
              'w-5 h-5',
              i <= Math.round(rating) ? 'text-yellow-400 fill-current' : 'text-gray-300'
            ]"
          />
        </div>
        <span class="rating-text">{{ rating }} ({{ reviews.length }} отзывов)</span>
      </div>
    </div>
    
    <div class="reviews-list">
      <div 
        v-for="review in reviews.slice(0, 3)" 
        :key="review.id"
        class="review-item"
      >
        <div class="review-header">
          <div class="reviewer-info">
            <span class="reviewer-name">{{ review.user_name }}</span>
            <div class="review-stars">
              <StarIcon 
                v-for="i in 5" 
                :key="i"
                :class="[
                  'w-4 h-4',
                  i <= review.rating ? 'text-yellow-400 fill-current' : 'text-gray-300'
                ]"
              />
            </div>
          </div>
          <span class="review-date">{{ formatDate(review.created_at) }}</span>
        </div>
        
        <div class="review-text">
          {{ review.comment }}
        </div>
      </div>
    </div>
    
    <button 
      v-if="reviews.length > 3"
      @click="showAllReviews"
      class="show-all-btn"
    >
      Показать все отзывы
    </button>
  </div>
</template>

<script setup>
import { StarIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
  reviews: {
    type: Array,
    default: () => []
  },
  rating: {
    type: Number,
    default: 0
  }
})

const formatDate = (date) => {
  return new Date(date).toLocaleDateString('ru-RU')
}

const showAllReviews = () => {
  // Логика показа всех отзывов
  console.log('Показываем все отзывы')
}
</script>

<style scoped>
.master-reviews {
  @apply bg-white rounded-lg p-6 shadow-sm;
}

.reviews-header {
  @apply flex justify-between items-center mb-6;
}

.section-title {
  @apply text-xl font-bold text-gray-900;
}

.rating-summary {
  @apply flex items-center gap-2;
}

.stars {
  @apply flex gap-1;
}

.rating-text {
  @apply text-gray-600;
}

.reviews-list {
  @apply space-y-4 mb-6;
}

.review-item {
  @apply p-4 border border-gray-200 rounded-lg;
}

.review-header {
  @apply flex justify-between items-start mb-2;
}

.reviewer-info {
  @apply flex flex-col gap-1;
}

.reviewer-name {
  @apply font-medium text-gray-900;
}

.review-stars {
  @apply flex gap-1;
}

.review-date {
  @apply text-sm text-gray-500;
}

.review-text {
  @apply text-gray-700;
}

.show-all-btn {
  @apply w-full py-2 px-4 border border-blue-600 text-blue-600 rounded-lg hover:bg-blue-50 transition-colors;
}
</style> 
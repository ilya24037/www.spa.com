<!-- resources/js/Components/Masters/ReviewsSection.vue -->
<template>
  <ContentCard>
    <template #header>
      <div class="flex items-center justify-between">
        <div>
          <h2 class="text-lg font-semibold">Отзывы</h2>
          <div class="flex items-center gap-3 mt-1">
            <!-- Общий рейтинг -->
            <div class="flex items-center gap-2">
              <span class="text-2xl font-bold">{{ averageRating }}</span>
              <div class="flex text-yellow-400">
                <svg 
                  v-for="i in 5" 
                  :key="i"
                  class="w-5 h-5"
                  :class="i <= Math.round(averageRating) ? 'text-yellow-400' : 'text-gray-300'"
                  fill="currentColor"
                  viewBox="0 0 20 20"
                >
                  <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                </svg>
              </div>
            </div>
            <span class="text-gray-500">{{ totalReviews }} {{ reviewsText }}</span>
          </div>
        </div>
        
        <!-- Кнопка добавить отзыв -->
        <button 
          v-if="canWriteReview"
          @click="$emit('write-review')"
          class="px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition-colors"
        >
          Написать отзыв
        </button>
      </div>
    </template>
    
    <!-- Статистика по рейтингам -->
    <div class="mb-6 p-4 bg-gray-50 rounded-lg">
      <div class="space-y-2">
        <div 
          v-for="(count, rating) in ratingDistribution"
          :key="rating"
          class="flex items-center gap-3"
        >
          <span class="text-sm text-gray-600 w-3">{{ rating }}</span>
          <svg class="w-4 h-4 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
          </svg>
          <div class="flex-1 bg-gray-200 rounded-full h-2 overflow-hidden">
            <div 
              class="h-full bg-yellow-400 transition-all duration-500"
              :style="{ width: `${(count / totalReviews) * 100}%` }"
            />
          </div>
          <span class="text-sm text-gray-600 w-10 text-right">{{ count }}</span>
        </div>
      </div>
    </div>
    
    <!-- Фильтр отзывов -->
    <div class="mb-4 flex items-center gap-4">
      <select 
        v-model="sortBy"
        class="text-sm border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500"
      >
        <option value="newest">Сначала новые</option>
        <option value="oldest">Сначала старые</option>
        <option value="highest">Сначала положительные</option>
        <option value="lowest">Сначала отрицательные</option>
      </select>
      
      <label class="flex items-center gap-2 text-sm">
        <input 
          v-model="showOnlyWithPhotos"
          type="checkbox"
          class="rounded text-indigo-600 focus:ring-indigo-500"
        >
        <span>Только с фото</span>
      </label>
    </div>
    
    <!-- Список отзывов -->
    <div class="space-y-4">
      <TransitionGroup
        enter-active-class="transition-all duration-300"
        enter-from-class="opacity-0 transform translate-y-2"
        leave-active-class="transition-all duration-200"
        leave-to-class="opacity-0 transform scale-95"
      >
        <div 
          v-for="review in paginatedReviews"
          :key="review.id"
          class="review-item border-b border-gray-200 pb-4 last:border-0"
        >
          <!-- Шапка отзыва -->
          <div class="flex items-start justify-between mb-3">
            <div class="flex items-center gap-3">
              <!-- Аватар -->
              <div class="w-10 h-10 bg-gray-200 rounded-full flex items-center justify-center">
                <span class="text-sm font-medium text-gray-600">
                  {{ review.client_name.charAt(0).toUpperCase() }}
                </span>
              </div>
              
              <!-- Имя и дата -->
              <div>
                <div class="font-medium">{{ review.client_name }}</div>
                <div class="flex items-center gap-2 text-sm text-gray-500">
                  <span>{{ formatDate(review.created_at) }}</span>
                  <span v-if="review.verified_booking" class="text-green-600 flex items-center gap-1">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                      <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                    </svg>
                    Подтверждённый визит
                  </span>
                </div>
              </div>
            </div>
            
            <!-- Рейтинг -->
            <div class="flex text-yellow-400">
              <svg 
                v-for="i in 5" 
                :key="i"
                class="w-5 h-5"
                :class="i <= review.rating ? 'text-yellow-400' : 'text-gray-300'"
                fill="currentColor"
                viewBox="0 0 20 20"
              >
                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
              </svg>
            </div>
          </div>
          
          <!-- Услуга -->
          <div v-if="review.service_name" class="mb-2">
            <span class="text-sm text-gray-600">Услуга:</span>
            <span class="text-sm font-medium ml-1">{{ review.service_name }}</span>
          </div>
          
          <!-- Текст отзыва -->
          <p class="text-gray-700 mb-3">{{ review.comment }}</p>
          
          <!-- Фото -->
          <div v-if="review.photos?.length" class="flex gap-2 mb-3">
            <button
              v-for="(photo, index) in review.photos"
              :key="index"
              @click="$emit('show-photo', photo)"
              class="w-20 h-20 rounded-lg overflow-hidden hover:opacity-90 transition-opacity"
            >
              <img 
                :src="photo"
                :alt="`Фото ${index + 1}`"
                class="w-full h-full object-cover"
              >
            </button>
          </div>
          
          <!-- Ответ мастера -->
          <div v-if="review.master_response" class="mt-3 ml-6 p-3 bg-indigo-50 rounded-lg">
            <div class="flex items-center gap-2 mb-2">
              <span class="text-sm font-medium text-indigo-900">Ответ мастера</span>
              <span class="text-xs text-indigo-600">{{ formatDate(review.response_at) }}</span>
            </div>
            <p class="text-sm text-indigo-800">{{ review.master_response }}</p>
          </div>
          
          <!-- Полезность отзыва -->
          <div class="mt-3 flex items-center gap-4">
            <span class="text-sm text-gray-500">Отзыв полезен?</span>
            <button 
              @click="$emit('vote-review', review.id, 'helpful')"
              class="text-sm text-gray-600 hover:text-green-600 flex items-center gap-1"
              :class="{ 'text-green-600': review.user_vote === 'helpful' }"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 10h4.764a2 2 0 011.789 2.894l-3.5 7A2 2 0 0115.263 21h-4.017c-.163 0-.326-.02-.485-.06L7 20m7-10V5a2 2 0 00-2-2h-.095c-.5 0-.905.405-.905.905 0 .714-.211 1.412-.608 2.006L7 11v9m7-10h-2M7 20H5a2 2 0 01-2-2v-6a2 2 0 012-2h2.5" />
              </svg>
              <span>{{ review.helpful_count || 0 }}</span>
            </button>
            <button 
              @click="$emit('vote-review', review.id, 'not_helpful')"
              class="text-sm text-gray-600 hover:text-red-600 flex items-center gap-1"
              :class="{ 'text-red-600': review.user_vote === 'not_helpful' }"
            >
              <svg class="w-4 h-4 rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 10h4.764a2 2 0 011.789 2.894l-3.5 7A2 2 0 0115.263 21h-4.017c-.163 0-.326-.02-.485-.06L7 20m7-10V5a2 2 0 00-2-2h-.095c-.5 0-.905.405-.905.905 0 .714-.211 1.412-.608 2.006L7 11v9m7-10h-2M7 20H5a2 2 0 01-2-2v-6a2 2 0 012-2h2.5" />
              </svg>
              <span>{{ review.not_helpful_count || 0 }}</span>
            </button>
          </div>
        </div>
      </TransitionGroup>
    </div>
    
    <!-- Пагинация -->
    <div v-if="totalPages > 1" class="mt-6 flex justify-center">
      <nav class="flex items-center gap-1">
        <button 
          @click="currentPage--"
          :disabled="currentPage === 1"
          class="p-2 rounded-lg hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
          </svg>
        </button>
        
        <button
          v-for="page in visiblePages"
          :key="page"
          @click="currentPage = page"
          class="px-3 py-1 rounded-lg text-sm font-medium transition-colors"
          :class="currentPage === page 
            ? 'bg-indigo-600 text-white' 
            : 'hover:bg-gray-100'"
        >
          {{ page }}
        </button>
        
        <button 
          @click="currentPage++"
          :disabled="currentPage === totalPages"
          class="p-2 rounded-lg hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
          </svg>
        </button>
      </nav>
    </div>
    
    <!-- Пустое состояние -->
    <div v-if="reviews.length === 0" class="text-center py-12">
      <svg class="mx-auto h-12 w-12 text-gray-400 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
      </svg>
      <p class="text-gray-500 mb-4">Пока нет отзывов</p>
      <p class="text-sm text-gray-400">Станьте первым, кто оставит отзыв!</p>
    </div>
  </ContentCard>
</template>

<script setup>
import { ref, computed } from 'vue'
// 🎯 FSD Импорты
import { ContentCard } from '@/src/shared'

const props = defineProps({
  reviews: {
    type: Array,
    default: () => []
  },
  canWriteReview: {
    type: Boolean,
    default: false
  },
  averageRating: {
    type: Number,
    default: 0
  }
})

const emit = defineEmits(['write-review', 'vote-review', 'show-photo'])

// Состояние
const currentPage = ref(1)
const reviewsPerPage = 5
const sortBy = ref('newest')
const showOnlyWithPhotos = ref(false)

// Вычисляемые свойства
const totalReviews = computed(() => props.reviews.length)

const reviewsText = computed(() => {
  const count = totalReviews.value
  const lastDigit = count % 10
  const lastTwoDigits = count % 100
  
  if (lastTwoDigits >= 11 && lastTwoDigits <= 14) return 'отзывов'
  if (lastDigit === 1) return 'отзыв'
  if (lastDigit >= 2 && lastDigit <= 4) return 'отзыва'
  return 'отзывов'
})

// Распределение по рейтингам
const ratingDistribution = computed(() => {
  const distribution = { 5: 0, 4: 0, 3: 0, 2: 0, 1: 0 }
  props.reviews.forEach(review => {
    distribution[review.rating]++
  })
  return distribution
})

// Фильтрация и сортировка
const filteredReviews = computed(() => {
  let filtered = [...props.reviews]
  
  // Фильтр по фото
  if (showOnlyWithPhotos.value) {
    filtered = filtered.filter(r => r.photos?.length > 0)
  }
  
  // Сортировка
  filtered.sort((a, b) => {
    switch (sortBy.value) {
      case 'newest':
        return new Date(b.created_at) - new Date(a.created_at)
      case 'oldest':
        return new Date(a.created_at) - new Date(b.created_at)
      case 'highest':
        return b.rating - a.rating
      case 'lowest':
        return a.rating - b.rating
      default:
        return 0
    }
  })
  
  return filtered
})

// Пагинация
const totalPages = computed(() => 
  Math.ceil(filteredReviews.value.length / reviewsPerPage)
)

const paginatedReviews = computed(() => {
  const start = (currentPage.value - 1) * reviewsPerPage
  const end = start + reviewsPerPage
  return filteredReviews.value.slice(start, end)
})

const visiblePages = computed(() => {
  const pages = []
  const total = totalPages.value
  const current = currentPage.value
  
  if (total <= 5) {
    for (let i = 1; i <= total; i++) {
      pages.push(i)
    }
  } else {
    if (current <= 3) {
      pages.push(1, 2, 3, 4, '...', total)
    } else if (current >= total - 2) {
      pages.push(1, '...', total - 3, total - 2, total - 1, total)
    } else {
      pages.push(1, '...', current - 1, current, current + 1, '...', total)
    }
  }
  
  return pages.filter(p => p !== '...')
})

// Методы
const formatDate = (date) => {
  if (!date) return ''
  
  const d = new Date(date)
  const now = new Date()
  const diff = now - d
  const days = Math.floor(diff / (1000 * 60 * 60 * 24))
  
  if (days === 0) return 'Сегодня'
  if (days === 1) return 'Вчера'
  if (days < 7) return `${days} дней назад`
  if (days < 30) return `${Math.floor(days / 7)} недель назад`
  
  return d.toLocaleDateString('ru-RU', { 
    day: 'numeric', 
    month: 'long',
    year: 'numeric'
  })
}
</script>
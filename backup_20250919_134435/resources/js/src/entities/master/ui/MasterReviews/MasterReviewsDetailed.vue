<!-- Enhanced Master Reviews component with integration -->
<template>
  <div :class="CONTAINER_CLASSES">
    <div :class="HEADER_CLASSES">
      <h3 :class="TITLE_CLASSES">
        Отзывы ({{ totalReviews }})
      </h3>
      <button
        v-if="hasMoreReviews"
        :class="LOAD_MORE_BUTTON_CLASSES"
        :disabled="loading"
        @click="loadMoreReviews"
      >
        {{ loading ? 'Загрузка...' : 'Показать еще' }}
      </button>
    </div>

    <!-- Rating summary with statistics -->
    <div :class="RATING_SUMMARY_CLASSES">
      <div class="flex items-center justify-between">
        <div class="flex items-center gap-4">
          <div :class="RATING_SCORE_CLASSES">
            <span :class="SCORE_NUMBER_CLASSES">{{ overallRating }}</span>
            <div :class="STARS_CONTAINER_CLASSES">
              <svg
                v-for="i in 5"
                :key="i"
                :class="getStarClasses(i)"
                fill="currentColor"
                viewBox="0 0 20 20"
              >
                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
              </svg>
            </div>
            <span :class="REVIEWS_COUNT_CLASSES">на основе {{ totalReviews }} отзывов</span>
          </div>
        </div>

        <!-- Rating distribution (inspired by Ozon) -->
        <div class="hidden md:block">
          <div class="space-y-2">
            <div 
              v-for="rating in [5, 4, 3, 2, 1]" 
              :key="rating" 
              class="flex items-center gap-2 text-sm"
            >
              <span class="w-3 text-gray-500">{{ rating }}</span>
              <svg class="w-3 h-3 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
              </svg>
              <div class="w-20 h-2 bg-gray-200 rounded-full overflow-hidden">
                <div 
                  class="h-full bg-yellow-400 transition-all duration-300"
                  :style="{ width: `${getRatingPercentage(rating)}%` }"
                />
              </div>
              <span class="w-8 text-xs text-gray-500">{{ getRatingCount(rating) }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Filter buttons -->
    <div v-if="displayReviews.length > 0" class="flex items-center gap-2 mb-6">
      <button
        v-for="filter in reviewFilters"
        :key="filter.key"
        :class="getFilterButtonClasses(filter.key)"
        @click="activeFilter = filter.key"
      >
        {{ filter.label }}
        <span v-if="filter.count > 0" class="ml-1 px-1.5 py-0.5 text-xs rounded-full bg-white bg-opacity-20">
          {{ filter.count }}
        </span>
      </button>
    </div>

    <!-- Reviews list -->
    <div :class="REVIEWS_LIST_CLASSES">
      <ReviewCard
        v-for="review in filteredReviews"
        :key="review.id"
        :review="review"
        class="mb-4 last:mb-0"
      />
    </div>

    <!-- Empty state -->
    <div v-if="displayReviews.length === 0" :class="EMPTY_STATE_CLASSES">
      <svg
        :class="EMPTY_ICON_CLASSES"
        fill="none"
        stroke="currentColor"
        viewBox="0 0 24 24"
      >
        <path
          stroke-linecap="round"
          stroke-linejoin="round"
          stroke-width="2"
          d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"
        />
      </svg>
      <p :class="EMPTY_TEXT_CLASSES">
        Пока нет отзывов
      </p>
      <p :class="EMPTY_SUBTITLE_CLASSES">
        Станьте первым, кто оставит отзыв о работе мастера
      </p>
    </div>

    <!-- Write review section -->
    <div v-if="canWriteReview" class="mt-6 p-4 bg-blue-50 rounded-lg border border-blue-200">
      <div class="flex items-start gap-3">
        <svg class="w-6 h-6 text-blue-500 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
        </svg>
        <div class="flex-1">
          <h4 class="font-medium text-gray-900 mb-1">Оставьте отзыв</h4>
          <p class="text-sm text-gray-600 mb-3">
            Поделитесь своим опытом с другими клиентами
          </p>
          <button 
            class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors text-sm font-medium"
            @click="openReviewForm"
          >
            Написать отзыв
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import dayjs from 'dayjs'
import 'dayjs/locale/ru'
import ReviewCard from '@/src/entities/review/ui/ReviewCard/ReviewCard.vue'

dayjs.locale('ru')

// 🎨 Стили согласно дизайн-системе
const CONTAINER_CLASSES = 'space-y-6'
const HEADER_CLASSES = 'flex items-center justify-between'
const TITLE_CLASSES = 'text-lg font-semibold text-gray-900'
const LOAD_MORE_BUTTON_CLASSES = 'text-sm text-blue-600 hover:text-blue-700 font-medium disabled:opacity-50 transition-colors'
const RATING_SUMMARY_CLASSES = 'p-6 bg-gray-50 rounded-lg'
const RATING_SCORE_CLASSES = 'flex flex-col items-start gap-2'
const SCORE_NUMBER_CLASSES = 'text-3xl font-bold text-gray-900'
const STARS_CONTAINER_CLASSES = 'flex gap-0.5'
const STAR_CLASSES = 'w-5 h-5'
const STAR_FILLED_CLASSES = 'text-yellow-400'
const STAR_EMPTY_CLASSES = 'text-gray-300'
const REVIEWS_COUNT_CLASSES = 'text-sm text-gray-600'
const REVIEWS_LIST_CLASSES = 'space-y-4'
const EMPTY_STATE_CLASSES = 'text-center py-12'
const EMPTY_ICON_CLASSES = 'w-12 h-12 mx-auto text-gray-400 mb-4'
const EMPTY_TEXT_CLASSES = 'text-gray-900 font-medium'
const EMPTY_SUBTITLE_CLASSES = 'text-sm text-gray-500 mt-1'

interface Review {
  id: number
  rating: number
  comment: string
  created_at: string
  client_name: string
  service_name?: string
  status?: string
}

interface Master {
  id: number
  rating?: number
  reviews_count?: number
  reviews?: Review[]
}

interface Props {
  master: Master
  initialReviews?: Review[]
  canWriteReview?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  initialReviews: () => [],
  canWriteReview: true
})

const emit = defineEmits(['load-more', 'write-review'])

// Состояние
const loading = ref(false)
const reviews = ref([...props.initialReviews])
const activeFilter = ref('all')

// Вычисляемые свойства
const displayReviews = computed(() => reviews.value)

const totalReviews = computed(() => props.master.reviews_count || 0)

const overallRating = computed(() => {
  return props.master.rating ? Number(props.master.rating).toFixed(1) : '5.0'
})

const hasMoreReviews = computed(() => {
  return reviews.value.length < totalReviews.value
})

// Rating distribution
const ratingDistribution = computed(() => {
  const distribution: Record<number, number> = { 1: 0, 2: 0, 3: 0, 4: 0, 5: 0 }
  reviews.value.forEach(review => {
    if (review.rating >= 1 && review.rating <= 5) {
      distribution[review.rating]++
    }
  })
  return distribution
})

// Review filters
const reviewFilters = computed(() => [
  { key: 'all', label: 'Все', count: reviews.value.length },
  { key: 'positive', label: 'Положительные', count: reviews.value.filter(r => r.rating >= 4).length },
  { key: 'negative', label: 'Отрицательные', count: reviews.value.filter(r => r.rating <= 3).length },
  { key: 'recent', label: 'Недавние', count: reviews.value.filter(r => {
    const daysDiff = dayjs().diff(dayjs(r.created_at), 'day')
    return daysDiff <= 30
  }).length }
])

const filteredReviews = computed(() => {
  switch (activeFilter.value) {
    case 'positive':
      return reviews.value.filter(r => r.rating >= 4)
    case 'negative':
      return reviews.value.filter(r => r.rating <= 3)
    case 'recent':
      return reviews.value.filter(r => {
        const daysDiff = dayjs().diff(dayjs(r.created_at), 'day')
        return daysDiff <= 30
      })
    default:
      return reviews.value
  }
})

// Методы
const getStarClasses = (starNumber: number) => {
  const rating = props.master.rating || 0
  const isActive = starNumber <= Math.round(rating)
  
  return [
    STAR_CLASSES,
    isActive ? STAR_FILLED_CLASSES : STAR_EMPTY_CLASSES
  ].join(' ')
}

const getRatingPercentage = (rating: number) => {
  const total = totalReviews.value
  if (total === 0) return 0
  return Math.round((ratingDistribution.value[rating] / total) * 100)
}

const getRatingCount = (rating: number) => {
  return ratingDistribution.value[rating] || 0
}

const getFilterButtonClasses = (filterKey: string) => {
  const baseClasses = 'px-3 py-1.5 text-sm font-medium rounded-full transition-colors'
  const activeClasses = 'bg-blue-600 text-white'
  const inactiveClasses = 'bg-gray-100 text-gray-700 hover:bg-gray-200'
  
  return `${baseClasses} ${activeFilter.value === filterKey ? activeClasses : inactiveClasses}`
}

const loadMoreReviews = async () => {
  loading.value = true
  try {
    emit('load-more')
  } finally {
    loading.value = false
  }
}

const openReviewForm = () => {
  emit('write-review')
}
</script>

<style scoped>
/* Smooth transitions for rating bars */
.transition-all {
  transition: all 0.3s ease-in-out;
}

/* Filter buttons animation */
button {
  transition: all 0.2s ease-in-out;
}

button:hover {
  transform: translateY(-1px);
}

button:active {
  transform: translateY(0);
}
</style>
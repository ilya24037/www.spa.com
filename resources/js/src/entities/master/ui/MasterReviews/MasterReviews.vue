<!-- resources/js/src/entities/master/ui/MasterReviews/MasterReviews.vue -->
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

    <!-- Общий рейтинг -->
    <div :class="RATING_SUMMARY_CLASSES">
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

    <!-- Список отзывов -->
    <div :class="REVIEWS_LIST_CLASSES">
      <div
        v-for="review in displayReviews"
        :key="review.id"
        :class="REVIEW_ITEM_CLASSES"
      >
        <div :class="REVIEW_HEADER_CLASSES">
          <div :class="REVIEWER_INFO_CLASSES">
            <span :class="REVIEWER_NAME_CLASSES">{{ review.author_name || 'Аноним' }}</span>
            <span :class="REVIEW_DATE_CLASSES">{{ formatDate(review.created_at) }}</span>
          </div>
          
          <div :class="REVIEW_RATING_CLASSES">
            <svg
              v-for="i in 5"
              :key="i"
              :class="getReviewStarClasses(i, review.rating)"
              fill="currentColor"
              viewBox="0 0 20 20"
            >
              <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
            </svg>
          </div>
        </div>

        <p :class="REVIEW_TEXT_CLASSES">
          {{ review.comment }}
        </p>

        <!-- Ответ мастера -->
        <div v-if="review.response" :class="RESPONSE_CLASSES">
          <div :class="RESPONSE_HEADER_CLASSES">
            <span :class="RESPONSE_AUTHOR_CLASSES">Ответ мастера</span>
            <span :class="RESPONSE_DATE_CLASSES">{{ formatDate(review.response_date) }}</span>
          </div>
          <p :class="RESPONSE_TEXT_CLASSES">
            {{ review.response }}
          </p>
        </div>
      </div>
    </div>

    <!-- Заглушка если нет отзывов -->
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
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import dayjs from 'dayjs'
import 'dayjs/locale/ru'

dayjs.locale('ru')

// 🎯 Стили согласно дизайн-системе
const CONTAINER_CLASSES = 'space-y-6'
const HEADER_CLASSES = 'flex items-center justify-between'
const TITLE_CLASSES = 'text-lg font-semibold text-gray-900'
const LOAD_MORE_BUTTON_CLASSES = 'text-sm text-blue-600 hover:text-blue-700 font-medium disabled:opacity-50'
const RATING_SUMMARY_CLASSES = 'p-4 bg-gray-50 rounded-lg'
const RATING_SCORE_CLASSES = 'flex items-center gap-4'
const SCORE_NUMBER_CLASSES = 'text-3xl font-bold text-gray-900'
const STARS_CONTAINER_CLASSES = 'flex gap-0.5'
const STAR_CLASSES = 'w-5 h-5'
const STAR_FILLED_CLASSES = 'text-yellow-400'
const STAR_EMPTY_CLASSES = 'text-gray-300'
const REVIEWS_COUNT_CLASSES = 'text-sm text-gray-600'
const REVIEWS_LIST_CLASSES = 'space-y-4'
const REVIEW_ITEM_CLASSES = 'p-4 border border-gray-200 rounded-lg'
const REVIEW_HEADER_CLASSES = 'flex items-start justify-between mb-3'
const REVIEWER_INFO_CLASSES = 'flex flex-col'
const REVIEWER_NAME_CLASSES = 'font-medium text-gray-900'
const REVIEW_DATE_CLASSES = 'text-xs text-gray-500'
const REVIEW_RATING_CLASSES = 'flex gap-0.5'
const REVIEW_STAR_CLASSES = 'w-4 h-4'
const REVIEW_TEXT_CLASSES = 'text-gray-700 leading-relaxed'
const RESPONSE_CLASSES = 'mt-4 p-3 bg-blue-50 rounded-lg border-l-4 border-blue-200'
const RESPONSE_HEADER_CLASSES = 'flex items-center justify-between mb-2'
const RESPONSE_AUTHOR_CLASSES = 'text-sm font-medium text-blue-800'
const RESPONSE_DATE_CLASSES = 'text-xs text-blue-600'
const RESPONSE_TEXT_CLASSES = 'text-sm text-blue-900'
const EMPTY_STATE_CLASSES = 'text-center py-12'
const EMPTY_ICON_CLASSES = 'w-12 h-12 mx-auto text-gray-400 mb-4'
const EMPTY_TEXT_CLASSES = 'text-gray-600 font-medium'
const EMPTY_SUBTITLE_CLASSES = 'text-sm text-gray-500 mt-1'

const props = defineProps({
    master: {
        type: Object,
        required: true
    },
    initialReviews: {
        type: Array,
        default: () => []
    }
})

const emit = defineEmits(['load-more'])

// Состояние
const loading = ref(false)
const reviews = ref([...props.initialReviews])

// Вычисляемые свойства
const displayReviews = computed(() => reviews.value)

const totalReviews = computed(() => props.master.reviews_count || 0)

const overallRating = computed(() => {
    return props.master.rating ? Number(props.master.rating).toFixed(1) : '5.0'
})

const hasMoreReviews = computed(() => {
    return reviews.value.length < totalReviews.value
})

// Методы
const getStarClasses = (starNumber) => {
    const rating = props.master.rating || 0
    const isActive = starNumber <= Math.round(rating)
  
    return [
        STAR_CLASSES,
        isActive ? STAR_FILLED_CLASSES : STAR_EMPTY_CLASSES
    ].join(' ')
}

const getReviewStarClasses = (starNumber, reviewRating) => {
    const isActive = starNumber <= (reviewRating || 0)
  
    return [
        REVIEW_STAR_CLASSES,
        isActive ? STAR_FILLED_CLASSES : STAR_EMPTY_CLASSES
    ].join(' ')
}

const formatDate = (date) => {
    if (!date) return ''
    return dayjs(date).format('DD MMMM YYYY')
}

const loadMoreReviews = async () => {
    loading.value = true
    try {
        emit('load-more')
    } finally {
        loading.value = false
    }
}
</script>


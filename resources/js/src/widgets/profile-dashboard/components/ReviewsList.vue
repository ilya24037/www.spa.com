<!-- resources/js/src/widgets/profile-dashboard/components/ReviewsList.vue -->
<template>
  <div :class="CONTAINER_CLASSES">
    <div v-if="!loading && reviews.length" :class="REVIEWS_LIST_CLASSES">
      <div
        v-for="review in reviews"
        :key="review.id"
        :class="REVIEW_ITEM_CLASSES"
      >
        <div :class="REVIEW_HEADER_CLASSES">
          <div>
            <h4 :class="AUTHOR_NAME_CLASSES">{{ review.author_name || 'Аноним' }}</h4>
            <div :class="RATING_CLASSES">
              <svg
                v-for="i in 5"
                :key="i"
                :class="getStarClasses(i, review.rating)"
                fill="currentColor"
                viewBox="0 0 20 20"
              >
                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
              </svg>
            </div>
          </div>
          <span :class="DATE_CLASSES">{{ formatDate(review.created_at) }}</span>
        </div>
        
        <p :class="COMMENT_CLASSES">{{ review.comment }}</p>
        
        <div v-if="review.response" :class="RESPONSE_CLASSES">
          <div :class="RESPONSE_HEADER_CLASSES">Ваш ответ:</div>
          <p :class="RESPONSE_TEXT_CLASSES">{{ review.response }}</p>
        </div>
        
        <div v-else-if="isMaster" :class="ACTIONS_CLASSES">
          <button
            @click="showResponseForm = review.id"
            :class="RESPOND_BUTTON_CLASSES"
          >
            Ответить
          </button>
        </div>
        
        <!-- Форма ответа -->
        <div v-if="showResponseForm === review.id" :class="RESPONSE_FORM_CLASSES">
          <textarea
            v-model="responseText"
            :class="TEXTAREA_CLASSES"
            placeholder="Ваш ответ на отзыв..."
            rows="3"
          ></textarea>
          <div :class="FORM_ACTIONS_CLASSES">
            <button
              @click="cancelResponse"
              :class="CANCEL_BUTTON_CLASSES"
            >
              Отмена
            </button>
            <button
              @click="submitResponse(review)"
              :disabled="!responseText.trim()"
              :class="SUBMIT_BUTTON_CLASSES"
            >
              Отправить
            </button>
          </div>
        </div>
      </div>
    </div>

    <div v-else-if="loading" :class="LOADING_CLASSES">
      <span>Загрузка отзывов...</span>
    </div>

    <div v-else :class="EMPTY_STATE_CLASSES">
      <h3>У вас пока нет отзывов</h3>
      <p>{{ isMaster ? 'Отзывы клиентов будут отображаться здесь' : 'Ваши отзывы будут отображаться здесь' }}</p>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import dayjs from 'dayjs'

const CONTAINER_CLASSES = 'p-6'
const REVIEWS_LIST_CLASSES = 'space-y-6'
const REVIEW_ITEM_CLASSES = 'p-4 border rounded-lg'
const REVIEW_HEADER_CLASSES = 'flex justify-between items-start mb-3'
const AUTHOR_NAME_CLASSES = 'font-medium text-gray-900'
const RATING_CLASSES = 'flex gap-0.5 mt-1'
const DATE_CLASSES = 'text-sm text-gray-500'
const COMMENT_CLASSES = 'text-gray-700 mb-3'
const RESPONSE_CLASSES = 'mt-4 p-3 bg-blue-50 rounded-lg'
const RESPONSE_HEADER_CLASSES = 'font-medium text-blue-800 mb-2'
const RESPONSE_TEXT_CLASSES = 'text-blue-900'
const ACTIONS_CLASSES = 'mt-4'
const RESPOND_BUTTON_CLASSES = 'px-4 py-2 text-blue-600 border border-blue-200 rounded-lg hover:bg-blue-50'
const RESPONSE_FORM_CLASSES = 'mt-4 space-y-3'
const TEXTAREA_CLASSES = 'w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500'
const FORM_ACTIONS_CLASSES = 'flex gap-2'
const CANCEL_BUTTON_CLASSES = 'px-4 py-2 border rounded-lg'
const SUBMIT_BUTTON_CLASSES = 'px-4 py-2 bg-blue-600 text-white rounded-lg disabled:opacity-50'
const LOADING_CLASSES = 'text-center py-12 text-gray-500'
const EMPTY_STATE_CLASSES = 'text-center py-12 text-gray-500'

const props = defineProps({
  reviews: { type: Array, default: () => [] },
  loading: { type: Boolean, default: false },
  isMaster: { type: Boolean, default: false }
})

const emit = defineEmits(['respond'])

const showResponseForm = ref(null)
const responseText = ref('')

const getStarClasses = (starNumber, rating) => {
  const base = 'w-4 h-4'
  return starNumber <= rating ? `${base} text-yellow-400` : `${base} text-gray-300`
}

const formatDate = (date) => dayjs(date).format('DD.MM.YYYY')

const cancelResponse = () => {
  showResponseForm.value = null
  responseText.value = ''
}

const submitResponse = (review) => {
  emit('respond', review, responseText.value)
  cancelResponse()
}
</script>
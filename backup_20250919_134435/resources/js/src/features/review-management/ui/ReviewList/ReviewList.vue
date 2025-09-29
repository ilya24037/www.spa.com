<template>
  <div class="space-y-4">
    <!-- Заголовок и кнопка добавления -->
    <div class="flex justify-between items-center">
      <h2 class="text-xl font-semibold">Отзывы</h2>
      <button
        v-if="canAddReview"
        @click="showAddForm = true"
        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
      >
        Добавить отзыв
      </button>
    </div>

    <!-- Фильтры -->
    <div class="flex gap-2">
      <select
        v-model="filters.rating"
        @change="loadReviews"
        class="px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
      >
        <option :value="undefined">Все рейтинги</option>
        <option :value="5">5 звёзд</option>
        <option :value="4">4 звезды</option>
        <option :value="3">3 звезды</option>
        <option :value="2">2 звезды</option>
        <option :value="1">1 звезда</option>
      </select>
      
      <select
        v-model="filters.status"
        @change="loadReviews"
        v-if="showStatusFilter"
        class="px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
      >
        <option :value="undefined">Все статусы</option>
        <option value="approved">Одобренные</option>
        <option value="pending">На модерации</option>
        <option value="rejected">Отклонённые</option>
      </select>
    </div>

    <!-- Состояние загрузки -->
    <div v-if="isLoading" class="space-y-3">
      <div v-for="i in 3" :key="i" class="animate-pulse">
        <div class="h-32 bg-gray-200 rounded-lg"></div>
      </div>
    </div>

    <!-- Состояние ошибки -->
    <div v-else-if="error" class="rounded-lg border-2 border-red-200 bg-red-50 p-6">
      <p class="text-red-600 font-medium mb-2">Произошла ошибка</p>
      <p class="text-red-500 text-sm mb-4">{{ error }}</p>
      <button 
        @click="loadReviews" 
        class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors"
      >
        Попробовать снова
      </button>
    </div>

    <!-- Пустое состояние -->
    <div v-else-if="reviews.length === 0" class="text-center py-12">
      <ChatAltIcon class="w-16 h-16 mx-auto text-gray-300 mb-4" />
      <p class="text-gray-500 mb-4">Отзывов пока нет</p>
      <button 
        v-if="canAddReview"
        @click="showAddForm = true"
        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
      >
        Оставить первый отзыв
      </button>
    </div>

    <!-- Список отзывов -->
    <div v-else class="space-y-4">
      <div v-for="review in reviews" :key="review.id" class="relative">
        <ReviewCard 
          :review="review"
          :compact="false"
        />
        
        <!-- Действия для отзыва -->
        <div v-if="canEditReview(review)" class="absolute top-4 right-4 flex gap-2">
          <button
            @click="editReview(review)"
            class="p-2 text-gray-600 hover:text-blue-600 transition-colors"
            title="Редактировать"
          >
            <PencilIcon class="w-5 h-5" />
          </button>
          <button
            @click="deleteReview(review)"
            class="p-2 text-gray-600 hover:text-red-600 transition-colors"
            title="Удалить"
          >
            <TrashIcon class="w-5 h-5" />
          </button>
        </div>

        <!-- Действия модерации -->
        <div v-if="canModerate && review.status === 'pending'" class="mt-2 flex gap-2">
          <button
            @click="approveReview(review)"
            class="px-3 py-1 bg-green-600 text-white text-sm rounded hover:bg-green-700 transition-colors"
          >
            Одобрить
          </button>
          <button
            @click="rejectReview(review)"
            class="px-3 py-1 bg-red-600 text-white text-sm rounded hover:bg-red-700 transition-colors"
          >
            Отклонить
          </button>
        </div>
      </div>
    </div>

    <!-- Пагинация -->
    <div v-if="meta && meta.last_page > 1" class="flex justify-center gap-2 mt-6">
      <button
        v-for="page in meta.last_page"
        :key="page"
        @click="loadReviews(page)"
        :class="[
          'px-3 py-1 rounded transition-colors',
          meta.current_page === page 
            ? 'bg-blue-600 text-white' 
            : 'bg-gray-200 hover:bg-gray-300 text-gray-700'
        ]"
      >
        {{ page }}
      </button>
    </div>

    <!-- Модальное окно добавления/редактирования -->
    <ReviewFormModal
      v-if="showAddForm || editingReview"
      :review="editingReview"
      @save="handleSaveReview"
      @close="closeForm"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { ChatAltIcon, PencilIcon, TrashIcon } from '@heroicons/vue/outline'
import ReviewCard from '@/entities/review/ui/ReviewCard/ReviewCard.vue'
import ReviewFormModal from '../ReviewFormModal/ReviewFormModal.vue'
import { reviewApi } from '@/entities/review/api/reviewApi'
import type { Review, ReviewFilters, ReviewsResponse } from '@/entities/review/model/types'
import { useToast } from '@/shared/ui/molecules/Toast/useToast'

interface Props {
  userId?: number
  canAddReview?: boolean
  canModerate?: boolean
  showStatusFilter?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  canAddReview: true,
  canModerate: false,
  showStatusFilter: false
})

const { showSuccess, showError } = useToast()

// Состояние
const reviews = ref<Review[]>([])
const meta = ref<ReviewsResponse['meta'] | null>(null)
const isLoading = ref(false)
const error = ref<string | null>(null)
const showAddForm = ref(false)
const editingReview = ref<Review | null>(null)

// Фильтры
const filters = ref<ReviewFilters>({
  user_id: props.userId
})

// Вычисляемые свойства
const canEditReview = computed(() => (review: Review) => {
  // Логика проверки прав на редактирование
  // Например, только автор или модератор
  return true // Заглушка
})

// Методы
const loadReviews = async (page = 1) => {
  isLoading.value = true
  error.value = null
  
  try {
    const response = props.userId 
      ? await reviewApi.getUserReviews(props.userId, { ...filters.value, page })
      : await reviewApi.getReviews({ ...filters.value, page })
    
    reviews.value = response.data
    meta.value = response.meta
  } catch (err) {
    error.value = 'Не удалось загрузить отзывы'
    console.error('Error loading reviews:', err)
  } finally {
    isLoading.value = false
  }
}

const editReview = (review: Review) => {
  editingReview.value = review
}

const deleteReview = async (review: Review) => {
  if (!confirm('Вы уверены, что хотите удалить этот отзыв?')) {
    return
  }
  
  try {
    await reviewApi.deleteReview(review.id)
    showSuccess('Отзыв удален')
    loadReviews()
  } catch (err) {
    showError('Не удалось удалить отзыв')
    console.error('Error deleting review:', err)
  }
}

const approveReview = async (review: Review) => {
  try {
    await reviewApi.approveReview(review.id)
    showSuccess('Отзыв одобрен')
    loadReviews()
  } catch (err) {
    showError('Не удалось одобрить отзыв')
    console.error('Error approving review:', err)
  }
}

const rejectReview = async (review: Review) => {
  try {
    await reviewApi.rejectReview(review.id)
    showSuccess('Отзыв отклонён')
    loadReviews()
  } catch (err) {
    showError('Не удалось отклонить отзыв')
    console.error('Error rejecting review:', err)
  }
}

const handleSaveReview = async (reviewData: any) => {
  try {
    if (editingReview.value) {
      await reviewApi.updateReview(editingReview.value.id, reviewData)
      showSuccess('Отзыв обновлён')
    } else {
      await reviewApi.createReview(reviewData)
      showSuccess('Отзыв добавлен')
    }
    closeForm()
    loadReviews()
  } catch (err) {
    showError('Не удалось сохранить отзыв')
    console.error('Error saving review:', err)
  }
}

const closeForm = () => {
  showAddForm.value = false
  editingReview.value = null
}

// Загрузка при монтировании
onMounted(() => {
  loadReviews()
})
</script>
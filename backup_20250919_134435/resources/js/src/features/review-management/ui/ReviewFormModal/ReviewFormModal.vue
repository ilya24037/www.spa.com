<template>
  <div class="fixed inset-0 z-50 overflow-y-auto">
    <!-- Оверлей -->
    <div 
      class="fixed inset-0 bg-black bg-opacity-50 transition-opacity"
      @click="$emit('close')"
    ></div>

    <!-- Модальное окно -->
    <div class="flex min-h-full items-center justify-center p-4">
      <div class="relative bg-white rounded-lg shadow-xl max-w-lg w-full p-6">
        <!-- Заголовок -->
        <div class="flex justify-between items-center mb-4">
          <h3 class="text-lg font-semibold">
            {{ review ? 'Редактировать отзыв' : 'Добавить отзыв' }}
          </h3>
          <button
            @click="$emit('close')"
            class="text-gray-400 hover:text-gray-600 transition-colors"
          >
            <XIcon class="w-6 h-6" />
          </button>
        </div>

        <!-- Форма -->
        <form @submit.prevent="handleSubmit" class="space-y-4">
          <!-- Рейтинг -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              Рейтинг
            </label>
            <div class="flex gap-2">
              <button
                v-for="star in 5"
                :key="star"
                type="button"
                @click="form.rating = star"
                :class="[
                  'p-1 transition-colors',
                  star <= form.rating ? 'text-yellow-400' : 'text-gray-300'
                ]"
              >
                <StarIcon class="w-8 h-8" fill="currentColor" />
              </button>
            </div>
            <span v-if="errors.rating" class="text-red-500 text-sm">
              {{ errors.rating }}
            </span>
          </div>

          <!-- Комментарий -->
          <div>
            <label for="comment" class="block text-sm font-medium text-gray-700 mb-2">
              Ваш отзыв
            </label>
            <textarea
              id="comment"
              v-model="form.comment"
              rows="4"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
              placeholder="Расскажите о вашем опыте..."
              required
            ></textarea>
            <span v-if="errors.comment" class="text-red-500 text-sm">
              {{ errors.comment }}
            </span>
          </div>

          <!-- Анонимность -->
          <div class="flex items-center">
            <input
              id="anonymous"
              v-model="form.is_anonymous"
              type="checkbox"
              class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
            >
            <label for="anonymous" class="ml-2 block text-sm text-gray-700">
              Оставить отзыв анонимно
            </label>
          </div>

          <!-- Кнопки -->
          <div class="flex justify-end gap-3 pt-4">
            <button
              type="button"
              @click="$emit('close')"
              class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors"
            >
              Отмена
            </button>
            <button
              type="submit"
              :disabled="isSubmitting"
              class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
            >
              {{ isSubmitting ? 'Сохранение...' : 'Сохранить' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, watch } from 'vue'
import { XIcon, StarIcon } from '@heroicons/vue/solid'
import type { Review } from '@/entities/review/model/types'

interface Props {
  review?: Review | null
}

const props = defineProps<Props>()
const emit = defineEmits<{
  save: [data: any]
  close: []
}>()

// Форма
const form = reactive({
  rating: props.review?.rating || 5,
  comment: props.review?.comment || '',
  is_anonymous: props.review?.is_anonymous || false
})

const errors = reactive({
  rating: '',
  comment: ''
})

const isSubmitting = ref(false)

// Наблюдаем за изменением review prop
watch(() => props.review, (newReview) => {
  if (newReview) {
    form.rating = newReview.rating
    form.comment = newReview.comment
    form.is_anonymous = newReview.is_anonymous
  }
})

// Валидация
const validate = (): boolean => {
  errors.rating = ''
  errors.comment = ''
  
  let isValid = true
  
  if (form.rating < 1 || form.rating > 5) {
    errors.rating = 'Выберите рейтинг от 1 до 5'
    isValid = false
  }
  
  if (!form.comment || form.comment.length < 10) {
    errors.comment = 'Отзыв должен содержать минимум 10 символов'
    isValid = false
  }
  
  if (form.comment.length > 1000) {
    errors.comment = 'Отзыв не должен превышать 1000 символов'
    isValid = false
  }
  
  return isValid
}

// Отправка формы
const handleSubmit = () => {
  if (!validate()) {
    return
  }
  
  isSubmitting.value = true
  
  emit('save', {
    rating: form.rating,
    comment: form.comment,
    is_anonymous: form.is_anonymous
  })
  
  // Сброс состояния будет происходить при закрытии модального окна
  setTimeout(() => {
    isSubmitting.value = false
  }, 1000)
}
</script>
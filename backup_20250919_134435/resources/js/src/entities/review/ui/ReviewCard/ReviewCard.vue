<template>
  <div class="bg-white border border-gray-200 rounded-lg p-4">
    <div class="flex items-start space-x-3">
      <!-- Аватар пользователя -->
      <div class="flex-shrink-0">
        <div class="w-10 h-10 bg-gray-300 rounded-full flex items-center justify-center">
          <span class="text-sm font-medium text-gray-700">
            {{ review.client_name?.charAt(0)?.toUpperCase() || 'У' }}
          </span>
        </div>
      </div>
      
      <!-- Контент отзыва -->
      <div class="flex-1 min-w-0">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm font-medium text-gray-900">
              {{ review.client_name || 'Анонимный пользователь' }}
            </p>
            <p class="text-xs text-gray-500">
              {{ formatDate(review.created_at) }}
            </p>
          </div>
          
          <!-- Рейтинг -->
          <div class="flex items-center">
            <div class="flex">
              <svg
                v-for="star in 5"
                :key="star"
                :class="[
                  'w-4 h-4',
                  star <= review.rating ? 'text-yellow-400' : 'text-gray-300'
                ]"
                fill="currentColor"
                viewBox="0 0 20 20"
              >
                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
              </svg>
            </div>
            <span class="ml-1 text-sm text-gray-600">{{ review.rating }}/5</span>
          </div>
        </div>
        
        <!-- Текст отзыва -->
        <div class="mt-2">
          <p 
            :class="[
              'text-gray-700',
              compact ? 'text-sm line-clamp-2' : 'text-base'
            ]"
          >
            {{ review.comment }}
          </p>
        </div>
        
        <!-- Дополнительная информация -->
        <div v-if="!compact && review.service_name" class="mt-2">
          <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
            {{ review.service_name }}
          </span>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
interface Review {
  id: number
  rating: number
  comment: string
  created_at: string
  client_name: string
  service_name?: string
  [key: string]: any
}

interface Props {
  review: Review
  compact?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  compact: false
})

// Функция форматирования даты
const formatDate = (dateString: string): string => {
  const date = new Date(dateString)
  const now = new Date()
  const diffTime = Math.abs(now.getTime() - date.getTime())
  const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24))
  
  if (diffDays === 1) {
    return 'Вчера'
  } else if (diffDays < 7) {
    return `${diffDays} дн. назад`
  } else if (diffDays < 30) {
    const weeks = Math.floor(diffDays / 7)
    return `${weeks} нед. назад`
  } else {
    return date.toLocaleDateString('ru-RU', {
      day: 'numeric',
      month: 'short',
      year: 'numeric'
    })
  }
}
</script>

<style scoped>
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>

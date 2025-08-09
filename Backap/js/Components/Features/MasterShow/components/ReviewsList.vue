<template>
  <div v-if="reviews.length" class="bg-white rounded-lg p-6 shadow-sm mt-6">
    <h2 class="text-xl font-bold text-gray-900 mb-4">Отзывы</h2>
    
    <div class="space-y-4">
      <div 
        v-for="review in reviews" 
        :key="review.id"
        class="border-b border-gray-200 pb-4 last:border-b-0"
      >
        <div class="flex items-center gap-3 mb-2">
          <div class="w-10 h-10 bg-gray-300 rounded-full flex items-center justify-center">
            {{ review.client_name?.charAt(0) || 'А' }}
          </div>
          <div>
            <div class="font-medium">{{ review.client_name || 'Анонимный клиент' }}</div>
            <div class="flex items-center gap-1">
              <StarIcon v-for="n in 5" :key="n" 
                :class="n <= review.rating ? 'text-yellow-400' : 'text-gray-300'"
                class="w-4 h-4" 
              />
            </div>
          </div>
        </div>
        <p class="text-gray-700">{{ review.comment }}</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { StarIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
  reviews: {
    type: Array,
    default: () => []
  }
})
</script>

<style scoped>
/* Стили для списка отзывов */
</style> 
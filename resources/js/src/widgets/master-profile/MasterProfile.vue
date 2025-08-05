<template>
  <div class="master-profile">
    <!-- Loading -->
    <div v-if="loading" class="animate-pulse">
      <div class="h-64 bg-gray-200 rounded-lg mb-6"></div>
      <div class="space-y-4">
        <div class="h-4 bg-gray-200 rounded w-3/4"></div>
        <div class="h-4 bg-gray-200 rounded w-1/2"></div>
      </div>
    </div>
    
    <!-- Profile -->
    <div v-else-if="master">
      <!-- Header -->
      <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
        <div class="flex flex-col md:flex-row gap-6">
          <div class="flex-shrink-0">
            <img
              :src="master.avatar || '/placeholder-avatar.jpg'"
              :alt="master.name"
              class="w-32 h-32 rounded-full object-cover"
            />
          </div>
          
          <div class="flex-1">
            <h1 class="text-2xl font-bold text-gray-900 mb-2">
              {{ master.name || 'Мастер' }}
            </h1>
            
            <p v-if="master.description" class="text-gray-600 mb-4">
              {{ master.description }}
            </p>
            
            <div class="flex flex-wrap gap-4">
              <span v-if="master.rating" class="flex items-center gap-1">
                <StarRating :rating="master.rating" :show-text="true" />
              </span>
              
              <span v-if="master.reviewsCount" class="text-gray-500">
                {{ master.reviewsCount }} отзывов
              </span>
            </div>
          </div>
        </div>
      </div>
      
      <!-- Content slots -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
          <slot name="services" />
          <slot name="gallery" />
          <slot name="reviews" />
        </div>
        
        <div class="space-y-6">
          <slot name="booking" />
          <slot name="contacts" />
        </div>
      </div>
    </div>
    
    <!-- Empty -->
    <div v-else class="text-center py-12">
      <p class="text-gray-500">Информация о мастере недоступна</p>
    </div>
  </div>
</template>

<script setup lang="ts">
import StarRating from '@/src/shared/ui/organisms/StarRating/StarRating.vue'

interface Master {
  id?: string | number
  name?: string
  avatar?: string
  description?: string
  rating?: number
  reviewsCount?: number
}

interface Props {
  master?: Master | null
  loading?: boolean
}

withDefaults(defineProps<Props>(), {
  master: null,
  loading: false
})
</script>
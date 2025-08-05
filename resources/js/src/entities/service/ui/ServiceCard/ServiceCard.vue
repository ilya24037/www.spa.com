<template>
  <article class="service-card bg-white rounded-lg shadow-sm hover:shadow-md transition-shadow p-4">
    <!-- Loading state -->
    <div v-if="loading" class="animate-pulse">
      <div class="h-4 bg-gray-200 rounded w-3/4 mb-2"></div>
      <div class="h-4 bg-gray-200 rounded w-1/2"></div>
    </div>
    
    <!-- Service content -->
    <div v-else-if="service">
      <h3 class="font-semibold text-gray-900 mb-2">
        {{ service.name || 'Услуга' }}
      </h3>
      
      <p v-if="service.description" class="text-sm text-gray-600 mb-3">
        {{ service.description }}
      </p>
      
      <div class="flex items-center justify-between">
        <span v-if="service.price" class="text-lg font-bold text-blue-600">
          {{ formatPrice(service.price) }}
        </span>
        
        <span v-if="service.duration" class="text-sm text-gray-500">
          {{ service.duration }} мин
        </span>
      </div>
      
      <slot name="actions" />
    </div>
    
    <!-- Empty state -->
    <div v-else class="text-gray-500 text-center py-4">
      Данные недоступны
    </div>
  </article>
</template>

<script setup lang="ts">
interface Service {
  id?: string | number
  name?: string
  description?: string
  price?: number
  duration?: number
}

interface Props {
  service?: Service | null
  loading?: boolean
}

withDefaults(defineProps<Props>(), {
  service: null,
  loading: false
})

const formatPrice = (price: number) => {
  return new Intl.NumberFormat('ru-RU', {
    style: 'currency',
    currency: 'RUB',
    minimumFractionDigits: 0
  }).format(price)
}
</script>
<template>
  <div v-if="services.length" class="bg-white rounded-lg p-6 shadow-sm mt-6">
    <h2 class="text-xl font-bold text-gray-900 mb-4">Услуги и цены</h2>
    
    <div class="space-y-4">
      <div 
        v-for="service in services" 
        :key="service.id"
        class="flex justify-between items-center p-4 border border-gray-200 rounded-lg"
      >
        <div>
          <h3 class="font-medium">{{ service.name }}</h3>
          <p class="text-sm text-gray-500">{{ service.duration }} мин</p>
        </div>
        <div class="text-right">
          <div class="font-bold text-lg">{{ formatPrice(service.price) }} ₽</div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  services: {
    type: Array,
    default: () => []
  }
})

// Метод форматирования цены
const formatPrice = (price) => {
  if (!price) return '0'
  return new Intl.NumberFormat('ru-RU').format(price)
}
</script>

<style scoped>
/* Стили для списка услуг */
</style> 
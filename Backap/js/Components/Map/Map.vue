<template>
  <div class="w-full h-full min-h-[200px] bg-gray-100 rounded-lg flex items-center justify-center relative overflow-hidden">
    <!-- Заглушка карты -->
    <div class="absolute inset-0 bg-gradient-to-br from-blue-50 to-blue-100"></div>
    
    <!-- Контент карты -->
    <div class="relative z-10 text-center">
      <svg class="mx-auto h-12 w-12 text-blue-400 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
            d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
            d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
      </svg>
      <h3 class="text-lg font-medium text-gray-800 mb-2">Карта мастеров</h3>
      <p class="text-sm text-gray-600">{{ cards.length }} мастеров в Москве</p>
    </div>
    
    <!-- Имитация маркеров на карте -->
    <div class="absolute inset-0 pointer-events-none">
      <div 
        v-for="(card, index) in cards.slice(0, 6)" 
        :key="card.id"
        class="absolute w-4 h-4 bg-red-500 rounded-full border-2 border-white shadow-md transform -translate-x-1/2 -translate-y-1/2"
        :style="getMarkerPosition(index)"
      >
        <div class="absolute -top-8 left-1/2 transform -translate-x-1/2 bg-white px-2 py-1 rounded shadow-md text-xs whitespace-nowrap opacity-0 group-hover:opacity-100 transition-opacity">
          {{ card.name }}
        </div>
      </div>
    </div>
    
    <!-- Кнопка центрирования -->
    <button class="absolute bottom-4 right-4 p-2 bg-white rounded-full shadow-md hover:shadow-lg transition-shadow">
      <svg class="w-5 h-5 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
            d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
            d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
      </svg>
    </button>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  cards: {
    type: Array,
    default: () => []
  }
})

// Генерируем случайные позиции для маркеров
const getMarkerPosition = (index) => {
  const positions = [
    { top: '25%', left: '30%' },
    { top: '40%', left: '60%' },
    { top: '30%', left: '45%' },
    { top: '55%', left: '35%' },
    { top: '45%', left: '70%' },
    { top: '60%', left: '55%' }
  ]
  
  return positions[index] || { top: '50%', left: '50%' }
}
</script>

<style scoped>
.group:hover .opacity-0 {
  opacity: 1;
}
</style>
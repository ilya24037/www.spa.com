<!-- resources/js/Components/Map/SimpleMap.vue -->
<template>
  <div 
    class="w-full bg-gray-100 rounded-lg overflow-hidden"
    :style="{ height: height + 'px' }"
  >
    <!-- Заглушка карты -->
    <div class="relative h-full">
      <!-- Фон карты -->
      <div class="absolute inset-0 bg-gradient-to-br from-blue-50 to-blue-100">
        <!-- Сетка для имитации карты -->
        <div class="h-full w-full opacity-10" style="
          background-image: 
            linear-gradient(0deg, #333 1px, transparent 1px),
            linear-gradient(90deg, #333 1px, transparent 1px);
          background-size: 50px 50px;
        "></div>
      </div>

      <!-- Маркеры -->
      <div class="absolute inset-0">
        <div
          v-for="(card, index) in visibleCards"
          :key="card.id"
          class="absolute transform -translate-x-1/2 -translate-y-1/2"
          :style="getMarkerPosition(index)"
        >
          <!-- Маркер -->
          <div class="relative">
            <div class="bg-blue-600 text-white px-3 py-1 rounded-full text-sm font-medium shadow-lg hover:bg-blue-700 cursor-pointer transition-colors">
              {{ formatPrice(card.min_price) }}
            </div>
            <!-- Стрелка -->
            <div class="absolute top-full left-1/2 transform -translate-x-1/2 -mt-1">
              <div class="w-0 h-0 border-l-4 border-r-4 border-t-4 border-transparent border-t-blue-600"></div>
            </div>
          </div>
        </div>
      </div>

      <!-- Контролы карты -->
      <div class="absolute top-4 right-4 flex flex-col gap-2">
        <button class="bg-white p-2 rounded shadow hover:bg-gray-50">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
          </svg>
        </button>
        <button class="bg-white p-2 rounded shadow hover:bg-gray-50">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4" />
          </svg>
        </button>
      </div>

      <!-- Кнопка "Показать списком" -->
      <div class="absolute bottom-4 left-4">
        <button class="bg-white px-4 py-2 rounded-lg shadow hover:bg-gray-50 text-sm font-medium">
          Показать списком
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  cards: {
    type: Array,
    default: () => []
  },
  center: {
    type: Object,
    default: () => ({ lat: 58.0105, lng: 56.2502 })
  },
  height: {
    type: Number,
    default: 400
  }
})

// Показываем максимум 10 маркеров
const visibleCards = computed(() => props.cards.slice(0, 10))

// Генерируем псевдослучайные позиции для маркеров
function getMarkerPosition(index) {
  const positions = [
    { top: '20%', left: '30%' },
    { top: '40%', left: '60%' },
    { top: '60%', left: '40%' },
    { top: '30%', left: '70%' },
    { top: '50%', left: '20%' },
    { top: '70%', left: '65%' },
    { top: '25%', left: '45%' },
    { top: '45%', left: '80%' },
    { top: '65%', left: '25%' },
    { top: '35%', left: '35%' },
  ]
  
  return positions[index] || { top: '50%', left: '50%' }
}

function formatPrice(price) {
  return price ? `${price} ₽` : 'Договорная'
}
</script>
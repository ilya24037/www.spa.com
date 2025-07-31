<template>
  <Card v-if="hasPromoContent" variant="elevated" class="bg-slate-50">
    <div class="flex items-center space-x-2 mb-4">
      <span class="text-lg">👁️</span>
      <span class="text-sm font-medium text-gray-800">
        Как это будет выглядеть в объявлении:
      </span>
    </div>
    
    <!-- Бейджи акций -->
    <div class="flex flex-wrap gap-3">
      <!-- Скидка -->
      <div v-if="discount" class="inline-flex items-center px-3 py-2 bg-red-500 text-white text-sm font-semibold rounded-lg">
        <span class="mr-1">🏷️</span>
        -{{ discount }}% новым клиентам
      </div>
      
      <!-- Подарок -->
      <div v-if="gift" class="inline-flex items-center px-3 py-2 bg-green-500 text-white text-sm font-medium rounded-lg">
        <span class="mr-1">🎁</span>
        {{ truncatedGift }}
      </div>
    </div>
    
    <!-- Статистика эффективности -->
    <div v-if="hasPromoContent" class="mt-4 pt-4 border-t border-gray-200">
      <div class="grid grid-cols-2 gap-4 text-center">
        <div>
          <div class="text-lg font-semibold text-green-600">+{{ expectedIncrease }}%</div>
          <div class="text-xs text-gray-600">больше откликов</div>
        </div>
        <div>
          <div class="text-lg font-semibold text-blue-600">{{ attractiveness }}/10</div>
          <div class="text-xs text-gray-600">привлекательность</div>
        </div>
      </div>
    </div>
  </Card>
</template>

<script setup>
import { computed } from 'vue'
import Card from '@/Components/UI/Cards/Card.vue'

const props = defineProps({
  discount: { type: [String, Number], default: '' },
  gift: { type: String, default: '' }
})

// Computed
const hasPromoContent = computed(() => {
  return props.discount || props.gift
})

const truncatedGift = computed(() => {
  if (!props.gift) return ''
  return props.gift.length > 30 ? props.gift.substring(0, 30) + '...' : props.gift
})

// Рассчитываем ожидаемое увеличение откликов
const expectedIncrease = computed(() => {
  let increase = 0
  
  if (props.discount) {
    const discountNum = parseInt(props.discount)
    increase += Math.min(discountNum * 1.5, 30) // До 30% за скидку
  }
  
  if (props.gift) {
    increase += 15 // +15% за подарок
  }
  
  return Math.round(increase)
})

// Оценка привлекательности (от 1 до 10)
const attractiveness = computed(() => {
  let score = 5 // Базовая оценка
  
  if (props.discount) {
    const discountNum = parseInt(props.discount)
    score += Math.min(discountNum / 5, 3) // До +3 за скидку
  }
  
  if (props.gift && props.gift.length > 10) {
    score += 2 // +2 за качественное описание подарка
  }
  
  return Math.min(Math.round(score), 10)
})
</script>
<!-- resources/js/Components/Cards/Cards.vue -->
<template>
  <div>
    <!-- Режим сетки -->
    <div 
      v-if="viewMode === 'grid'"
      class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4"
    >
      <MasterCard
        v-for="card in cards"
        :key="card.id"
        :master="card"
      />
    </div>

    <!-- Режим списка -->
    <div 
      v-else
      class="space-y-4"
    >
      <MasterCardList
        v-for="card in cards"
        :key="card.id"
        :master="card"
      />
    </div>

    <!-- Пустое состояние -->
    <div 
      v-if="cards.length === 0"
      class="text-center py-12"
    >
      <svg class="w-16 h-16 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
      </svg>
      <p class="text-gray-500">Мастера не найдены</p>
      <p class="text-sm text-gray-400 mt-2">Попробуйте изменить параметры поиска</p>
    </div>
  </div>
</template>

<script setup>
import MasterCard from './MasterCard.vue'
import MasterCardList from './MasterCardList.vue'

defineProps({
  cards: {
    type: Array,
    default: () => []
  },
  viewMode: {
    type: String,
    default: 'grid',
    validator: (value) => ['grid', 'list'].includes(value)
  }
})
</script>
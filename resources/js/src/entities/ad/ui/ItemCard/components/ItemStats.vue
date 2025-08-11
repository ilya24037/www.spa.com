<!-- ItemStats - статистика объявления -->
<template>
  <div class="item-stats">
    <div v-if="item.views" class="stat-item">
      <svg class="stat-icon" viewBox="0 0 16 16" fill="none">
        <path d="M1 8C1 8 3.5 3 8 3C12.5 3 15 8 15 8C15 8 12.5 13 8 13C3.5 13 1 8 1 8Z" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"/>
        <path d="M8 10C9.10457 10 10 9.10457 10 8C10 6.89543 9.10457 6 8 6C6.89543 6 6 6.89543 6 8C6 9.10457 6.89543 10 8 10Z" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"/>
      </svg>
      <span>{{ formatNumber(item.views) }}</span>
    </div>
    
    <div v-if="item.favorites" class="stat-item">
      <svg class="stat-icon" viewBox="0 0 16 16" fill="none">
        <path d="M8 14L1.5 7.5C0.5 6.5 0.5 5 1.5 4C2.5 3 4 3 5 4L8 7L11 4C12 3 13.5 3 14.5 4C15.5 5 15.5 6.5 14.5 7.5L8 14Z" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"/>
      </svg>
      <span>{{ formatNumber(item.favorites) }}</span>
    </div>
    
    <div v-if="item.messages" class="stat-item">
      <svg class="stat-icon" viewBox="0 0 16 16" fill="none">
        <path d="M14 10C14 10.5 13.5 11 13 11H4L1 14V2C1 1.5 1.5 1 2 1H13C13.5 1 14 1.5 14 2V10Z" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"/>
      </svg>
      <span>{{ formatNumber(item.messages) }}</span>
    </div>
    
    <div v-if="item.calls" class="stat-item">
      <svg class="stat-icon" viewBox="0 0 16 16" fill="none">
        <path d="M14.5 11V13C14.5 13.5 14 14 13.5 14C6.5 14 1 8.5 1 1.5C1 1 1.5 0.5 2 0.5H4C4.5 0.5 5 1 5 1.5C5 2.5 5.2 3.5 5.5 4.5C5.6 4.8 5.5 5.2 5.3 5.4L3.5 7.2C4.5 9.5 6.5 11.5 8.8 12.5L10.6 10.7C10.8 10.5 11.2 10.4 11.5 10.5C12.5 10.8 13.5 11 14.5 11Z" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"/>
      </svg>
      <span>{{ formatNumber(item.calls) }}</span>
    </div>
  </div>
</template>

<script setup lang="ts">
import type { AdItem } from '../ItemCard.types'

interface Props {
  item: AdItem
}

defineProps<Props>()

// Форматирование чисел
const formatNumber = (num: number) => {
  if (num >= 1000) {
    return (num / 1000).toFixed(1).replace('.0', '') + 'k'
  }
  return num.toString()
}
</script>

<style scoped>
.item-stats {
  display: flex;
  align-items: center;
  gap: 12px;
  font-size: 13px;
  color: #666;
}

.stat-item {
  display: flex;
  align-items: center;
  gap: 4px;
}

.stat-icon {
  width: 14px;
  height: 14px;
  flex-shrink: 0;
}
</style>
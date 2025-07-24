<template>
  <div class="item-stats-section">
    <!-- Счетчики -->
    <div v-if="item.status !== 'waiting_payment'" class="item-counters">
      <!-- Просмотры (глаз) -->
      <div class="counter-item">
        <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
          <path d="M8 3C4.5 3 1.73 5.11 1 8c.73 2.89 3.5 5 7 5s6.27-2.11 7-5c-.73-2.89-3.5-5-7-5zm0 8.5c-1.93 0-3.5-1.57-3.5-3.5S6.07 4.5 8 4.5s3.5 1.57 3.5 3.5S9.93 11.5 8 11.5zm0-5.5c-.83 0-1.5.67-1.5 1.5S7.17 9.5 8 9.5s1.5-.67 1.5-1.5S8.83 6 8 6z" fill="currentColor"/>
        </svg>
        <span class="counter-value">{{ item.views_count || 0 }}</span>
      </div>
      
      <!-- Подписчики (человек) -->
      <div class="counter-item">
        <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
          <path d="M8 8c1.66 0 3-1.34 3-3S9.66 2 8 2 5 3.34 5 5s1.34 3 3 3zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z" fill="currentColor"/>
        </svg>
        <span class="counter-value">{{ item.subscribers_count || 0 }}</span>
      </div>
      
      <!-- Избранное (сердце) -->
      <div class="counter-item">
        <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
          <path d="M8 14s6-4.5 6-8.5C14 3.5 12 2 10 2c-1 0-2 .5-2 1.5C8 2.5 7 2 6 2 4 2 2 3.5 2 5.5 2 9.5 8 14 8 14z" fill="currentColor"/>
        </svg>
        <span class="counter-value">{{ item.favorites_count || 0 }}</span>
      </div>
    </div>

    <!-- Время до окончания -->
    <div class="item-lifetime">
      <span v-if="item.status === 'waiting_payment'" class="lifetime-text text-gray-900">
        Не оплачено
      </span>
      <span v-else class="lifetime-text" :class="{ 'lifetime-warning': getDaysLeft() < 7 }">
        Осталось {{ getDaysLeft() }} дней
      </span>
    </div>

    <!-- Чаты -->
    <div class="item-chats">
      <svg width="16" height="16" viewBox="0 0 16 16" fill="none" class="chat-icon">
        <path d="M2 3h12c.6 0 1 .4 1 1v6c0 .6-.4 1-1 1h-3l-3 2v-2H2c-.6 0-1-.4-1-1V4c0-.6.4-1 1-1z" 
              stroke="currentColor" 
              stroke-width="1" 
              fill="none"/>
      </svg>
      <span class="chat-text">
        {{ item.new_messages_count > 0 ? `${item.new_messages_count} новых чатов` : 'Нет новых чатов' }}
      </span>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  item: {
    type: Object,
    required: true
  }
})

const getDaysLeft = () => {
  if (!props.item.expires_at) return 30
  
  const expiresAt = new Date(props.item.expires_at)
  const now = new Date()
  const diffTime = expiresAt - now
  const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24))
  
  return Math.max(0, diffDays)
}
</script>

<style scoped>
.item-stats-section {
  @apply flex flex-col gap-3;
}

.item-counters {
  @apply flex gap-4 text-sm text-gray-500;
}

.counter-item {
  @apply flex items-center gap-1;
}

.counter-value {
  @apply font-medium;
}

.item-lifetime {
  @apply text-sm;
}

.lifetime-text {
  @apply text-gray-600;
}

.lifetime-warning {
  @apply text-orange-600 font-medium;
}

.item-chats {
  @apply flex items-center gap-2 text-sm text-gray-600;
}

.chat-icon {
  @apply text-gray-400;
}

.chat-text {
  @apply text-sm;
}
</style>
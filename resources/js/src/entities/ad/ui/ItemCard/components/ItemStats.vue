<!-- ItemStats - статистика объявления для черновиков -->
<template>
  <div class="item-stats-section">
    <!-- Статус удаления для черновиков -->
    <div v-if="item.status === 'draft'" class="item-lifetime">
      <div class="draft-status">Удалится навсегда через {{ getDraftDaysLeft() }} {{ getDaysWord(getDraftDaysLeft()) }}</div>
    </div>

    <!-- Чаты с радио-кнопкой -->
    <div class="item-chats">
      <input 
        type="radio" 
        class="chat-radio"
        :checked="!item.new_messages_count"
        disabled
      />
      <span class="chat-text">
        {{ item.new_messages_count > 0 ? `${item.new_messages_count} новых чатов` : 'Нет новых чатов' }}
      </span>
    </div>
  </div>
</template>

<script setup lang="ts">
import type { AdItem } from '../ItemCard.types'

interface Props {
  item: AdItem
}

const props = defineProps<Props>()

// Вычисление дней до удаления черновика (30 дней с момента создания)
const getDraftDaysLeft = () => {
  if (!props.item.created_at) return 18 // По умолчанию как на скрине
  
  const createdAt = new Date(props.item.created_at)
  const deleteDate = new Date(createdAt)
  deleteDate.setDate(deleteDate.getDate() + 30) // Черновики хранятся 30 дней
  
  const now = new Date()
  const diffTime = deleteDate.getTime() - now.getTime()
  const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24))
  
  // Возвращаем количество дней до удаления
  return Math.max(0, Math.min(30, diffDays))
}

// Правильное склонение слова "день"
const getDaysWord = (days: number) => {
  const lastDigit = days % 10
  const lastTwoDigits = days % 100
  
  if (lastTwoDigits >= 11 && lastTwoDigits <= 14) {
    return 'дней'
  }
  
  if (lastDigit === 1) {
    return 'день'
  }
  
  if (lastDigit >= 2 && lastDigit <= 4) {
    return 'дня'
  }
  
  return 'дней'
}
</script>

<style scoped>
.item-stats-section {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.item-lifetime {
  font-size: 14px;
}

.draft-status {
  font-size: 14px;
  font-weight: 400;
  color: #000000;
}

.item-chats {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 14px;
  color: #000000;
}

.chat-radio {
  width: 16px;
  height: 16px;
  margin: 0;
  cursor: default;
}

.chat-text {
  font-weight: 400;
  color: #000000;
}
</style>
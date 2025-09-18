<!-- ItemStats - статистика объявления для черновиков -->
<template>
  <div class="item-stats-section">
    <!-- Статус модерации -->
    <div v-if="item.status === 'active' && !item.is_published" class="moderation-status">
      <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
        <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
          <path d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z"/>
        </svg>
        На проверке
      </span>
    </div>

    <!-- Статус активного объявления -->
    <div v-else-if="item.status === 'active' && item.is_published" class="active-status">
      <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
        <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
          <path d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"/>
        </svg>
        Активно
      </span>
    </div>

    <!-- Статус удаления для черновиков -->
    <div v-else-if="item.status === 'draft'" class="item-lifetime">
      <div class="draft-status">Удалится навсегда через {{ getDraftDaysLeft() }} {{ getDaysWord(getDraftDaysLeft()) }}</div>
    </div>

    <!-- Чаты с радио-кнопкой -->
    <div class="item-chats">
      <BaseRadio 
        :model-value="chatRadioValue"
        :value="true"
        :label="chatLabel"
        disabled
        class="chat-radio-wrapper"
      />
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import BaseRadio from '@/src/shared/ui/atoms/BaseRadio/BaseRadio.vue'
import type { AdItem } from '../ItemCard.types'

interface Props {
  item: AdItem
}

const props = defineProps<Props>()

// Значение для радио-кнопки (выбрана если нет новых сообщений)
const chatRadioValue = computed(() => !props.item.new_messages_count)

// Текст для радио-кнопки
const chatLabel = computed(() => {
  return props.item.new_messages_count > 0 
    ? `${props.item.new_messages_count} новых чатов` 
    : 'Нет новых чатов'
})

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

.moderation-status,
.active-status {
  margin-bottom: 4px;
}

.item-chats {
  display: flex;
  align-items: center;
}

/* Кастомизация обертки радио-кнопки */
.chat-radio-wrapper :deep(.radio-container) {
  padding: 0;
  cursor: default;
}

.chat-radio-wrapper :deep(.custom-radio) {
  width: 16px;
  height: 16px;
}

.chat-radio-wrapper :deep(.radio-dot) {
  width: 6px;
  height: 6px;
}

.chat-radio-wrapper :deep(.radio-label) {
  font-size: 14px;
  color: #000000;
  font-weight: 400;
}
</style>
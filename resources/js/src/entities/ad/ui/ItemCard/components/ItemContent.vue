<!-- ItemContent - содержимое карточки объявления -->
<template>
  <div class="item-content">
    <!-- Статус и ожидание оплаты -->
    <div v-if="item.status === 'inactive' || item.status === 'waiting_payment'" class="item-status-badge">
      <span v-if="item.status === 'waiting_payment'" class="badge-payment">
        Ожидает оплаты
      </span>
      <span v-else-if="item.status === 'inactive'" class="badge-inactive">
        Неактивно
      </span>
    </div>
    
    <!-- Заголовок -->
    <h3 class="item-title">
      {{ item.title || 'Без названия' }}
    </h3>
    
    <!-- Цена -->
    <div v-if="displayPrice" class="item-price">
      {{ formattedPrice }}
    </div>
    
    <!-- Локация -->
    <div v-if="displayLocation" class="item-location">
      <svg class="location-icon" viewBox="0 0 16 16" fill="none">
        <path d="M8 8.5C9.10457 8.5 10 7.60457 10 6.5C10 5.39543 9.10457 4.5 8 4.5C6.89543 4.5 6 5.39543 6 6.5C6 7.60457 6.89543 8.5 8 8.5Z"
              stroke="currentColor"
              stroke-linecap="round"
              stroke-linejoin="round"/>
        <path d="M8 1.5C4.5 1.5 2 4 2 7C2 10.5 8 14.5 8 14.5C8 14.5 14 10.5 14 7C14 4 11.5 1.5 8 1.5Z"
              stroke="currentColor"
              stroke-linecap="round"
              stroke-linejoin="round"/>
      </svg>
      <span>{{ displayLocation }}</span>
    </div>
    
    <!-- Дата -->
    <div class="item-date">
      {{ formatDate(item.created_at) }}
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import type { AdItem } from '../ItemCard.types'

interface Props {
  item: AdItem
  itemUrl: string
}

const props = defineProps<Props>()

// Форматирование цены
const displayPrice = computed(() => {
  if (props.item.price) {
    if (typeof props.item.price === 'object') {
      return props.item.price.amount
    }
    return props.item.price
  }
  return null
})

const formattedPrice = computed(() => {
  if (!displayPrice.value) return ''
  
  const price = displayPrice.value
  const formatted = new Intl.NumberFormat('ru-RU').format(price)
  
  if (typeof props.item.price === 'object' && props.item.price.period) {
    return `${formatted} ₽/${props.item.price.period}`
  }
  
  return `${formatted} ₽`
})

// Форматирование локации
const displayLocation = computed(() => {
  if (!props.item.location) return ''
  
  if (typeof props.item.location === 'string') {
    return props.item.location
  }
  
  const parts = []
  if (props.item.location.city) parts.push(props.item.location.city)
  if (props.item.location.metro) parts.push(`м. ${props.item.location.metro}`)
  if (props.item.location.district) parts.push(props.item.location.district)
  
  return parts.join(', ')
})

// Форматирование даты
const formatDate = (dateString?: string) => {
  if (!dateString) return ''
  
  const date = new Date(dateString)
  const now = new Date()
  const diffTime = Math.abs(now.getTime() - date.getTime())
  const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24))
  
  if (diffDays === 0) return 'Сегодня'
  if (diffDays === 1) return 'Вчера'
  if (diffDays < 7) return `${diffDays} дней назад`
  
  return date.toLocaleDateString('ru-RU', {
    day: 'numeric',
    month: 'long'
  })
}
</script>

<style scoped>
.item-content {
  padding: 12px 0;
}

.item-status-badge {
  margin-bottom: 8px;
}

.badge-payment,
.badge-inactive {
  display: inline-block;
  padding: 4px 8px;
  font-size: 12px;
  font-weight: 500;
  border-radius: 4px;
}

.badge-payment {
  background: #fff3cd;
  color: #856404;
}

.badge-inactive {
  background: #f8d7da;
  color: #721c24;
}

.item-title {
  font-size: 16px;
  font-weight: 500;
  color: #222;
  margin-bottom: 8px;
  line-height: 1.4;
  overflow: hidden;
  text-overflow: ellipsis;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
}

.item-price {
  font-size: 18px;
  font-weight: 600;
  color: #222;
  margin-bottom: 8px;
}

.item-location {
  display: flex;
  align-items: center;
  gap: 4px;
  font-size: 14px;
  color: #666;
  margin-bottom: 8px;
}

.location-icon {
  width: 14px;
  height: 14px;
  flex-shrink: 0;
}

.item-date {
  font-size: 13px;
  color: #999;
}
</style>
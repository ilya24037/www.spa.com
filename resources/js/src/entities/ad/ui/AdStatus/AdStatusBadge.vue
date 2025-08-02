<!-- resources/js/src/entities/ad/ui/AdStatus/AdStatusBadge.vue -->
<template>
  <div :class="containerClasses">
    <AdStatus 
      :status="status" 
      :show-icon="showIcon"
      :size="size"
    />
    
    <!-- Дополнительная информация -->
    <div v-if="showDetails" :class="detailsClasses">
      <!-- Дата истечения -->
      <span v-if="expiresAt && (status === 'active' || status === 'waiting_payment')" :class="expiresClasses">
        <svg :class="clockIconClasses" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        до {{ formatExpiryDate(expiresAt) }}
      </span>
      
      <!-- Счетчик просмотров -->
      <span v-if="views && status === 'active'" :class="viewsClasses">
        <svg :class="eyeIconClasses" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
        </svg>
        {{ views }}
      </span>
      
      <!-- Причина отклонения -->
      <span v-if="rejectionReason && (status === 'rejected' || status === 'blocked')" :class="rejectionClasses">
        {{ rejectionReason }}
      </span>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import AdStatus from './AdStatus.vue'
import dayjs from 'dayjs'
import 'dayjs/locale/ru'

dayjs.locale('ru')

// 🎯 Стили согласно дизайн-системе
const CONTAINER_BASE_CLASSES = 'flex flex-col gap-1'
const DETAILS_CLASSES = 'flex items-center gap-2 text-xs text-gray-500'
const EXPIRES_CLASSES = 'flex items-center gap-0.5'
const VIEWS_CLASSES = 'flex items-center gap-0.5'
const REJECTION_CLASSES = 'text-red-600 font-medium'
const ICON_CLASSES = 'w-3 h-3'

const props = defineProps({
  status: {
    type: String,
    required: true
  },
  showIcon: {
    type: Boolean,
    default: true
  },
  size: {
    type: String,
    default: 'sm'
  },
  showDetails: {
    type: Boolean,
    default: false
  },
  expiresAt: {
    type: [String, Date],
    default: null
  },
  views: {
    type: Number,
    default: null
  },
  rejectionReason: {
    type: String,
    default: null
  },
  layout: {
    type: String,
    default: 'column',
    validator: (value) => ['column', 'row'].includes(value)
  }
})

// Вычисляемые свойства
const containerClasses = computed(() => {
  const layoutClasses = {
    column: 'flex flex-col gap-1',
    row: 'flex items-center gap-2'
  }
  
  return [
    CONTAINER_BASE_CLASSES,
    layoutClasses[props.layout]
  ].join(' ')
})

const detailsClasses = computed(() => {
  if (props.layout === 'row') {
    return 'flex items-center gap-2 text-xs text-gray-500'
  }
  return DETAILS_CLASSES
})

const expiresClasses = computed(() => EXPIRES_CLASSES)
const viewsClasses = computed(() => VIEWS_CLASSES)
const rejectionClasses = computed(() => REJECTION_CLASSES)
const clockIconClasses = computed(() => ICON_CLASSES)
const eyeIconClasses = computed(() => ICON_CLASSES)

// Методы
const formatExpiryDate = (date) => {
  if (!date) return ''
  
  const expiryDate = dayjs(date)
  const now = dayjs()
  const diffDays = expiryDate.diff(now, 'day')
  
  if (diffDays === 0) {
    return 'сегодня'
  } else if (diffDays === 1) {
    return 'завтра'
  } else if (diffDays < 7) {
    return `${diffDays} дн.`
  } else {
    return expiryDate.format('DD.MM')
  }
}
</script>
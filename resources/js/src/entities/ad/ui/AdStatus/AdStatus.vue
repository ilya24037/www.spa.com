<!-- resources/js/src/entities/ad/ui/AdStatus/AdStatus.vue -->
<template>
  <span :class="statusClasses" v-if="status">
    <!-- Иконка -->
    <svg 
      v-if="showIcon && status === 'draft'" 
      :class="iconClasses" 
      fill="none" 
      stroke="currentColor" 
      viewBox="0 0 24 24"
    >
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
    </svg>
    
    <svg 
      v-if="showIcon && status === 'waiting_payment'" 
      :class="iconClasses" 
      fill="none" 
      stroke="currentColor" 
      viewBox="0 0 24 24"
    >
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
    </svg>
    
    <svg 
      v-if="showIcon && status === 'active'" 
      :class="iconClasses" 
      fill="none" 
      stroke="currentColor" 
      viewBox="0 0 24 24"
    >
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
    </svg>
    
    <svg 
      v-if="showIcon && status === 'archived'" 
      :class="iconClasses" 
      fill="none" 
      stroke="currentColor" 
      viewBox="0 0 24 24"
    >
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/>
    </svg>
    
    <svg 
      v-if="showIcon && status === 'expired'" 
      :class="iconClasses" 
      fill="none" 
      stroke="currentColor" 
      viewBox="0 0 24 24"
    >
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
    </svg>
    
    <svg 
      v-if="showIcon && (status === 'rejected' || status === 'blocked')" 
      :class="iconClasses" 
      fill="none" 
      stroke="currentColor" 
      viewBox="0 0 24 24"
    >
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
    </svg>

    {{ statusLabel }}
  </span>
</template>

<script setup>
import { computed } from 'vue'

// 📊 Статусы объявлений (из Laravel Enum)
const AD_STATUSES = {
  draft: {
    label: 'Черновик',
    color: 'bg-gray-100 text-gray-800'
  },
  waiting_payment: {
    label: 'Ждет оплаты',
    color: 'bg-amber-100 text-amber-800'
  },
  active: {
    label: 'Активное',
    color: 'bg-green-100 text-green-800'
  },
  archived: {
    label: 'В архиве',
    color: 'bg-gray-100 text-gray-600'
  },
  expired: {
    label: 'Истекло',
    color: 'bg-red-100 text-red-800'
  },
  rejected: {
    label: 'Отклонено',
    color: 'bg-red-100 text-red-800'
  },
  blocked: {
    label: 'Заблокировано',
    color: 'bg-red-200 text-red-900'
  }
}

const props = defineProps({
  status: {
    type: String,
    required: true,
    validator: (value) => Object.keys(AD_STATUSES).includes(value)
  },
  showIcon: {
    type: Boolean,
    default: true
  },
  size: {
    type: String,
    default: 'sm',
    validator: (value) => ['xs', 'sm', 'md'].includes(value)
  }
})

// Вычисляемые свойства
const statusConfig = computed(() => AD_STATUSES[props.status] || AD_STATUSES.draft)

const statusLabel = computed(() => statusConfig.value.label)

const statusClasses = computed(() => {
  const baseClasses = 'inline-flex items-center gap-1 rounded-full font-medium'
  
  const sizeClasses = {
    xs: 'px-1.5 py-0.5 text-xs',
    sm: 'px-2 py-1 text-xs', 
    md: 'px-2.5 py-1.5 text-sm'
  }
  
  return [
    baseClasses,
    statusConfig.value.color,
    sizeClasses[props.size]
  ].join(' ')
})

const iconClasses = computed(() => {
  const iconSizes = {
    xs: 'w-2.5 h-2.5',
    sm: 'w-3 h-3',
    md: 'w-4 h-4'
  }
  
  return iconSizes[props.size]
})
</script>
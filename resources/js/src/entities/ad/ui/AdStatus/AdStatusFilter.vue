<!-- resources/js/src/entities/ad/ui/AdStatus/AdStatusFilter.vue -->
<template>
  <div :class="containerClasses">
    <label v-if="label" :class="labelClasses">{{ label }}</label>
    
    <div :class="filtersClasses">
      <!-- Кнопка "Все" -->
      <button
        @click="selectStatus(null)"
        :class="getFilterButtonClasses(null)"
      >
        <span>Все</span>
        <span v-if="counts && counts.total" :class="countClasses">
          {{ counts.total }}
        </span>
      </button>
      
      <!-- Кнопки статусов -->
      <button
        v-for="(statusData, statusKey) in filteredStatuses"
        :key="statusKey"
        @click="selectStatus(statusKey)"
        :class="getFilterButtonClasses(statusKey)"
      >
        <AdStatus 
          :status="statusKey" 
          :show-icon="showIcons"
          size="xs"
        />
        <span v-if="counts && counts[statusKey]" :class="countClasses">
          {{ counts[statusKey] }}
        </span>
      </button>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import AdStatus from './AdStatus.vue'

// 🎯 Стили согласно дизайн-системе
const CONTAINER_CLASSES = 'space-y-2'
const LABEL_CLASSES = 'block text-sm font-medium text-gray-700'
const FILTERS_CLASSES = 'flex flex-wrap gap-2'
const BUTTON_BASE_CLASSES = 'inline-flex items-center gap-2 px-3 py-1.5 rounded-lg text-sm font-medium transition-colors'
const BUTTON_ACTIVE_CLASSES = 'bg-blue-100 text-blue-800 border border-blue-200'
const BUTTON_INACTIVE_CLASSES = 'bg-white text-gray-600 border border-gray-200 hover:bg-gray-50'
const COUNT_CLASSES = 'text-xs bg-gray-100 text-gray-600 px-1.5 py-0.5 rounded-full min-w-[20px] text-center'

// 📊 Все доступные статусы (из Laravel Enum)
const ALL_STATUSES = {
  draft: { label: 'Черновик', order: 1 },
  waiting_payment: { label: 'Ждет оплаты', order: 2 },
  active: { label: 'Активное', order: 3 },
  archived: { label: 'В архиве', order: 4 },
  expired: { label: 'Истекло', order: 5 },
  rejected: { label: 'Отклонено', order: 6 },
  blocked: { label: 'Заблокировано', order: 7 }
}

const props = defineProps({
  modelValue: {
    type: [String, null],
    default: null
  },
  label: {
    type: String,
    default: ''
  },
  showIcons: {
    type: Boolean,
    default: true
  },
  allowedStatuses: {
    type: Array,
    default: () => Object.keys(ALL_STATUSES)
  },
  counts: {
    type: Object,
    default: () => ({})
  },
  hideEmptyStatuses: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['update:modelValue', 'change'])

// Вычисляемые свойства
const containerClasses = computed(() => CONTAINER_CLASSES)
const labelClasses = computed(() => LABEL_CLASSES)
const filtersClasses = computed(() => FILTERS_CLASSES)
const countClasses = computed(() => COUNT_CLASSES)

const filteredStatuses = computed(() => {
  let statuses = {}
  
  // Фильтруем по разрешенным статусам
  props.allowedStatuses.forEach(statusKey => {
    if (ALL_STATUSES[statusKey]) {
      statuses[statusKey] = ALL_STATUSES[statusKey]
    }
  })
  
  // Скрываем пустые статусы если нужно
  if (props.hideEmptyStatuses && props.counts) {
    statuses = Object.fromEntries(
      Object.entries(statuses).filter(([key]) => 
        props.counts[key] && props.counts[key] > 0
      )
    )
  }
  
  // Сортируем по порядку
  return Object.fromEntries(
    Object.entries(statuses).sort(([,a], [,b]) => a.order - b.order)
  )
})

// Методы
const selectStatus = (status) => {
  emit('update:modelValue', status)
  emit('change', status)
}

const getFilterButtonClasses = (status) => {
  const isActive = props.modelValue === status
  
  return [
    BUTTON_BASE_CLASSES,
    isActive ? BUTTON_ACTIVE_CLASSES : BUTTON_INACTIVE_CLASSES
  ].join(' ')
}
</script>
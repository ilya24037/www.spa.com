<!-- resources/js/src/features/masters-filter/ui/FilterPrice/FilterPrice.vue -->
<template>
  <div :class="CONTAINER_CLASSES">
    <h4 :class="TITLE_CLASSES">Стоимость</h4>
    
    <div :class="INPUTS_CONTAINER_CLASSES">
      <div :class="INPUT_GROUP_CLASSES">
        <label :class="LABEL_CLASSES">От</label>
        <input
          :value="from"
          @input="emit('update:from', Number(($event.target as HTMLInputElement).value) || null)"
          type="number"
          min="0"
          placeholder="0"
          :class="INPUT_CLASSES"
        >
        <span :class="CURRENCY_CLASSES">₽</span>
      </div>
      
      <div :class="INPUT_GROUP_CLASSES">
        <label :class="LABEL_CLASSES">До</label>
        <input
          :value="to"
          @input="emit('update:to', Number(($event.target as HTMLInputElement).value) || null)"
          type="number"
          min="0"
          placeholder="10000"
          :class="INPUT_CLASSES"
        >
        <span :class="CURRENCY_CLASSES">₽</span>
      </div>
    </div>

    <!-- Быстрые кнопки -->
    <div :class="QUICK_BUTTONS_CONTAINER_CLASSES">
      <button
        v-for="range in priceRanges"
        :key="range.label"
        @click="selectRange(range)"
        :class="getQuickButtonClasses(range)"
      >
        {{ range.label }}
      </button>
    </div>

    <!-- Показать текущий диапазон -->
    <div v-if="hasSelection" :class="SELECTION_DISPLAY_CLASSES">
      <span :class="SELECTION_TEXT_CLASSES">
        {{ formatPriceRange }}
      </span>
      <button
        @click="clearSelection"
        :class="CLEAR_SELECTION_BUTTON_CLASSES"
      >
        ✕
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'

// 🎯 Стили согласно дизайн-системе
const CONTAINER_CLASSES = 'space-y-3'
const TITLE_CLASSES = 'font-medium text-gray-900'
const INPUTS_CONTAINER_CLASSES = 'grid grid-cols-2 gap-3'
const INPUT_GROUP_CLASSES = 'relative'
const LABEL_CLASSES = 'text-xs text-gray-600 mb-1 block'
const INPUT_CLASSES = 'w-full pr-6 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm'
const CURRENCY_CLASSES = 'absolute right-2 top-1/2 -translate-y-1/2 text-xs text-gray-500 mt-3'
const QUICK_BUTTONS_CONTAINER_CLASSES = 'flex flex-wrap gap-2'
const QUICK_BUTTON_BASE_CLASSES = 'px-3 py-1.5 text-xs rounded-full border transition-colors'
const QUICK_BUTTON_INACTIVE_CLASSES = 'border-gray-200 text-gray-600 hover:border-gray-300 hover:bg-gray-50'
const QUICK_BUTTON_ACTIVE_CLASSES = 'border-blue-500 bg-blue-50 text-blue-700'
const SELECTION_DISPLAY_CLASSES = 'flex items-center justify-between p-2 bg-blue-50 border border-blue-200 rounded-lg'
const SELECTION_TEXT_CLASSES = 'text-sm text-blue-700 font-medium'
const CLEAR_SELECTION_BUTTON_CLASSES = 'text-blue-600 hover:text-blue-800 font-medium'

// TypeScript интерфейсы
interface FilterPriceProps {
  from?: number | null
  to?: number | null
}

interface PriceRange {
  label: string
  from: number | null
  to: number | null
}

const props = withDefaults(defineProps<FilterPriceProps>(), {
  from: null,
  to: null
});

// TypeScript типизация emits
const emit = defineEmits<{
  'update:from': [value: number | null]
  'update:to': [value: number | null]
}>()

// Предустановленные диапазоны цен
const priceRanges: PriceRange[] = [
  { label: 'До 2000', from: null, to: 2000 },
  { label: '2000-3000', from: 2000, to: 3000 },
  { label: '3000-5000', from: 3000, to: 5000 },
  { label: '5000-8000', from: 5000, to: 8000 },
  { label: 'От 8000', from: 8000, to: null }
]

// Вычисляемые свойства
const hasSelection = computed(() => props.from !== null || props.to !== null)

const formatPriceRange = computed(() => {
  if (props.from && props.to) {
    return `${props.from.toLocaleString()} - ${props.to.toLocaleString()} ₽`
  } else if (props.from) {
    return `От ${props.from.toLocaleString()} ₽`
  } else if (props.to) {
    return `До ${props.to.toLocaleString()} ₽`
  }
  return ''
})

// Методы
const getQuickButtonClasses = (range: PriceRange): string => {
  const isActive = props.from === range.from && props.to === range.to
  
  return [
    QUICK_BUTTON_BASE_CLASSES,
    isActive ? QUICK_BUTTON_ACTIVE_CLASSES : QUICK_BUTTON_INACTIVE_CLASSES
  ].join(' ')
}

const selectRange = (range: PriceRange): void => {
  emit('update:from', range.from)
  emit('update:to', range.to)
}

const clearSelection = (): void => {
  emit('update:from', null)
  emit('update:to', null)
}
</script>
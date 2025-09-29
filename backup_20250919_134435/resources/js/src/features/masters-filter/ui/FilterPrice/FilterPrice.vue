<!-- Фильтр по цене -->
<template>
  <div class="space-y-4">
    <!-- Заголовок с символом рубля -->
    <div class="text-base font-semibold text-gray-900">
      Цена, ₽
    </div>
    
    <!-- Поля ввода -->
    <div class="flex items-center gap-2">
      <input
        :value="from"
        type="number"
        min="0"
        placeholder="58"
        class="flex-1 min-w-0 px-2 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm"
        @input="emit('update:from', Number(($event.target as HTMLInputElement).value) || null)"
      >
      
      <span class="text-gray-400 flex-shrink-0">—</span>
      
      <input
        :value="to"
        type="number"
        min="0"
        placeholder="167037"
        class="flex-1 min-w-0 px-2 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm"
        @input="emit('update:to', Number(($event.target as HTMLInputElement).value) || null)"
      >
    </div>

    <!-- Быстрые кнопки -->
    <div class="flex flex-wrap gap-2">
      <button
        v-for="range in priceRanges"
        :key="range.label"
        :class="getQuickButtonClasses(range)"
        @click="selectRange(range)"
      >
        {{ range.label }}
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'

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
    const baseClasses = 'px-3 py-1.5 text-xs rounded-full border transition-colors'
    const activeClasses = isActive 
        ? 'border-blue-500 bg-blue-50 text-blue-700' 
        : 'border-gray-300 text-gray-600 hover:border-gray-400 hover:bg-gray-50'
    
    return `${baseClasses} ${activeClasses}`
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
<!-- Кнопка каталога в стиле Ozon -->
<template>
  <button 
    type="button"
    :disabled="disabled"
    :class="buttonClasses"
    @click="handleClick"
    aria-label="Открыть каталог"
    :aria-expanded="isOpen"
    aria-haspopup="true"
  >
    <!-- Иконка меню -->
    <svg 
      class="w-5 h-5 flex-shrink-0" 
      fill="none" 
      stroke="currentColor" 
      viewBox="0 0 24 24"
      aria-hidden="true"
    >
      <path 
        stroke-linecap="round" 
        stroke-linejoin="round" 
        stroke-width="2" 
        d="M4 6h16M4 12h16M4 18h16"
      />
    </svg>
    
    <!-- Текст кнопки (скрывается на мобильных) -->
    <span v-if="showText" class="hidden sm:inline whitespace-nowrap">
      {{ text }}
    </span>
    
    <!-- Иконка стрелки -->
    <svg 
      v-if="showArrow"
      :class="arrowClasses"
      class="w-3 h-3 opacity-80 transition-transform duration-200" 
      fill="currentColor" 
      viewBox="0 0 24 24"
      aria-hidden="true"
    >
      <path d="M7 10l5 5 5-5H7z"/>
    </svg>
  </button>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import type { CatalogButtonProps } from '../../model/search.types'

// Props
const props = withDefaults(defineProps<CatalogButtonProps>(), {
  text: 'Каталог',
  isOpen: false,
  disabled: false,
  showText: true,
  showArrow: true
})

// Emits
const emit = defineEmits<{
  (event: 'click'): void
  (event: 'toggle'): void
}>()

// Computed
const buttonClasses = computed(() => {
  const base = [
    'flex items-center gap-1 h-full px-3',
    'bg-blue-600 text-white',
    'text-sm font-medium',
    'transition-colors',
    'whitespace-nowrap',
    'border-r border-blue-700'
  ]

  if (props.disabled) {
    base.push('opacity-50 cursor-not-allowed')
  } else {
    base.push('hover:bg-blue-700 active:bg-blue-800')
  }

  if (props.isOpen) {
    base.push('bg-blue-700')
  }

  return base.join(' ')
})

const arrowClasses = computed(() => {
  return props.isOpen ? 'rotate-180' : ''
})

// Methods
const handleClick = () => {
  if (!props.disabled) {
    emit('click')
    emit('toggle')
  }
}
</script>
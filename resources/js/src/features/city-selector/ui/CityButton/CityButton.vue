<!-- РљРЅРѕРїРєР° РІС‹Р±РѕСЂР° РіРѕСЂРѕРґР° -->
<template>
  <button
    @click="handleClick"
    :disabled="disabled"
    :class="buttonClasses"
    :aria-pressed="isSelected"
    :aria-label="buttonAriaLabel"
  >
    <!-- РќР°Р·РІР°РЅРёРµ РіРѕСЂРѕРґР° СЃ РїРѕРґСЃРІРµС‚РєРѕР№ -->
    <span class="font-medium truncate">
      <template v-if="highlightQuery">
        <span v-html="highlightedName"></span>
      </template>
      <template v-else>
        {{ city.name }}
      </template>
    </span>

    <!-- Р РµРіРёРѕРЅ (РµСЃР»Рё РїРѕРєР°Р·С‹РІР°РµС‚СЃСЏ) -->
    <span 
      v-if="showRegion && city.region" 
      class="text-xs text-gray-500 truncate mt-0.5"
      :title="city.region"
    >
      {{ city.region }}
    </span>

    <!-- РРЅРґРёРєР°С‚РѕСЂ РІС‹Р±СЂР°РЅРЅРѕРіРѕ РіРѕСЂРѕРґР° -->
    <div
      v-if="isSelected"
      class="absolute top-1 right-1 w-2 h-2 bg-blue-600 rounded-full"
      aria-hidden="true"
    ></div>

    <!-- РРЅРґРёРєР°С‚РѕСЂ РїРѕРїСѓР»СЏСЂРЅРѕСЃС‚Рё -->
    <div
      v-if="city.isPopular && showPopularBadge"
      class="absolute top-1 left-1 w-1.5 h-1.5 bg-yellow-400 rounded-full"
      title="РџРѕРїСѓР»СЏСЂРЅС‹Р№ РіРѕСЂРѕРґ"
      aria-hidden="true"
    ></div>
  </button>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import type { City } from '../../model/city.store'

// TypeScript РёРЅС‚РµСЂС„РµР№СЃС‹
interface Props {
  city: City
  isSelected?: boolean
  disabled?: boolean
  showRegion?: boolean
  showPopularBadge?: boolean
  highlightQuery?: string
  variant?: 'default' | 'compact'
}

const props = withDefaults(defineProps<Props>(), {
  isSelected: false,
  disabled: false,
  showRegion: true,
  showPopularBadge: false,
  highlightQuery: '',
  variant: 'default'
})

// TypeScript С‚РёРїРёР·Р°С†РёСЏ emits
const emit = defineEmits<{
  'click': [city: City]
}>()

// Computed properties
const buttonClasses = computed(() => [
  'relative w-full p-3 text-left rounded-lg border transition-colors duration-200',
  'focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2',
  {
    // РћР±С‹С‡РЅРѕРµ СЃРѕСЃС‚РѕСЏРЅРёРµ
    'border-gray-200 bg-white text-gray-700 hover:bg-gray-50 hover:border-gray-300': 
      !props.isSelected && !props.disabled,
    
    // Р’С‹Р±СЂР°РЅРЅРѕРµ СЃРѕСЃС‚РѕСЏРЅРёРµ
    'border-blue-500 bg-blue-50 text-blue-900 shadow-sm': 
      props.isSelected && !props.disabled,
    
    // РћС‚РєР»СЋС‡РµРЅРЅРѕРµ СЃРѕСЃС‚РѕСЏРЅРёРµ
    'opacity-50 cursor-not-allowed border-gray-200 bg-gray-50 text-gray-400': 
      props.disabled,
    
    // РљРѕРјРїР°РєС‚РЅС‹Р№ РІР°СЂРёР°РЅС‚
    'p-2': props.variant === 'compact',
    
    // РћР±С‹С‡РЅС‹Р№ РІР°СЂРёР°РЅС‚
    'p-3': props.variant === 'default'
  }
])

const highlightedName = computed(() => {
  if (!props.highlightQuery) return props.city.name
  
  const query = props.highlightQuery.toLowerCase()
  const name = props.city.name
  const index = name.toLowerCase().indexOf(query)
  
  if (index === -1) return name
  
  const before = name.substring(0, index)
  const match = name.substring(index, index + query.length)
  const after = name.substring(index + query.length)
  
  return `${before}<mark class="bg-yellow-200 font-medium">${match}</mark>${after}`
})

const buttonAriaLabel = computed(() => {
  let label = `Р’С‹Р±СЂР°С‚СЊ РіРѕСЂРѕРґ ${props.city.name}`
  
  if (props.city.region) {
    label += `, ${props.city.region}`
  }
  
  if (props.isSelected) {
    label += ', РІС‹Р±СЂР°РЅРѕ'
  }
  
  if (props.city.isPopular) {
    label += ', РїРѕРїСѓР»СЏСЂРЅС‹Р№'
  }
  
  return label
})

// Methods
const handleClick = (): void => {
  if (!props.disabled) {
    emit('click', props.city)
  }
}
</script>

<style scoped>
/* РђРЅРёРјР°С†РёРё РґР»СЏ hover СЌС„С„РµРєС‚РѕРІ */
.city-button-hover {
  transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
}

.city-button-hover:hover {
  transform: translateY(-1px);
  box-shadow: 0 4px 12px -2px rgba(0, 0, 0, 0.1);
}

/* РџРѕРґСЃРІРµС‚РєР° РїРѕРёСЃРєР° */
:deep(mark) {
  background-color: theme('colors.yellow.200');
  font-weight: 600;
  padding: 0 1px;
  border-radius: 2px;
}

/* РРЅРґРёРєР°С‚РѕСЂС‹ */
.popular-indicator {
  background: linear-gradient(135deg, #fbbf24, #f59e0b);
}

.selected-indicator {
  background: linear-gradient(135deg, #3b82f6, #1d4ed8);
}

/* Responsive adjustments */
@media (max-width: 640px) {
  .text-xs {
    font-size: 0.75rem;
    line-height: 1rem;
  }
}

/* Accessibility improvements */
@media (prefers-reduced-motion: reduce) {
  .city-button-hover {
    transition: none;
  }
  
  .city-button-hover:hover {
    transform: none;
    box-shadow: none;
  }
}

/* High contrast mode support */
@media (prefers-contrast: high) {
  .border-gray-200 {
    border-color: #000;
  }
  
  .bg-blue-50 {
    background-color: #e6f2ff;
  }
  
  :deep(mark) {
    background-color: #ffff00;
    color: #000;
  }
}

/* Dark mode РїРѕРґРґРµСЂР¶РєР° */
@media (prefers-color-scheme: dark) {
  .bg-white {
    background-color: theme('colors.gray-800');
  }
  
  .text-gray-700 {
    color: theme('colors.gray-200');
  }
  
  .border-gray-200 {
    border-color: theme('colors.gray.600');
  }
  
  .hover\:bg-gray-50:hover {
    background-color: theme('colors.gray-700');
  }
  
  .bg-blue-50 {
    background-color: theme('colors.blue.900');
  }
  
  .text-blue-900 {
    color: theme('colors.blue.100');
  }
}
</style>


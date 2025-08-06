<!-- РЎС‡РµС‚С‡РёРє СЃСЂР°РІРЅРµРЅРёСЏ - FSD Feature -->
<template>
  <div class="relative">
    <!-- РћСЃРЅРѕРІРЅР°СЏ РєРЅРѕРїРєР° -->
    <component 
      :is="linkComponent"
      :href="href"
      @click="handleClick"
      @mouseenter="handleMouseEnter"
      @mouseleave="handleMouseLeave"
      :disabled="disabled"
      :class="buttonClasses"
      :aria-label="buttonAriaLabel"
    >
      <!-- РРєРѕРЅРєР° СЃСЂР°РІРЅРµРЅРёСЏ -->
      <svg 
        :class="compareIconClasses" 
        fill="none" 
        stroke="currentColor" 
        viewBox="0 0 24 24"
        aria-hidden="true"
      >
        <path 
          stroke-linecap="round" 
          stroke-linejoin="round" 
          stroke-width="2" 
          d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"
        />
      </svg>
      
      <!-- РЎС‡РµС‚С‡РёРє -->
      <Transition
        enter-active-class="transition-all duration-200 ease-out"
        enter-from-class="scale-0 opacity-0"
        enter-to-class="scale-100 opacity-100"
        leave-active-class="transition-all duration-150 ease-in"
        leave-from-class="scale-100 opacity-100"
        leave-to-class="scale-0 opacity-0"
      >
        <span 
          v-if="displayCount > 0"
          :class="badgeClasses"
          :aria-label="`${displayCount} СЌР»РµРјРµРЅС‚РѕРІ РІ СЃСЂР°РІРЅРµРЅРёРё`"
        >
          {{ formattedCount }}
        </span>
      </Transition>
      
      <!-- РўРµРєСЃС‚ (РµСЃР»Рё РЅРµ compact) -->
      <span 
        v-if="!compact && showText"
        :class="textClasses"
      >
        {{ buttonText }}
      </span>
      
      <!-- РРЅРґРёРєР°С‚РѕСЂ Р·Р°РіСЂСѓР·РєРё -->
      <div
        v-if="isLoading"
        class="absolute inset-0 flex items-center justify-center bg-white bg-opacity-75 rounded-lg"
      >
        <div class="w-4 h-4 border-2 border-blue-500 border-t-transparent rounded-full animate-spin"></div>
      </div>
    </component>
    
    <!-- Tooltip СЃ РґРѕРїРѕР»РЅРёС‚РµР»СЊРЅРѕР№ РёРЅС„РѕСЂРјР°С†РёРµР№ -->
    <Transition
      enter-active-class="transition-all duration-200 ease-out"
      enter-from-class="opacity-0 scale-95 translate-y-1"
      enter-to-class="opacity-100 scale-100 translate-y-0"
      leave-active-class="transition-all duration-150 ease-in"
      leave-from-class="opacity-100 scale-100 translate-y-0"
      leave-to-class="opacity-0 scale-95 translate-y-1"
    >
      <div
        v-if="showTooltip && showTooltipState"
        :class="tooltipClasses"
        role="tooltip"
        :aria-hidden="!showTooltipState"
      >
        <div class="text-xs font-medium mb-1">РЎСЂР°РІРЅРµРЅРёРµ</div>
        <div class="text-xs text-gray-600">
          <div v-if="stats.masterCount > 0">РњР°СЃС‚РµСЂР°: {{ stats.masterCount }}/{{ maxItemsPerType }}</div>
          <div v-if="stats.serviceCount > 0">РЈСЃР»СѓРіРё: {{ stats.serviceCount }}/{{ maxItemsPerType }}</div>
          <div v-if="stats.adCount > 0">РћР±СЉСЏРІР»РµРЅРёСЏ: {{ stats.adCount }}/{{ maxItemsPerType }}</div>
          <div v-if="!hasCompareItems" class="text-gray-500">РџСѓСЃС‚Рѕ</div>
          <div v-if="hasCompareItems" class="text-xs text-blue-600 mt-1">
            Р“РѕС‚РѕРІРѕ Рє СЃСЂР°РІРЅРµРЅРёСЋ
          </div>
        </div>
        <!-- РЎС‚СЂРµР»РѕС‡РєР° tooltip -->
        <div class="absolute top-full left-1/2 -translate-x-1/2">
          <div class="w-2 h-2 bg-gray-900 rotate-45 -mt-1"></div>
        </div>
      </div>
    </Transition>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onUnmounted } from 'vue'
import { Link } from '@inertiajs/vue3'
import { useCompareStore } from '../../model/compare.store'

// TypeScript РёРЅС‚РµСЂС„РµР№СЃС‹
interface Props {
  href?: string
  variant?: 'default' | 'compact' | 'icon-only'
  size?: 'small' | 'medium' | 'large'
  compact?: boolean
  showText?: boolean
  showTooltip?: boolean
  disabled?: boolean
  maxCount?: number
  customClass?: string
  buttonText?: string
}

const props = withDefaults(defineProps<Props>(), {
  href: '/compare',
  variant: 'default',
  size: 'medium',
  compact: false,
  showText: true,
  showTooltip: true,
  disabled: false,
  maxCount: 99,
  customClass: '',
  buttonText: 'РЎСЂР°РІРЅРёС‚СЊ'
})

// TypeScript С‚РёРїРёР·Р°С†РёСЏ emits
const emit = defineEmits<{
  'click': [event: Event]
  'tooltip-show': []
  'tooltip-hide': []
}>()

// Store
const compareStore = useCompareStore()

// Local state
const showTooltipState = ref(false)
const tooltipTimeout = ref<number>()

// Computed
const count = computed(() => compareStore.totalCount)
const stats = computed(() => compareStore.stats)
const isLoading = computed(() => compareStore.isLoading)
const hasCompareItems = computed(() => compareStore.hasCompareItems)
const maxItemsPerType = computed(() => compareStore.maxItemsPerType)

const displayCount = computed(() => Math.min(count.value, props.maxCount))

const formattedCount = computed(() => {
  if (count.value > props.maxCount) {
    return `${props.maxCount}+`
  }
  return count.value.toString()
})

const linkComponent = computed(() => props.href ? Link : 'button')

const buttonClasses = computed(() => [
  'relative inline-flex items-center gap-1.5 transition-colors duration-200',
  'focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 rounded-lg',
  {
    // Size variants
    'p-1.5': props.size === 'small',
    'p-2': props.size === 'medium',
    'p-2.5': props.size === 'large',
    
    // Color variants
    'text-gray-700 hover:text-blue-600': !props.disabled,
    'opacity-50 cursor-not-allowed': props.disabled,
    
    // Compact mode
    'px-1.5 py-1': props.compact,
    
    // With count highlight
    'text-blue-600': count.value > 0 && !props.disabled
  },
  props.customClass
])

const compareIconClasses = computed(() => [
  'transition-colors duration-200',
  {
    'w-4 h-4': props.size === 'small',
    'w-5 h-5': props.size === 'medium',
    'w-6 h-6': props.size === 'large',
    'text-blue-500 stroke-2': count.value > 0,
    'text-current stroke-1.5': count.value === 0
  }
])

const badgeClasses = computed(() => [
  'absolute -top-1 -right-1 min-w-5 h-5 bg-blue-500 text-white text-xs font-bold',
  'flex items-center justify-center rounded-full px-1.5 ring-2 ring-white',
  'leading-none'
])

const textClasses = computed(() => [
  'font-medium transition-colors duration-200',
  {
    'text-xs': props.size === 'small',
    'text-sm': props.size === 'medium', 
    'text-base': props.size === 'large',
    'hidden sm:inline': props.compact
  }
])

const tooltipClasses = computed(() => [
  'absolute bottom-full left-1/2 -translate-x-1/2 mb-2 z-10',
  'bg-gray-900 text-white text-xs rounded-lg shadow-lg px-3 py-2',
  'min-w-max max-w-xs'
])

const buttonAriaLabel = computed(() => {
  let label = props.buttonText
  if (count.value > 0) {
    label += ` (${count.value} СЌР»РµРјРµРЅС‚РѕРІ)`
  }
  return label
})

// Methods
const handleClick = (event: Event): void => {
  if (props.disabled) {
    event.preventDefault()
    return
  }
  
  emit('click', event)
}

const showTooltip = (): void => {
  if (!props.showTooltip) return
  
  clearTimeout(tooltipTimeout.value)
  showTooltipState.value = true
  emit('tooltip-show')
}

const hideTooltip = (): void => {
  if (!props.showTooltip) return
  
  tooltipTimeout.value = setTimeout(() => {
    showTooltipState.value = false
    emit('tooltip-hide')
  }, 150)
}

const handleMouseEnter = (): void => {
  showTooltip()
}

const handleMouseLeave = (): void => {
  hideTooltip()
}

// Cleanup
onUnmounted(() => {
  clearTimeout(tooltipTimeout.value)
})
</script>

<style scoped>
/* РђРЅРёРјР°С†РёРё РґР»СЏ scale СЌС„С„РµРєС‚Р° РїСЂРё РґРѕР±Р°РІР»РµРЅРёРё */
@keyframes scaleUp {
  0%, 100% {
    transform: scale(1);
  }
  50% {
    transform: scale(1.1);
  }
}

.compare-pulse {
  animation: scaleUp 0.3s ease-in-out;
}

/* Hover СЌС„С„РµРєС‚С‹ */
.compare-button-hover {
  transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
}

.compare-button-hover:hover {
  transform: translateY(-1px);
}

/* Pulse Р°РЅРёРјР°С†РёСЏ РґР»СЏ СЃС‡РµС‚С‡РёРєР° */
.badge-pulse {
  animation: pulse 1s infinite;
}

@keyframes pulse {
  0%, 100% {
    opacity: 1;
  }
  50% {
    opacity: 0.8;
  }
}

/* Loading spinner */
@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

/* Tooltip СЃС‚РёР»Рё */
.tooltip-arrow::before {
  content: '';
  position: absolute;
  top: 100%;
  left: 50%;
  transform: translateX(-50%);
  border: 4px solid transparent;
  border-top-color: theme('colors.gray.900');
}

/* Accessibility improvements */
@media (prefers-reduced-motion: reduce) {
  * {
    animation: none !important;
    transition: none !important;
  }
}

/* High contrast mode */
@media (prefers-contrast: high) {
  .text-blue-500 {
    color: #0066cc;
  }
  
  .bg-blue-500 {
    background-color: #0066cc;
  }
}

/* Dark mode РїРѕРґРґРµСЂР¶РєР° */
@media (prefers-color-scheme: dark) {
  .text-gray-700 {
    color: theme('colors.gray.200');
  }
  
  .hover\:text-blue-600:hover {
    color: theme('colors.blue.400');
  }
  
  .bg-gray-900 {
    background-color: theme('colors.gray-700');
  }
}

/* Mobile optimizations */
@media (max-width: 640px) {
  .text-sm {
    font-size: 0.75rem;
  }
}
</style>


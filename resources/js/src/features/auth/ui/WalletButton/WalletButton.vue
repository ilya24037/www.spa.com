<!-- РљРЅРѕРїРєР° РєРѕС€РµР»СЊРєР°/Р±Р°Р»Р°РЅСЃР° -->
<template>
  <button
    @click="handleClick"
    :class="buttonClasses"
    :disabled="disabled"
    :aria-label="buttonAriaLabel"
  >
    <!-- РРєРѕРЅРєР° РєРѕС€РµР»СЊРєР° -->
    <svg
      class="w-5 h-5"
      fill="none"
      stroke="currentColor"
      viewBox="0 0 24 24"
      aria-hidden="true"
    >
      <path
        stroke-linecap="round"
        stroke-linejoin="round"
        stroke-width="2"
        d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"
      />
    </svg>

    <!-- Р‘Р°Р»Р°РЅСЃ -->
    <span class="ml-2 text-sm font-medium">
      {{ formattedBalance }}
    </span>

    <!-- РРЅРґРёРєР°С‚РѕСЂ СЃС‚Р°С‚СѓСЃР° -->
    <div
      v-if="showStatus"
      :class="statusIndicatorClasses"
      :title="statusTitle"
    ></div>
  </button>
</template>

<script setup lang="ts">
import { computed } from 'vue'

// TypeScript РёРЅС‚РµСЂС„РµР№СЃС‹
interface Props {
  balance?: number
  currency?: string
  disabled?: boolean
  showStatus?: boolean
  status?: 'normal' | 'low' | 'empty'
  lowThreshold?: number
  compact?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  balance: 0,
  currency: 'в‚Ѕ',
  disabled: false,
  showStatus: true,
  status: 'normal',
  lowThreshold: 100,
  compact: false
})

// TypeScript С‚РёРїРёР·Р°С†РёСЏ emits
const emit = defineEmits<{
  'click': []
}>()

// Computed properties
const formattedBalance = computed(() => {
  const formatted = new Intl.NumberFormat('ru-RU').format(props.balance)
  return `${formatted} ${props.currency}`
})

const balanceStatus = computed(() => {
  if (props.balance === 0) return 'empty'
  if (props.balance < props.lowThreshold) return 'low'
  return 'normal'
})

const buttonClasses = computed(() => [
  'inline-flex items-center px-3 py-2 rounded-lg transition-colors duration-200',
  'border text-sm font-medium focus:outline-none focus:ring-2 focus:ring-offset-2',
  {
    // Normal state
    'border-gray-200 text-gray-700 bg-white hover:bg-gray-50 focus:ring-gray-500': 
      balanceStatus.value === 'normal' && !props.disabled,
    
    // Low balance
    'border-yellow-200 text-yellow-800 bg-yellow-50 hover:bg-yellow-100 focus:ring-yellow-500': 
      balanceStatus.value === 'low' && !props.disabled,
    
    // Empty balance
    'border-red-200 text-red-800 bg-red-50 hover:bg-red-100 focus:ring-red-500': 
      balanceStatus.value === 'empty' && !props.disabled,
    
    // Disabled state
    'opacity-50 cursor-not-allowed border-gray-200 text-gray-500 bg-gray-50': 
      props.disabled,
    
    // Compact mode
    'px-2 py-1': props.compact
  }
])

const statusIndicatorClasses = computed(() => [
  'ml-2 w-2 h-2 rounded-full',
  {
    'bg-green-400': balanceStatus.value === 'normal',
    'bg-yellow-400': balanceStatus.value === 'low',
    'bg-red-400': balanceStatus.value === 'empty'
  }
])

const statusTitle = computed(() => {
  const statusMessages = {
    normal: 'Р‘Р°Р»Р°РЅСЃ РІ РЅРѕСЂРјРµ',
    low: 'РќРёР·РєРёР№ Р±Р°Р»Р°РЅСЃ',
    empty: 'Р‘Р°Р»Р°РЅСЃ РїСѓСЃС‚'
  }
  return statusMessages[balanceStatus.value]
})

const buttonAriaLabel = computed(() => {
  let label = `РљРѕС€РµР»РµРє, Р±Р°Р»Р°РЅСЃ ${formattedBalance.value}`
  
  if (balanceStatus.value === 'low') {
    label += ', РЅРёР·РєРёР№ Р±Р°Р»Р°РЅСЃ'
  } else if (balanceStatus.value === 'empty') {
    label += ', Р±Р°Р»Р°РЅСЃ РїСѓСЃС‚'
  }
  
  return label
})

// Methods
const handleClick = (): void => {
  if (!props.disabled) {
    emit('click')
  }
}
</script>

<style scoped>
/* РђРЅРёРјР°С†РёСЏ РёР·РјРµРЅРµРЅРёСЏ Р±Р°Р»Р°РЅСЃР° */
.balance-change-enter-active,
.balance-change-leave-active {
  transition: all 0.3s ease;
}

.balance-change-enter-from {
  transform: scale(1.1);
  color: theme('colors.green.600');
}

.balance-change-leave-to {
  transform: scale(0.9);
  opacity: 0;
}

/* Pulse РґР»СЏ РЅРёР·РєРѕРіРѕ Р±Р°Р»Р°РЅСЃР° */
.low-balance-pulse {
  animation: pulse-warning 2s infinite;
}

@keyframes pulse-warning {
  0%, 100% {
    opacity: 1;
  }
  50% {
    opacity: 0.7;
  }
}

/* Hover СЌС„С„РµРєС‚С‹ */
.wallet-button:hover .status-indicator {
  transform: scale(1.2);
}

/* Accessibility improvements */
@media (prefers-reduced-motion: reduce) {
  * {
    animation: none !important;
    transition: none !important;
  }
}

/* Dark mode РїРѕРґРґРµСЂР¶РєР° */
@media (prefers-color-scheme: dark) {
  .bg-white {
    background-color: theme('colors.gray.800');
  }
  
  .text-gray-700 {
    color: theme('colors.gray.200');
  }
  
  .border-gray-200 {
    border-color: theme('colors.gray.600');
  }
  
  .hover\:bg-gray-50:hover {
    background-color: theme('colors.gray-700');
  }
}
</style>


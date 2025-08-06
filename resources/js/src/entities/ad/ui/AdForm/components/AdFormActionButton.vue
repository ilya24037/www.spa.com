<!-- resources/js/src/entities/ad/ui/AdForm/components/AdFormActionButton.vue -->
<template>
  <button
    :type="type"
    :disabled="disabled || loading"
    :class="buttonClasses"
    @click="$emit('click', $event)"
  >
    <!-- Loader -->
    <svg 
      v-if="loading" 
      :class="loaderClasses" 
      fill="none" 
      viewBox="0 0 24 24"
    >
      <circle 
        class="opacity-25" 
        cx="12" 
        cy="12" 
        r="10" 
        stroke="currentColor" 
        stroke-width="4"
      />
      <path 
        class="opacity-75" 
        fill="currentColor" 
        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
      />
    </svg>
    
    <!-- РРєРѕРЅРєР° -->
    <component 
      v-else-if="icon" 
      :is="icon" 
      :class="iconClasses"
    />
    
    <!-- РўРµРєСЃС‚ -->
    <span>
      <slot />
    </span>
  </button>
</template>

<script setup>
import { computed } from 'vue'

// рџЋЇ РЎС‚РёР»Рё СЃРѕРіР»Р°СЃРЅРѕ РґРёР·Р°Р№РЅ-СЃРёСЃС‚РµРјРµ
const BASE_CLASSES = 'inline-flex items-center justify-center gap-2 font-medium rounded-lg transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2'
const DISABLED_CLASSES = 'opacity-50 cursor-not-allowed'

const VARIANTS = {
  primary: {
    default: 'bg-blue-600 text-white hover:bg-blue-700 focus:ring-blue-500',
    disabled: 'bg-blue-400'
  },
  secondary: {
    default: 'bg-gray-100 text-gray-700 hover:bg-gray-200 focus:ring-gray-500',
    disabled: 'bg-gray-50'
  },
  success: {
    default: 'bg-green-600 text-white hover:bg-green-700 focus:ring-green-500',
    disabled: 'bg-green-400'
  },
  danger: {
    default: 'bg-red-600 text-white hover:bg-red-700 focus:ring-red-500',
    disabled: 'bg-red-400'
  }
}

const SIZES = {
  small: {
    button: 'px-3 py-1.5 text-sm',
    icon: 'w-4 h-4',
    loader: 'w-4 h-4'
  },
  medium: {
    button: 'px-4 py-2 text-sm',
    icon: 'w-4 h-4',
    loader: 'w-4 h-4'
  },
  large: {
    button: 'px-6 py-3 text-base',
    icon: 'w-5 h-5',
    loader: 'w-5 h-5'
  }
}

const props = defineProps({
  variant: {
    type: String,
    default: 'primary',
    validator: (value) => ['primary', 'secondary', 'danger', 'success', 'warning'].includes(value)
  },
  size: {
    type: String,
    default: 'medium',
    validator: (value) => ['small', 'medium', 'large'].includes(value)
  },
  type: {
    type: String,
    default: 'button'
  },
  disabled: {
    type: Boolean,
    default: false
  },
  loading: {
    type: Boolean,
    default: false
  },
  icon: {
    type: String,
    default: null
  }
})

defineEmits(['click'])

// Р’С‹С‡РёСЃР»СЏРµРјС‹Рµ СЃРІРѕР№СЃС‚РІР°
const buttonClasses = computed(() => {
  const variant = VARIANTS[props.variant]
  const size = SIZES[props.size]
  
  const isDisabled = props.disabled || props.loading
  const variantClasses = isDisabled ? variant.disabled : variant.default
  
  return [
    BASE_CLASSES,
    variantClasses,
    size.button,
    isDisabled ? DISABLED_CLASSES : ''
  ].filter(Boolean).join(' ')
})

const iconClasses = computed(() => SIZES[props.size].icon)
const loaderClasses = computed(() => `${SIZES[props.size].loader} animate-spin`)
</script>

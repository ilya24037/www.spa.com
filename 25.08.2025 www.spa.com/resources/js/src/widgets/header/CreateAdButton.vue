<!-- Кнопка "Разместить объявление" как на Avito -->
<template>
  <Link
    :href="href"
    :class="buttonClasses"
    :aria-label="ariaLabel"
  >
    <!-- Иконка плюс (опционально) -->
    <svg 
      v-if="showIcon"
      class="w-5 h-5"
      fill="none" 
      stroke="currentColor" 
      viewBox="0 0 24 24"
    >
      <path 
        stroke-linecap="round" 
        stroke-linejoin="round" 
        stroke-width="2" 
        d="M12 4v16m8-8H4"
      />
    </svg>
    
    <!-- Текст кнопки -->
    <span :class="textClasses">
      {{ text }}
    </span>
  </Link>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { Link } from '@inertiajs/vue3'

// Props
interface Props {
  text?: string
  href?: string
  showIcon?: boolean
  variant?: 'primary' | 'secondary' | 'outline'
  size?: 'sm' | 'md' | 'lg'
  fullWidth?: boolean
}

const props = withDefaults(defineProps<Props>(), {
    text: 'Разместить объявление',
    href: '/additem',
    showIcon: true,
    variant: 'primary',
    size: 'md',
    fullWidth: false
})

// Computed styles
const buttonClasses = computed(() => [
    'inline-flex items-center justify-center gap-2 font-medium rounded-lg transition-all duration-200',
    'focus:outline-none focus:ring-2 focus:ring-offset-2 whitespace-nowrap',
    {
    // Variants
        'bg-blue-600 text-white hover:bg-blue-700 focus:ring-blue-500 shadow-sm hover:shadow-md': 
      props.variant === 'primary',
        'bg-white text-blue-600 border-2 border-blue-600 hover:bg-blue-50 focus:ring-blue-500': 
      props.variant === 'outline',
        'bg-gray-500 text-gray-500 hover:bg-gray-500 focus:ring-gray-500': 
      props.variant === 'secondary',
    
        // Sizes
        'px-3 py-2 text-sm': props.size === 'sm',
        'px-4 py-2.5 text-base': props.size === 'md',
        'px-6 py-3 text-lg': props.size === 'lg',
    
        // Full width
        'w-full': props.fullWidth
    }
])

const textClasses = computed(() => [
    {
        'hidden sm:inline': props.showIcon && props.size === 'sm',
        'font-semibold': props.variant === 'primary'
    }
])

const ariaLabel = computed(() => 
    `${props.text} - перейти к форме создания нового объявления`
)
</script>

<style scoped>
/* Hover эффект с подъемом */
.inline-flex:hover {
  transform: translateY(-1px);
}

/* Активное состояние */
.inline-flex:active {
  transform: scale(0.98);
}

/* Пульсация для привлечения внимания */
@keyframes pulse {
  0%, 100% {
    opacity: 1;
  }
  50% {
    opacity: 0.8;
  }
}

.inline-flex {
  animation: pulse 3s ease-in-out infinite;
}

.inline-flex:hover {
  animation: none;
}

/* Мобильная адаптация */
@media (max-width: 640px) {
  .inline-flex {
    padding-left: 0.75rem;
    padding-right: 0.75rem;
  }
  
  /* На мобильных показываем только иконку для экономии места */
  .inline-flex svg {
    width: 1.25rem;
    height: 1.25rem;
  }
}

/* Accessibility */
@media (prefers-reduced-motion: reduce) {
  * {
    animation: none !important;
    transition: none !important;
  }
}

/* Focus видимость для клавиатурной навигации */
.inline-flex:focus-visible {
  outline: 2px solid #3b82f6;
  outline-offset: 2px;
}
</style>
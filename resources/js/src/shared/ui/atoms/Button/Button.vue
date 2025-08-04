<template>
  <component
    :is="componentTag"
    :type="type"
    :href="href"
    :to="to"
    :disabled="disabled || loading"
    :class="buttonClasses"
    :aria-disabled="disabled || loading"
    :aria-busy="loading"
    :aria-label="ariaLabel"
    @click="handleClick"
  >
    <!-- Loading spinner -->
    <span v-if="loading" class="button-spinner" aria-hidden="true">
      <svg class="animate-spin" viewBox="0 0 24 24" fill="none">
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
    </span>

    <!-- Icon left -->
    <span v-if="iconLeft && !loading" class="button-icon-left">
      <component :is="iconLeft" />
    </span>

    <!-- Button text -->
    <span class="button-text">
      <slot>{{ loading ? loadingText : text }}</slot>
    </span>

    <!-- Icon right -->
    <span v-if="iconRight && !loading" class="button-icon-right">
      <component :is="iconRight" />
    </span>
  </component>
</template>

<script setup lang="ts">
import { computed, type Component } from 'vue'
import { Link } from '@inertiajs/vue3'

// TypeScript типы для props
export interface ButtonProps {
  // Внешний вид
  variant?: 'primary' | 'secondary' | 'danger' | 'success' | 'warning' | 'ghost' | 'link'
  size?: 'xs' | 'sm' | 'md' | 'lg' | 'xl'
  fullWidth?: boolean
  rounded?: boolean | 'sm' | 'md' | 'lg' | 'full'
  
  // Состояния
  loading?: boolean
  loadingText?: string
  disabled?: boolean
  
  // Содержимое
  text?: string
  iconLeft?: Component | string
  iconRight?: Component | string
  
  // Действия
  type?: 'button' | 'submit' | 'reset'
  href?: string
  to?: string | object
  
  // Доступность
  ariaLabel?: string
}

// Props с дефолтными значениями
const props = withDefaults(defineProps<ButtonProps>(), {
  variant: 'primary',
  size: 'md',
  fullWidth: false,
  rounded: 'md',
  loading: false,
  loadingText: 'Загрузка...',
  disabled: false,
  type: 'button'
})

// Emits
const emit = defineEmits<{
  click: [event: MouseEvent]
}>()

// Computed для определения тега компонента
const componentTag = computed(() => {
  if (props.href) return 'a'
  if (props.to) return Link
  return 'button'
})

// Классы для кнопки
const buttonClasses = computed(() => {
  const classes = [
    'button',
    `button--${props.variant}`,
    `button--${props.size}`,
    {
      'button--full-width': props.fullWidth,
      'button--loading': props.loading,
      'button--disabled': props.disabled,
      [`button--rounded-${props.rounded}`]: props.rounded !== true,
      'button--rounded': props.rounded === true
    }
  ]
  
  return classes
})

// Обработчик клика
const handleClick = (event: MouseEvent) => {
  if (props.disabled || props.loading) {
    event.preventDefault()
    event.stopPropagation()
    return
  }
  
  emit('click', event)
}
</script>

<style scoped>
/* Базовые стили кнопки */
.button {
  @apply inline-flex items-center justify-center gap-2;
  @apply font-medium transition-all duration-200;
  @apply focus:outline-none focus:ring-2 focus:ring-offset-2;
  @apply disabled:cursor-not-allowed;
  position: relative;
  text-decoration: none;
}

/* Размеры */
.button--xs {
  @apply px-2.5 py-1 text-xs;
}

.button--sm {
  @apply px-3 py-1.5 text-sm;
}

.button--md {
  @apply px-4 py-2 text-sm;
}

.button--lg {
  @apply px-6 py-3 text-base;
}

.button--xl {
  @apply px-8 py-4 text-lg;
}

/* Варианты */
.button--primary {
  @apply bg-blue-600 text-white hover:bg-blue-700;
  @apply focus:ring-blue-500;
  @apply disabled:bg-blue-300;
}

.button--secondary {
  @apply bg-gray-600 text-white hover:bg-gray-700;
  @apply focus:ring-gray-500;
  @apply disabled:bg-gray-300;
}

.button--danger {
  @apply bg-red-600 text-white hover:bg-red-700;
  @apply focus:ring-red-500;
  @apply disabled:bg-red-300;
}

.button--success {
  @apply bg-green-600 text-white hover:bg-green-700;
  @apply focus:ring-green-500;
  @apply disabled:bg-green-300;
}

.button--warning {
  @apply bg-yellow-500 text-white hover:bg-yellow-600;
  @apply focus:ring-yellow-400;
  @apply disabled:bg-yellow-300;
}

.button--ghost {
  @apply bg-transparent text-gray-700 hover:bg-gray-100;
  @apply focus:ring-gray-500;
  @apply disabled:text-gray-400 disabled:hover:bg-transparent;
}

.button--link {
  @apply bg-transparent text-blue-600 hover:text-blue-700 hover:underline;
  @apply focus:ring-blue-500 p-0;
  @apply disabled:text-gray-400 disabled:no-underline;
}

/* Скругление */
.button--rounded-sm {
  @apply rounded-sm;
}

.button--rounded-md {
  @apply rounded-md;
}

.button--rounded-lg {
  @apply rounded-lg;
}

.button--rounded-full {
  @apply rounded-full;
}

.button--rounded {
  @apply rounded;
}

/* Состояния */
.button--full-width {
  @apply w-full;
}

.button--loading {
  @apply cursor-wait;
}

.button--disabled {
  @apply opacity-50 cursor-not-allowed;
}

/* Спиннер */
.button-spinner {
  @apply w-4 h-4;
}

.button-spinner svg {
  @apply w-full h-full;
}

/* Иконки */
.button-icon-left,
.button-icon-right {
  @apply w-5 h-5 flex-shrink-0;
}

/* Адаптивность */
@media (max-width: 640px) {
  .button--lg,
  .button--xl {
    @apply px-4 py-2 text-base;
  }
}
</style>
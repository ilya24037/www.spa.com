<!-- ErrorState.vue - Универсальный компонент отображения ошибок -->
<template>
  <div
    v-if="!state.isDismissed && error"
    :class="[
      'error-state',
      `error-state--${size}`,
      `error-state--${variant}`,
      props.class
    ]"
    role="alert"
    aria-live="assertive"
    :aria-label="`Ошибка: ${errorConfig.title}`"
    data-testid="error-state"
  >
    <!-- Иконка ошибки -->
    <div v-if="showIcon" class="error-state__icon-wrapper">
      <div :class="['error-state__icon', errorConfig.iconColor]">
        <component :is="getIcon()" class="w-full h-full" />
      </div>
    </div>

    <!-- Контент ошибки -->
    <div class="error-state__content">
      <!-- Заголовок -->
      <h3 class="error-state__title">
        {{ customTitle || errorConfig.title }}
      </h3>

      <!-- Сообщение -->
      <p class="error-state__message">
        {{ customMessage || error.message || errorConfig.defaultMessage }}
      </p>

      <!-- Детали ошибки -->
      <div v-if="showDetails && (error.details || error.code)" class="error-state__details">
        <button
          v-if="!state.showFullDetails"
          @click="state.showFullDetails = true"
          class="error-state__details-toggle"
          aria-label="Показать детали ошибки"
        >
          <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
          </svg>
          Подробнее
        </button>

        <div v-else class="error-state__details-content">
          <button
            @click="state.showFullDetails = false"
            class="error-state__details-toggle mb-2"
            aria-label="Скрыть детали ошибки"
          >
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" />
            </svg>
            Скрыть детали
          </button>
          
          <div class="error-state__details-text">
            <p v-if="error.details" class="mb-2">{{ error.details }}</p>
            <div v-if="error.code" class="text-xs">
              Код ошибки: <code class="font-mono bg-gray-100 px-1 py-0.5 rounded">{{ error.code }}</code>
            </div>
            <div v-if="error.requestId" class="text-xs mt-1">
              ID запроса: <code class="font-mono bg-gray-100 px-1 py-0.5 rounded">{{ error.requestId }}</code>
            </div>
          </div>
        </div>
      </div>

      <!-- Действия -->
      <div v-if="showActions && effectiveActions.length > 0" class="error-state__actions">
        <button
          v-for="(action, index) in effectiveActions"
          :key="index"
          @click="handleAction(action)"
          :disabled="action.loading || state.isRetrying"
          :class="[
            'error-state__action',
            `error-state__action--${action.variant || 'primary'}`,
            { 'error-state__action--loading': action.loading || (index === 0 && state.isRetrying) }
          ]"
          :aria-label="action.label"
        >
          <span v-if="action.loading || (index === 0 && state.isRetrying)" class="error-state__action-spinner">
            <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
            </svg>
          </span>
          <span v-else-if="action.icon" class="error-state__action-icon" v-html="action.icon" />
          {{ action.label }}
        </button>
      </div>

      <!-- Ссылка на помощь -->
      <div v-if="error.helpUrl" class="error-state__help">
        <a 
          :href="error.helpUrl" 
          target="_blank" 
          rel="noopener noreferrer"
          class="error-state__help-link"
        >
          Нужна помощь?
        </a>
      </div>
    </div>

    <!-- Кнопка закрытия -->
    <button
      v-if="dismissible"
      @click="handleDismiss"
      class="error-state__dismiss"
      aria-label="Закрыть сообщение об ошибке"
    >
      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
      </svg>
    </button>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import type {
  ErrorStateProps,
  ErrorStateEmits,
  ErrorStateState,
  ErrorTypeConfig,
  ErrorAction
} from './ErrorState.types'
import { DEFAULT_ERROR_CONFIGS } from './ErrorState.types'

// Props
const props = withDefaults(defineProps<ErrorStateProps>(), {
  size: 'medium',
  variant: 'card',
  showDetails: true,
  showIcon: true,
  showActions: true,
  retryable: true,
  dismissible: false
})

// Emits
const emit = defineEmits<ErrorStateEmits>()

// Состояние компонента
const state = ref<ErrorStateState>({
  isRetrying: false,
  isDismissed: false,
  showFullDetails: false,
  reportSent: false
})

// Вычисляемые свойства
const errorConfig = computed<ErrorTypeConfig>(() => {
  if (!props.error) {
    return DEFAULT_ERROR_CONFIGS.generic
  }
  return DEFAULT_ERROR_CONFIGS[props.error.type] || DEFAULT_ERROR_CONFIGS.generic
})

const effectiveActions = computed<ErrorAction[]>(() => {
  if (props.actions && props.actions.length > 0) {
    return props.actions
  }

  const defaultActions: ErrorAction[] = []
  
  // Добавляем кнопку повтора если разрешено
  if (props.retryable && errorConfig.value.defaultActions) {
    const retryAction = errorConfig.value.defaultActions.find(a => a.label === 'Повторить')
    if (retryAction) {
      defaultActions.push({
        ...retryAction,
        action: handleRetry
      })
    }
  }

  // Добавляем кнопку отчета если ошибка репортабельная
  if (errorConfig.value.reportable && !state.reportSent) {
    defaultActions.push({
      label: 'Сообщить об ошибке',
      variant: 'secondary',
      action: handleReport
    })
  }

  return defaultActions
})

// Методы
const getIcon = () => {
  // В реальном приложении здесь был бы импорт иконок
  // Для примера возвращаем SVG компонент
  return {
    template: `
      <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
          d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
      </svg>
    `
  }
}

const handleRetry = async (): Promise<void> => {
  try {
    state.isRetrying = true
    emit('retry')
    
    // Симуляция задержки для UX
    await new Promise(resolve => setTimeout(resolve, 1000))
  } finally {
    state.isRetrying = false
  }
}

const handleDismiss = (): void => {
  state.isDismissed = true
  emit('dismiss')
}

const handleAction = async (action: ErrorAction): Promise<void> => {
  if (action.action) {
    await action.action()
  }
  emit('action', action)
}

const handleReport = async (): Promise<void> => {
  if (!props.error) return
  
  try {
    emit('report', props.error)
    state.reportSent = true
    
    // В реальном приложении здесь был бы API вызов
    console.log('Error reported:', props.error)
  } catch (err) {
    console.error('Failed to report error:', err)
  }
}
</script>

<style scoped>
/* Базовые стили */
.error-state {
  @apply relative flex gap-4 p-4 rounded-lg border;
}

/* Варианты отображения */
.error-state--inline {
  @apply bg-red-50 border-red-200 text-red-800;
}

.error-state--card {
  @apply bg-white border-red-200 shadow-sm;
}

.error-state--modal {
  @apply bg-white border-gray-200 shadow-xl;
}

.error-state--page {
  @apply bg-gray-50 border-0 min-h-[400px] items-center justify-center text-center;
}

/* Размеры */
.error-state--small {
  @apply p-3 text-sm;
}

.error-state--medium {
  @apply p-4;
}

.error-state--large {
  @apply p-6 text-lg;
}

.error-state--full {
  @apply p-8 min-h-screen;
}

/* Иконка */
.error-state__icon-wrapper {
  @apply flex-shrink-0;
}

.error-state__icon {
  @apply w-8 h-8;
}

.error-state--small .error-state__icon {
  @apply w-5 h-5;
}

.error-state--large .error-state__icon {
  @apply w-12 h-12;
}

/* Контент */
.error-state__content {
  @apply flex-1 min-w-0;
}

.error-state__title {
  @apply font-semibold text-gray-900 mb-1;
}

.error-state--small .error-state__title {
  @apply text-sm;
}

.error-state--large .error-state__title {
  @apply text-xl;
}

.error-state__message {
  @apply text-gray-600;
}

.error-state--small .error-state__message {
  @apply text-xs;
}

/* Детали */
.error-state__details {
  @apply mt-3;
}

.error-state__details-toggle {
  @apply inline-flex items-center text-sm text-gray-500 hover:text-gray-700 transition-colors;
}

.error-state__details-content {
  @apply mt-2 p-3 bg-gray-50 rounded-md;
}

.error-state__details-text {
  @apply text-sm text-gray-600;
}

/* Действия */
.error-state__actions {
  @apply flex gap-2 mt-4;
}

.error-state__action {
  @apply inline-flex items-center gap-2 px-4 py-2 rounded-lg font-medium transition-all;
  @apply disabled:opacity-50 disabled:cursor-not-allowed;
}

.error-state__action--primary {
  @apply bg-blue-600 text-white hover:bg-blue-700;
}

.error-state__action--secondary {
  @apply bg-gray-100 text-gray-700 hover:bg-gray-200;
}

.error-state__action--danger {
  @apply bg-red-600 text-white hover:bg-red-700;
}

.error-state__action--loading {
  @apply cursor-wait;
}

.error-state__action-spinner {
  @apply inline-flex;
}

/* Помощь */
.error-state__help {
  @apply mt-3 pt-3 border-t border-gray-200;
}

.error-state__help-link {
  @apply text-sm text-blue-600 hover:text-blue-700 underline;
}

/* Кнопка закрытия */
.error-state__dismiss {
  @apply absolute top-2 right-2 p-1 rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100 transition-all;
}

/* Анимации */
@keyframes slideIn {
  from {
    opacity: 0;
    transform: translateY(-10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.error-state {
  animation: slideIn 0.3s ease-out;
}
</style>
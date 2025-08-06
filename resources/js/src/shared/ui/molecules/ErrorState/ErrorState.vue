<!-- ErrorState.vue - РЈРЅРёРІРµСЂСЃР°Р»СЊРЅС‹Р№ РєРѕРјРїРѕРЅРµРЅС‚ РѕС‚РѕР±СЂР°Р¶РµРЅРёСЏ РѕС€РёР±РѕРє -->
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
    :aria-label="`РћС€РёР±РєР°: ${errorConfig.title}`"
    data-testid="error-state"
  >
    <!-- РРєРѕРЅРєР° РѕС€РёР±РєРё -->
    <div v-if="showIcon" class="error-state__icon-wrapper">
      <div :class="['error-state__icon', errorConfig.iconColor]">
        <component :is="getIcon()" class="w-full h-full" />
      </div>
    </div>

    <!-- РљРѕРЅС‚РµРЅС‚ РѕС€РёР±РєРё -->
    <div class="error-state__content">
      <!-- Р—Р°РіРѕР»РѕРІРѕРє -->
      <h3 class="error-state__title">
        {{ customTitle || errorConfig.title }}
      </h3>

      <!-- РЎРѕРѕР±С‰РµРЅРёРµ -->
      <p class="error-state__message">
        {{ customMessage || error.message || errorConfig.defaultMessage }}
      </p>

      <!-- Р”РµС‚Р°Р»Рё РѕС€РёР±РєРё -->
      <div v-if="showDetails && (error.details || error.code)" class="error-state__details">
        <button
          v-if="!state.showFullDetails"
          @click="state.showFullDetails = true"
          class="error-state__details-toggle"
          aria-label="РџРѕРєР°Р·Р°С‚СЊ РґРµС‚Р°Р»Рё РѕС€РёР±РєРё"
        >
          <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
          </svg>
          РџРѕРґСЂРѕР±РЅРµРµ
        </button>

        <div v-else class="error-state__details-content">
          <button
            @click="state.showFullDetails = false"
            class="error-state__details-toggle mb-2"
            aria-label="РЎРєСЂС‹С‚СЊ РґРµС‚Р°Р»Рё РѕС€РёР±РєРё"
          >
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" />
            </svg>
            РЎРєСЂС‹С‚СЊ РґРµС‚Р°Р»Рё
          </button>
          
          <div class="error-state__details-text">
            <p v-if="error.details" class="mb-2">{{ error.details }}</p>
            <div v-if="error.code" class="text-xs">
              РљРѕРґ РѕС€РёР±РєРё: <code class="font-mono bg-gray-100 px-1 py-0.5 rounded">{{ error.code }}</code>
            </div>
            <div v-if="error.requestId" class="text-xs mt-1">
              ID Р·Р°РїСЂРѕСЃР°: <code class="font-mono bg-gray-100 px-1 py-0.5 rounded">{{ error.requestId }}</code>
            </div>
          </div>
        </div>
      </div>

      <!-- Р”РµР№СЃС‚РІРёСЏ -->
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

      <!-- РЎСЃС‹Р»РєР° РЅР° РїРѕРјРѕС‰СЊ -->
      <div v-if="error.helpUrl" class="error-state__help">
        <a 
          :href="error.helpUrl" 
          target="_blank" 
          rel="noopener noreferrer"
          class="error-state__help-link"
        >
          РќСѓР¶РЅР° РїРѕРјРѕС‰СЊ?
        </a>
      </div>
    </div>

    <!-- РљРЅРѕРїРєР° Р·Р°РєСЂС‹С‚РёСЏ -->
    <button
      v-if="dismissible"
      @click="handleDismiss"
      class="error-state__dismiss"
      aria-label="Р—Р°РєСЂС‹С‚СЊ СЃРѕРѕР±С‰РµРЅРёРµ РѕР± РѕС€РёР±РєРµ"
    >
      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
      </svg>
    </button>
  </div>
</template>

<script setup lang="ts">
import { logger } from '@/src/shared/lib/logger'
import { computed, ref } from 'vue'
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

// РЎРѕСЃС‚РѕСЏРЅРёРµ РєРѕРјРїРѕРЅРµРЅС‚Р°
const state = ref<ErrorStateState>({
  isRetrying: false,
  isDismissed: false,
  showFullDetails: false,
  reportSent: false
})

// Р’С‹С‡РёСЃР»СЏРµРјС‹Рµ СЃРІРѕР№СЃС‚РІР°
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
  
  // Р”РѕР±Р°РІР»СЏРµРј РєРЅРѕРїРєСѓ РїРѕРІС‚РѕСЂР° РµСЃР»Рё СЂР°Р·СЂРµС€РµРЅРѕ
  if (props.retryable && errorConfig.value.defaultActions) {
    const retryAction = errorConfig.value.defaultActions.find(a => a.label === 'РџРѕРІС‚РѕСЂРёС‚СЊ')
    if (retryAction) {
      defaultActions.push({
        ...retryAction,
        action: handleRetry
      })
    }
  }

  // Р”РѕР±Р°РІР»СЏРµРј РєРЅРѕРїРєСѓ РѕС‚С‡РµС‚Р° РµСЃР»Рё РѕС€РёР±РєР° СЂРµРїРѕСЂС‚Р°Р±РµР»СЊРЅР°СЏ
  if (errorConfig.value.reportable && !state.reportSent) {
    defaultActions.push({
      label: 'РЎРѕРѕР±С‰РёС‚СЊ РѕР± РѕС€РёР±РєРµ',
      variant: 'secondary',
      action: handleReport
    })
  }

  return defaultActions
})

// РњРµС‚РѕРґС‹
const getIcon = () => {
  // Р’ СЂРµР°Р»СЊРЅРѕРј РїСЂРёР»РѕР¶РµРЅРёРё Р·РґРµСЃСЊ Р±С‹Р» Р±С‹ РёРјРїРѕСЂС‚ РёРєРѕРЅРѕРє
  // Р”Р»СЏ РїСЂРёРјРµСЂР° РІРѕР·РІСЂР°С‰Р°РµРј SVG РєРѕРјРїРѕРЅРµРЅС‚
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
    
    // РЎРёРјСѓР»СЏС†РёСЏ Р·Р°РґРµСЂР¶РєРё РґР»СЏ UX
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
    
    // Р’ СЂРµР°Р»СЊРЅРѕРј РїСЂРёР»РѕР¶РµРЅРёРё Р·РґРµСЃСЊ Р±С‹Р» Р±С‹ API РІС‹Р·РѕРІ
    // Error reported
  } catch (err) {
    logger.error('Failed to report error:', err)
  }
}
</script>

<style scoped>
/* Р‘Р°Р·РѕРІС‹Рµ СЃС‚РёР»Рё */
.error-state {
  @apply relative flex gap-4 p-4 rounded-lg border;
}

/* Р’Р°СЂРёР°РЅС‚С‹ РѕС‚РѕР±СЂР°Р¶РµРЅРёСЏ */
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

/* Р Р°Р·РјРµСЂС‹ */
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

/* РРєРѕРЅРєР° */
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

/* РљРѕРЅС‚РµРЅС‚ */
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

/* Р”РµС‚Р°Р»Рё */
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

/* Р”РµР№СЃС‚РІРёСЏ */
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

/* РџРѕРјРѕС‰СЊ */
.error-state__help {
  @apply mt-3 pt-3 border-t border-gray-200;
}

.error-state__help-link {
  @apply text-sm text-blue-600 hover:text-blue-700 underline;
}

/* РљРЅРѕРїРєР° Р·Р°РєСЂС‹С‚РёСЏ */
.error-state__dismiss {
  @apply absolute top-2 right-2 p-1 rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100 transition-all;
}

/* РђРЅРёРјР°С†РёРё */
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


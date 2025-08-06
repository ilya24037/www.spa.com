<template>
  <div v-if="hasError" class="error-boundary">
    <div class="error-container">
      <div class="error-icon">
        <svg class="w-16 h-16 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
        </svg>
      </div>
      
      <h2 class="error-title">{{ errorTitle || 'Что-то пошло не так' }}</h2>
      
      <p class="error-message">{{ errorMessage || 'Произошла непредвиденная ошибка при загрузке компонента.' }}</p>
      
      <div v-if="showDetails && error" class="error-details">
        <details class="error-details-container">
          <summary class="error-details-summary">Подробности ошибки</summary>
          <pre class="error-details-content">{{ error }}</pre>
        </details>
      </div>
      
      <div class="error-actions">
        <button 
          v-if="showReload !== false"
          @click="reload" 
          class="error-button error-button-primary"
        >
          <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                  d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
          </svg>
          Перезагрузить страницу
        </button>
        
        <button 
          v-if="showHome"
          @click="goHome" 
          class="error-button error-button-secondary"
        >
          На главную
        </button>
      </div>
    </div>
  </div>
  <slot v-else />
</template>

<script setup lang="ts">
import { ref, onErrorCaptured, type PropType } from 'vue'

const props = defineProps({
  errorTitle: {
    type: String,
    default: ''
  },
  errorMessage: {
    type: String,
    default: ''
  },
  showDetails: {
    type: Boolean,
    default: false
  },
  showReload: {
    type: Boolean,
    default: true
  },
  showHome: {
    type: Boolean,
    default: true
  },
  onError: {
    type: Function as PropType<(error: Error) => void>,
    default: null
  }
})

const hasError = ref(false)
const error = ref<Error | null>(null)

// Перехват ошибок дочерних компонентов
onErrorCaptured((err: Error) => {
  console.error('ErrorBoundary caught:', err)
  hasError.value = true
  error.value = err
  
  // Вызываем callback если он передан
  if (props.onError) {
    props.onError(err)
  }
  
  // Предотвращаем дальнейшее распространение ошибки
  return false
})

// Методы
const reload = () => {
  window.location.reload()
}

const goHome = () => {
  window.location.href = '/'
}

// Экспорт для программного управления
defineExpose({
  reset: () => {
    hasError.value = false
    error.value = null
  },
  setError: (err: Error) => {
    hasError.value = true
    error.value = err
  }
})
</script>

<style scoped>
.error-boundary {
  @apply min-h-[400px] flex items-center justify-center p-8;
}

.error-container {
  @apply max-w-md mx-auto text-center;
}

.error-icon {
  @apply flex justify-center mb-4;
}

.error-title {
  @apply text-2xl font-bold text-gray-900 mb-2;
}

.error-message {
  @apply text-gray-600 mb-6;
}

.error-details {
  @apply mb-6;
}

.error-details-container {
  @apply text-left bg-gray-50 rounded-lg p-4;
}

.error-details-summary {
  @apply cursor-pointer text-sm font-medium text-gray-700 hover:text-gray-900;
}

.error-details-content {
  @apply mt-2 text-xs text-red-600 overflow-x-auto;
}

.error-actions {
  @apply flex gap-3 justify-center;
}

.error-button {
  @apply inline-flex items-center px-4 py-2 text-sm font-medium rounded-md
         focus:outline-none focus:ring-2 focus:ring-offset-2 transition-colors;
}

.error-button-primary {
  @apply bg-blue-600 text-white hover:bg-blue-700 focus:ring-blue-500;
}

.error-button-secondary {
  @apply bg-gray-200 text-gray-700 hover:bg-gray-300 focus:ring-gray-500;
}
</style>
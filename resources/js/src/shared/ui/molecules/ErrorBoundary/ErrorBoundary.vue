<template>
  <div>
    <!-- Если есть ошибка, показываем заглушку -->
    <div 
      v-if="hasError" 
      class="error-boundary"
      role="alert"
      :aria-live="'assertive'"
    >
      <div class="error-boundary__content">
        <!-- Заголовок ошибки -->
        <div class="error-boundary__header">
          <svg 
            class="error-boundary__icon" 
            fill="none" 
            stroke="currentColor" 
            viewBox="0 0 24 24"
            aria-hidden="true"
          >
            <path 
              stroke-linecap="round" 
              stroke-linejoin="round" 
              stroke-width="2" 
              d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" 
            />
          </svg>
          <h3 class="error-boundary__title">{{ errorTitle }}</h3>
        </div>

        <!-- Сообщение об ошибке -->
        <p class="error-boundary__message">{{ errorMessage }}</p>

        <!-- Fallback компонент если передан -->
        <component 
          v-if="props.fallback" 
          :is="props.fallback" 
          :error="errorDetails" 
          @reload="reload"
        />

        <!-- Кнопки действий -->
        <div class="error-boundary__actions">
          <button 
            v-if="showReload"
            @click="reload"
            class="error-boundary__button error-boundary__button--primary"
            type="button"
          >
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
            </svg>
            Попробовать снова
          </button>

          <button 
            v-if="showDetails && errorDetails"
            @click="toggleDetails"
            class="error-boundary__button error-boundary__button--secondary"
            type="button"
          >
            {{ showErrorDetails ? 'Скрыть детали' : 'Показать детали' }}
          </button>
        </div>

        <!-- Детали ошибки (скрываемые) -->
        <div 
          v-if="showDetails && showErrorDetails && errorDetails" 
          class="error-boundary__details"
        >
          <div class="error-boundary__details-header">
            <h4>Техническая информация</h4>
            <button 
              @click="copyErrorDetails"
              class="error-boundary__copy-button"
              type="button"
              title="Скопировать детали ошибки"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
              </svg>
            </button>
          </div>
          
          <div class="error-boundary__error-info">
            <div>
              <strong>Ошибка:</strong> {{ errorDetails.errorMessage }}
            </div>
            <div>
              <strong>Компонент:</strong> {{ errorDetails.componentInfo }}
            </div>
            <div>
              <strong>Время:</strong> {{ new Date(errorDetails.timestamp).toLocaleString() }}
            </div>
          </div>

          <details class="error-boundary__stack-trace">
            <summary>Stack Trace</summary>
            <pre class="error-boundary__stack-code">{{ errorDetails.stack }}</pre>
          </details>
        </div>
      </div>
    </div>
    
    <!-- Если ошибки нет, показываем контент -->
    <div v-else>
      <slot />
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onErrorCaptured } from 'vue'
import type { ErrorBoundaryProps, ErrorBoundaryEmits, ErrorInfo } from './ErrorBoundary.types'

const props = withDefaults(defineProps<ErrorBoundaryProps>(), {
  errorTitle: 'Произошла ошибка',
  errorMessage: 'Не удалось загрузить компонент. Попробуйте обновить страницу.',
  showReload: true,
  showDetails: false,
  logErrors: true
})

const emit = defineEmits<ErrorBoundaryEmits>()

const hasError = ref(false)
const errorDetails = ref<ErrorInfo | null>(null)
const showErrorDetails = ref(false)

// Перехватываем ошибки в дочерних компонентах
onErrorCaptured((err: Error, instance: any, info: string) => {
  const errorInfo: ErrorInfo = {
    error: err,
    errorMessage: err.message,
    stack: err.stack || 'Stack trace недоступен',
    componentInfo: info,
    timestamp: new Date().toISOString(),
    userAgent: navigator.userAgent
  }
  
  errorDetails.value = errorInfo
  hasError.value = true
  
  // Логируем ошибку если включено
  if (props.logErrors) {
    console.error('🚨 ErrorBoundary перехватил ошибку:', {
      message: err.message,
      stack: err.stack,
      component: info,
      timestamp: errorInfo.timestamp
    })
  }
  
  // Эмитим событие ошибки
  emit('error', errorInfo)
  
  // Останавливаем распространение ошибки
  return false
})

// Перезагрузка компонента
const reload = () => {
  hasError.value = false
  errorDetails.value = null
  showErrorDetails.value = false
  emit('reload')
}

// Переключить показ деталей ошибки
const toggleDetails = () => {
  showErrorDetails.value = !showErrorDetails.value
}

// Скопировать детали ошибки в буфер обмена
const copyErrorDetails = async () => {
  if (!errorDetails.value) return
  
  const details = {
    error: errorDetails.value.errorMessage,
    stack: errorDetails.value.stack,
    component: errorDetails.value.componentInfo,
    timestamp: errorDetails.value.timestamp,
    userAgent: errorDetails.value.userAgent
  }
  
  try {
    await navigator.clipboard.writeText(JSON.stringify(details, null, 2))
    emit('copy-success')
  } catch (err) {
    console.error('Не удалось скопировать детали ошибки:', err)
    emit('copy-error')
  }
}
</script>

<style scoped>
.error-boundary {
  padding: 1.5rem;
  background: #fef2f2;
  border: 1px solid #fecaca;
  border-radius: 8px;
  color: #991b1b;
}

.error-boundary__content {
  max-width: 100%;
}

.error-boundary__header {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  margin-bottom: 1rem;
}

.error-boundary__icon {
  width: 1.25rem;
  height: 1.25rem;
  flex-shrink: 0;
  color: #dc2626;
}

.error-boundary__title {
  margin: 0;
  font-size: 1.125rem;
  font-weight: 600;
  color: #991b1b;
}

.error-boundary__message {
  margin: 0 0 1.5rem 0;
  font-size: 0.875rem;
  color: #7f1d1d;
  line-height: 1.5;
}

.error-boundary__actions {
  display: flex;
  gap: 0.75rem;
  margin-bottom: 1rem;
}

.error-boundary__button {
  display: inline-flex;
  align-items: center;
  padding: 0.5rem 1rem;
  border-radius: 6px;
  font-size: 0.875rem;
  font-weight: 500;
  transition: all 0.2s ease;
  cursor: pointer;
  border: 1px solid transparent;
}

.error-boundary__button--primary {
  background: #dc2626;
  color: white;
  border-color: #dc2626;
}

.error-boundary__button--primary:hover {
  background: #b91c1c;
  border-color: #b91c1c;
}

.error-boundary__button--secondary {
  background: white;
  color: #dc2626;
  border-color: #dc2626;
}

.error-boundary__button--secondary:hover {
  background: #fef2f2;
}

.error-boundary__details {
  margin-top: 1rem;
  padding: 1rem;
  background: #fff5f5;
  border: 1px solid #f3f4f6;
  border-radius: 6px;
  font-size: 0.875rem;
}

.error-boundary__details-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 0.75rem;
}

.error-boundary__details-header h4 {
  margin: 0;
  font-size: 0.875rem;
  font-weight: 600;
  color: #374151;
}

.error-boundary__copy-button {
  padding: 0.25rem;
  background: transparent;
  border: none;
  border-radius: 4px;
  color: #6b7280;
  cursor: pointer;
  transition: all 0.2s ease;
}

.error-boundary__copy-button:hover {
  background: #f3f4f6;
  color: #374151;
}

.error-boundary__error-info {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  margin-bottom: 1rem;
  color: #4b5563;
}

.error-boundary__error-info strong {
  color: #374151;
}

.error-boundary__stack-trace {
  margin-top: 1rem;
}

.error-boundary__stack-trace summary {
  cursor: pointer;
  font-weight: 500;
  color: #374151;
  margin-bottom: 0.5rem;
}

.error-boundary__stack-code {
  margin: 0.5rem 0 0 0;
  padding: 0.75rem;
  background: #f9fafb;
  border: 1px solid #e5e7eb;
  border-radius: 4px;
  font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', monospace;
  font-size: 0.75rem;
  line-height: 1.4;
  color: #374151;
  overflow-x: auto;
  white-space: pre-wrap;
  word-break: break-all;
}

/* Адаптивность */
@media (max-width: 768px) {
  .error-boundary {
    padding: 1rem;
  }
  
  .error-boundary__actions {
    flex-direction: column;
  }
  
  .error-boundary__button {
    justify-content: center;
  }
  
  .error-boundary__details-header {
    flex-direction: column;
    align-items: flex-start;
    gap: 0.5rem;
  }
  
  .error-boundary__stack-code {
    font-size: 0.6875rem;
  }
}

/* Темная тема */
@media (prefers-color-scheme: dark) {
  .error-boundary {
    background: #450a0a;
    border-color: #7c2d12;
    color: #fca5a5;
  }
  
  .error-boundary__title {
    color: #fca5a5;
  }
  
  .error-boundary__message {
    color: #f87171;
  }
  
  .error-boundary__details {
    background: #3c0a0a;
    border-color: #4b5563;
  }
  
  .error-boundary__details-header h4 {
    color: #f3f4f6;
  }
  
  .error-boundary__error-info {
    color: #d1d5db;
  }
  
  .error-boundary__error-info strong {
    color: #f3f4f6;
  }
  
  .error-boundary__stack-trace summary {
    color: #f3f4f6;
  }
  
  .error-boundary__stack-code {
    background: #1f2937;
    border-color: #374151;
    color: #e5e7eb;
  }
}
</style>
</script>
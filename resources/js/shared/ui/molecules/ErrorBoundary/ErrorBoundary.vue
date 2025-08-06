<!-- 
  Простой ErrorBoundary компонент для изоляции ошибок виджетов
-->
<template>
  <div class="error-boundary">
    <!-- Error состояние -->
    <div v-if="hasError" class="error-boundary__error">
      <div class="bg-red-50 border border-red-200 rounded-lg p-6">
        <div class="flex items-start">
          <div class="flex-shrink-0">
            <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
              <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
            </svg>
          </div>
          <div class="ml-3 flex-1">
            <h3 class="text-sm font-medium text-red-800">
              Ошибка в компоненте
            </h3>
            <div class="mt-2 text-sm text-red-700">
              {{ errorMessage }}
            </div>
            <div class="mt-4 flex gap-2">
              <button
                @click="retry"
                class="bg-red-600 text-white px-3 py-1 rounded text-xs hover:bg-red-700 transition-colors"
              >
                Попробовать еще раз
              </button>
              <button
                @click="reset"
                class="bg-gray-100 text-gray-700 px-3 py-1 rounded text-xs hover:bg-gray-200 transition-colors"
              >
                Сбросить
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Обычное содержимое -->
    <div v-else class="error-boundary__content">
      <slot />
    </div>
  </div>
</template>

<script setup lang="ts">
/**
 * Простой ErrorBoundary для Vue 3
 * Изолирует ошибки компонентов
 */

import { ref, onErrorCaptured } from 'vue'

interface Props {
  fallback?: string
  showDetails?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  fallback: 'Произошла ошибка',
  showDetails: false
})

const emit = defineEmits<{
  'error': [error: Error]
}>()

// === СОСТОЯНИЕ ===
const hasError = ref(false)
const errorMessage = ref('')
const retryKey = ref(0)

// === ОБРАБОТКА ОШИБОК ===
onErrorCaptured((error: Error) => {
  hasError.value = true
  errorMessage.value = props.showDetails 
    ? error.message 
    : props.fallback
  
  // Emit error для внешней обработки
  emit('error', error)
  
  console.error('[ErrorBoundary] Caught error:', error)
  
  // Предотвращаем дальнейшее распространение ошибки
  return false
})

// === МЕТОДЫ ===

/**
 * Повтор - перерендер компонента
 */
function retry() {
  hasError.value = false
  errorMessage.value = ''
  retryKey.value++
}

/**
 * Сброс состояния ошибки
 */
function reset() {
  hasError.value = false
  errorMessage.value = ''
}
</script>

<style scoped>
.error-boundary {
  @apply w-full;
}

.error-boundary__error {
  min-height: 100px;
  display: flex;
  align-items: center;
}

.error-boundary__content {
  @apply w-full;
}
</style>
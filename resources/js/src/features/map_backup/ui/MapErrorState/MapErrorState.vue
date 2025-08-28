<template>
  <div 
    class="map-error-state"
    role="alert"
    aria-live="assertive"
  >
    <div 
      class="map-error-state__container"
      :style="{ minHeight: `${height}px` }"
    >
      <!-- Иконка ошибки -->
      <div class="map-error-state__icon">
        <svg 
          class="w-16 h-16 text-red-400"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
          aria-hidden="true"
        >
          <path 
            stroke-linecap="round" 
            stroke-linejoin="round" 
            stroke-width="2" 
            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
          />
        </svg>
      </div>

      <!-- Заголовок ошибки -->
      <h3 class="map-error-state__title">
        {{ title }}
      </h3>

      <!-- Описание ошибки -->
      <p class="map-error-state__message">
        {{ message }}
      </p>

      <!-- Детали ошибки (если есть) -->
      <details 
        v-if="details"
        class="map-error-state__details"
      >
        <summary class="cursor-pointer text-sm text-gray-500 hover:text-gray-700">
          Подробности
        </summary>
        <pre class="mt-2 text-xs bg-gray-100 p-2 rounded overflow-auto">{{ details }}</pre>
      </details>

      <!-- Кнопка повтора -->
      <button
        v-if="showRetry"
        @click="$emit('retry')"
        class="map-error-state__retry-btn"
        aria-label="Попробовать снова загрузить карту"
      >
        <svg 
          class="w-4 h-4 mr-2"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
        >
          <path 
            stroke-linecap="round" 
            stroke-linejoin="round" 
            stroke-width="2" 
            d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"
          />
        </svg>
        Попробовать снова
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
interface Props {
  height?: number
  title?: string
  message?: string
  details?: string
  showRetry?: boolean
}

withDefaults(defineProps<Props>(), {
  height: 400,
  title: 'Не удалось загрузить карту',
  message: 'Произошла ошибка при загрузке карты. Пожалуйста, попробуйте позже.',
  showRetry: true
})

defineEmits<{
  retry: []
}>()
</script>

<style scoped>
.map-error-state {
  @apply relative w-full;
}

.map-error-state__container {
  @apply flex flex-col items-center justify-center;
  @apply bg-red-50 border-2 border-red-200 rounded-lg p-8;
  @apply text-center;
}

.map-error-state__icon {
  @apply mb-4;
}

.map-error-state__title {
  @apply text-lg font-semibold text-gray-900 mb-2;
}

.map-error-state__message {
  @apply text-sm text-gray-600 mb-4 max-w-md;
}

.map-error-state__details {
  @apply w-full max-w-md mb-4;
}

.map-error-state__retry-btn {
  @apply inline-flex items-center px-4 py-2;
  @apply bg-white border border-gray-300 rounded-md shadow-sm;
  @apply text-sm font-medium text-gray-700;
  @apply hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500;
  @apply transition-colors duration-200;
}

/* Адаптивность */
@media (max-width: 640px) {
  .map-error-state__container {
    @apply p-4;
  }
  
  .map-error-state__icon svg {
    @apply w-12 h-12;
  }
}
</style>
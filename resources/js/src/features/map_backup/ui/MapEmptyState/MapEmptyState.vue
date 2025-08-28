<template>
  <div 
    class="map-empty-state"
    role="status"
    aria-label="Нет данных для отображения"
  >
    <div 
      class="map-empty-state__container"
      :style="{ minHeight: `${height}px` }"
    >
      <!-- Иконка -->
      <div class="map-empty-state__icon">
        <svg 
          class="w-16 h-16 text-gray-400"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
          aria-hidden="true"
        >
          <path 
            stroke-linecap="round" 
            stroke-linejoin="round" 
            stroke-width="2" 
            d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"
          />
        </svg>
      </div>

      <!-- Заголовок -->
      <h3 class="map-empty-state__title">
        {{ title }}
      </h3>

      <!-- Описание -->
      <p class="map-empty-state__message">
        {{ message }}
      </p>

      <!-- Действие -->
      <div 
        v-if="$slots.action"
        class="map-empty-state__action"
      >
        <slot name="action" />
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
interface Props {
  height?: number
  title?: string
  message?: string
}

withDefaults(defineProps<Props>(), {
  height: 400,
  title: 'Нет данных для отображения',
  message: 'В данный момент нет доступных маркеров на карте'
})
</script>

<style scoped>
.map-empty-state {
  @apply relative w-full;
}

.map-empty-state__container {
  @apply flex flex-col items-center justify-center;
  @apply bg-gray-50 border-2 border-dashed border-gray-300 rounded-lg p-8;
  @apply text-center;
}

.map-empty-state__icon {
  @apply mb-4;
}

.map-empty-state__title {
  @apply text-lg font-medium text-gray-900 mb-2;
}

.map-empty-state__message {
  @apply text-sm text-gray-500 max-w-sm;
}

.map-empty-state__action {
  @apply mt-6;
}

/* Адаптивность */
@media (max-width: 640px) {
  .map-empty-state__container {
    @apply p-4;
  }
  
  .map-empty-state__icon svg {
    @apply w-12 h-12;
  }
}
</style>
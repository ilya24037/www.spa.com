<template>
  <div 
    class="map-skeleton"
    role="status"
    aria-label="Загрузка карты"
  >
    <!-- Основной контейнер -->
    <div 
      class="map-skeleton__container animate-pulse"
      :style="{ height: `${height}px` }"
    >
      <!-- Имитация карты -->
      <div class="map-skeleton__map">
        <!-- Сетка для имитации карты -->
        <div class="map-skeleton__grid">
          <div 
            v-for="i in 12" 
            :key="i"
            class="map-skeleton__tile"
          />
        </div>
        
        <!-- Имитация маркера по центру -->
        <div class="map-skeleton__center-marker">
          <div class="map-skeleton__marker-icon" />
          <div class="map-skeleton__marker-shadow" />
        </div>
      </div>

      <!-- Имитация контролов -->
      <div class="map-skeleton__controls">
        <!-- Зум контролы -->
        <div class="map-skeleton__zoom">
          <div class="map-skeleton__zoom-in" />
          <div class="map-skeleton__zoom-out" />
        </div>
        
        <!-- Кнопка геолокации -->
        <div 
          v-if="showGeolocation"
          class="map-skeleton__geolocation"
        />
      </div>

      <!-- Текст загрузки -->
      <div class="map-skeleton__loading-text">
        <div class="map-skeleton__spinner" />
        <span class="map-skeleton__text">{{ loadingText }}</span>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
interface Props {
  height?: number
  showGeolocation?: boolean
  loadingText?: string
}

withDefaults(defineProps<Props>(), {
  height: 400,
  showGeolocation: false,
  loadingText: 'Загрузка карты...'
})
</script>

<style scoped>
.map-skeleton {
  @apply relative w-full;
}

.map-skeleton__container {
  @apply relative w-full bg-gray-100 rounded-lg overflow-hidden;
}

.map-skeleton__map {
  @apply absolute inset-0;
}

.map-skeleton__grid {
  @apply grid grid-cols-4 gap-1 h-full p-1;
}

.map-skeleton__tile {
  @apply bg-gray-200 rounded;
}

.map-skeleton__center-marker {
  @apply absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2;
}

.map-skeleton__marker-icon {
  @apply w-8 h-8 bg-gray-300 rounded-full;
}

.map-skeleton__marker-shadow {
  @apply w-6 h-2 bg-gray-200 rounded-full mt-1 mx-auto;
}

.map-skeleton__controls {
  @apply absolute top-4 right-4 space-y-2;
}

.map-skeleton__zoom {
  @apply bg-white rounded-lg shadow-md p-1 space-y-1;
}

.map-skeleton__zoom-in,
.map-skeleton__zoom-out {
  @apply w-8 h-8 bg-gray-200 rounded;
}

.map-skeleton__geolocation {
  @apply w-10 h-10 bg-white rounded-lg shadow-md;
}

.map-skeleton__loading-text {
  @apply absolute bottom-4 left-1/2 transform -translate-x-1/2;
  @apply flex items-center space-x-2 bg-white rounded-full px-4 py-2 shadow-lg;
}

.map-skeleton__spinner {
  @apply w-4 h-4 border-2 border-blue-500 border-t-transparent rounded-full animate-spin;
}

.map-skeleton__text {
  @apply text-sm text-gray-600;
}

/* Анимация пульсации */
@keyframes pulse {
  0%, 100% {
    opacity: 1;
  }
  50% {
    opacity: 0.5;
  }
}

.animate-pulse {
  animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}
</style>
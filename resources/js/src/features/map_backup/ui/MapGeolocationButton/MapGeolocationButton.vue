<template>
  <button
    @click="handleClick"
    class="map-geolocation-button"
    :class="{ 'map-geolocation-button--active': locationActive }"
    :disabled="isLoading"
    :title="tooltip"
  >
    <div v-if="isLoading" class="map-geolocation-button__spinner">
      <svg class="animate-spin" width="20" height="20" viewBox="0 0 24 24">
        <circle 
          class="opacity-25" 
          cx="12" 
          cy="12" 
          r="10" 
          stroke="currentColor" 
          stroke-width="4"
          fill="none"
        />
        <path 
          class="opacity-75" 
          fill="currentColor" 
          d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
        />
      </svg>
    </div>
    <svg 
      v-else
      class="map-geolocation-button__icon" 
      :class="{ 'text-blue-600': locationActive, 'text-gray-600': !locationActive }"
      fill="none" 
      viewBox="0 0 24 24" 
      stroke="currentColor"
    >
      <path 
        stroke-linecap="round" 
        stroke-linejoin="round" 
        stroke-width="2" 
        d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"
      />
      <path 
        stroke-linecap="round" 
        stroke-linejoin="round" 
        stroke-width="2" 
        d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"
      />
    </svg>
  </button>
</template>

<script setup lang="ts">
import { computed } from 'vue'

interface Props {
  locationActive?: boolean
  isLoading?: boolean
  disabled?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  locationActive: false,
  isLoading: false,
  disabled: false
})

const emit = defineEmits<{
  click: []
}>()

const tooltip = computed(() => {
  if (props.isLoading) return 'Определение местоположения...'
  if (props.locationActive) return 'Показано ваше местоположение'
  return 'Определить мое местоположение'
})

const handleClick = () => {
  if (!props.disabled && !props.isLoading) {
    emit('click')
  }
}
</script>

<style scoped>
.map-geolocation-button {
  position: absolute;
  top: 12px;
  right: 12px;
  z-index: 100;
  background: white;
  border: 1px solid #ddd;
  border-radius: 6px;
  padding: 8px;
  cursor: pointer;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
  transition: all 0.2s ease;
  display: flex;
  align-items: center;
  justify-content: center;
  width: 36px;
  height: 36px;
}

.map-geolocation-button:hover:not(:disabled) {
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
  border-color: #bbb;
}

.map-geolocation-button:active:not(:disabled) {
  transform: scale(0.95);
}

.map-geolocation-button:disabled {
  cursor: not-allowed;
  opacity: 0.5;
}

.map-geolocation-button--active {
  background: #eff6ff;
  border-color: #3b82f6;
}

.map-geolocation-button__icon {
  width: 20px;
  height: 20px;
}

.map-geolocation-button__spinner {
  display: flex;
  align-items: center;
  justify-content: center;
}

/* Оптимизация для touch устройств */
@media (pointer: coarse) {
  .map-geolocation-button {
    padding: 10px;
    min-width: 44px;
    min-height: 44px;
  }
}

/* Адаптивность */
@media (max-width: 768px) {
  .map-geolocation-button {
    top: 8px;
    right: 8px;
  }
}
</style>
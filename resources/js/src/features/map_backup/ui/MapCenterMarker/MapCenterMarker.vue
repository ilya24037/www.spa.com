<template>
  <div
    v-if="visible"
    class="map-center-marker"
    :class="{ 'map-center-marker--active': isActive }"
    @mouseenter="handleMouseEnter"
    @mouseleave="handleMouseLeave"
  >
    <svg 
      width="32" 
      height="40" 
      viewBox="0 0 32 40" 
      fill="none"
      class="map-center-marker__icon"
    >
      <!-- Основная капля -->
      <path 
        d="M16 0C7.164 0 0 7.164 0 16C0 24.836 16 40 16 40S32 24.836 32 16C32 7.164 24.836 0 16 0Z" 
        :fill="markerColor"
      />
      <!-- Внутренний круг -->
      <circle cx="16" cy="16" r="6" fill="white" />
      <!-- Центральная точка -->
      <circle cx="16" cy="16" r="2" :fill="markerColor" />
    </svg>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'

interface Props {
  visible?: boolean
  color?: string
  active?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  visible: true,
  color: '#007BFF',
  active: false
})

const emit = defineEmits<{
  'marker-hover': []
  'marker-leave': []
}>()

const isHovered = ref(false)

const isActive = computed(() => props.active || isHovered.value)

const markerColor = computed(() => {
  if (isActive.value) return '#0056b3'
  return props.color
})

const handleMouseEnter = () => {
  isHovered.value = true
  emit('marker-hover')
}

const handleMouseLeave = () => {
  isHovered.value = false
  emit('marker-leave')
}
</script>

<style scoped>
.map-center-marker {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  z-index: 100;
  pointer-events: auto;
  cursor: pointer;
  filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.3));
  transition: all 0.2s ease;
}

.map-center-marker__icon {
  display: block;
  transition: transform 0.2s ease;
}

.map-center-marker:hover .map-center-marker__icon {
  transform: scale(1.1);
}

.map-center-marker--active {
  filter: drop-shadow(0 4px 8px rgba(0, 0, 0, 0.4));
}

.map-center-marker--active .map-center-marker__icon {
  transform: scale(1.1);
}

/* Анимация появления */
@keyframes bounceIn {
  0% {
    transform: translate(-50%, -50%) scale(0);
    opacity: 0;
  }
  50% {
    transform: translate(-50%, -50%) scale(1.2);
  }
  100% {
    transform: translate(-50%, -50%) scale(1);
    opacity: 1;
  }
}

.map-center-marker {
  animation: bounceIn 0.3s ease-out;
}

/* Оптимизация для touch устройств */
@media (pointer: coarse) {
  .map-center-marker {
    min-width: 44px;
    min-height: 44px;
  }
}
</style>
<template>
  <transition name="fade">
    <div
      v-if="visible && address"
      class="map-address-tooltip"
      :style="tooltipStyle"
    >
      {{ address }}
    </div>
  </transition>
</template>

<script setup lang="ts">
import { computed } from 'vue'

interface Props {
  visible?: boolean
  address?: string
  position?: {
    x: number
    y: number
  }
}

const props = withDefaults(defineProps<Props>(), {
  visible: false,
  address: '',
  position: () => ({ x: 0, y: 0 })
})

const tooltipStyle = computed(() => ({
  left: `${props.position.x + 10}px`,
  top: `${props.position.y - 30}px`
}))
</script>

<style scoped>
.map-address-tooltip {
  position: absolute;
  z-index: 1000;
  background: #1a1a1a;
  color: white;
  padding: 8px 12px;
  border-radius: 6px;
  font-size: 13px;
  line-height: 1.4;
  max-width: 300px;
  word-wrap: break-word;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
  pointer-events: none;
  transform: translateY(-100%);
  white-space: nowrap;
}

.map-address-tooltip::after {
  content: '';
  position: absolute;
  top: 100%;
  left: 20px;
  border: 6px solid transparent;
  border-top-color: #1a1a1a;
}

/* Анимация появления/исчезновения */
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.2s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

/* Адаптивность */
@media (max-width: 768px) {
  .map-address-tooltip {
    max-width: 250px;
    font-size: 12px;
    padding: 6px 10px;
  }
}
</style>
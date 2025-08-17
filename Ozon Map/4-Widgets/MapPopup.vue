<template>
  <div 
    v-if="isVisible"
    ref="popupElement"
    class="map-popup"
    :class="[
      `position-${position}`,
      { 'has-arrow': showArrow },
      `theme-${theme}`
    ]"
    :style="popupStyle"
  >
    <!-- Close Button -->
    <button
      v-if="showCloseButton"
      class="popup-close-button"
      type="button"
      title="Закрыть"
      @click="close"
    >
      <svg width="12" height="12" viewBox="0 0 12 12" fill="currentColor">
        <path d="M9.5 3.205L8.795 2.5 6 5.295 3.205 2.5l-.705.705L5.295 6 2.5 8.795l.705.705L6 6.705 8.795 9.5l.705-.705L6.705 6z"/>
      </svg>
    </button>
    
    <!-- Header -->
    <div v-if="title || $slots.header" class="popup-header">
      <slot name="header">
        <h3 class="popup-title">{{ title }}</h3>
      </slot>
    </div>
    
    <!-- Content -->
    <div class="popup-content">
      <slot>
        <div v-if="content" v-html="content"></div>
      </slot>
    </div>
    
    <!-- Footer -->
    <div v-if="$slots.footer" class="popup-footer">
      <slot name="footer"></slot>
    </div>
    
    <!-- Arrow -->
    <div v-if="showArrow" class="popup-arrow"></div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch, nextTick, onMounted, onUnmounted } from 'vue'
import type { Map as MapLibreMap, LngLat } from 'maplibre-gl'

export interface MapPopupProps {
  /** Instance карты MapLibre */
  map?: MapLibreMap
  /** Координаты для привязки popup */
  coordinates?: [number, number] | LngLat
  /** Заголовок popup */
  title?: string
  /** Содержимое popup (HTML) */
  content?: string
  /** Показать popup */
  visible?: boolean
  /** Позиция относительно координат */
  position?: 'top' | 'bottom' | 'left' | 'right' | 'center'
  /** Показать стрелку */
  showArrow?: boolean
  /** Показать кнопку закрытия */
  showCloseButton?: boolean
  /** Максимальная ширина */
  maxWidth?: number
  /** Максимальная высота */
  maxHeight?: number
  /** Отступ от края карты */
  offset?: [number, number]
  /** Закрыть при клике вне popup */
  closeOnClickOutside?: boolean
  /** Закрыть при движении карты */
  closeOnMapMove?: boolean
  /** Тема оформления */
  theme?: 'default' | 'ozon' | 'dark'
  /** CSS класс */
  className?: string
}

export interface MapPopupEmits {
  (e: 'close'): void
  (e: 'position-changed', position: [number, number]): void
}

const props = withDefaults(defineProps<MapPopupProps>(), {
  visible: false,
  position: 'top',
  showArrow: true,
  showCloseButton: true,
  maxWidth: 300,
  maxHeight: 400,
  offset: () => [0, 0],
  closeOnClickOutside: true,
  closeOnMapMove: false,
  theme: 'default'
})

const emit = defineEmits<MapPopupEmits>()

// State
const popupElement = ref<HTMLElement>()
const isVisible = ref(props.visible)
const popupPosition = ref<[number, number]>([0, 0])

// Computed
const popupStyle = computed(() => {
  const [x, y] = popupPosition.value
  const [offsetX, offsetY] = props.offset
  
  return {
    position: 'absolute',
    left: `${x + offsetX}px`,
    top: `${y + offsetY}px`,
    maxWidth: `${props.maxWidth}px`,
    maxHeight: `${props.maxHeight}px`,
    transform: getTransformOrigin(),
    zIndex: 1000
  }
})

// Methods
const show = () => {
  isVisible.value = true
  updatePosition()
}

const close = () => {
  isVisible.value = false
  emit('close')
}

const toggle = () => {
  if (isVisible.value) {
    close()
  } else {
    show()
  }
}

const updatePosition = () => {
  if (!props.map || !props.coordinates) return
  
  const coords = Array.isArray(props.coordinates) 
    ? props.coordinates 
    : [props.coordinates.lng, props.coordinates.lat]
  
  const point = props.map.project(coords as [number, number])
  popupPosition.value = [point.x, point.y]
  
  emit('position-changed', [point.x, point.y])
  
  nextTick(() => {
    adjustPopupPosition()
  })
}

const adjustPopupPosition = () => {
  if (!popupElement.value || !props.map) return
  
  const popup = popupElement.value
  const mapContainer = props.map.getContainer()
  const mapRect = mapContainer.getBoundingClientRect()
  const popupRect = popup.getBoundingClientRect()
  
  let [x, y] = popupPosition.value
  
  // Adjust horizontal position
  if (x + popupRect.width > mapRect.width) {
    x = mapRect.width - popupRect.width - 10
  }
  if (x < 10) {
    x = 10
  }
  
  // Adjust vertical position
  if (y + popupRect.height > mapRect.height) {
    y = mapRect.height - popupRect.height - 10
  }
  if (y < 10) {
    y = 10
  }
  
  popupPosition.value = [x, y]
}

const getTransformOrigin = (): string => {
  switch (props.position) {
    case 'top':
      return 'translate(-50%, -100%)'
    case 'bottom':
      return 'translate(-50%, 0%)'
    case 'left':
      return 'translate(-100%, -50%)'
    case 'right':
      return 'translate(0%, -50%)'
    case 'center':
    default:
      return 'translate(-50%, -50%)'
  }
}

const handleMapMove = () => {
  if (props.closeOnMapMove) {
    close()
  } else {
    updatePosition()
  }
}

const handleMapClick = () => {
  if (props.closeOnMapMove) {
    close()
  }
}

const handleOutsideClick = (event: MouseEvent) => {
  if (!props.closeOnClickOutside || !popupElement.value) return
  
  const target = event.target as HTMLElement
  if (!popupElement.value.contains(target)) {
    close()
  }
}

const handleKeyDown = (event: KeyboardEvent) => {
  if (event.key === 'Escape' && isVisible.value) {
    close()
  }
}

// Watchers
watch(() => props.visible, (newVisible) => {
  if (newVisible) {
    show()
  } else {
    close()
  }
})

watch(() => props.coordinates, () => {
  if (isVisible.value) {
    updatePosition()
  }
}, { deep: true })

watch(() => props.map, (newMap, oldMap) => {
  // Remove old listeners
  if (oldMap) {
    oldMap.off('move', handleMapMove)
    oldMap.off('click', handleMapClick)
  }
  
  // Add new listeners
  if (newMap) {
    newMap.on('move', handleMapMove)
    newMap.on('click', handleMapClick)
    
    if (isVisible.value) {
      updatePosition()
    }
  }
})

// Lifecycle
onMounted(() => {
  document.addEventListener('click', handleOutsideClick)
  document.addEventListener('keydown', handleKeyDown)
  
  if (props.map) {
    props.map.on('move', handleMapMove)
    props.map.on('click', handleMapClick)
  }
  
  if (props.visible && props.coordinates) {
    show()
  }
})

onUnmounted(() => {
  document.removeEventListener('click', handleOutsideClick)
  document.removeEventListener('keydown', handleKeyDown)
  
  if (props.map) {
    props.map.off('move', handleMapMove)
    props.map.off('click', handleMapClick)
  }
})

// Expose public methods
defineExpose({
  show,
  close,
  toggle,
  updatePosition
})
</script>

<style scoped>
.map-popup {
  position: absolute;
  pointer-events: auto;
  will-change: transform;
}

/* Base Styles */
.map-popup {
  background: white;
  border-radius: 8px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
  border: 1px solid rgba(0, 0, 0, 0.1);
  overflow: hidden;
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
  font-size: 14px;
  line-height: 1.4;
}

/* Close Button */
.popup-close-button {
  position: absolute;
  top: 8px;
  right: 8px;
  background: rgba(255, 255, 255, 0.9);
  border: none;
  border-radius: 4px;
  width: 24px;
  height: 24px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  color: #666;
  transition: all 0.2s;
  z-index: 1;
}

.popup-close-button:hover {
  background: #f5f5f5;
  color: #333;
}

.popup-close-button:active {
  transform: scale(0.95);
}

/* Header */
.popup-header {
  padding: 16px 40px 0 16px;
  border-bottom: 1px solid #f0f0f0;
}

.popup-title {
  margin: 0 0 12px 0;
  font-size: 16px;
  font-weight: 600;
  color: #333;
  line-height: 1.3;
}

/* Content */
.popup-content {
  padding: 16px;
  overflow-y: auto;
  word-wrap: break-word;
}

.popup-header + .popup-content {
  padding-top: 12px;
}

/* Footer */
.popup-footer {
  padding: 0 16px 16px 16px;
  border-top: 1px solid #f0f0f0;
  margin-top: 12px;
  padding-top: 12px;
}

/* Arrow */
.popup-arrow {
  position: absolute;
  width: 0;
  height: 0;
  border: 8px solid transparent;
}

.position-top.has-arrow .popup-arrow {
  bottom: -16px;
  left: 50%;
  transform: translateX(-50%);
  border-top-color: white;
  border-bottom: none;
}

.position-bottom.has-arrow .popup-arrow {
  top: -16px;
  left: 50%;
  transform: translateX(-50%);
  border-bottom-color: white;
  border-top: none;
}

.position-left.has-arrow .popup-arrow {
  right: -16px;
  top: 50%;
  transform: translateY(-50%);
  border-left-color: white;
  border-right: none;
}

.position-right.has-arrow .popup-arrow {
  left: -16px;
  top: 50%;
  transform: translateY(-50%);
  border-right-color: white;
  border-left: none;
}

/* Themes */
.theme-ozon {
  border-color: var(--ozon-primary, #005bff);
  box-shadow: 0 4px 12px rgba(0, 91, 255, 0.2);
}

.theme-ozon .popup-header {
  background: linear-gradient(135deg, var(--ozon-primary, #005bff), var(--ozon-primary-dark, #0050e0));
  color: white;
  border-bottom-color: transparent;
}

.theme-ozon .popup-title {
  color: white;
}

.theme-ozon .popup-close-button {
  background: rgba(255, 255, 255, 0.2);
  color: white;
}

.theme-ozon .popup-close-button:hover {
  background: rgba(255, 255, 255, 0.3);
}

.theme-ozon.position-top.has-arrow .popup-arrow {
  border-top-color: var(--ozon-primary, #005bff);
}

.theme-ozon.position-bottom.has-arrow .popup-arrow {
  border-bottom-color: var(--ozon-primary, #005bff);
}

.theme-ozon.position-left.has-arrow .popup-arrow {
  border-left-color: var(--ozon-primary, #005bff);
}

.theme-ozon.position-right.has-arrow .popup-arrow {
  border-right-color: var(--ozon-primary, #005bff);
}

.theme-dark {
  background: #2a2a2a;
  border-color: #444;
  color: white;
}

.theme-dark .popup-header {
  border-bottom-color: #444;
}

.theme-dark .popup-title {
  color: white;
}

.theme-dark .popup-footer {
  border-top-color: #444;
}

.theme-dark .popup-close-button {
  background: rgba(255, 255, 255, 0.1);
  color: #ccc;
}

.theme-dark .popup-close-button:hover {
  background: rgba(255, 255, 255, 0.2);
  color: white;
}

.theme-dark.position-top.has-arrow .popup-arrow {
  border-top-color: #2a2a2a;
}

.theme-dark.position-bottom.has-arrow .popup-arrow {
  border-bottom-color: #2a2a2a;
}

.theme-dark.position-left.has-arrow .popup-arrow {
  border-left-color: #2a2a2a;
}

.theme-dark.position-right.has-arrow .popup-arrow {
  border-right-color: #2a2a2a;
}

/* Animations */
.map-popup {
  animation: popupFadeIn 0.2s ease-out;
}

@keyframes popupFadeIn {
  from {
    opacity: 0;
    transform: translateY(-10px) scale(0.95);
  }
  to {
    opacity: 1;
    transform: translateY(0) scale(1);
  }
}

/* Mobile Optimizations */
@media (max-width: 768px) {
  .map-popup {
    max-width: calc(100vw - 32px) !important;
    max-height: calc(100vh - 64px) !important;
  }
  
  .popup-header {
    padding: 12px 36px 0 12px;
  }
  
  .popup-content {
    padding: 12px;
  }
  
  .popup-footer {
    padding: 0 12px 12px 12px;
  }
  
  .popup-close-button {
    width: 32px;
    height: 32px;
    top: 4px;
    right: 4px;
  }
}

/* Accessibility - Reduced Motion */
@media (prefers-reduced-motion: reduce) {
  .map-popup {
    animation: none;
  }
  
  .popup-close-button {
    transition: none;
  }
}

/* High Contrast Mode */
@media (forced-colors: active) {
  .map-popup {
    border: 2px solid ButtonText;
  }
  
  .popup-header {
    border-bottom-color: ButtonText;
  }
  
  .popup-footer {
    border-top-color: ButtonText;
  }
  
  .popup-close-button {
    border: 1px solid ButtonText;
  }
}

/* Print Styles */
@media print {
  .map-popup {
    position: static !important;
    transform: none !important;
    box-shadow: none;
    border: 1px solid #000;
    page-break-inside: avoid;
  }
  
  .popup-close-button {
    display: none;
  }
  
  .popup-arrow {
    display: none;
  }
}
</style>
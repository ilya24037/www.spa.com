<!-- resources/js/src/features/map/ui/UniversalMap/MapMarker.vue -->
<template>
  <div
    :class="CONTAINER_CLASSES"
    :style="position"
    @click="$emit('click')"
    @mouseenter="$emit('hover', true)"
    @mouseleave="$emit('hover', false)"
  >
    <!-- Маркер -->
    <div :class="MARKER_CLASSES">
      <div :class="getMarkerContentClasses()">
        {{ markerLabel }}
      </div>
      <!-- Стрелка -->
      <div v-if="mode !== 'mini'" :class="ARROW_CLASSES">
        <div :class="ARROW_TRIANGLE_CLASSES"></div>
      </div>
    </div>

    <!-- Тултип при наведении -->
    <div v-if="isHovered && hasTooltip" :class="TOOLTIP_CLASSES">
      <div :class="TOOLTIP_TITLE_CLASSES">{{ marker.tooltip.title }}</div>
      <div v-if="marker.tooltip.subtitle" :class="TOOLTIP_SUBTITLE_CLASSES">
        {{ marker.tooltip.subtitle }}
      </div>
      <!-- Стрелка тултипа -->
      <div :class="TOOLTIP_ARROW_CLASSES">
        <div :class="TOOLTIP_ARROW_TRIANGLE_CLASSES"></div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

// 🎯 Стили согласно дизайн-системе
const CONTAINER_CLASSES = 'absolute transform -translate-x-1/2 -translate-y-1/2 cursor-pointer'
const MARKER_CLASSES = 'relative'
const MARKER_CONTENT_BASE_CLASSES = 'transition-all duration-200 hover:scale-110 px-2 py-1 rounded-full text-xs font-medium shadow-md'
const MARKER_CONTENT_DEFAULT_CLASSES = 'bg-blue-600 text-white'
const MARKER_CONTENT_PREMIUM_CLASSES = 'bg-purple-600 text-white'
const MARKER_CONTENT_VERIFIED_CLASSES = 'bg-green-600 text-white'
const ARROW_CLASSES = 'absolute top-full left-1/2 transform -translate-x-1/2 -mt-1'
const ARROW_TRIANGLE_CLASSES = 'w-0 h-0 border-l-2 border-r-2 border-t-2 border-transparent border-t-blue-600'
const TOOLTIP_CLASSES = 'absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 bg-white px-3 py-2 rounded-lg shadow-lg text-sm whitespace-nowrap z-10'
const TOOLTIP_TITLE_CLASSES = 'font-medium text-gray-900'
const TOOLTIP_SUBTITLE_CLASSES = 'text-gray-600'
const TOOLTIP_ARROW_CLASSES = 'absolute top-full left-1/2 transform -translate-x-1/2'
const TOOLTIP_ARROW_TRIANGLE_CLASSES = 'w-0 h-0 border-l-4 border-r-4 border-t-4 border-transparent border-t-white'

const props = defineProps({
  marker: {
    type: Object,
    required: true
  },
  position: {
    type: Object,
    required: true
  },
  mode: {
    type: String,
    default: 'full'
  },
  isHovered: {
    type: Boolean,
    default: false
  }
})

defineEmits(['click', 'hover'])

// Вычисляемые свойства
const markerLabel = computed(() => {
  if (props.marker.price) {
    return `${props.marker.price}₽`
  }
  if (props.marker.name) {
    return props.marker.name
  }
  return props.marker.label || '●'
})

const hasTooltip = computed(() => 
  props.marker.tooltip && (props.marker.tooltip.title || props.marker.tooltip.subtitle)
)

// Методы
const getMarkerContentClasses = () => {
  const classes = [MARKER_CONTENT_BASE_CLASSES]
  
  if (props.marker.is_premium) {
    classes.push(MARKER_CONTENT_PREMIUM_CLASSES)
  } else if (props.marker.is_verified) {
    classes.push(MARKER_CONTENT_VERIFIED_CLASSES)
  } else {
    classes.push(MARKER_CONTENT_DEFAULT_CLASSES)
  }
  
  return classes.join(' ')
}
</script>